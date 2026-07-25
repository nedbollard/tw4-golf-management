<?php

namespace App\Core;

use App\Controllers\BaseController;
use App\Services\AuthService;
use App\Services\ConfigService;
use App\Services\FlashMessage;
use App\Services\Logger;

/**
 * URL Router class for clean URL handling
 */
class Router
{
    private array $routes = [];
    private string $basePath = '';
    private ?Application $app = null;
    private ?ServiceContainer $services = null;

    public function __construct(?Application $app = null, ?ServiceContainer $services = null)
    {
        $this->app = $app;
        $this->services = $services;
        if ($this->services === null && $app !== null) {
            $this->services = new ServiceContainer($app->getDatabase());
        }
    }

    public function loadRoutes(array $routes): void
    {
        $this->routes = $routes;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->getPath();
        
        // Get routes for this HTTP method
        $methodRoutes = $this->routes[$method] ?? [];
        
        foreach ($methodRoutes as $route) {
            $_SESSION['router_debug']['checking_route'] = $route;
            if ($this->matchesRoute($route, $method, $path)) {
                $_SESSION['router_debug']['matched_route'] = $route;
                $this->executeRoute($route, $method, $path);
                return;
            }
        }
        
        $this->handle404();
    }

    public function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: {$url}", true, $statusCode);

        if (php_sapi_name() === 'cli') {
            $_SERVER['CLI_REDIRECT_URL'] = $url;
            $_SERVER['CLI_REDIRECT_STATUS'] = $statusCode;
            return;
        }

        exit;
    }

    private function matchesRoute(array $route, string $method, string $path): bool
    {
        return $this->pathMatches($route['path'], $path);
    }

    private function pathMatches(string $routePath, string $requestPath): bool
    {
        $pattern = preg_replace_callback('/\{([^}]+)\}/', function ($matches) {
            $paramName = $matches[1];
            return $paramName === 'id' ? '(\d+)' : '([^/]+)';
        }, $routePath);

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

        $controller = $this->resolveController($controllerClass);
        
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
        if ($path === '' || $path === null) {
            return '/';
        }

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path === '' ? '/' : $path;
    }

    private function handle404(): void
    {
        http_response_code(404);
        include __DIR__ . '/../Views/errors/404.php';
    }

    /**
     * Resolve controller dependencies using reflection
     */
    private function resolveController(string $controllerClass): object
    {
        $reflection = new \ReflectionClass($controllerClass);
        $constructor = $reflection->getConstructor();
        
        if ($constructor === null) {
            return new $controllerClass($this->app);
        }
        
        $parameters = $constructor->getParameters();
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $dependencies[] = $this->resolveDependency($parameter);
        }
        
        $controller = $reflection->newInstanceArgs($dependencies);
        if ($controller instanceof BaseController) {
            $controller->initializeServices(
                $this->resolveService(ConfigService::class),
                $this->resolveService(AuthService::class),
                $this->resolveService(FlashMessage::class),
                $this->resolveService(Logger::class)
            );
        }

        return $controller;
    }

    /**
     * Resolve a single dependency based on type hint
     */
    private function resolveDependency(\ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();
        
        // Handle Application parameter
        if ($type && $type->getName() === Application::class) {
            return $this->app;
        }
        
        // Handle service dependencies
        if ($type && !$type->isBuiltin()) {
            $serviceClass = $type->getName();
            return $this->resolveService($serviceClass);
        }
        
        // Handle optional parameters
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        
        throw new \RuntimeException("Cannot resolve dependency: {$parameter->getName()}");
    }

    /**
     * Resolve service instances
     */
    private function resolveService(string $serviceClass): object
    {
        if ($this->services === null) {
            throw new \RuntimeException('Service container is not configured.');
        }

        return $this->services->get($serviceClass);
    }
}
