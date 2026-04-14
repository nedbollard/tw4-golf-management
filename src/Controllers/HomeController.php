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
            if (!session_save_path()) {
                session_save_path('/tmp');
            }
            session_start();
        }
        
        // Check if user is logged in
        $auth = $this->app->getDatabase()->getAuth();
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
