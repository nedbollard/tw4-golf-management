<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\ServiceContainer;
use App\Services\ConfigService;
use App\Services\AuthService;
use App\Services\FlashMessage;
use App\Services\Logger;

/**
 * Base Controller class with common functionality
 */
abstract class BaseController
{
    protected Application $app;
    protected ConfigService $configService;
    protected AuthService $authService;
    protected FlashMessage $flash;
    private Logger $baseLogger;
    private bool $servicesInitialized = false;
    private ?ServiceContainer $fallbackServices = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function initializeServices(
        ConfigService $configService,
        AuthService $authService,
        FlashMessage $flash,
        Logger $logger
    ): void {
        if ($this->servicesInitialized) {
            return;
        }

        $this->configService = $configService;
        $this->configService->initializeDefaultConfig();

        $this->authService = $authService;
        $this->authService->updateActivity();

        $this->flash = $flash;
        $this->baseLogger = $logger;

        if (!isset($_SESSION['config_checked'])) {
            $this->configService->loadConfigToSession();
            $_SESSION['config_checked'] = true;
        }

        $this->servicesInitialized = true;
    }

    protected function ensureServicesInitialized(): void
    {
        if ($this->servicesInitialized) {
            return;
        }

        $this->initializeServices(
            $this->resolveService(ConfigService::class),
            $this->resolveService(AuthService::class),
            $this->resolveService(FlashMessage::class),
            $this->resolveService(Logger::class)
        );
    }

    protected function resolveService(string $serviceClass): object
    {
        $this->fallbackServices ??= new ServiceContainer($this->app->getDatabase());
        return $this->fallbackServices->get($serviceClass);
    }

    protected function render(string $view, array $data = []): void
    {
        $this->ensureServicesInitialized();
        $viewPath = $this->app->getConfig()['paths']['views'] . '/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View {$view} not found");
        }

        // Add application title to all views
        $data['app_title'] = $this->configService->getApplicationTitle();
        $data['config_status'] = $this->configService->getConfigStatus();
        // Add CSRF token to all views
        $data['csrf_token'] = $this->generateCsrfToken();
        // Add flash messages to all views.
        $flash = $this->flash->all();
        $data['flash'] = $flash;
        if (!array_key_exists('errors', $data)) {
            $data['errors'] = $flash['error'] ?? [];
        }
        if (!array_key_exists('success', $data)) {
            $data['success'] = $flash['success'] ?? [];
        }
        if (!array_key_exists('warnings', $data)) {
            $data['warnings'] = $flash['warning'] ?? [];
        }
        if (!array_key_exists('info', $data)) {
            $data['info'] = $flash['info'] ?? [];
        }

        // Add old input data to all views.
        if (!array_key_exists('old', $data)) {
            $data['old'] = $this->flash->hasOld() ? $this->flash->getOld() : [];
        }

        // Register an HTML escaping helper function for use within view files
        $escape = function (string $value): string {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };

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

    protected function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function validateCsrf(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if ($token === null) {
            return false;
        }
        
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        
        // Use hash_equals to prevent timing attacks
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Retrieves and sanitizes incoming request data to prevent XSS injection.
     */
    protected function getPostData(): array
    {
        $rawData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $sanitized = $this->sanitizeRecursive($rawData);

        // CSRF and submit-control fields are transport metadata, not model data.
        unset($sanitized['csrf_token'], $sanitized['_token'], $sanitized['submit']);

        return $sanitized;
    }

    /**
     * Recursively strips tags from inputs to cleanly sanitize complex nested data structures.
     */
    private function sanitizeRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeRecursive($value);
            } elseif (is_string($value)) {
                $data[$key] = strip_tags($value);
            }
        }
        return $data;
    }

    protected function requireAuth(): void
    {
        $this->ensureServicesInitialized();
        $this->authService->requireLogin();
    }

    protected function requireRole(string $role): void
    {
        $this->ensureServicesInitialized();
        $this->authService->requireRole($role);
    }

    protected function requireScoringConfigReady(string $redirect = '/scorer/menu'): void
    {
        $this->ensureServicesInitialized();
        $status = $this->configService->getConfigStatus();
        if ($status === 'ready') {
            return;
        }

        try {
            $username = isset($_SESSION['username']) ? (string) $_SESSION['username'] : null;
            $this->baseLogger->log(
                Logger::LEVEL_WARNING,
                Logger::EVENT_SECURITY,
                'Blocked scoring action while configuration status is not ready',
                [
                    'config_status' => $status,
                    'request_method' => (string) ($_SERVER['REQUEST_METHOD'] ?? 'unknown'),
                    'request_uri' => (string) ($_SERVER['REQUEST_URI'] ?? 'unknown'),
                    'redirect' => $redirect,
                ],
                $username
            );
        } catch (\Throwable $e) {
            // Avoid blocking request flow if logging is unavailable.
        }

        $this->flash->error('Scoring is disabled until configuration status is set to ready by an admin.');
        $this->redirect($redirect);
    }
}
