<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\ConfigService;
use App\Services\Logger;

/**
 * Configuration Controller - Admin-only system configuration
 */
class ConfigController extends BaseController
{
    private Logger $logger;

    public function __construct(Application $app, ConfigService $configService, Logger $logger)
    {
        parent::__construct($app);
        $this->configService = $configService;
        $this->logger = $logger;
    }

    public function index(): void
    {
        $this->requireRole('admin');

        // Keep newly introduced config keys visible on already-initialized installs.
        $this->configService->initializeDefaultConfig();

        $roundState = $this->app->getDatabase()->fetchOne(
            'SELECT workflow_step FROM TW4_live.round ORDER BY row_id ASC LIMIT 1'
        );
        $workflowStep = (string) ($roundState['workflow_step'] ?? 'between_rounds');
        if (!in_array($workflowStep, ['between_rounds', 'not_started'], true)) {
            $this->flash->error('Configuration is locked while a round is in progress.');
            $this->redirect('/admin/menu');
            return;
        }
        
        $allConfigs = $this->configService->getAllConfigRows();
        $status = $this->configService->getConfigStatus();
        
        $this->render('config/index', [
            'configs' => $allConfigs,
            'status' => $status,
        ]);
    }

    public function save(): void
    {
        $this->requireRole('admin');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/config');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->flash->error('Invalid CSRF token');
            $this->redirect('/config');
            return;
        }

        $roundState = $this->app->getDatabase()->fetchOne(
            'SELECT workflow_step FROM TW4_live.round ORDER BY row_id ASC LIMIT 1'
        );
        $workflowStep = (string) ($roundState['workflow_step'] ?? 'between_rounds');
        if (!in_array($workflowStep, ['between_rounds', 'not_started'], true)) {
            $this->flash->error('Configuration is locked while a round is in progress.');
            $this->redirect('/config');
            return;
        }
        
        $data = $this->getPostData();
        $errors = [];
        $updates = [];
        $changes = [];
        
        // Validate and update each configuration value
        foreach ($data as $key => $value) {
            if (strpos($key, 'config_') === 0) {
                $configId = str_replace('config_', '', $key);
                $configType = $data["type_$configId"] ?? '';
                $configName = $data["name_$configId"] ?? '';
                
                // Get current value for logging
                $currentConfig = $this->configService->getConfigRow($configId);
                $oldValue = $currentConfig['config_value_string'] ?? '';
                
                // Validate based on type
                $validationResult = $this->validateConfigValue($value, $configType, $configName);
                if (!$validationResult['valid']) {
                    $errors[$key] = $validationResult['message'];
                } else {
                    $newValue = $validationResult['value'];
                    
                    // Only log if value actually changed
                    if ($oldValue !== (string)$newValue) {
                        $changes[$configName] = [
                            'old_value' => $oldValue,
                            'new_value' => (string)$newValue,
                            'type' => $configType,
                            'config_id' => $configId
                        ];
                    }
                    
                    $updates[$configId] = [
                        'name' => $configName,
                        'value' => $newValue,
                        'type' => $configType
                    ];
                }
            }
        }
        
        if (!empty($errors)) {
            $this->flash->error($errors);
            $this->redirect('/config');
            return;
        }
        
        // Save all valid updates with audit information
        $successCount = 0;
        $username = $_SESSION['username'] ?? 'unknown';
        $statusPromotedToReady = false;
        $currentStatus = $this->configService->getConfigStatus();
        
        foreach ($updates as $configId => $update) {
            if ($this->configService->updateConfigRow($configId, $update['value'], $update['type'], $username)) {
                $successCount++;
            }
        }

        // When team_haggle_state is set to floating, reset the serious revision counter to 0.
        if (isset($changes['team_haggle_state']) && $changes['team_haggle_state']['new_value'] === 'floating') {
            $this->app->getDatabase()->query(
                'UPDATE config_application
                 SET config_value_int = 0, config_value_string = \'0\', updated_by = ?
                 WHERE config_name = ?',
                [$username, 'team_haggle_serious_revision']
            );
            $this->logger->info('team_haggle_serious_revision reset to 0 (team_haggle_state set to floating)', [
                'updated_by' => $username,
            ], $username);
        }

        // When team_haggle_state is set to serious, clear the live team tables so the
        // serious panel opens as a fresh blank slate rather than showing floating-generated
        // teams as a pre-filled revisit. Floating mode writes to the same tables on every
        // round finish, so without this the admin would always see stale auto-assigned teams.
        if (isset($changes['team_haggle_state']) && $changes['team_haggle_state']['new_value'] === 'serious') {
            $db = $this->app->getDatabase();
            $db->query('DELETE FROM TW4_live.best_five_team_member');
            $db->query('DELETE FROM TW4_live.best_five_team');
            $this->logger->info('best_five_team tables cleared on switch to serious mode', [
                'updated_by' => $username,
            ], $username);
        }

        // Auto-set config_status to "ready" after a valid save, even if no field values changed.
        if ($currentStatus !== 'ready') {
            $statusPromotedToReady = $this->configService->setConfigStatus('ready');
        }

        if ($statusPromotedToReady) {
            $this->logger->info("System status automatically set to 'ready' after configuration update", [
                'updated_by' => $username,
                'changes_count' => count($changes)
            ], $username);
        }
        
        // Log detailed changes
        if (!empty($changes)) {
            $this->logger->logConfig('updated', [
                'updated_by' => $username,
                'changes_count' => count($changes),
                'changed_configs' => $changes,
                'timestamp' => date('Y-m-d H:i:s')
            ], $username);
            
            // Log each individual change for detailed tracking
            foreach ($changes as $configName => $change) {
                $this->logger->info("Configuration updated: $configName", [
                    'config_name' => $configName,
                    'old_value' => $change['old_value'],
                    'new_value' => $change['new_value'],
                    'type' => $change['type'],
                    'updated_by' => $username
                ], $username);
            }
        }
        
        if ($successCount > 0) {
            $this->flash->success("Successfully updated $successCount configuration values.");
        } elseif ($statusPromotedToReady) {
            $this->flash->success('No configuration values changed, but status is now set to ready.');
        } else {
            $this->flash->success('No changes were made to configuration values.');
        }
        
        $this->redirect('/config');
    }

    public function delete(): void
    {
        $this->requireRole('admin');
        $this->redirect('/config');
    }
    
    /**
     * Validate configuration value based on type
     */
    private function validateConfigValue(string $value, string $type, string $name): array
    {
        if ($type === 'int') {
            $normalized = trim($value);

            // Check if it's a valid integer (no decimals, no non-digit suffixes)
            if ($normalized === '' || preg_match('/^-?\d+$/', $normalized) !== 1) {
                return ['valid' => false, 'message' => 'Value must be an integer', 'value' => $value];
            }

            return ['valid' => true, 'value' => (int) $normalized];
        } elseif ($type === 'string') {
            if (in_array($name, ['handicap_method', 'handicap_system', 'handicap_sytem'], true)) {
                $normalized = strtolower(trim($value));
                $allowed = [
                    'modern' => 'modern',
                    'legacy' => 'legacy',
                    'none' => 'none',
                    'm' => 'modern',
                    'l' => 'legacy',
                    'n' => 'none',
                ];

                if (!isset($allowed[$normalized])) {
                    return [
                        'valid' => false,
                        'message' => 'Handicap method must be modern, legacy, or none',
                        'value' => $value,
                    ];
                }

                return ['valid' => true, 'value' => $allowed[$normalized]];
            }

            if ($name === 'team_haggle_state') {
                $normalized = strtolower(trim($value));
                $allowed = [
                    'floating' => 'floating',
                    'serious' => 'serious',
                    'f' => 'floating',
                    'l' => 'serious',
                    's' => 'serious',
                ];

                if (!isset($allowed[$normalized])) {
                    return [
                        'valid' => false,
                        'message' => 'team_haggle_state must be floating or serious',
                        'value' => $value,
                    ];
                }

                return ['valid' => true, 'value' => $allowed[$normalized]];
            }

            if ($name === 'team_haggle_makeup_method') {
                $normalized = strtolower(trim($value));
                $allowed = [
                    'average' => 'average',
                    'median' => 'median',
                    'lowest' => 'lowest',
                ];

                if (!isset($allowed[$normalized])) {
                    return [
                        'valid' => false,
                        'message' => 'team_haggle_makeup_method must be average, median, or lowest',
                        'value' => $value,
                    ];
                }

                return ['valid' => true, 'value' => $allowed[$normalized]];
            }

            if ($name === 'season_year' && preg_match('/^\d{2}_\d{2}$/', trim($value)) !== 1) {
                return ['valid' => false, 'message' => 'Season year must use the format NN_NN, for example 25_26', 'value' => $value];
            }

            // Check if it's a valid string (not empty for required fields)
            if (empty(trim($value)) && in_array($name, ['config_status', 'club_name', 'competition_name', 'season_year'])) {
                return ['valid' => false, 'message' => 'Value cannot be empty', 'value' => $value];
            }
            return ['valid' => true, 'value' => trim($value)];
        } else {
            return ['valid' => false, 'message' => 'Invalid configuration type', 'value' => $value];
        }
    }
}
