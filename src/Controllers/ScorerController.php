<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\Logger;
use App\Services\ScoreEntryService;
use App\Services\RoundWorkflowService;

/**
 * Scorer Controller - Scorer-only functions
 */
class ScorerController extends BaseController
{
    private ScoreEntryService $scoreEntryService;
    private RoundWorkflowService $roundWorkflowService;
    private Logger $logger;

    public function __construct(
        Application $app,
        Logger $logger,
        ScoreEntryService $scoreEntryService,
        RoundWorkflowService $roundWorkflowService
    )
    {
        parent::__construct($app);
        $this->scoreEntryService = $scoreEntryService;
        $this->roundWorkflowService = $roundWorkflowService;
        $this->logger = $logger;
    }

    public function menu(): void
    {
        $this->requireRole('scorer');

        $configStatus = $this->configService->getConfigStatus();
        $scoringDisabled = $configStatus !== 'ready';

        $user = $this->authService->getUser();

        $active = $this->roundWorkflowService->getActiveRoundForScorerMenu();
        $roundState = $this->roundWorkflowService->getMenuState(
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

        $viewData = [
            'user'       => $user,
            'roundState' => $roundState,
        ];

        if ($scoringDisabled) {
            $viewData['errors'] = ['Scoring is disabled until configuration status is set to ready by an admin.'];
        }

        $this->render('scorer/menu', $viewData);
    }

    public function deleteCards(): void
    {
        $this->requireRole('scorer');
        $this->requireScoringConfigReady('/scorer/menu');

        $user = $this->authService->getUser();
        $active = $this->roundWorkflowService->getActiveRoundForScorerMenu();

        if (!$active || (string) ($active['workflow_step'] ?? '') !== 'card_entry_open') {
            $this->flash->error('Cards can only be deleted while the round is open for card entry.');
            $this->redirect('/scorer/menu');
            return;
        }

        if (empty($active['card_entry_reopened'])) {
            $this->flash->error('Cards can only be deleted after an admin has reset the round to card entry.');
            $this->redirect('/scorer/menu');
            return;
        }

        $roundId = (int) ($active['round_id'] ?? 0);
        $staffId = (int) ($user['user_id'] ?? 0);

        if (!$this->scoreEntryService->assertEntryLock($roundId, $staffId)
            && !$this->roundWorkflowService->openCardEntry($roundId, $staffId)) {
            $this->flash->error('Card entry lock is held by another scorer session.');
            $this->redirect('/scorer/menu');
            return;
        }

        try {
            $cards = $this->scoreEntryService->getCardsForDeletion($roundId);
        } catch (\RuntimeException $e) {
            $this->flash->error($e->getMessage());
            $this->redirect('/scorer/menu');
            return;
        }

        if (empty($cards)) {
            $this->flash->error('No cards are currently available to delete.');
            $this->redirect('/scorer/menu');
            return;
        }

        $this->render('scores/delete-cards', [
            'round' => $active,
            'cards' => $cards,
        ]);
    }

    public function deleteCardsSelected(): void
    {
        $this->requireRole('scorer');
        $this->requireScoringConfigReady('/scorer/menu');

        if (!$this->validateCsrf()) {
            $this->flash->error('Invalid CSRF token');
            $this->redirect('/scores/delete-cards');
            return;
        }

        $user = $this->authService->getUser();
        $active = $this->roundWorkflowService->getActiveRoundForScorerMenu();

        if (!$active || (string) ($active['workflow_step'] ?? '') !== 'card_entry_open') {
            $this->flash->error('Cards can only be deleted while the round is open for card entry.');
            $this->redirect('/scorer/menu');
            return;
        }

        if (empty($active['card_entry_reopened'])) {
            $this->flash->error('Cards can only be deleted after an admin has reset the round to card entry.');
            $this->redirect('/scorer/menu');
            return;
        }

        $roundId = (int) ($active['round_id'] ?? 0);
        $staffId = (int) ($user['user_id'] ?? 0);

        if (!$this->scoreEntryService->assertEntryLock($roundId, $staffId)
            && !$this->roundWorkflowService->openCardEntry($roundId, $staffId)) {
            $this->flash->error('Card entry lock is held by another scorer session.');
            $this->redirect('/scorer/menu');
            return;
        }

        $posted = $this->getPostData();
        $selectedIds = array_values(array_filter(array_map(
            static fn(mixed $value): int => (int) $value,
            (array) ($posted['selected_cards'] ?? [])
        ), static fn(int $value): bool => $value > 0));

        if (empty($selectedIds)) {
            $this->flash->error('Please select at least one card to delete.');
            $this->redirect('/scores/delete-cards');
            return;
        }

        $confirm = strtolower(trim((string) ($posted['confirm_delete'] ?? '')));
        if ($confirm !== 'yes') {
            $this->flash->error('Delete confirmation was not accepted.');
            $this->redirect('/scores/delete-cards');
            return;
        }

        try {
            $result = $this->scoreEntryService->deleteCards($roundId, $selectedIds, (string) ($user['username'] ?? 'system'));
        } catch (\RuntimeException $e) {
            $this->flash->error($e->getMessage());
            $this->redirect('/scores/delete-cards');
            return;
        }

        $deletedCards = (array) ($result['deleted_cards'] ?? []);
        $deletedPlayers = array_map(
            static fn(array $player): string => (string) ($player['display_player'] ?? 'unknown'),
            (array) ($result['deleted_players'] ?? [])
        );

        $this->logger->log(
            Logger::LEVEL_WARNING,
            Logger::EVENT_SYSTEM,
            'Scorer deleted live cards',
            [
                'round_id' => $roundId,
                'deleted_card_ids' => array_map(static fn(array $card): int => (int) ($card['card_id'] ?? 0), $deletedCards),
                'deleted_players' => $deletedPlayers,
                'restored_handicaps' => array_map(
                    static fn(array $player): array => [
                        'player_identifier' => (string) ($player['player_identifier'] ?? ''),
                        'handicap_applied' => (int) ($player['handicap_applied'] ?? 0),
                    ],
                    (array) ($result['deleted_players'] ?? [])
                ),
                'remaining_card_count' => (int) ($result['remaining_card_count'] ?? 0),
            ],
            (string) ($user['username'] ?? 'system')
        );

        $remainingCardCount = (int) ($result['remaining_card_count'] ?? 0);

        $this->flash->success(sprintf(
            'Deleted %d card(s) successfully.',
            count($deletedCards)
        ));

        if ($remainingCardCount > 0) {
            $this->redirect('/scores/delete-cards');
            return;
        }

        $this->redirect('/scorer/menu');
    }
}
