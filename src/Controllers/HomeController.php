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
        // Check if user is logged in
        $user = $this->app->getDatabase()->getAuth()->getUser();
        
        // Show main menu - use render to get config data
        $this->render('home/index', [
            'user' => $user,
            'isLoggedIn' => $user !== null
        ]);
    }

    public function underConstruction(): void
    {
        $this->render('common/under_construction');
    }
}
