<?php

namespace App\Controllers;

use App\Core\Application;
use App\Services\ConfigService;

/**
 * Base Controller class with common functionality
 */
abstract class BaseController
{
    protected Application $app;
    protected ConfigService $configService;

    public function __construct(Application $app)
    {
        $this->app = $app;
        // Initialize ConfigService for all controllers
        $this->configService = new \App\Services\ConfigService($this->app->getDatabase());
        $this->configService->initializeDefaultConfig();
        
        // Check configuration status and load to session
        if (!isset($_SESSION['config_checked'])) {
            $this->configService->loadConfigToSession();
            $_SESSION['config_checked'] = true;
        }
    }

    protected function render(string $view, array $data = []): void
    {
        $viewPath = $this->app->getConfig()['paths']['views'] . '/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View {$view} not found");
        }

        // Add application title to all views
        $data['app_title'] = $this->configService->getApplicationTitle();
        $data['config_status'] = $this->configService->getConfigStatus();

        extract($data);
        require $viewPath;
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        if (php_sapi_name() !== 'cli') {
            exit;
        }
    }

    protected function redirect(string $url, int $statusCode = 302): void
    {
        $this->app->getRouter()->redirect($url, $statusCode);
    }

    protected function validateCsrf(): bool
    {
        // CSRF validation logic here
        return true; // Simplified for now
    }

    protected function getPostData(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? $_POST;
    }

    protected function requireAuth(): void
    {
        $auth = $this->app->getDatabase()->getAuth();
        $auth->requireLogin();
    }

    protected function requireRole(string $role): void
    {
        $auth = $this->app->getDatabase()->getAuth();
        $auth->requireRole($role);
    }
}
