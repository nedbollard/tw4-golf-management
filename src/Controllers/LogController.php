<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\Logger;

/**
 * Log Controller - Admin log viewing and management
 */
class LogController extends BaseController
{
    private Logger $logger;
    
    public function __construct(Application $app, Logger $logger)
    {
        parent::__construct($app);
        $this->logger = $logger;
    }
    
    /**
     * Display log viewer with filters
     */
    public function index(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');
        
        // Get filter parameters
        $filters = [
            'level' => $_GET['level'] ?? '',
            'event_type' => $_GET['event_type'] ?? '',
            'username' => $_GET['username'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? ''
        ];
        
        $search = $_GET['search'] ?? '';
        $order = $_GET['order'] ?? 'DESC';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        // Get logs and count
        $logs = $this->logger->getLogs($filters, $search, $order, $limit, $offset);
        $totalCount = $this->logger->getLogsCount($filters, $search);
        $filterOptions = $this->logger->getFilterOptions();
        
        // Calculate pagination
        $totalPages = ceil($totalCount / $limit);
        
        $this->render('logs/index', [
            'logs' => $logs,
            'filters' => $filters,
            'search' => $search,
            'order' => $order,
            'filterOptions' => $filterOptions,
            'pagination' => [
                'current' => $page,
                'total' => $totalPages,
                'limit' => $limit,
                'count' => $totalCount
            ]
        ]);
    }
    
    /**
     * Export logs to CSV
     */
    public function export(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');
        
        // Get filter parameters (same as index)
        $filters = [
            'level' => $_GET['level'] ?? '',
            'event_type' => $_GET['event_type'] ?? '',
            'username' => $_GET['username'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? ''
        ];
        
        $search = $_GET['search'] ?? '';
        $order = $_GET['order'] ?? 'DESC';
        
        // Get all logs (no pagination for export)
        $logs = $this->logger->getLogs($filters, $search, $order, 10000, 0);
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="logs_' . date('Y-m-d_H-i-s') . '.csv"');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add CSV header
        fputcsv($output, [
            'Timestamp',
            'Level',
            'Event Type',
            'Message',
            'Context',
            'Username',
            'IP Address',
            'User Agent'
        ]);
        
        // Add log entries
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['timestamp'],
                $log['level'],
                $log['event_type'],
                $log['message'],
                is_array($log['context']) ? json_encode($log['context']) : $log['context'],
                $log['username'],
                $log['ip_address'],
                $log['user_agent']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Clear old logs (admin only)
     */
    public function clear(): void
    {
        $this->requireAuth();
        $this->requireRole('admin');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $days = max(1, (int)($_POST['days'] ?? 30));
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-$days days"));
            
            // This would require adding a delete method to the Logger service
            // For now, just log the action
            $this->logger->log(
                Logger::LEVEL_INFO,
                Logger::EVENT_SYSTEM,
                "Log cleanup requested - delete logs older than $days days",
                ['cutoff_date' => $cutoffDate],
                $_SESSION['username'] ?? null
            );
            
            $_SESSION['success'] = "Log cleanup request logged. Implementation pending.";
            $this->redirect('/logs');
        }
        
        $this->render('logs/clear');
    }
}
