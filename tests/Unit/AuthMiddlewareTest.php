<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Middleware\Auth;
use App\Core\Database;

class AuthMiddlewareTest extends TestCase
{
    private Auth $auth;
    private Database $db;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock session for testing
        $_SESSION = [];
        $this->db = $this->createMock(Database::class);
        $this->auth = new Auth($this->db);
    }

    public function testGetUserReturnsNullWhenNotLoggedIn(): void
    {
        // No session data set
        $this->assertNull($this->auth->getUser());
        $this->assertFalse($this->auth->isLoggedIn());
    }

    public function testGetUserReturnsUserWhenLoggedIn(): void
    {
        // Mock session data
        $_SESSION['user_id'] = 1;
        
        // Mock database response
        $expectedUser = [
            'row_id' => 1,
            'username' => 'testuser',
            'role' => 'admin'
        ];
        
        $this->db->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo('SELECT row_id, username, role FROM staff WHERE row_id = ?'),
                $this->equalTo([1])
            )
            ->willReturn($expectedUser);

        $user = $this->auth->getUser();
        
        $this->assertNotNull($user);
        $this->assertEquals($expectedUser, $user);
        $this->assertTrue($this->auth->isLoggedIn());
    }

    public function testLoginWithValidCredentials(): void
    {
        $username = 'testuser';
        $password = 'password123';
        
        // Mock database response
        $mockUser = [
            'row_id' => 1,
            'username' => 'testuser',
            'role' => 'admin',
            'password_hash' => password_hash($password, PASSWORD_DEFAULT)
        ];
        
        $this->db->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo('SELECT row_id, username, role, password_hash FROM staff WHERE username = ?'),
                $this->equalTo([$username])
            )
            ->willReturn($mockUser);

        $result = $this->auth->login($username, $password);
        
        $this->assertTrue($result);
        $this->assertEquals(1, $_SESSION['user_id']);
        $this->assertEquals($username, $_SESSION['username']);
        $this->assertEquals('admin', $_SESSION['user_role']);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $username = 'testuser';
        $password = 'wrongpassword';
        
        // Mock database response
        $mockUser = [
            'row_id' => 1,
            'username' => 'testuser',
            'role' => 'admin',
            'password_hash' => password_hash('correctpassword', PASSWORD_DEFAULT)
        ];
        
        $this->db->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo('SELECT row_id, username, role, password_hash FROM staff WHERE username = ?'),
                $this->equalTo([$username])
            )
            ->willReturn($mockUser);

        $result = $this->auth->login($username, $password);
        
        $this->assertFalse($result);
        $this->assertEmpty($_SESSION);
    }

    public function testLoginWithNonExistentUser(): void
    {
        $username = 'nonexistent';
        $password = 'password123';
        
        $this->db->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo('SELECT row_id, username, role, password_hash FROM staff WHERE username = ?'),
                $this->equalTo([$username])
            )
            ->willReturn(false);

        $result = $this->auth->login($username, $password);
        
        $this->assertFalse($result);
        $this->assertEmpty($_SESSION);
    }

    public function testLogoutClearsSession(): void
    {
        // Set up logged in state
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'testuser';
        $_SESSION['user_role'] = 'admin';
        
        $this->auth->logout();
        
        $this->assertEmpty($_SESSION);
        $this->assertFalse($this->auth->isLoggedIn());
        $this->assertNull($this->auth->getUser());
    }

    public function testRequireRoleRedirectsWhenNotLoggedIn(): void
    {
        $this->expectException(\Exception::class);
        $this->auth->requireRole('admin');
    }

    public function testHasRoleReturnsFalseWhenNotLoggedIn(): void
    {
        $this->assertFalse($this->auth->hasRole('admin'));
    }

    public function testHasRoleReturnsCorrectValueWhenLoggedIn(): void
    {
        // Mock session data
        $_SESSION['user_id'] = 1;
        
        // Mock database response
        $mockUser = [
            'row_id' => 1,
            'username' => 'testuser',
            'role' => 'admin'
        ];
        
        $this->db->expects($this->once())
            ->method('fetchOne')
            ->willReturn($mockUser);

        $this->assertTrue($this->auth->hasRole('admin'));
        $this->assertFalse($this->auth->hasRole('scorer'));
    }

    public function testGetUserCachesResult(): void
    {
        // Mock session data
        $_SESSION['user_id'] = 1;
        
        // Mock database response
        $mockUser = [
            'row_id' => 1,
            'username' => 'testuser',
            'role' => 'admin'
        ];
        
        $this->db->expects($this->once())
            ->method('fetchOne')
            ->willReturn($mockUser);

        // Call getUser twice - should only query database once
        $user1 = $this->auth->getUser();
        $user2 = $this->auth->getUser();
        
        $this->assertEquals($mockUser, $user1);
        $this->assertEquals($mockUser, $user2);
    }
}
