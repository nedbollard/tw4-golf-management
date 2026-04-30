<?php

namespace App\Services;

use App\Core\Database;

/**
 * Configuration Service - Handle application configuration
 */
class ConfigService
{
    private Database $db;
    private ?array $configCache = null;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getConfigStatus(): string
    {
        $result = $this->db->fetchOne(
            'SELECT config_value_string FROM config_application WHERE config_name = ?',
            ['config_status']
        );
        
        return $result['config_value_string'] ?? 'waiting';
    }

    public function setConfigStatus(string $status): bool
    {
        return $this->db->update(
            'config_application',
            ['config_value_string' => $status],
            ['config_name' => 'config_status']
        ) > 0;
    }

    public function getAllConfig(): array
    {
        if ($this->configCache === null) {
            $configs = $this->db->fetchAll(
                'SELECT config_name, config_value_string FROM config_application'
            );
            
            $this->configCache = [];
            foreach ($configs as $config) {
                $this->configCache[$config['config_name']] = $config['config_value_string'];
            }
        }
        
        return $this->configCache;
    }

    public function getConfigValue(string $key, $default = null): ?string
    {
        $config = $this->getAllConfig();
        return $config[$key] ?? $default;
    }

    public function setConfigValue(string $key, string $value): bool
    {
        $this->configCache = null; // Clear cache
        
        $existing = $this->db->fetchOne(
            'SELECT row_id FROM config_application WHERE config_name = ?',
            [$key]
        );
        
        if ($existing) {
            return $this->db->update(
                'config_application',
                ['config_value_string' => $value],
                ['config_name' => $key]
            ) > 0;
        } else {
            return $this->db->insert('config_application', [
                'config_name' => $key,
                'config_value_string' => $value
            ]) > 0;
        }
    }

    public function initializeDefaultConfig(): void
    {
        $currentYear = (int) date('y');
        $nextYear = ($currentYear + 1) % 100;
        $defaultConfigs = [
            'config_status' => ['value' => 'waiting', 'type' => 'string'],
            'club_name' => ['value' => 'TW4 Golf Club', 'type' => 'string'],
            'competition_name' => ['value' => 'Twilight', 'type' => 'string'],
            'season_year' => ['value' => sprintf('%02d_%02d', $currentYear, $nextYear), 'type' => 'string'],
            'handicap_system' => ['value' => 'modern', 'type' => 'string'],
            'max_handicap' => ['value' => 54, 'type' => 'int'],
            'team_haggle_state' => ['value' => 'F', 'type' => 'string'],
            'club_number' => ['value' => 294, 'type' => 'int'],
            'entry_fee' => ['value' => 0, 'type' => 'int'],
        ];

        $existingRows = $this->db->fetchAll('SELECT config_name FROM config_application');
        $existingNames = [];
        foreach ($existingRows as $row) {
            if (isset($row['config_name'])) {
                $existingNames[$row['config_name']] = true;
            }
        }

        foreach ($defaultConfigs as $key => $definition) {
            if (isset($existingNames[$key])) {
                continue;
            }

            $data = [
                'config_name' => $key,
                'config_type' => $definition['type'],
                'updated_by' => 'system',
            ];

            if ($definition['type'] === 'int') {
                $data['config_value_int'] = (int) $definition['value'];
                $data['config_value_string'] = (string) $definition['value'];
            } else {
                $data['config_value_string'] = (string) $definition['value'];
            }

            $this->db->insert('config_application', $data);
        }

        $this->configCache = null;
    }

    public function getApplicationTitle(): string
    {
        $status = $this->getConfigStatus();
        
        if ($status === 'waiting') {
            return 'Configuration required to complete installation';
        }
        
        $clubName = $this->getConfigValue('club_name', 'TW4 Golf Club');
        $competitionName = $this->getConfigValue('competition_name', 'Weekly Competition');
        
        return "$clubName - $competitionName";
    }

    public function loadConfigToSession(): void
    {
        if ($this->getConfigStatus() === 'ready') {
            $config = $this->getAllConfig();
            
            // Load essential config to session
            $_SESSION['config'] = [
                'club_name' => $config['club_name'] ?? 'TW4 Golf Club',
                'competition_name' => $config['competition_name'] ?? 'Weekly Competition',
                'season_year' => $config['season_year'] ?? date('Y'),
            ];
        }
    }

    /**
     * Get all configuration rows with full details
     */
    public function getAllConfigRows(): array
    {
        return $this->db->fetchAll(
            'SELECT row_id, config_name, config_value_string, config_type, updated_by, updated_ts FROM config_application ORDER BY config_name'
        );
    }
    
    /**
     * Get a specific configuration row
     */
    public function getConfigRow(int $rowId): array
    {
        return $this->db->fetchOne(
            'SELECT row_id, config_name, config_value_string, config_value_int, config_type, updated_by, updated_ts FROM config_application WHERE row_id = ?',
            [$rowId]
        );
    }
    
    /**
     * Update a specific configuration row with audit information
     */
    public function updateConfigRow(int $rowId, $value, string $type, string $updatedBy = null): bool
    {
        $data = [
            'updated_by' => $updatedBy
        ];
        
        if ($type === 'int') {
            $data['config_value_int'] = $value;
            $data['config_value_string'] = (string)$value;
        } else {
            $data['config_value_string'] = $value;
        }
        
        return $this->db->update(
            'config_application',
            $data,
            ['row_id' => $rowId]
        ) > 0;
    }
    
    /**
     * Add a new configuration row
     */
    public function addConfigRow(string $name, $value, string $type): bool
    {
        // Check if configuration already exists
        $existing = $this->db->fetchOne(
            'SELECT row_id FROM config_application WHERE config_name = ?',
            [$name]
        );
        
        if ($existing) {
            return false; // Configuration already exists
        }
        
        $data = [
            'config_name' => $name,
            'config_type' => $type
        ];
        
        if ($type === 'int') {
            $data['config_value_int'] = $value;
            $data['config_value_string'] = (string)$value;
        } else {
            $data['config_value_string'] = $value;
        }
        
        return $this->db->insert('config_application', $data) > 0;
    }
    
    /**
     * Delete a configuration row
     */
    public function deleteConfigRow(int $rowId): bool
    {
        return $this->db->delete('config_application', ['row_id' => $rowId]) > 0;
    }
}
