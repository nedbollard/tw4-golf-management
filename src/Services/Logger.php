<?php

namespace App\Services;

use App\Core\Database;

/**
 * General Purpose Logging Service - Handles all application events
 */
class Logger
{
    private Database $db;
    private string $logFile;
    
    // Log levels
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';
    
    // Event types
    const EVENT_LOGIN = 'LOGIN';
    const EVENT_LOGOUT = 'LOGOUT';
    const EVENT_AUTH = 'AUTH';
    const EVENT_CONFIG = 'CONFIG';
    const EVENT_PLAYER = 'PLAYER';
    const EVENT_SYSTEM = 'SYSTEM';
    const EVENT_SECURITY = 'SECURITY';
    const EVENT_ERROR = 'ERROR';
    
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->logFile = __DIR__ . '/../../logs/application.log';
    }
    
    /**
     * Log any application event
     */
    public function log(string $level, string $event, string $message, array $context = [], string $username = null): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Log to database
        $this->db->insert('application_log', [
            'timestamp' => $timestamp,
            'level' => $level,
            'event_type' => $event,
            'message' => $message,
            'context' => !empty($context) ? json_encode($context) : null,
            'username' => $username,
            'ip_address' => $ip,
            'user_agent' => $userAgent
        ]);
        
        // Also log to file for backup
        $logEntry = sprintf(
            "[%s] %s: %s | %s | User: %s | IP: %s | %s\n",
            $timestamp,
            strtoupper($level),
            $event,
            $message,
            $username ?? 'system',
            $ip,
            !empty($context) ? 'Context: ' . json_encode($context) : ''
        );
        
        $this->writeToFile($logEntry);
    }
    
    /**
     * Convenience method for login events
     */
    public function logLogin(string $username, bool $success, string $reason = ''): void
    {
        $this->log(
            $success ? self::LEVEL_INFO : self::LEVEL_WARNING,
            self::EVENT_LOGIN,
            $success ? 'Login successful' : 'Login failed',
            ['success' => $success, 'reason' => $reason],
            $username
        );
    }
    
    /**
     * Convenience method for logout events
     */
    public function logLogout(string $username): void
    {
        $this->log(
            self::LEVEL_INFO,
            self::EVENT_LOGOUT,
            'User logged out',
            [],
            $username
        );
    }
    
    /**
     * Convenience method for configuration changes
     */
    public function logConfig(string $action, array $changes, string $username): void
    {
        $this->log(
            self::LEVEL_INFO,
            self::EVENT_CONFIG,
            "Configuration $action",
            ['changes' => $changes],
            $username
        );
    }
    
    /**
     * Convenience method for security events
     */
    public function logSecurity(string $message, array $context = [], string $username = null): void
    {
        $this->log(
            self::LEVEL_WARNING,
            self::EVENT_SECURITY,
            $message,
            $context,
            $username
        );
    }
    
    /**
     * Get logs with filtering and sorting
     */
    public function getLogs(array $filters = [], string $search = '', string $order = 'DESC', int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM application_log WHERE 1=1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['level'])) {
            $sql .= " AND level = ?";
            $params[] = $filters['level'];
        }
        
        if (!empty($filters['event_type'])) {
            $sql .= " AND event_type = ?";
            $params[] = $filters['event_type'];
        }
        
        if (!empty($filters['username'])) {
            $sql .= " AND username = ?";
            $params[] = $filters['username'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND timestamp >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND timestamp <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        // Apply search
        if (!empty($search)) {
            $sql .= " AND (message LIKE ? OR username LIKE ? OR ip_address LIKE ? OR context LIKE ?)";
            $searchParam = '%' . $search . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        // Apply order
        $order = strtoupper($order);
        $sql .= " ORDER BY timestamp " . ($order === 'ASC' ? 'ASC' : 'DESC');
        
        // Apply pagination
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $logs = $this->db->fetchAll($sql, $params);
        
        // Decode JSON context for display
        foreach ($logs as &$log) {
            if (!empty($log['context'])) {
                $log['context'] = json_decode($log['context'], true);
            }
        }
        
        return $logs;
    }
    
    /**
     * Get total count of logs matching criteria
     */
    public function getLogsCount(array $filters = [], string $search = ''): int
    {
        $sql = "SELECT COUNT(*) as count FROM application_log WHERE 1=1";
        $params = [];
        
        // Apply same filters as getLogs
        if (!empty($filters['level'])) {
            $sql .= " AND level = ?";
            $params[] = $filters['level'];
        }
        
        if (!empty($filters['event_type'])) {
            $sql .= " AND event_type = ?";
            $params[] = $filters['event_type'];
        }
        
        if (!empty($filters['username'])) {
            $sql .= " AND username = ?";
            $params[] = $filters['username'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND timestamp >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND timestamp <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($search)) {
            $sql .= " AND (message LIKE ? OR username LIKE ? OR ip_address LIKE ? OR context LIKE ?)";
            $searchParam = '%' . $search . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return (int) $result['count'];
    }
    
    /**
     * Get unique values for filter dropdowns
     */
    public function getFilterOptions(): array
    {
        return [
            'levels' => $this->getUniqueValues('level'),
            'event_types' => $this->getUniqueValues('event_type'),
            'usernames' => $this->getUniqueValues('username')
        ];
    }
    
    /**
     * Get unique values from a column
     */
    private function getUniqueValues(string $column): array
    {
        $result = $this->db->fetchAll("SELECT DISTINCT $column FROM application_log WHERE $column IS NOT NULL ORDER BY $column");
        return array_column($result, $column);
    }
    
    /**
     * Write to log file
     */
    private function writeToFile(string $message): void
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            // Try to create directory with proper permissions
            if (!mkdir($logDir, 0755, true)) {
                // If we can't create the directory, just skip file logging
                return;
            }
        }
        
        // Only write if directory exists and is writable
        if (is_dir($logDir) && is_writable($logDir)) {
            file_put_contents($this->logFile, $message, FILE_APPEND | LOCK_EX);
        }
    }
    
    public function debug(string $message, array $context = [], string $username = null): void
    {
        $this->log(self::LEVEL_DEBUG, self::EVENT_SYSTEM, $message, $context, $username);
    }
    
    public function info(string $message, array $context = [], string $username = null): void
    {
        $this->log(self::LEVEL_INFO, self::EVENT_SYSTEM, $message, $context, $username);
    }
    
    public function warning(string $message, array $context = [], string $username = null): void
    {
        $this->log(self::LEVEL_WARNING, self::EVENT_SYSTEM, $message, $context, $username);
    }
    
    public function error(string $message, array $context = [], string $username = null): void
    {
        $this->log(self::LEVEL_ERROR, self::EVENT_ERROR, $message, $context, $username);
    }
    
    public function critical(string $message, array $context = [], string $username = null): void
    {
        $this->log(self::LEVEL_CRITICAL, self::EVENT_ERROR, $message, $context, $username);
    }
}
