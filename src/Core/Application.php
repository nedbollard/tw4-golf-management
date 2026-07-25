<?php

namespace App\Core;

/**
 * Application class - Main application entry point
 */
class Application
{
    private static ?Application $instance = null;
    private array $config = [];
    private Database $database;
    private ServiceContainer $services;
    private Router $router;

    private function __construct()
    {
        $this->loadConfig();
        $this->initializeDatabase();
        $this->services = new ServiceContainer($this->database);
        $this->initializeRouter();
    }

    public static function getInstance(): Application
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run(): void
    {
        try {
            $this->router->dispatch();
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function getServices(): ServiceContainer
    {
        return $this->services;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    private function loadConfig(): void
    {
        $configFile = __DIR__ . '/../config/config.php';
        if (file_exists($configFile)) {
            $this->config = require $configFile;
        }
    }

    private function initializeDatabase(): void
    {
        $this->database = new Database($this->config['database']);
    }

    private function initializeRouter(): void
    {
        $this->router = new Router($this, $this->services);
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        $routesFile = __DIR__ . '/../config/routes.php';
        if (file_exists($routesFile)) {
            $routes = require $routesFile;
            $this->router->loadRoutes($routes);
        }
    }

    private function handleError(\Throwable $e): void
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $stackTrace = $e->getTraceAsString();

        error_log("Application Error on {$requestUri}: " . $e->getMessage() . " in " . $e->getFile() . ':' . $e->getLine());
        error_log("Stack trace:\n" . $stackTrace);

        if ($this->config['debug'] ?? false) {
            throw $e;
        } else {
            $this->router->redirect('/error');
        }
    }
}
