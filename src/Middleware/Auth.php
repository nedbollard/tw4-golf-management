<?php

namespace App\Middleware;

use App\Core\Database;

/**
 * Authentication middleware for handling user sessions and login
 */
class Auth
{
    private Database $db;
    private ?array $user = null;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->startSession();
    }

    public function isLoggedIn(): bool
    {
        return $this->getUser() !== null;
    }

    public function getUser(): ?array
    {
        if ($this->user === null && isset($_SESSION['user_id'])) {
            $this->user = $this->db->fetchOne(
                'SELECT row_id, username, role FROM staff WHERE row_id = ?',
                [$_SESSION['user_id']]
            );
        }
        
        return $this->user;
    }

    public function login(string $username, string $password): bool
    {
        $user = $this->db->fetchOne(
            'SELECT row_id, username, role, password_hash FROM staff WHERE username = ?',
            [$username]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = $user['row_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $this->user = $user;

        return true;
    }

    public function logout(): void
    {
        session_destroy();
        $this->user = null;
    }

    public function requireRole(string $role): void
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
        }

        $user = $this->getUser();
        if ($user['user_role'] !== $role) {
            $this->redirect('/unauthorized');
        }
    }

    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
        }
    }

    public function hasRole(string $role): bool
    {
        $user = $this->getUser();
        return $user && $user['user_role'] === $role;
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}
