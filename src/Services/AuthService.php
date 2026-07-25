<?php

namespace App\Services;

use App\Core\Database;

/**
 * Authentication Service - Handle all auth-related operations
 */
class AuthService
{
    private Database $db;
    private const SESSION_TIMEOUT = 3600; // 1 hour in seconds

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
            $_SESSION['session_id'] = session_id();
            $_SESSION['last_activity_at'] = time();
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
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return false;
        }

        // Check session timeout
        if (isset($_SESSION['last_activity_at'])) {
            $inactiveTime = time() - $_SESSION['last_activity_at'];
            if ($inactiveTime > self::SESSION_TIMEOUT) {
                $this->logout();
                return false;
            }
        }

        return true;
    }

    public function updateActivity(): void
    {
        if ($this->isLoggedIn()) {
            $_SESSION['last_activity_at'] = time();
        }
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
            $this->redirectToLogin();
        }
    }

    public function requireRole(string $role): void
    {
        if (!$this->isLoggedIn()) {
            $this->redirectToLogin();
            return;
        }

        if (!$this->hasRole($role)) {
            $currentRole = (string) ($_SESSION['user_role'] ?? 'unknown');
            $query = http_build_query([
                'code' => 403,
                'message' => sprintf(
                    'Access denied. This page requires the %s role. You are currently signed in as %s.',
                    $role,
                    $currentRole
                ),
            ]);
            $this->redirectToUrl('/error?' . $query);
        }
    }

    private function redirectToLogin(): void
    {
        $this->redirectToUrl('/login');
    }

    private function redirectToUrl(string $url, int $statusCode = 302): void
    {
        header('Location: ' . $url, true, $statusCode);
        if (php_sapi_name() === 'cli') {
            $_SERVER['CLI_REDIRECT_URL'] = $url;
            $_SERVER['CLI_REDIRECT_STATUS'] = $statusCode;
            return;
        }
        exit;
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
