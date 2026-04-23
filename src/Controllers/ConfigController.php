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
        
        $allConfigs = $this->configService->getAllConfigRows();
        $status = $this->configService->getConfigStatus();
        
        $this->render('config/index', [
            'configs' => $allConfigs,
            'status' => $status,
            'errors' => $_SESSION['errors'] ?? [],
            'success' => isset($_SESSION['success']) ? (is_array($_SESSION['success']) ? $_SESSION['success'] : [$_SESSION['success']]) : []
        ]);
        
        // Clear session messages
        unset($_SESSION['errors']);
        unset($_SESSION['success']);
    }

    public function save(): void
    {
        $this->requireRole('admin');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
            $_SESSION['errors'] = $errors;
            $this->redirect('/config');
            return;
        }
        
        // Save all valid updates with audit information
        $successCount = 0;
        $username = $_SESSION['username'] ?? 'unknown';
        $firstUpdate = false;
        
        foreach ($updates as $configId => $update) {
            if ($this->configService->updateConfigRow($configId, $update['value'], $update['type'], $username)) {
                $successCount++;
                $firstUpdate = true;
            }
        }
        
        // Auto-set config_status to "ready" on first update
        if ($firstUpdate) {
            $this->configService->setConfigStatus('ready');
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
            $_SESSION['success'] = "Successfully updated $successCount configuration values.";
        } else {
            $_SESSION['success'] = "No changes were made to configuration values.";
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
            // Check if it's a valid integer
            if (!is_numeric($value) || (string)(int)$value !== $value) {
                return ['valid' => false, 'message' => 'Value must be an integer', 'value' => $value];
            }
            return ['valid' => true, 'value' => (int)$value];
        } elseif ($type === 'string') {
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
