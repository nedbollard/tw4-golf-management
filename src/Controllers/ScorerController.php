<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;

/**
 * Scorer Controller - Scorer-only functions
 */
class ScorerController extends BaseController
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    public function menu(): void
    {
        $this->requireRole('scorer');

        $user = $this->app->getDatabase()->getAuth()->getUser();

        // Placeholder workflow state until RoundWorkflowService is implemented.
        // step statuses: 'waiting' | 'in_progress' | 'completed'
        // enabled: whether the step button is clickable
        $roundState = [
            'active_round'  => null,   // will be populated by RoundWorkflowService
            'card_count'    => 0,
            'lock'          => null,   // ['holder_name' => '...', 'acquired_at' => '...']
            'steps' => [
                1 => ['label' => 'Start a New Round',  'status' => 'waiting', 'enabled' => true,  'route' => '/rounds/start'],
                2 => ['label' => 'Enter Cards',        'status' => 'waiting', 'enabled' => false, 'route' => '/scores/enter'],
                3 => ['label' => 'Present Results',    'status' => 'waiting', 'enabled' => false, 'route' => '/scores/present-results'],
                4 => ['label' => 'Finish the Round',   'status' => 'waiting', 'enabled' => false, 'route' => '/rounds/finish'],
            ],
        ];

        $this->render('scorer/menu', [
            'user'       => $user,
            'roundState' => $roundState,
        ]);
    }
}
