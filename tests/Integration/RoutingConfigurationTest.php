<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Routing Configuration Tests
 * Tests route configuration and order to prevent routing conflicts
 */
class RoutingConfigurationTest extends TestCase
{
    public function testRouteConfigurationFileExists(): void
    {
        $routesFile = __DIR__ . '/../../src/config/routes.php';
        $this->assertFileExists($routesFile, 'Routes configuration file should exist');
    }

    public function testRouteConfigurationReturnsValidArray(): void
    {
        $routes = require __DIR__ . '/../../src/config/routes.php';
        $this->assertIsArray($routes, 'Routes configuration should return an array');
        $this->assertArrayHasKey('GET', $routes, 'Routes should have GET methods');
        $this->assertArrayHasKey('POST', $routes, 'Routes should have POST methods');
    }

    public function testRosterRoutesAreCorrectlyConfigured(): void
    {
        $routes = require __DIR__ . '/../../src/config/routes.php';
        $getRoutes = $routes['GET'];
        $postRoutes = $routes['POST'];

        // Test GET routes
        $this->assertArrayHasKey('/roster', $getRoutes, 'Should have /roster route');
        $this->assertArrayHasKey('/roster/create', $getRoutes, 'Should have /roster/create route');
        $this->assertArrayHasKey('/roster/{id}', $getRoutes, 'Should have /roster/{id} route');
        $this->assertArrayHasKey('/roster/{id}/edit', $getRoutes, 'Should have /roster/{id}/edit route');

        // Test POST routes
        $this->assertArrayHasKey('/roster/create', $postRoutes, 'Should have POST /roster/create route');
        $this->assertArrayHasKey('/roster/{id}/update', $postRoutes, 'Should have POST /roster/{id}/update route');
        $this->assertArrayHasKey('/roster/{id}/delete', $postRoutes, 'Should have POST /roster/{id}/delete route');
    }

    public function testRosterRouteOrderPreventsConflicts(): void
    {
        $routes = require __DIR__ . '/../../src/config/routes.php';
        $getRoutes = $routes['GET'];

        // Get the order of routes as they appear in the array
        $routeKeys = array_keys($getRoutes);

        // Find positions of critical routes
        $createPos = array_search('/roster/create', $routeKeys);
        $showPos = array_search('/roster/{id}', $routeKeys);
        $editPos = array_search('/roster/{id}/edit', $routeKeys);

        // Assert that specific routes come before parameterized ones
        $this->assertLessThan($showPos, $createPos, 
            '/roster/create should come before /roster/{id} to prevent routing conflicts');
        $this->assertLessThan($editPos, $showPos, 
            '/roster/{id} should come before /roster/{id}/edit');
    }

    public function testRosterCreateRoutePointsToCorrectController(): void
    {
        $routes = require __DIR__ . '/../../src/config/routes.php';
        $getRoutes = $routes['GET'];

        $createRoute = $getRoutes['/roster/create'];
        $this->assertEquals('App\\Controllers\\RosterController', $createRoute['controller']);
        $this->assertEquals('create', $createRoute['method']);
    }

    public function testRosterShowRoutePointsToCorrectController(): void
    {
        $routes = require __DIR__ . '/../../src/config/routes.php';
        $getRoutes = $routes['GET'];

        $showRoute = $getRoutes['/roster/{id}'];
        $this->assertEquals('App\\Controllers\\RosterController', $showRoute['controller']);
        $this->assertEquals('show', $showRoute['method']);
    }

    public function testRosterControllerClassExists(): void
    {
        $this->assertTrue(class_exists('App\\Controllers\\RosterController'));
    }

    public function testRosterControllerCreateMethodExists(): void
    {
        $this->assertTrue(method_exists('App\\Controllers\\RosterController', 'create'));
    }

    public function testRosterControllerShowMethodExists(): void
    {
        $this->assertTrue(method_exists('App\\Controllers\\RosterController', 'show'));
    }

    public function testRosterControllerShowMethodExpectsIntParameter(): void
    {
        $reflection = new \ReflectionClass('App\\Controllers\\RosterController');
        $showMethod = $reflection->getMethod('show');
        
        $parameters = $showMethod->getParameters();
        $this->assertCount(1, $parameters, 'Show method should have exactly one parameter');
        
        $playerIdParam = $parameters[0];
        $this->assertEquals('playerId', $playerIdParam->getName());
        $this->assertEquals('int', $playerIdParam->getType());
    }

    public function testRosterControllerCreateMethodHasNoRequiredParameters(): void
    {
        $reflection = new \ReflectionClass('App\\Controllers\\RosterController');
        $createMethod = $reflection->getMethod('create');
        
        $parameters = $createMethod->getParameters();
        $this->assertCount(0, $parameters, 'Create method should have no parameters');
    }

    public function testRoutePathsAreCorrectlyFormatted(): void
    {
        $routes = require __DIR__ . '/../../src/config/routes.php';
        $getRoutes = $routes['GET'];

        // Test that paths are correctly formatted
        $this->assertEquals('/roster/create', $getRoutes['/roster/create']['path']);
        $this->assertEquals('/roster/{id}', $getRoutes['/roster/{id}']['path']);
        $this->assertEquals('/roster/{id}/edit', $getRoutes['/roster/{id}/edit']['path']);
    }

    public function testStaffRoutesAreCorrectlyConfigured(): void
    {
        $routes = require __DIR__ . '/../../src/config/routes.php';
        $getRoutes = $routes['GET'];
        $postRoutes = $routes['POST'];

        // Test staff routes
        $this->assertArrayHasKey('/staff', $getRoutes);
        $this->assertArrayHasKey('/staff/add', $getRoutes);
        $this->assertArrayHasKey('/staff/edit/{id}', $getRoutes);
        
        $this->assertArrayHasKey('/staff/add', $postRoutes);
        $this->assertArrayHasKey('/staff/update/{id}', $postRoutes);
        $this->assertArrayHasKey('/staff/delete/{id}', $postRoutes);
    }

    public function testAuthRoutesAreCorrectlyConfigured(): void
    {
        $routes = require __DIR__ . '/../../src/config/routes.php';
        $getRoutes = $routes['GET'];
        $postRoutes = $routes['POST'];

        // Test auth routes
        $this->assertArrayHasKey('/login', $getRoutes);
        $this->assertArrayHasKey('/logout', $getRoutes);
        $this->assertArrayHasKey('/register', $getRoutes);
        
        $this->assertArrayHasKey('/login', $postRoutes);
        $this->assertArrayHasKey('/logout', $postRoutes);
    }
}
