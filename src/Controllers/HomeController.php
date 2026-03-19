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
        // Show main menu - use render to get config data
        $this->render('home/index');
    }

    public function underConstruction(): void
    {
        $this->render('common/under_construction');
    }
}
