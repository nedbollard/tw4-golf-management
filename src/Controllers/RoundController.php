<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;

/**
 * Round Controller - Handle round-related operations
 */
class RoundController extends BaseController
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    public function index(): void
    {
        $this->requireAuth();
        $this->render('rounds/index', [
            'title' => 'Rounds - TW4 Golf Management'
        ]);
    }

    public function start(): void
    {
        $this->requireAuth();
        $this->render('rounds/start', [
            'title' => 'Start Round - TW4 Golf Management'
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->redirect('/rounds');
    }
}