<?php

namespace App\Core;

/**
 * URL Router class for clean URL handling
 */
class Router
{
    private array $routes = [];
    private string $basePath = '';
    private ?Application $app = null;

    public function __construct(Application $app = null)
    {
        $this->app = $app;
    }

    public function loadRoutes(array $routes): void
    {
        $this->routes = $routes;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->getPath();
        
        // Get routes for this HTTP method
        $methodRoutes = $this->routes[$method] ?? [];
        
        foreach ($methodRoutes as $route) {
            if ($this->matchesRoute($route, $method, $path)) {
                $this->executeRoute($route, $method, $path);
                return;
            }
        }
        
        $this->handle404();
    }

    public function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: {$url}", true, $statusCode);
        exit;
    }

    private function matchesRoute(array $route, string $method, string $path): bool
    {
        return $this->pathMatches($route['path'], $path);
    }

    private function pathMatches(string $routePath, string $requestPath): bool
    {
        // Convert route parameters to regex
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        return preg_match($pattern, $requestPath);
    }

    private function executeRoute(array $route, string $method, string $path): void
    {
        $controllerClass = $route['controller'];
        $method = $route['method'];
        
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller {$controllerClass} not found");
        }

        // Create controller with appropriate dependencies
        if ($controllerClass === 'App\\Controllers\\AuthController') {
            $authService = new \App\Services\AuthService($this->app->getDatabase());
            $logger = new \App\Services\Logger($this->app->getDatabase());
            $controller = new $controllerClass($this->app, $authService, $logger);
        } elseif ($controllerClass === 'App\\Controllers\\PlayerController') {
            $playerService = new \App\Services\PlayerService($this->app->getDatabase());
            $controller = new $controllerClass($this->app, $playerService);
        } elseif ($controllerClass === 'App\\Controllers\\HomeController') {
            $configService = new \App\Services\ConfigService($this->app->getDatabase());
            $controller = new $controllerClass($this->app, $configService);
        } elseif ($controllerClass === 'App\\Controllers\\ConfigController') {
            $configService = new \App\Services\ConfigService($this->app->getDatabase());
            $logger = new \App\Services\Logger($this->app->getDatabase());
            $controller = new $controllerClass($this->app, $configService, $logger);
        } elseif ($controllerClass === 'App\\Controllers\\StaffController') {
            $logger = new \App\Services\Logger($this->app->getDatabase());
            $controller = new $controllerClass($this->app, $logger);
        } elseif ($controllerClass === 'App\\Controllers\\LogController') {
            $logger = new \App\Services\Logger($this->app->getDatabase());
            $controller = new $controllerClass($this->app, $logger);
        } elseif ($controllerClass === 'App\\Controllers\\AdminController') {
            $controller = new $controllerClass($this->app);
        } elseif ($controllerClass === 'App\\Controllers\\ScorerController') {
            $controller = new $controllerClass($this->app);
        } elseif ($controllerClass === 'App\\Controllers\\RoleSwitchController') {
            $controller = new $controllerClass($this->app);
        } else {
            $controller = new $controllerClass($this->app);
        }
        
        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Method {$method} not found in {$controllerClass}");
        }

        // Extract route parameters
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route['path']);
        $pattern = '#^' . $pattern . '$#';
        
        $params = [];
        if (preg_match($pattern, $path, $matches)) {
            array_shift($matches); // Remove full match
            $params = $matches;
        }

        call_user_func_array([$controller, $method], $params);
    }

    private function getPath(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
        return $path === '' ? '/' : $path;
    }

    private function handle404(): void
    {
        http_response_code(404);
        include __DIR__ . '/../Views/errors/404.php';
    }
}
