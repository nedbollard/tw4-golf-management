<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Core\Application;

/**
 * Roster Workflow Integration Tests
 * Tests complete user workflows including routing, controllers, and services
 */
class RosterWorkflowTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application();
    }

    public function testRosterCreateWorkflowWithoutAuthentication(): void
    {
        // Simulate HTTP request to roster creation
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/roster/create';
        
        // Capture output
        ob_start();
        try {
            $this->app->run();
            $output = ob_get_clean();
            
            // Should redirect to login (HTTP 302)
            $this->assertStringContainsString('Location:', xdebug_get_headers());
            $this->assertStringContainsString('/login', implode('', xdebug_get_headers()));
            
        } catch (\Exception $e) {
            ob_end_clean();
            
            // If we get here, it means there's a routing issue
            $this->fail("Roster creation workflow failed: " . $e->getMessage());
        }
    }

    public function testRosterIndexWorkflowWithoutAuthentication(): void
    {
        // Test that roster listing also requires authentication
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/roster';
        
        ob_start();
        try {
            $this->app->run();
            $output = ob_get_clean();
            
            // Should redirect to login
            $headers = xdebug_get_headers();
            $this->assertNotEmpty($headers);
            $this->assertStringContainsString('Location:', implode('', $headers));
            
        } catch (\Exception $e) {
            ob_end_clean();
            $this->fail("Roster index workflow failed: " . $e->getMessage());
        }
    }

    public function testInvalidRosterRouteHandling(): void
    {
        // Test that invalid roster routes are handled gracefully
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/roster/invalid-action';
        
        ob_start();
        try {
            $this->app->run();
            $output = ob_get_clean();
            
            // Should either 404 or handle gracefully
            // This test ensures the router doesn't crash on invalid routes
            
        } catch (\Exception $e) {
            ob_end_clean();
            
            // Should not crash with routing errors
            $this->assertNotStringContainsString('TypeError', $e->getMessage());
            $this->assertNotStringContainsString('ArgumentCountError', $e->getMessage());
        }
    }

    public function testRosterShowWithInvalidParameter(): void
    {
        // Test the specific issue that was causing problems
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/roster/create'; // This should NOT go to show() method
        
        ob_start();
        try {
            $this->app->run();
            $output = ob_get_clean();
            
            // Should NOT get a TypeError about string vs int
            // Should either work (redirect to login) or handle gracefully
            
        } catch (\TypeError $e) {
            ob_end_clean();
            
            // This would indicate the routing issue we fixed
            $this->fail("Routing issue detected: " . $e->getMessage() . 
                       " - This suggests /roster/create is being routed to show() method");
            
        } catch (\Exception $e) {
            ob_end_clean();
            
            // Other exceptions might be expected (like auth redirects)
            $this->assertNotStringContainsString('must be of type int, string given', $e->getMessage());
        }
    }

    public function testRosterRoutesWithNumericIds(): void
    {
        // Test that numeric IDs work correctly for show routes
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/roster/123';
        
        ob_start();
        try {
            $this->app->run();
            $output = ob_get_clean();
            
            // Should either work (if user exists and authenticated) or redirect to login
            // Should NOT crash with routing errors
            
        } catch (\TypeError $e) {
            ob_end_clean();
            $this->fail("Numeric ID routing failed: " . $e->getMessage());
            
        } catch (\Exception $e) {
            ob_end_clean();
            // Other exceptions might be expected
        }
    }

    public function testRosterEditWorkflow(): void
    {
        // Test roster edit route
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/roster/123/edit';
        
        ob_start();
        try {
            $this->app->run();
            $output = ob_get_clean();
            
            // Should either work (if authenticated) or redirect to login
            // Should NOT crash with routing errors
            
        } catch (\TypeError $e) {
            ob_end_clean();
            $this->fail("Roster edit workflow failed: " . $e->getMessage());
            
        } catch (\Exception $e) {
            ob_end_clean();
            // Other exceptions might be expected
        }
    }

    public function testRouteOrderDoesNotCauseConflicts(): void
    {
        // This test specifically checks for the route order issue we fixed
        
        // Test /roster/create specifically
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/roster/create';
        
        ob_start();
        try {
            $this->app->run();
            $output = ob_get_clean();
            
            // The key test: should NOT get a TypeError about int vs string
            // This would indicate that /roster/create is correctly routed to create() method
            // not incorrectly routed to show('create')
            
        } catch (\TypeError $e) {
            ob_end_clean();
            
            // Check if this is the specific error we're trying to prevent
            if (strpos($e->getMessage(), 'must be of type int, string given') !== false) {
                $this->fail("Route order conflict detected: /roster/create is being routed to show() method instead of create() method");
            }
            
            $this->fail("Unexpected TypeError in roster routing: " . $e->getMessage());
            
        } catch (\Exception $e) {
            ob_end_clean();
            // Other exceptions are acceptable (auth, database, etc.)
        }
    }

    public function testPostRosterCreateHandling(): void
    {
        // Test POST route for roster creation
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/roster/create';
        $_POST = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'gender' => 'male'
        ];
        
        ob_start();
        try {
            $this->app->run();
            $output = ob_get_clean();
            
            // Should either work (if authenticated) or redirect to login
            // Should NOT crash with routing errors
            
        } catch (\TypeError $e) {
            ob_end_clean();
            $this->fail("POST roster creation failed: " . $e->getMessage());
            
        } catch (\Exception $e) {
            ob_end_clean();
            // Other exceptions might be expected
        }
    }

    public function testAllRosterRoutesAreAccessible(): void
    {
        $rosterRoutes = [
            'GET /roster',
            'GET /roster/create', 
            'GET /roster/123',
            'GET /roster/123/edit',
            'POST /roster/create',
            'POST /roster/123/update',
            'POST /roster/123/delete'
        ];
        
        foreach ($rosterRoutes as $route) {
            [$method, $uri] = explode(' ', $route, 2);
            
            $_SERVER['REQUEST_METHOD'] = $method;
            $_SERVER['REQUEST_URI'] = $uri;
            
            ob_start();
            try {
                $this->app->run();
                $output = ob_get_clean();
                
                // Should not crash with TypeError (routing issues)
                // May fail for other reasons (auth, validation) which is acceptable
                
            } catch (\TypeError $e) {
                ob_end_clean();
                $this->fail("Route {$route} caused TypeError: " . $e->getMessage());
                
            } catch (\Exception $e) {
                ob_end_clean();
                // Other exceptions are acceptable for this test
            }
            
            // Clean up for next iteration
            $_POST = [];
        }
    }
}
