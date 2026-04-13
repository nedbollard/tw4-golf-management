<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Core\Application;
use App\Core\Database;
use App\Middleware\Auth;

class AuthenticationFlowTest extends TestCase
{
    private Application $app;
    private Database $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = Application::getInstance();
        $this->db = $this->app->getDatabase();
        
        // Clean up session
        $_SESSION = [];
        
        // Ensure test data exists
        $this->ensureTestStaffExists();
    }

    protected function tearDown(): void
    {
        // Clean up session after each test
        $_SESSION = [];
        parent::tearDown();
    }

    private function ensureTestStaffExists(): void
    {
        // Check if test staff exists, create if not
        $existing = $this->db->fetchOne(
            'SELECT row_id FROM staff WHERE username = ?',
            ['testuser']
        );
        
        if (!$existing) {
            $this->db->insert('staff', [
                'username' => 'testuser',
                'password_hash' => password_hash('testpass123', PASSWORD_DEFAULT),
                'first_name' => 'Test',
                'last_name' => 'User',
                'role' => 'admin',
                'is_active' => 1
            ]);
        }
    }

    public function testAuthenticationFlowWithCorrectTable(): void
    {
        // Test that Auth middleware uses correct staff table
        $auth = new Auth($this->db);
        
        // Test login
        $result = $auth->login('testuser', 'testpass123');
        $this->assertTrue($result);
        
        // Test that user is retrieved from staff table
        $user = $auth->getUser();
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user['username']);
        $this->assertEquals('admin', $user['role']);
        
        // Test session data
        $this->assertTrue($auth->isLoggedIn());
        $this->assertTrue($auth->hasRole('admin'));
        $this->assertFalse($auth->hasRole('scorer'));
        
        // Test logout
        $auth->logout();
        $this->assertFalse($auth->isLoggedIn());
        $this->assertNull($auth->getUser());
    }

    public function testDatabaseTableDependencies(): void
    {
        // This test ensures that all authentication components use the correct database schema
        $auth = new Auth($this->db);
        
        // Verify staff table exists and has correct structure
        $tableInfo = $this->db->fetchOne("DESCRIBE staff");
        $this->assertNotNull($tableInfo, 'Staff table should exist');
        
        // Verify required columns exist in staff table
        $requiredColumns = ['row_id', 'username', 'password_hash', 'role', 'first_name', 'last_name'];
        foreach ($requiredColumns as $column) {
            $columnInfo = $this->db->fetchOne("SHOW COLUMNS FROM staff LIKE '" . $column . "'");
            $this->assertNotNull($columnInfo, "Column '{$column}' should exist in staff table");
        }
        
        // Test that queries use correct table and column names
        $auth->login('testuser', 'testpass123');
        $user = $auth->getUser();
        
        $this->assertArrayHasKey('username', $user, 'User should have username field from staff table');
        $this->assertArrayHasKey('role', $user, 'User should have role field from staff table');
        $this->assertArrayNotHasKey('user_name', $user, 'User should not have user_name field (from users table)');
        $this->assertArrayNotHasKey('user_role', $user, 'User should not have user_role field (from users table)');
    }

    public function testAuthServiceConsistency(): void
    {
        // Test that AuthService and Auth middleware are consistent
        $authService = $this->db->getAuth();
        $authMiddleware = new Auth($this->db);
        
        // Login with both methods
        $authService->login('testuser', 'testpass123');
        $authMiddleware->login('testuser', 'testpass123');
        
        // Both should return the same user data structure
        $serviceUser = $authService->getUser();
        $middlewareUser = $authMiddleware->getUser();
        
        $this->assertNotNull($serviceUser);
        $this->assertNotNull($middlewareUser);
        
        // Check that both use the same field names
        $this->assertArrayHasKey('username', $serviceUser);
        $this->assertArrayHasKey('username', $middlewareUser);
        $this->assertArrayHasKey('user_role', $serviceUser);
        $this->assertArrayHasKey('role', $middlewareUser);
    }

    public function testSessionManagement(): void
    {
        $auth = new Auth($this->db);
        
        // Test session starts correctly
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        
        // Test login sets session data
        $auth->login('testuser', 'testpass123');
        
        $this->assertArrayHasKey('user_id', $_SESSION);
        $this->assertArrayHasKey('username', $_SESSION);
        $this->assertArrayHasKey('user_role', $_SESSION);
        
        // Test logout clears session
        $auth->logout();
        
        $this->assertArrayNotHasKey('user_id', $_SESSION);
        $this->assertArrayNotHasKey('username', $_SESSION);
        $this->assertArrayNotHasKey('user_role', $_SESSION);
    }

    public function testPasswordVerification(): void
    {
        $auth = new Auth($this->db);
        
        // Test correct password
        $result = $auth->login('testuser', 'testpass123');
        $this->assertTrue($result);
        
        $auth->logout();
        
        // Test incorrect password
        $result = $auth->login('testuser', 'wrongpassword');
        $this->assertFalse($result);
        
        // Test non-existent user
        $result = $auth->login('nonexistent', 'anypassword');
        $this->assertFalse($result);
    }

    public function testRoleBasedAccess(): void
    {
        $auth = new Auth($this->db);
        
        // Test admin user
        $auth->login('testuser', 'testpass123');
        
        $this->assertTrue($auth->hasRole('admin'));
        $this->assertFalse($auth->hasRole('scorer'));
        
        $auth->logout();
        
        // Test not logged in
        $this->assertFalse($auth->hasRole('admin'));
        $this->assertFalse($auth->hasRole('scorer'));
    }
}
