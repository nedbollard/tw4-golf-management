<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\RoundWorkflowService;

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
        $this->requireRole('scorer');

        $user = $this->app->getDatabase()->getAuth()->getUser();
        $workflow = new RoundWorkflowService($this->app->getDatabase());
        $active = $workflow->getActiveRoundForScorerMenu();

        if ($active) {
            $workflow->openCardEntry((int) $active['round_id'], (int) ($user['user_id'] ?? 0));
        }

        $this->redirect('/scorer/menu');
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->redirect('/scores');
    }

    public function presentResults(): void
    {
        $this->requireRole('scorer');

        $user = $this->app->getDatabase()->getAuth()->getUser();
        $workflow = new RoundWorkflowService($this->app->getDatabase());
        $active = $workflow->getActiveRoundForScorerMenu();

        if ($active) {
            $workflow->presentResults((int) $active['round_id'], (int) ($user['user_id'] ?? 0));
        }

        $this->redirect('/scorer/menu');
    }
}