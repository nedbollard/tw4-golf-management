<?php

namespace App\Services;

use App\Core\Database;

class ScoreEntryService
{
    private RoundLockService $lockService;
    private CardScoringCalculator $calculator;
    private CardEntryQueryService $queryService;
    private CardPersistenceService $persistenceService;
    private CardDeletionService $deletionService;
    private CardChartQueryService $chartQueryService;

    public function __construct(Database $db)
    {
        $this->lockService = new RoundLockService($db);
        $this->calculator = new CardScoringCalculator();
        $this->queryService = new CardEntryQueryService($db);
        $this->persistenceService = new CardPersistenceService($db);
        $this->deletionService = new CardDeletionService($db);
        $this->chartQueryService = new CardChartQueryService($db);
    }

    public function getSelectablePlayers(int $roundId): array
    {
        return $this->queryService->getSelectablePlayers($roundId);
    }

    public function assertEntryLock(int $roundId, int $staffId): bool
    {
        return $this->lockService->assertLockHeld($roundId, $staffId);
    }

    public function buildEntryData(int $roundId, int $playerId): ?array
    {
        return $this->queryService->buildEntryData($roundId, $playerId);
    }

    /**
     * @param array<string, mixed> $entryData Entry data produced by buildEntryData().
     * @param array<int|string, string> $postedScores Scores keyed by one-based hole number.
     * @return array<string, mixed> Entry data enriched with validation errors and score totals.
     */
    public function calculateCard(array $entryData, array $postedScores): array
    {
        return $this->calculator->calculate($entryData, $postedScores);
    }

    public function saveCard(int $roundId, int $playerId, array $entryData, string $username): void
    {
        $this->persistenceService->save($roundId, $playerId, $entryData, $username);
    }

    public function getCardsForDeletion(int $roundId): array
    {
        return $this->deletionService->getCards($roundId);
    }

    public function deleteCards(int $roundId, array $cardIds, string $updatedBy): array
    {
        return $this->deletionService->delete($roundId, $cardIds, $updatedBy);
    }

    public function getCardChartData(int $playerId): ?array
    {
        return $this->chartQueryService->getChartData($playerId);
    }
}
