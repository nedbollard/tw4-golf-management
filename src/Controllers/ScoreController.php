<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;

/**
 * Score Controller - Handle score-related operations
 */
class ScoreController extends BaseController
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    public function index(): void
    {
        $this->requireAuth();
        $this->render('scores/index', [
            'title' => 'Scores - TW4 Golf Management'
        ]);
    }

    public function enter(): void
    {
        $this->requireAuth();
        $this->render('scores/enter', [
            'title' => 'Enter Scores - TW4 Golf Management'
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->redirect('/scores');
    }
}