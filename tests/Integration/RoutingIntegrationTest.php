<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Core\Application;
use App\Core\Router;

/**
 * Routing Integration Tests
 * Tests the complete HTTP request routing workflow
 */
class RoutingIntegrationTest extends TestCase
{
    private Application $app;
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application();
        $this->router = $this->app->getRouter();
    }

    public function testRosterCreateRouteResolvesCorrectly(): void
    {
        // Test that /roster/create routes to the correct controller and method
        $routes = $this->router->getRoutes();
        
        // Check GET routes
        $getRoutes = $routes['GET'];
        $this->assertArrayHasKey('/roster/create', $getRoutes);
        
        $createRoute = $getRoutes['/roster/create'];
        $this->assertEquals('App\\Controllers\\RosterController', $createRoute['controller']);
        $this->assertEquals('create', $createRoute['method']);
    }

    public function testRosterShowRouteResolvesCorrectly(): void
    {
        // Test that /roster/{id} routes to the correct controller and method
        $routes = $this->router->getRoutes();
        
        $getRoutes = $routes['GET'];
        $this->assertArrayHasKey('/roster/{id}', $getRoutes);
        
        $showRoute = $getRoutes['roster/{id}'];
        $this->assertEquals('App\\Controllers\\RosterController', $showRoute['controller']);
        $this->assertEquals('show', $showRoute['method']);
    }

    public function testRosterEditRouteResolvesCorrectly(): void
    {
        // Test that /roster/{id}/edit routes to the correct controller and method
        $routes = $this->router->getRoutes();
        
        $getRoutes = $routes['GET'];
        $this->assertArrayHasKey('/roster/{id}/edit', $getRoutes);
        
        $editRoute = $getRoutes['roster/{id}/edit'];
        $this->assertEquals('App\\Controllers\\RosterController', $editRoute['controller']);
        $this->assertEquals('edit', $editRoute['method']);
    }

    public function testRosterPostCreateRouteResolvesCorrectly(): void
    {
        // Test that POST /roster/create routes to the correct controller and method
        $routes = $this->router->getRoutes();
        
        $postRoutes = $routes['POST'];
        $this->assertArrayHasKey('/roster/create', $postRoutes);
        
        $createRoute = $postRoutes['roster/create'];
        $this->assertEquals('App\\Controllers\\RosterController', $createRoute['controller']);
        $this->assertEquals('store', $createRoute['method']);
    }

    public function testRosterRouteOrderPreventsConflicts(): void
    {
        // Test that specific routes come before parameterized routes
        $routes = $this->router->getRoutes();
        $getRoutes = $routes['GET'];
        
        // Get the order of routes as they appear in the array
        $routeKeys = array_keys($getRoutes);
        
        // Find positions of critical routes
        $createPos = array_search('/roster/create', $routeKeys);
        $showPos = array_search('/roster/{id}', $routeKeys);
        $editPos = array_search('/roster/{id}/edit', $routeKeys);
        
        // Assert that specific routes come before parameterized ones
        $this->assertLessThan($showPos, $createPos, 
            '/roster/create should come before /roster/{id} to prevent conflicts');
        $this->assertLessThan($editPos, $showPos, 
            '/roster/{id} should come before /roster/{id}/edit');
    }

    public function testStaffRoutesAreCorrectlyConfigured(): void
    {
        $routes = $this->router->getRoutes();
        
        // Test staff routes
        $getRoutes = $routes['GET'];
        $this->assertArrayHasKey('/staff', $getRoutes);
        $this->assertArrayHasKey('/staff/add', $getRoutes);
        $this->assertArrayHasKey('/staff/edit/{id}', $getRoutes);
        
        $postRoutes = $routes['POST'];
        $this->assertArrayHasKey('/staff/add', $postRoutes);
        $this->assertArrayHasKey('/staff/update/{id}', $postRoutes);
        $this->assertArrayHasKey('/staff/delete/{id}', $postRoutes);
    }

    public function testAuthRoutesAreCorrectlyConfigured(): void
    {
        $routes = $this->router->getRoutes();
        
        // Test auth routes
        $getRoutes = $routes['GET'];
        $this->assertArrayHasKey('/login', $getRoutes);
        $this->assertArrayHasKey('/logout', $getRoutes);
        $this->assertArrayHasKey('/register', $getRoutes);
        
        $postRoutes = $routes['POST'];
        $this->assertArrayHasKey('/login', $postRoutes);
        $this->assertArrayHasKey('/logout', $postRoutes);
    }

    public function testRoutePathMatching(): void
    {
        // Test that route paths match the expected patterns
        $routes = $this->router->getRoutes();
        $getRoutes = $routes['GET'];
        
        // Test exact path matching
        $this->assertEquals('/roster/create', $getRoutes['/roster/create']['path']);
        $this->assertEquals('/roster/{id}', $getRoutes['/roster/{id}']['path']);
        $this->assertEquals('/roster/{id}/edit', $getRoutes['/roster/{id}/edit']['path']);
    }

    public function testRouteControllerClassesExist(): void
    {
        $routes = $this->router->getRoutes();
        
        // Test that all controller classes exist
        $getRoutes = $routes['GET'];
        $postRoutes = $routes['POST'];
        
        $allRoutes = array_merge($getRoutes, $postRoutes);
        
        foreach ($allRoutes as $route) {
            $controllerClass = $route['controller'];
            $this->assertTrue(class_exists($controllerClass), 
                "Controller class {$controllerClass} should exist");
            
            $method = $route['method'];
            $this->assertTrue(method_exists($controllerClass, $method), 
                "Method {$method} should exist in {$controllerClass}");
        }
    }

    public function testRosterControllerMethodsExist(): void
    {
        $controllerClass = 'App\\Controllers\\RosterController';
        
        $this->assertTrue(class_exists($controllerClass));
        
        // Test all expected methods exist
        $expectedMethods = ['index', 'create', 'show', 'edit', 'store', 'update', 'delete', 'destroy', 'search', 'activate'];
        
        foreach ($expectedMethods as $method) {
            $this->assertTrue(method_exists($controllerClass, $method), 
                "Method {$method} should exist in RosterController");
        }
    }

    public function testRouteParameterPatterns(): void
    {
        // Test that parameterized routes use correct patterns
        $routes = $this->router->getRoutes();
        $getRoutes = $routes['GET'];
        
        // Test that ID parameters are correctly specified
        $showRoute = $getRoutes['/roster/{id}'];
        $this->assertStringContains('{id}', $showRoute['path']);
        
        $editRoute = $getRoutes['/roster/{id}/edit'];
        $this->assertStringContains('{id}', $editRoute['path']);
    }
}
