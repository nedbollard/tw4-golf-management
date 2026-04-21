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

        $before = $this->app->getDatabase()->fetchOne(
            'SELECT row_id, workflow_step, locked_by_staff_id, lock_release_reason
             FROM TW4_live.round
             WHERE row_id = ?',
            [$roundId]
        );

        if ($roundId < 1) {
            $_SESSION['errors'] = ['No live round is available to unlock.'];
            $this->redirect('/admin/scoring-state');
            return;
        }

        $lockService = new RoundLockService($this->app->getDatabase());
        $released = $lockService->forceReleaseLock($roundId, $adminStaffId, 'admin_forced');

        $after = $this->app->getDatabase()->fetchOne(
            'SELECT row_id, workflow_step, locked_by_staff_id, lock_release_reason
             FROM TW4_live.round
             WHERE row_id = ?',
            [$roundId]
        );

        $this->logger->log(
            Logger::LEVEL_WARNING,
            Logger::EVENT_SYSTEM,
            'Admin forced release of scoring lock (state applied)',
            [
                'round_id' => $roundId,
                'admin_staff_id' => $adminStaffId,
                'rows_updated' => $released,
                'before_locked_by_staff_id' => isset($before['locked_by_staff_id']) ? (int) $before['locked_by_staff_id'] : null,
                'after_locked_by_staff_id' => isset($after['locked_by_staff_id']) ? (int) $after['locked_by_staff_id'] : null,
                'before_workflow_step' => (string) ($before['workflow_step'] ?? 'unknown'),
                'after_workflow_step' => (string) ($after['workflow_step'] ?? 'unknown'),
                'before_lock_release_reason' => $before['lock_release_reason'] ?? null,
                'after_lock_release_reason' => $after['lock_release_reason'] ?? null,
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

            $after = $this->app->getDatabase()->fetchOne(
                'SELECT row_id, workflow_step, card_count, lock_release_reason, locked_by_staff_id
                 FROM TW4_live.round
                 WHERE row_id = ?',
                [(int) ($result['round_id'] ?? 0)]
            );

            $this->logger->log(
                Logger::LEVEL_WARNING,
                Logger::EVENT_SYSTEM,
                'Admin reset scoring state from results_presented to card_entry_open (state applied)',
                [
                    'round_id' => (int) ($result['round_id'] ?? 0),
                    'admin_staff_id' => $adminStaffId,
                    'from_step' => $result['from_step'] ?? 'unknown',
                    'to_step' => $result['to_step'] ?? 'unknown',
                    'results_rows_cleared' => (int) ($result['results_rows_cleared'] ?? 0),
                    'card_count' => (int) ($result['card_count'] ?? 0),
                    'applied_workflow_step' => (string) ($after['workflow_step'] ?? 'unknown'),
                    'applied_card_count' => (int) ($after['card_count'] ?? 0),
                    'applied_locked_by_staff_id' => isset($after['locked_by_staff_id']) ? (int) $after['locked_by_staff_id'] : null,
                    'applied_lock_release_reason' => $after['lock_release_reason'] ?? null,
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
