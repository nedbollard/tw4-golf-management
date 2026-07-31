<?php

namespace App\Services;

use App\Core\Database;

class RoundWorkflowService
{
    private Database $db;
    private RoundLockService $lockService;
    private RoundStateService $stateService;
    private HandicapUpdateService $handicapUpdateService;
    private RoundHistoryService $roundHistoryService;
    private BestFiveService $bestFiveService;
    private EclecticService $eclecticService;
    private FloatingTeamHaggleService $floatingTeamHaggleService;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->lockService = new RoundLockService($db);
        $this->stateService = new RoundStateService($db);
        $this->handicapUpdateService = new HandicapUpdateService($db);
        $this->roundHistoryService = new RoundHistoryService($db);
        $this->bestFiveService = new BestFiveService($db);
        $this->eclecticService = new EclecticService($db);
        $this->floatingTeamHaggleService = new FloatingTeamHaggleService($db);
    }

    public function getActiveRoundForScorerMenu(): ?array
    {
        return $this->getPermanentRound();
    }

    public function getPermanentRound(): ?array
    {
        return $this->stateService->getPermanentRound();
    }

    /**
     * @return array{
     *     round: array<string, mixed>|null,
     *     courses: list<array<string, mixed>>,
     *     current_season_year: string,
     *     default_round_date: string,
     *     default_round_number: int,
     *     default_course_played_id: int|null,
     *     club_number: int
     * }
     * @throws \RuntimeException If the configured season year is missing or invalid.
     */
    public function getStartRoundFormData(): array
    {
        return $this->stateService->getStartRoundFormData();
    }

    /**
     * @param array{round_number?: int|string, round_date?: string, course_played_id?: int|string} $payload
     * @return array<string, mixed> The updated round row, or an empty array if it cannot be reloaded.
     * @throws \RuntimeException If the round cannot be started or locked.
     * @throws \Throwable If the transaction fails.
     */
    public function startRound(array $payload, int $staffId): array
    {
        $existing = $this->getPermanentRound();
        if (!$existing) {
            throw new \RuntimeException('Unable to initialise live round row.');
        }

        if (!$this->isBetweenRoundsStep((string) ($existing['workflow_step'] ?? 'between_rounds'))) {
            throw new \RuntimeException('Round can only be started when workflow_step is between_rounds.');
        }

        $formData = $this->getStartRoundFormData();
        $roundId = (int) $existing['round_id'];
        $seasonYear = (string) ($formData['current_season_year'] ?? '');
        $roundNumber = isset($payload['round_number'])
            ? max(1, (int) $payload['round_number'])
            : (int) $formData['default_round_number'];
        $roundDate = $payload['round_date'] ?? $formData['default_round_date'];
        $coursePlayedId = isset($payload['course_played_id']) && $payload['course_played_id'] !== ''
            ? (int) $payload['course_played_id']
            : $formData['default_course_played_id'];

        if (!$this->lockService->acquireLock($roundId, $staffId)) {
            throw new \RuntimeException('Unable to acquire lock for round start.');
        }

        if (!$this->isRoundSeasonNumberAvailable($seasonYear, $roundNumber, $roundId)) {
            throw new \RuntimeException(sprintf(
                'Round %d already exists for season %s.',
                $roundNumber,
                $seasonYear
            ));
        }

        $this->bestFiveService->ensureBestFiveTables();
        $this->eclecticService->ensureEclecticTables();

        $this->db->beginTransaction();

        try {
            $this->bestFiveService->stageForRoundStart($seasonYear, $_SESSION['username'] ?? 'system');
            $this->eclecticService->stageForRoundStart($seasonYear, $_SESSION['username'] ?? 'system');
            $this->db->query('DELETE FROM TW4_live.card_by_hole');
            $this->db->query('DELETE FROM TW4_live.card');
            $this->db->query('DELETE FROM TW4_live.results');

            $this->db->query(
                "UPDATE TW4_live.round
                 SET season_year = ?,
                     number_round = ?,
                     round_date = ?,
                     course_played_id = ?,
                     workflow_step = 'card_entry_open',
                     card_count = 0,
                     card_entry_reopened = 0,
                     results_presented_at = NULL,
                     finished_at = NULL,
                     updated_by = ?
                 WHERE row_id = ?",
                [
                    $seasonYear,
                    $roundNumber,
                    $roundDate,
                    $coursePlayedId,
                    $_SESSION['username'] ?? 'system',
                    $roundId,
                ]
            );

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        return $this->db->fetchOne(
            'SELECT row_id AS round_id, season_year, number_round AS round_number, round_date, course_played_id, workflow_step, card_count
             FROM TW4_live.round WHERE row_id = ?',
            [$roundId]
        ) ?? [];
    }

    public function openCardEntry(int $roundId, int $staffId): bool
    {
        if (!$this->lockService->acquireLock($roundId, $staffId)) {
            return false;
        }

        $stmt = $this->db->query(
            "UPDATE TW4_live.round
             SET workflow_step = 'card_entry_open'
             WHERE row_id = ?",
            [$roundId]
        );

        return $stmt->rowCount() >= 0;
    }

    /**
     * @return array{
     *     round_id: int,
     *     from_step: string,
     *     to_step: string,
     *     results_rows_cleared: int,
     *     card_count: int
     * }
     * @throws \RuntimeException If the live round is missing or cannot be reset from its current state.
     * @throws \Throwable If the transaction fails.
     */
    public function adminResetResultsToCardEntry(string $updatedBy): array
    {
        $round = $this->getPermanentRound();
        if (!$round) {
            throw new \RuntimeException('Unable to locate live round row.');
        }

        $roundId = (int) ($round['round_id'] ?? 0);
        if ($roundId < 1) {
            throw new \RuntimeException('Invalid live round row.');
        }

        $currentStep = (string) ($round['workflow_step'] ?? 'between_rounds');
        if (!in_array($currentStep, ['between_rounds', 'not_started', 'results_presented'], true)) {
            throw new \RuntimeException('Reset is only allowed when workflow_step is between_rounds or results_presented.');
        }

        $resultsCountRow = $this->db->fetchOne('SELECT COUNT(*) AS total FROM TW4_live.results');
        $resultsRowsCleared = (int) ($resultsCountRow['total'] ?? 0);

        $cardCountRow = $this->db->fetchOne(
            'SELECT COUNT(*) AS total
             FROM TW4_live.card'
        );
        $cardCount = (int) ($cardCountRow['total'] ?? 0);

        $seasonYear = trim((string) ($round['season_year'] ?? ''));
        $roundNumber = (int) ($round['round_number'] ?? 0);

        // Recover round_date and course_played_id from history if they were cleared by finishRound
        $historyRoundMeta = null;
        if ($seasonYear !== '' && $roundNumber > 0) {
            $historyRoundMeta = $this->db->fetchOne(
                'SELECT round_date, course_played_id
                 FROM TW4_history.round
                 WHERE season_year = ? AND number_round = ?
                 LIMIT 1',
                [$seasonYear, $roundNumber]
            );
        }

        $this->db->beginTransaction();
        try {
            $this->db->query('DELETE FROM TW4_live.results');
            $this->db->query(
                "UPDATE TW4_live.round
                 SET workflow_step = 'card_entry_open',
                     card_count = ?,
                     card_entry_reopened = 1,
                     round_date = COALESCE(round_date, ?),
                     course_played_id = COALESCE(course_played_id, ?),
                     results_presented_at = NULL,
                     finished_at = NULL,
                     locked_by_staff_id = NULL,
                     lock_acquired_at = NULL,
                     lock_expires_at = NULL,
                     lock_released_at = NOW(),
                     lock_release_reason = 'admin_forced',
                     updated_by = ?
                 WHERE row_id = ?",
                [
                    $cardCount,
                    $historyRoundMeta['round_date'] ?? null,
                    $historyRoundMeta['course_played_id'] ?? null,
                    $updatedBy,
                    $roundId,
                ]
            );

            if ($seasonYear !== '' && $roundNumber > 0) {
                // Restore roster handicaps to their pre-round values using handicap_applied from cards.
                // This works even if audit entries were previously deleted, because cards retain handicap_applied.
                // This ensures re-finishing the round will correctly record the handicap changes.
                // (Card table only contains cards for the current live round, so no round filter needed)
                $this->db->query(
                    'UPDATE TW4_base.roster r
                     SET r.handicap = (
                         SELECT c.handicap_applied
                         FROM TW4_live.card c
                         WHERE c.row_id_player = r.row_id
                         LIMIT 1
                     )
                     WHERE r.row_id IN (
                         SELECT DISTINCT row_id_player
                         FROM TW4_live.card
                     )'
                );

                // Now delete the audit entries for this round
                $this->db->query(
                    'DELETE FROM TW4_base.handicap_audit
                     WHERE season_year = ? AND number_round = ? AND handicap_source = ?',
                    [$seasonYear, $roundNumber, 'card_scoring']
                );
                $this->db->query(
                    'DELETE FROM TW4_history.best_five_scores
                     WHERE season_year = ? AND number_round_movement = ?',
                    [$seasonYear, $roundNumber]
                );
                $this->db->query(
                    'DELETE FROM TW4_history.eclectic_scores
                     WHERE season_year = ? AND number_round_movement = ?',
                    [$seasonYear, $roundNumber]
                );
            }
            $this->db->query('DELETE FROM TW4_live.best_five_scores');
            $this->db->query('DELETE FROM TW4_live.eclectic_scores');
            $this->floatingTeamHaggleService->clearLiveTables();

            $this->db->commit();
        } catch (\Throwable $e) {
            try {
                $this->db->rollback();
            } catch (\Throwable $rollbackException) {
                // Swallow rollback errors (e.g. PDO "There is no active transaction").
                // The original exception is what matters and will be re-thrown below.
            }
            throw $e;
        }

        return [
            'round_id' => $roundId,
            'from_step' => $currentStep,
            'to_step' => 'card_entry_open',
            'results_rows_cleared' => $resultsRowsCleared,
            'card_count' => $cardCount,
        ];
    }

    public function presentResults(int $roundId, int $staffId): bool
    {
        if (!$this->lockService->assertLockHeld($roundId, $staffId)) {
            return false;
        }

        if (!$this->validateCanPresentResults($roundId)) {
            return false;
        }

        $stmt = $this->db->query(
            "UPDATE TW4_live.round
             SET workflow_step = 'results_presented',
                 results_presented_at = NOW(),
                 updated_by = ?
             WHERE row_id = ?",
            [$_SESSION['username'] ?? 'system', $roundId]
        );

        return $stmt->rowCount() >= 0;
    }

    /**
     * @throws \RuntimeException If the round identity is incomplete.
     * @throws \Throwable If the transaction fails.
     */
    public function finishRound(int $roundId, int $staffId): bool
    {
        if (!$this->lockService->assertLockHeld($roundId, $staffId)) {
            return false;
        }

        if (!$this->validateCanFinishRound($roundId)) {
            return false;
        }

        $round = $this->db->fetchOne(
            'SELECT row_id, season_year, number_round
             FROM TW4_live.round
             WHERE row_id = ?',
            [$roundId]
        );

        $seasonYear = trim((string) ($round['season_year'] ?? ''));
        $numberRound = (int) ($round['number_round'] ?? 0);

        if ($seasonYear === '' || $numberRound < 1) {
            throw new \RuntimeException('Round identity is incomplete. season_year and number_round are required before finish.');
        }

        $updatedBy = (string) ($_SESSION['username'] ?? 'system');

        $this->bestFiveService->ensureBestFiveTables();
        $this->eclecticService->ensureEclecticTables();

        $this->db->beginTransaction();

        try {
            $eclecticContext = $this->eclecticService->buildRoundContext($roundId);
            $this->handicapUpdateService->applyForFinishedRound($updatedBy, $seasonYear, $numberRound);
            $this->bestFiveService->refreshForFinish($seasonYear, $numberRound, $updatedBy);
            $this->floatingTeamHaggleService->refreshForFinish($seasonYear, $updatedBy);
            // Always refresh per-course eclectic tracks; include_eclectic controls combined reporting.
            $this->eclecticService->refreshForFinish($roundId, $seasonYear, $numberRound, $updatedBy, $eclecticContext);
            $this->roundHistoryService->replaceHistorySnapshot($roundId, $seasonYear, $numberRound, $updatedBy);
            $this->eclecticService->persistRoundContext($seasonYear, $numberRound, $eclecticContext, $updatedBy);

            // Export snapshots BEFORE resetting course_played_id so course names are available
            $this->exportRoundSnapshots($seasonYear, $numberRound);

            $this->db->query(
                "UPDATE TW4_base.roster
                 SET status = 'active', updated_by = ?
                 WHERE status = 'scored'",
                [$updatedBy]
            );

            $stmt = $this->db->query(
                "UPDATE TW4_live.round
                 SET workflow_step = 'between_rounds',
                     round_date = NULL,
                     course_played_id = NULL,
                     card_count = 0,
                     results_presented_at = NULL,
                     finished_at = NULL,
                     locked_by_staff_id = NULL,
                     lock_acquired_at = NULL,
                     lock_expires_at = NULL,
                     lock_release_reason = 'finished',
                     updated_by = ?
                 WHERE row_id = ?",
                [$updatedBy, $roundId]
            );

            $this->db->commit();
            return $stmt->rowCount() >= 0;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function exportRoundSnapshots(string $seasonYear, int $numberRound): void
    {
        try {
            $exportService = new SnapshotExportService($this->db);
            $exportService->exportRoundSnapshots($seasonYear, $numberRound, true);
        } catch (\Throwable $e) {
            // Export failures are non-fatal; log but don't fail the round finish
            // (Logging is handled by the caller)
        }
    }

    public function validateCanPresentResults(int $roundId): bool
    {
        return $this->stateService->validateCanPresentResults($roundId);
    }

    public function validateCanFinishRound(int $roundId): bool
    {
        return $this->stateService->validateCanFinishRound($roundId);
    }

    /**
     * @return array{
     *     active_round: array<string, mixed>|null,
     *     card_count: int,
     *     lock: array<string, mixed>|null,
     *     steps: array<int, array{label: string, status: string, enabled: bool, route: string}>
     * }
     */
    public function getMenuState(?int $roundId, int $staffId): array
    {
        return $this->stateService->getMenuState($roundId, $staffId);
    }

    private function isBetweenRoundsStep(string $step): bool
    {
        return in_array($step, ['between_rounds', 'not_started'], true);
    }

    public function getCardCount(int $roundId): int
    {
        return $this->stateService->getCardCount($roundId);
    }

    private function isRoundSeasonNumberAvailable(string $seasonYear, int $roundNumber, int $roundId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT row_id
             FROM TW4_live.round
             WHERE season_year = ?
               AND number_round = ?
               AND row_id <> ?
             LIMIT 1',
            [$seasonYear, $roundNumber, $roundId]
        );

        return $row === null;
    }
}
