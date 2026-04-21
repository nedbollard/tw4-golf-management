<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\Logger;
use App\Services\RoundLockService;
use App\Services\RoundWorkflowService;

/**
 * Admin Controller - Admin-only functions
 * 
 * reviewed? Yes
 */
class AdminController extends BaseController
{
    private Logger $logger;

    public function __construct(Application $app, Logger $logger)
    {
        parent::__construct($app);
        $this->logger = $logger;
    }

    public function menu(): void
    {
        $this->requireRole('admin');
        
        $this->render('admin/menu');
    }

    public function scoringState(): void
    {
        $this->requireRole('admin');

        $workflow = new RoundWorkflowService($this->app->getDatabase());
        $round = $workflow->getPermanentRound();

        $this->render('admin/scoring-state', [
            'round' => $round,
            'errors' => $_SESSION['errors'] ?? [],
            'success' => $_SESSION['success'] ?? null,
        ]);

        unset($_SESSION['errors'], $_SESSION['success']);
    }

    public function unlockScoringProcess(): void
    {
        $this->requireRole('admin');

        $user = $this->app->getDatabase()->getAuth()->getUser();
        $adminStaffId = (int) ($user['user_id'] ?? 0);
        $username = (string) ($user['username'] ?? 'system');

        $workflow = new RoundWorkflowService($this->app->getDatabase());
        $round = $workflow->getPermanentRound();
        $roundId = (int) ($round['round_id'] ?? 0);

        if ($roundId < 1) {
            $_SESSION['errors'] = ['No live round is available to unlock.'];
            $this->redirect('/admin/scoring-state');
            return;
        }

        $lockService = new RoundLockService($this->app->getDatabase());
        $released = $lockService->forceReleaseLock($roundId, $adminStaffId, 'admin_forced');

        $this->logger->log(
            Logger::LEVEL_WARNING,
            Logger::EVENT_SYSTEM,
            'Admin forced release of scoring lock',
            [
                'round_id' => $roundId,
                'admin_staff_id' => $adminStaffId,
                'rows_updated' => $released,
            ],
            $username
        );

        $_SESSION['success'] = 'Scoring lock released.';
        $this->redirect('/admin/scoring-state');
    }

    public function resetResultsToCardEntry(): void
    {
        $this->requireRole('admin');

        $user = $this->app->getDatabase()->getAuth()->getUser();
        $adminStaffId = (int) ($user['user_id'] ?? 0);
        $username = (string) ($user['username'] ?? 'system');

        $workflow = new RoundWorkflowService($this->app->getDatabase());

        try {
            $result = $workflow->adminResetResultsToCardEntry($username);

            $this->logger->log(
                Logger::LEVEL_WARNING,
                Logger::EVENT_SYSTEM,
                'Admin reset scoring state from results_presented to card_entry_open',
                [
                    'round_id' => (int) ($result['round_id'] ?? 0),
                    'admin_staff_id' => $adminStaffId,
                    'from_step' => $result['from_step'] ?? 'unknown',
                    'to_step' => $result['to_step'] ?? 'unknown',
                    'results_rows_cleared' => (int) ($result['results_rows_cleared'] ?? 0),
                    'card_count' => (int) ($result['card_count'] ?? 0),
                ],
                $username
            );

            $_SESSION['success'] = 'Scoring state reset to card entry open. Live results were cleared.';
        } catch (\RuntimeException $e) {
            $_SESSION['errors'] = [$e->getMessage()];
        }

        $this->redirect('/admin/scoring-state');
    }
}
