<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\Logger;
use App\Services\ResultsPresentationService;
use App\Services\RoundWorkflowService;
use App\Services\ScoreEntryService;

/**
 * Score Controller - Handle score-related operations
 */
class ScoreController extends BaseController
{
    private ScoreEntryService $scoreEntryService;
    private ResultsPresentationService $resultsPresentationService;
    private Logger $logger;

    public function __construct(Application $app, Logger $logger = null)
    {
        parent::__construct($app);
        $this->scoreEntryService = new ScoreEntryService($this->app->getDatabase());
        $this->resultsPresentationService = new ResultsPresentationService($this->app->getDatabase());
        $this->logger = $logger ?? new Logger($this->app->getDatabase());
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
        
        try {
            $entryData = $this->scoreEntryService->buildEntryData($roundId, $playerId);
        } catch (\RuntimeException $e) {
            // Get player identifier for error message
            $player = $this->app->getDatabase()->fetchOne(
                'SELECT player_identifier FROM TW4_base.roster WHERE row_id = ?',
                [$playerId]
            );
            $playerIdent = $player['player_identifier'] ?? "Player #$playerId";
            $_SESSION['errors'] = ["Unable to prepare card entry for $playerIdent: " . $e->getMessage()];
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

        try {
            $entryData = $this->scoreEntryService->buildEntryData($roundId, $playerId);
        } catch (\RuntimeException $e) {
            // Get player identifier for error message
            $player = $this->app->getDatabase()->fetchOne(
                'SELECT player_identifier FROM TW4_base.roster WHERE row_id = ?',
                [$playerId]
            );
            $playerIdent = $player['player_identifier'] ?? "Player #$playerId";
            $_SESSION['errors'] = ["Unable to prepare card entry for $playerIdent: " . $e->getMessage()];
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
            $playerIdent = $entryData['player']['player_identifier'] ?? "Player #$playerId";
            $_SESSION['success'] = "Card saved successfully for $playerIdent.";
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
        $staffId = (int) ($user['user_id'] ?? 0);
        $workflow = new RoundWorkflowService($this->app->getDatabase());
        $active = $workflow->getActiveRoundForScorerMenu();

        if (!$active || ($active['workflow_step'] ?? 'not_started') !== 'card_entry_open') {
            $_SESSION['errors'] = ['Round is not open for presenting results.'];
            $this->redirect('/scorer/menu');
            return;
        }

        $roundId = (int) $active['round_id'];
        if (!$this->scoreEntryService->assertEntryLock($roundId, $staffId)
            && !$workflow->openCardEntry($roundId, $staffId)) {
            $_SESSION['errors'] = ['Card entry lock is not held by your session.'];
            $this->redirect('/scorer/menu');
            return;
        }

        if (!$workflow->validateCanPresentResults($roundId)) {
            $_SESSION['errors'] = ['At least four cards are required before presenting results.'];
            $this->redirect('/scorer/menu');
            return;
        }

        try {
            $resultsData = $this->resultsPresentationService->buildPresentationData($roundId);
        } catch (\RuntimeException $e) {
            $_SESSION['errors'] = [$e->getMessage()];
            $this->redirect('/scorer/menu');
            return;
        }

        $this->render('scores/present-results', [
            'title' => 'Present Results - TW4 Golf Management',
            'round' => $active,
            'resultsData' => $resultsData,
            'errors' => $_SESSION['errors'] ?? [],
            'old' => $_SESSION['old'] ?? [],
        ]);

        unset($_SESSION['errors'], $_SESSION['old']);
    }

    public function leaderboard(): void
    {
        $workflow = new RoundWorkflowService($this->app->getDatabase());
        $active = $workflow->getActiveRoundForScorerMenu();

        $resultsData = [
            'leaderboard' => [],
        ];
        $notice = null;
        $showPublishedResultsNudge = false;

        if (!$active || ($active['workflow_step'] ?? 'not_started') === 'not_started') {
            $notice = 'No live round is active yet.';
            $showPublishedResultsNudge = true;
        } else {
            $roundId = (int) ($active['round_id'] ?? 0);

            try {
                $resultsData = $this->resultsPresentationService->buildPresentationData($roundId);
            } catch (\RuntimeException $e) {
                $notice = $e->getMessage();
            }

            $leaderboard = $resultsData['leaderboard'] ?? [];
        }

        $this->render('scores/leaderboard', [
            'title' => 'Leaderboard - TW4 Golf Management',
            'round' => $active,
            'resultsData' => $resultsData,
            'notice' => $notice,
            'showPublishedResultsNudge' => $showPublishedResultsNudge,
        ]);
    }

    public function finalizeResults(): void
    {
        $this->requireRole('scorer');

        $user = $this->app->getDatabase()->getAuth()->getUser();
        $staffId = (int) ($user['user_id'] ?? 0);
        $username = (string) ($user['username'] ?? 'system');

        $workflow = new RoundWorkflowService($this->app->getDatabase());
        $active = $workflow->getActiveRoundForScorerMenu();

        if (!$active || ($active['workflow_step'] ?? 'not_started') !== 'card_entry_open') {
            $_SESSION['errors'] = ['Round is not open for presenting results.'];
            $this->redirect('/scorer/menu');
            return;
        }

        $roundId = (int) $active['round_id'];
        if (!$this->scoreEntryService->assertEntryLock($roundId, $staffId)
            && !$workflow->openCardEntry($roundId, $staffId)) {
            $_SESSION['errors'] = ['Card entry lock is not held by your session.'];
            $this->redirect('/scorer/menu');
            return;
        }

        if (!$workflow->validateCanPresentResults($roundId)) {
            $_SESSION['errors'] = ['At least four cards are required before presenting results.'];
            $this->redirect('/scorer/menu');
            return;
        }

        try {
            $resultsData = $this->resultsPresentationService->buildPresentationData($roundId);
            $closestToPinIdentifier = trim((string) ($this->getPostData()['closest_to_pin_identifier'] ?? ''));
            $options = $resultsData['closest_to_pin_options'] ?? [];

            if ($closestToPinIdentifier === '' || !in_array($closestToPinIdentifier, $options, true)) {
                $_SESSION['errors'] = ['Please choose a valid closest-to-pin winner.'];
                $_SESSION['old'] = ['closest_to_pin_identifier' => $closestToPinIdentifier];
                $this->redirect('/scores/present-results');
                return;
            }

            $beforeState = $this->app->getDatabase()->fetchOne(
                'SELECT row_id, workflow_step FROM TW4_live.round WHERE row_id = ?',
                [$roundId]
            );

            $this->resultsPresentationService->saveResults($roundId, $resultsData, $closestToPinIdentifier, $username);

            if (!$workflow->presentResults($roundId, $staffId)) {
                throw new \RuntimeException('Unable to move workflow to results_presented.');
            }

            $afterState = $this->app->getDatabase()->fetchOne(
                'SELECT row_id, workflow_step, results_presented_at FROM TW4_live.round WHERE row_id = ?',
                [$roundId]
            );

            $this->logger->log(
                Logger::LEVEL_INFO,
                Logger::EVENT_SYSTEM,
                'Scoring workflow changed to results_presented (state applied)',
                [
                    'round_id' => $roundId,
                    'staff_id' => $staffId,
                    'before_workflow_step' => (string) ($beforeState['workflow_step'] ?? 'unknown'),
                    'after_workflow_step' => (string) ($afterState['workflow_step'] ?? 'unknown'),
                    'results_presented_at' => $afterState['results_presented_at'] ?? null,
                ],
                $username
            );

            $recordedData = $this->buildRecordedResultsData($resultsData, $closestToPinIdentifier);
            $this->render('scores/results-recorded', [
                'title' => 'Results Recorded - TW4 Golf Management',
                'round' => $active,
                'recordedData' => $recordedData,
                'success' => 'Results stored for this live round.',
            ]);
            return;
        } catch (\RuntimeException $e) {
            $_SESSION['errors'] = [$e->getMessage()];
            $_SESSION['old'] = ['closest_to_pin_identifier' => (string) ($this->getPostData()['closest_to_pin_identifier'] ?? '')];
            $this->redirect('/scores/present-results');
            return;
        }
    }

    private function buildRecordedResultsData(array $resultsData, string $closestToPinIdentifier): array
    {
        $leaderboard = $resultsData['leaderboard'] ?? [];
        $podium = array_slice($leaderboard, 0, 3);

        $ballWinners = [];
        foreach ($leaderboard as $entry) {
            $twos = (int) ($entry['twos_count'] ?? 0);
            if ($twos > 0) {
                $ballWinners[] = [
                    'type' => 'twos',
                    'who' => (string) ($entry['display_name'] ?? $entry['player_identifier'] ?? ''),
                    'count' => $twos,
                ];
            }
        }

        $ballWinners[] = [
            'type' => 'C_P',
            'who' => $closestToPinIdentifier,
            'count' => 1,
        ];

        $thirdPlacePoints = isset($podium[2]) ? (int) ($podium[2]['points'] ?? -1) : -1;
        $commiserations = [];
        foreach ($leaderboard as $entry) {
            $position = (int) ($entry['position'] ?? 0);
            $points = (int) ($entry['points'] ?? -2);
            if ($thirdPlacePoints >= 0 && $position > 3 && $points === $thirdPlacePoints) {
                $commiserations[] = $entry;
            }
        }

        return [
            'podium' => $podium,
            'ball_winners' => $ballWinners,
            'commiserations' => $commiserations,
        ];
    }
}