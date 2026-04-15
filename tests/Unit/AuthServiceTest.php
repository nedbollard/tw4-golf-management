<?php

namespace Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use App\Core\Database;
use App\Services\AuthService;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private Database|MockObject $dbMock;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock session for testing
        $_SESSION = [];

        $this->dbMock = $this->createMock(Database::class);
        $this->authService = new AuthService($this->dbMock);
    }

    public function testLogin(): void
    {
        // Mock session data
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['user_role'] = 'admin';

        $this->assertTrue($this->authService->isLoggedIn());
        $this->assertEquals('admin', $_SESSION['username']);
        $this->assertEquals('admin', $_SESSION['user_role']);
    }

    public function testLogout(): void
    {
        // Set up logged in state
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['user_role'] = 'admin';

        $this->assertTrue($this->authService->isLoggedIn());

        // Logout
        $this->authService->logout();

        $this->assertFalse($this->authService->isLoggedIn());
        $this->assertEmpty($_SESSION);
    }

    public function testGetUser(): void
    {
        // Set up logged in state
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['user_role'] = 'admin';

        $user = $this->authService->getUser();

        $this->assertIsArray($user);
        $this->assertEquals(1, $user['user_id']);
        $this->assertEquals('admin', $user['username']);
        $this->assertEquals('admin', $user['user_role']);
    }

    public function testGetUserWhenNotLoggedIn(): void
    {
        $user = $this->authService->getUser();
        $this->assertNull($user);
    }

    public function testHasRole(): void
    {
        // Set up logged in state
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['user_role'] = 'admin';

        $this->assertTrue($this->authService->hasRole('admin'));
        $this->assertFalse($this->authService->hasRole('scorer'));

        // Change role
        $_SESSION['user_role'] = 'scorer';
        $this->assertTrue($this->authService->hasRole('scorer'));
        $this->assertFalse($this->authService->hasRole('admin'));
    }

    public function testHasRoleWhenNotLoggedIn(): void
    {
        $this->assertFalse($this->authService->hasRole('admin'));
        $this->assertFalse($this->authService->hasRole('scorer'));
    }

    public function testValidateStaffData(): void
    {
        $validData = [
            'username' => 'testuser',
            'password' => 'password123',
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'admin'
        ];

        $method = new \ReflectionMethod(\App\Services\AuthService::class, 'validateStaffData');
        $method->setAccessible(true);

        $errors = $method->invoke($this->authService, $validData);
        $this->assertEmpty($errors);
    }

    public function testValidateStaffDataWithErrors(): void
    {
        $invalidData = [
            'username' => '',
            'password' => '123',
            'first_name' => '',
            'last_name' => '',
            'role' => 'invalid'
        ];

        $method = new \ReflectionMethod(\App\Services\AuthService::class, 'validateStaffData');
        $method->setAccessible(true);

        $errors = $method->invoke($this->authService, $invalidData);
        
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
        // Check for specific validation errors
        $this->assertContains('Username is required', $errors);
        $this->assertContains('Password must be at least 8 characters', $errors);
        $this->assertContains('Role must be admin or scorer', $errors);
    }
}
