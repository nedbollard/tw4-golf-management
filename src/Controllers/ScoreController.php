<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\RoundWorkflowService;
use App\Services\ScoreEntryService;

/**
 * Score Controller - Handle score-related operations
 */
class ScoreController extends BaseController
{
    private ScoreEntryService $scoreEntryService;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->scoreEntryService = new ScoreEntryService($this->app->getDatabase());
    }

    public function index(): void
    {
        $this->requireAuth();
        $this->redirect('/scores/enter');
    }

    public function enter(): void
    {
        $this->requireRole('scorer');

        $user = $this->app->getDatabase()->getAuth()->getUser();
        $workflow = new RoundWorkflowService($this->app->getDatabase());
        $active = $workflow->getActiveRoundForScorerMenu();

        if (!$active || ($active['workflow_step'] ?? 'not_started') !== 'card_entry_open') {
            $_SESSION['errors'] = ['Start a round before entering cards.'];
            $this->redirect('/scorer/menu');
            return;
        }

        $roundId = (int) $active['round_id'];
        if (!$workflow->openCardEntry($roundId, (int) ($user['user_id'] ?? 0))) {
            $_SESSION['errors'] = ['Unable to acquire lock for card entry.'];
            $this->redirect('/scorer/menu');
            return;
        }

        $this->render('scores/select-player', [
            'title' => 'Select Player - TW4 Golf Management',
            'round' => $active,
            'players' => $this->scoreEntryService->getSelectablePlayers($roundId),
            'success' => $_SESSION['success'] ?? null,
            'errors' => $_SESSION['errors'] ?? [],
        ]);

        unset($_SESSION['success'], $_SESSION['errors']);
    }

    public function enterCard(int $playerId): void
    {
        $this->requireRole('scorer');

        $workflow = new RoundWorkflowService($this->app->getDatabase());
        $active = $workflow->getActiveRoundForScorerMenu();

        if (!$active || ($active['workflow_step'] ?? 'not_started') !== 'card_entry_open') {
            $_SESSION['errors'] = ['Round is not open for card entry.'];
            $this->redirect('/scorer/menu');
            return;
        }

        $roundId = (int) $active['round_id'];
        $entryData = $this->scoreEntryService->buildEntryData($roundId, $playerId);
        if ($entryData === null) {
            $_SESSION['errors'] = ['Unable to prepare card entry for this player.'];
            $this->redirect('/scores/enter');
            return;
        }

        $this->render('scores/enter-card', [
            'title' => 'Enter Card - TW4 Golf Management',
            'round' => $active,
            'entry' => $entryData,
            'errors' => $_SESSION['errors'] ?? [],
        ]);

        unset($_SESSION['errors']);
    }

    public function storeCard(int $playerId): void
    {
        $this->requireRole('scorer');

        $user = $this->app->getDatabase()->getAuth()->getUser();
        $staffId = (int) ($user['user_id'] ?? 0);
        $username = (string) ($user['username'] ?? 'system');

        $workflow = new RoundWorkflowService($this->app->getDatabase());
        $active = $workflow->getActiveRoundForScorerMenu();
        if (!$active || ($active['workflow_step'] ?? 'not_started') !== 'card_entry_open') {
            $_SESSION['errors'] = ['Round is not open for card entry.'];
            $this->redirect('/scorer/menu');
            return;
        }

        $roundId = (int) $active['round_id'];
        if (!$this->scoreEntryService->assertEntryLock($roundId, $staffId)) {
            $_SESSION['errors'] = ['Card entry lock is not held by your session.'];
            $this->redirect('/scorer/menu');
            return;
        }

        $entryData = $this->scoreEntryService->buildEntryData($roundId, $playerId);
        if ($entryData === null) {
            $_SESSION['errors'] = ['Unable to prepare card entry for this player.'];
            $this->redirect('/scores/enter');
            return;
        }

        $postedScores = $this->getPostData()['scores'] ?? [];
        $calculated = $this->scoreEntryService->calculateCard($entryData, is_array($postedScores) ? $postedScores : []);

        if (!empty($calculated['errors'])) {
            $this->render('scores/enter-card', [
                'title' => 'Enter Card - TW4 Golf Management',
                'round' => $active,
                'entry' => $calculated,
                'errors' => $calculated['errors'],
            ]);
            return;
        }

        $action = (string) ($this->getPostData()['action'] ?? 'calculate');
        if ($action === 'save') {
            $this->scoreEntryService->saveCard($roundId, $playerId, $calculated, $username);
            $_SESSION['success'] = 'Card saved successfully.';
            $this->redirect('/scores/enter');
            return;
        }

        $this->render('scores/enter-card', [
            'title' => 'Enter Card - TW4 Golf Management',
            'round' => $active,
            'entry' => $calculated,
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->redirect('/scores/enter');
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