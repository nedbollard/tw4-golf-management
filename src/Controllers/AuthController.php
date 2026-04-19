<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\AuthService;
use App\Services\Logger;
use App\Services\RoundLockService;

/**
 * Authentication Controller - Handles user login, logout, and registration
 */
class AuthController extends BaseController
{
    private AuthService $authService;
    private Logger $logger;
    
    public function __construct(Application $app, AuthService $authService, Logger $logger)
    {
        parent::__construct($app);
        $this->authService = $authService;
        $this->logger = $logger;
    }

    public function showLogin(): void
    {
        // If already logged in, redirect to appropriate menu
        if ($user = $this->app->getDatabase()->getAuth()->getUser()) {
            if ($user['user_role'] === 'admin') {
                $this->redirect('/admin/menu');
            } elseif ($user['user_role'] === 'scorer') {
                $this->redirect('/scorer/menu');
            } else {
                $this->redirect('/dashboard');
            }
            return;
        }

        include __DIR__ . '/../Views/auth/login.php';
    }

    public function showRegister(): void
    {
        if ($this->app->getDatabase()->getAuth()->isLoggedIn()) {
            $this->redirect('/');
        }

        $this->render('auth/register', [
            'title' => 'Register - TW4 Golf Management',
            'errors' => $_SESSION['errors'] ?? []
        ]);

        unset($_SESSION['errors']);
    }

    public function login(): void
    {
        $data = $this->getPostData();
        
        $errors = $this->validateLoginData($data);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            $this->redirect('/login');
        }

        if ($this->authService->login($data['username'], $data['password'])) {
            // Log successful login
            $this->logger->logLogin($data['username'], true);
            
            // Redirect to role-specific menu
            $user = $this->app->getDatabase()->getAuth()->getUser();
            if ($user['user_role'] === 'admin') {
                $this->redirect('/admin/menu');
            } elseif ($user['user_role'] === 'scorer') {
                $this->redirect('/scorer/menu');
            } else {
                $this->redirect('/dashboard');
            }
        } else {
            // Log failed login
            $this->logger->logLogin($data['username'], false, 'Invalid credentials');
            
            $_SESSION['errors'] = ['login' => 'Invalid username or password'];
            $this->redirect('/login');
        }
    }

    public function logout(): void
    {
        $user = $this->app->getDatabase()->getAuth()->getUser();
        if ($user && isset($user['username'])) {
            $this->logger->logLogout($user['username']);

            $lockService = new RoundLockService($this->app->getDatabase());
            $lockService->releaseAnyLocksByStaff((int) ($user['user_id'] ?? 0), 'logout');
        }
        
        $this->authService->logout();
        $this->redirect('/');
    }

    public function register(): void
    {
        $data = $this->getPostData();
        
        $errors = $this->validateRegistrationData($data);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $this->redirect('/register');
        }

        if ($this->authService->registerStaff($data)) {
            $this->redirect('/login');
        } else {
            $_SESSION['errors'] = ['register' => 'Registration failed. Please try again.'];
            $this->redirect('/register');
        }
    }

    private function validateLoginData(array $data): array
    {
        $errors = [];
        
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        }
        
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }
        
        // Debug: Log validation attempts
        error_log("AuthController: Login attempt - Username: '" . ($data['username'] ?? 'empty') . "', Password: '" . ($data['password'] ?? 'empty') . "'");
        error_log("AuthController: Validation errors: " . json_encode($errors));
        
        return $errors;
    }

    private function validateRegistrationData(array $data): array
    {
        $errors = [];
        
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        }
        
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }

        if (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        
        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required';
        }

        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required';
        }

        if (!in_array($data['role'], ['admin', 'scorer'])) {
            $errors['role'] = 'Role must be admin or scorer';
        }

        return $errors;
    }
}
