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

        $configStatus = $this->configService->getConfigStatus();
        $scoringDisabled = $configStatus !== 'ready';

        $user = $this->authService->getUser();

        $workflow = new RoundWorkflowService($this->app->getDatabase());
        $active = $workflow->getActiveRoundForScorerMenu();
        $roundState = $workflow->getMenuState(
            $active ? (int) $active['round_id'] : null,
            (int) ($user['user_id'] ?? 0)
        );

        if (!empty($_SESSION['just_finished_round']) && isset($roundState['steps']) && is_array($roundState['steps'])) {
            foreach ($roundState['steps'] as &$step) {
                $step['status'] = 'completed';
                $step['enabled'] = false;
            }
            unset($step);
            unset($_SESSION['just_finished_round']);
        }

        if ($scoringDisabled && isset($roundState['steps']) && is_array($roundState['steps'])) {
            foreach ($roundState['steps'] as &$step) {
                $step['enabled'] = false;
            }
            unset($step);
        }

        $errors = [];
        if ($scoringDisabled) {
            $errors[] = 'Scoring is disabled until configuration status is set to ready by an admin.';
        }

        $this->render('scorer/menu', [
            'user'       => $user,
            'roundState' => $roundState,
            'errors'     => $errors,
        ]);
    }
}
