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

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->getPath();
        
        // Debug: Store routing info in session
        $_SESSION['router_debug'] = [
            'method' => $method,
            'path' => $path,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
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
        
        return $reflection->newInstanceArgs($dependencies);
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
        // Map service classes to their instantiation logic
        $serviceMap = [
            \App\Services\AuthService::class => fn() => new \App\Services\AuthService($this->app->getDatabase()),
            \App\Services\PlayerService::class => fn() => new \App\Services\PlayerService($this->app->getDatabase()),
            \App\Services\ConfigService::class => fn() => new \App\Services\ConfigService($this->app->getDatabase()),
            \App\Services\Logger::class => fn() => new \App\Services\Logger($this->app->getDatabase()),
            \App\Services\RosterService::class => fn() => new \App\Services\RosterService($this->app->getDatabase()),
        ];
        
        if (isset($serviceMap[$serviceClass])) {
            return $serviceMap[$serviceClass]();
        }
        
        throw new \RuntimeException("Service not registered: {$serviceClass}");
    }
}
