<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\ConfigService;

/**
 * Home Controller - Main application entry point
 */
class HomeController extends BaseController
{
    public function __construct(Application $app, ConfigService $configService)
    {
        parent::__construct($app);
        // ConfigService is already handled by BaseController
    }

    public function index(): void
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            // Ensure session save path is configured
            $savePath = session_save_path();
            if (empty($savePath)) {
                session_save_path('/tmp');
            }

            $isSecure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['HTTP_X_FORWARDED_PROTOCOL'] ?? '') === 'https');

            // Set session cookie parameters for proper session handling
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            ini_set('session.cookie_secure', $isSecure ? '1' : '0');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Optional: Regenerate session ID after login to prevent fixation attacks

            session_regenerate_id(true);

        }
        
        // Check if user is logged in
        $auth = $this->authService;
        $user = $auth->getUser();
        $isLoggedIn = $auth->isLoggedIn();
        
        // Show main menu - use render to get config data
        $this->render('home/index', [
            'user' => $user,
            'isLoggedIn' => $isLoggedIn
        ]);
    }

    public function underConstruction(): void
    {
        $this->render('common/under_construction');
    }
}
