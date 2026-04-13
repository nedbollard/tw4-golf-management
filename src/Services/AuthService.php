<?php

namespace App\Services;

use App\Core\Database;

/**
 * Authentication Service - Handle all auth-related operations
 */
class AuthService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function login(string $username, string $password): bool
    {
        $staff = $this->db->fetchOne(
            'SELECT row_id, username, password_hash, role FROM staff WHERE username = ?',
            [$username]
        );
        
        if (!$staff) {
            return false;
        }
        
        if (password_verify($password, $staff['password_hash'])) {
            $_SESSION['user_id'] = $staff['row_id'];
            $_SESSION['username'] = $staff['username'];
            $_SESSION['user_role'] = $staff['role'];
            return true;
        }
        
        return false;
    }

    public function logout(): void
    {
        // Destroy all session data
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public function getUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'user_role' => $_SESSION['user_role']
        ];
    }

    public function hasRole(string $role): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        return ($_SESSION['user_role'] ?? '') === $role;
    }

    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }

    public function requireRole(string $role): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        if (!$this->hasRole($role)) {
            header('Location: /error?code=403&message=Access denied. Role ' . $role . ' required.');
            exit;
        }
    }

    public function registerStaff(array $data): int
    {
        // Validate data
        $errors = $this->validateStaffData($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        // Hash password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);

        return $this->db->insert('staff', $data);
    }

    public function createInitialAdmin(string $username, string $password): int
    {
        $adminData = [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'role' => 'admin',
            'is_active' => true
        ];

        return $this->db->insert('staff', $adminData);
    }

    private function validateStaffData(array $data): array
    {
        $errors = [];

        if (empty($data['username'])) {
            $errors[] = 'Username is required';
        }

        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        }

        if (strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        if (empty($data['first_name'])) {
            $errors[] = 'First name is required';
        }

        if (empty($data['last_name'])) {
            $errors[] = 'Last name is required';
        }

        if (!in_array($data['role'], ['admin', 'scorer'])) {
            $errors[] = 'Role must be admin or scorer';
        }

        return $errors;
    }
}
