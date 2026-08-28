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
    private Logger $logger;
    private RoundLockService $roundLockService;
    
    public function __construct(
        Application $app,
        AuthService $authService,
        Logger $logger,
        RoundLockService $roundLockService
    )
    {
        parent::__construct($app);
        $this->authService = $authService;
        $this->logger = $logger;
        $this->roundLockService = $roundLockService;
    }

    public function showLogin(): void
    {
        // If already logged in, redirect to appropriate menu
        if ($user = $this->authService->getUser()) {
            if ($user['user_role'] === 'admin') {
                $this->redirect('/admin/menu');
            } elseif ($user['user_role'] === 'scorer') {
                $this->redirect('/scorer/menu');
            } else {
                $this->redirect('/dashboard');
            }
            return;
        }

        $this->render('auth/login', [
            'title' => 'Login - TW4 Golf Management',
        ]);
    }

    public function showRegister(): void
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect('/');
        }

        $this->render('auth/register', [
            'title' => 'Register - TW4 Golf Management'
        ]);
    }

    public function login(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash->error('Invalid CSRF token');
            $this->redirect('/login');
            return;
        }

        $data = $this->getPostData();
        
        $errors = $this->validateLoginData($data);
        
        if (!empty($errors)) {
            $this->flash->error($errors);
            $this->flash->setOld($data);
            $this->redirect('/login');
            return;
        }

        if ($this->authService->login($data['username'], $data['password'])) {
            // Log successful login
            $this->logger->logLogin($data['username'], true);
            
            // Redirect to role-specific menu
            $user = $this->authService->getUser();
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
            
            $this->flash->error('Invalid username or password');
            $this->flash->setOld($data);
            $this->redirect('/login');
        }
    }

    public function logout(): void
    {
        $user = $this->authService->getUser();
        if ($user && isset($user['username'])) {
            $this->logger->logLogout($user['username']);

            $this->roundLockService->releaseAnyLocksByStaff((int) ($user['user_id'] ?? 0), 'logout');
        }
        
        $this->authService->logout();
        $this->redirect('/');
    }

    public function register(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash->error('Invalid CSRF token');
            $this->redirect('/register');
            return;
        }

        $data = $this->getPostData();
        
        $errors = $this->validateRegistrationData($data);
        
        if (!empty($errors)) {
            $this->flash->error($errors);
            $this->flash->setOld($data);
            $this->redirect('/register');
            return;
        }

        if ($this->authService->registerStaff($data)) {
            $this->redirect('/login');
        } else {
            $this->flash->error('Registration failed. Please try again.');
            $this->flash->setOld($data);
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
