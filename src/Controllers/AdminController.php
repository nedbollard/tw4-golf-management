<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\Logger;
use App\Services\RoundLockService;
use App\Services\RoundWorkflowService;
use App\Services\TeamHaggleSeriousService;

/**
 * Admin Controller - Admin-only functions
 * 
 * reviewed? Yes
 */
class AdminController extends BaseController
{
    private Logger $logger;
    private RoundWorkflowService $roundWorkflowService;
    private RoundLockService $roundLockService;
    private TeamHaggleSeriousService $teamHaggleSeriousService;

    public function __construct(
        Application $app,
        Logger $logger,
        RoundWorkflowService $roundWorkflowService,
        RoundLockService $roundLockService,
        TeamHaggleSeriousService $teamHaggleSeriousService
    )
    {
        parent::__construct($app);
        $this->logger = $logger;
        $this->roundWorkflowService = $roundWorkflowService;
        $this->roundLockService = $roundLockService;
        $this->teamHaggleSeriousService = $teamHaggleSeriousService;
    }

    public function menu(): void
    {
        $this->requireRole('admin');

        $this->render('admin/menu');
    }

    public function scoringState(): void
    {
        $this->requireRole('admin');

        $round = $this->roundWorkflowService->getPermanentRound();

        $this->render('admin/scoring-state', [
            'round' => $round,
        ]);
    }

    public function unlockScoringProcess(): void
    {
        $this->requireRole('admin');

        $user = $this->authService->getUser();
        $adminStaffId = (int) ($user['user_id'] ?? 0);
        $username = (string) ($user['username'] ?? 'system');

        $round = $this->roundWorkflowService->getPermanentRound();
        $roundId = (int) ($round['round_id'] ?? 0);

        $before = $this->app->getDatabase()->fetchOne(
            'SELECT row_id, workflow_step, locked_by_staff_id, lock_release_reason
             FROM TW4_live.round
             WHERE row_id = ?',
            [$roundId]
        );

        if ($roundId < 1) {
            $this->flash->error('No live round is available to unlock.');
            $this->redirect('/admin/scoring-state');
            return;
        }

        $released = $this->roundLockService->forceReleaseLock($roundId, $adminStaffId, 'admin_forced');

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

        $this->flash->success('Scoring lock released.');
        $this->redirect('/admin/scoring-state');
    }

    public function resetResultsToCardEntry(): void
    {
        $this->requireRole('admin');

        $user = $this->authService->getUser();
        $adminStaffId = (int) ($user['user_id'] ?? 0);
        $username = (string) ($user['username'] ?? 'system');

        try {
            $result = $this->roundWorkflowService->adminResetResultsToCardEntry($username);
            $fromStep = (string) ($result['from_step'] ?? 'unknown');

            $after = $this->app->getDatabase()->fetchOne(
                'SELECT row_id, workflow_step, card_count, lock_release_reason, locked_by_staff_id
                 FROM TW4_live.round
                 WHERE row_id = ?',
                [(int) ($result['round_id'] ?? 0)]
            );

            $this->logger->log(
                Logger::LEVEL_WARNING,
                Logger::EVENT_SYSTEM,
                sprintf('Admin reset scoring state from %s to card_entry_open (state applied)', $fromStep),
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

            $this->flash->success('Scoring state reset to card entry open. Live results were cleared.');
        } catch (\RuntimeException $e) {
            $this->flash->error($e->getMessage());
        }

        $this->redirect('/admin/scoring-state');
    }

    public function teamHaggleSerious(): void
    {
        $this->requireRole('admin');

        if (!$this->teamHaggleSeriousService->isSeriousMode()) {
            $this->flash->error('team_haggle_state must be set to serious before editing fixed teams.');
            $this->redirect('/admin/menu');
            return;
        }

        $state = $this->teamHaggleSeriousService->buildEditorState();
        $workflowStep = (string) ($state['round']['workflow_step'] ?? 'between_rounds');
        if (!in_array($workflowStep, ['between_rounds', 'not_started'], true)) {
            $this->flash->error('Serious team-haggle membership can only be changed between rounds.');
            $this->redirect('/admin/menu');
            return;
        }

        $this->render('admin/team-haggle-serious', [
            'state' => $state,
        ]);
    }

    public function teamHaggleSeriousSave(): void
    {
        $this->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/team-haggle');
            return;
        }

        if (!$this->validateCsrf()) {
            $this->flash->error('Invalid CSRF token');
            $this->redirect('/admin/team-haggle');
            return;
        }

        if (!$this->teamHaggleSeriousService->isSeriousMode()) {
            $this->flash->error('team_haggle_state must be set to serious before editing fixed teams.');
            $this->redirect('/admin/menu');
            return;
        }

        $data = $this->getPostData();
        $draft = $this->parseDraftFromRequest($data['draft'] ?? []);
        $action = (string) ($data['action'] ?? 'apply');

        if ($action === 'save') {
            try {
                $user = $this->authService->getUser();
                $updatedBy = (string) ($user['username'] ?? 'system');
                $postedRevision = max(0, (int) ($data['revision'] ?? 0));

                $result = $this->teamHaggleSeriousService->saveTeams($draft, $postedRevision, $updatedBy);
                $this->logger->info('Serious team-haggle teams saved', [
                    'teams_saved' => (int) ($result['teams_saved'] ?? 0),
                    'new_revision' => (int) ($result['revision'] ?? 0),
                ], $updatedBy);

                $this->flash->success('Team-haggle membership saved.');
                $this->redirect('/admin/team-haggle');
                return;
            } catch (\RuntimeException $e) {
                $state = $this->teamHaggleSeriousService->buildEditorState($draft);
                $this->render('admin/team-haggle-serious', [
                    'state' => $state,
                    'errors' => [$e->getMessage()],
                ]);
                return;
            }
        }

        $removedOrderRaw = trim((string) ($data['removed_order'] ?? ''));
        $removedOrder = $removedOrderRaw === '' ? [] : array_filter(array_map('trim', explode(',', $removedOrderRaw)));
        $replacementIds = array_values(array_map('strval', (array) ($data['replacement_ids'] ?? [])));

        $applied = $this->teamHaggleSeriousService->applyReplacements($draft, $removedOrder, $replacementIds);
        $state = $this->teamHaggleSeriousService->buildEditorState((array) ($applied['teams'] ?? []), (array) ($applied['messages'] ?? []));

        $this->render('admin/team-haggle-serious', [
            'state' => $state,
        ]);
    }

    private function parseDraftFromRequest(array $draft): array
    {
        $normalized = [];
        foreach ($draft as $team => $slots) {
            $teamNumber = (int) $team;
            if ($teamNumber < 1 || !is_array($slots)) {
                continue;
            }

            foreach ($slots as $slot => $identifier) {
                $slotNumber = (int) $slot;
                if ($slotNumber < 1) {
                    continue;
                }

                $normalized[$teamNumber][$slotNumber] = [
                    'player_identifier' => trim((string) $identifier),
                ];
            }
        }

        return $normalized;
    }
}
