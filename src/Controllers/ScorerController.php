<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\RoundWorkflowService;

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

        $workflow = new RoundWorkflowService($this->app->getDatabase());
        $active = $workflow->getActiveRoundForScorerMenu();
        $roundState = $workflow->getMenuState(
            $active ? (int) $active['round_id'] : null,
            (int) ($user['user_id'] ?? 0)
        );

        $this->render('scorer/menu', [
            'user'       => $user,
            'roundState' => $roundState,
        ]);
    }
}
