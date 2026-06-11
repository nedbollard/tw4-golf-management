<?php

namespace App\Services;

use App\Core\Database;

class RoundWorkflowService
{
    private Database $db;
    private RoundLockService $lockService;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->lockService = new RoundLockService($db);
    }

    public function getActiveRoundForScorerMenu(): ?array
    {
        return $this->getPermanentRound();
    }

    public function getPermanentRound(): ?array
    {
        $round = $this->db->fetchOne(
            "SELECT row_id AS round_id,
                    season_year,
                    number_round AS round_number,
                    round_date,
                    course_played_id,
                    workflow_step,
                    card_count,
                    locked_by_staff_id,
                    lock_acquired_at,
                    lock_expires_at,
                    results_presented_at,
                    finished_at
             FROM TW4_live.round
             ORDER BY row_id ASC
             LIMIT 1"
        );

        if ($round) {
            return $round;
        }

        $roundId = $this->db->insert('TW4_live.round', [
            'number_round' => 0,
            'workflow_step' => 'not_started',
            'card_count' => 0,
            'updated_by' => 'system',
        ]);

        return $this->db->fetchOne(
            "SELECT row_id AS round_id,
                    season_year,
                    number_round AS round_number,
                    round_date,
                    course_played_id,
                    workflow_step,
                    card_count,
                    locked_by_staff_id,
                    lock_acquired_at,
                    lock_expires_at,
                    results_presented_at,
                    finished_at
             FROM TW4_live.round
             WHERE row_id = ?",
            [$roundId]
        );
    }

    public function getStartRoundFormData(): array
    {
        $round = $this->getPermanentRound();
        $today = date('Y-m-d');
        $seasonYear = $this->getConfiguredSeasonYear();
        $clubNumber = $this->getConfiguredClubNumber();
        $courses = $this->db->fetchAll(
            'SELECT row_id, name_course, name_club
             FROM TW4_base.course_played
             ORDER BY name_club, name_course'
        );

        return [
            'round' => $round,
            'courses' => $courses,
            'current_season_year' => $seasonYear,
            'default_round_date' => $today,
            'default_round_number' => $this->determineDefaultRoundNumber($round, $seasonYear),
            'default_course_played_id' => $this->determineDefaultCoursePlayedId($courses, $today, $clubNumber),
            'club_number' => $clubNumber,
        ];
    }

    public function startRound(array $payload, int $staffId): array
    {
        $existing = $this->getPermanentRound();
        if (!$existing) {
            throw new \RuntimeException('Unable to initialise live round row.');
        }

        if (($existing['workflow_step'] ?? 'not_started') !== 'not_started') {
            throw new \RuntimeException('Round can only be started when workflow_step is not_started.');
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

        $this->ensureBestFiveTables();

        $this->db->beginTransaction();

        try {
            $this->stageBestFiveForRoundStart($seasonYear, $_SESSION['username'] ?? 'system');
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

        $currentStep = (string) ($round['workflow_step'] ?? 'not_started');
        if ($currentStep !== 'results_presented') {
            throw new \RuntimeException('Reset is only allowed when workflow_step is results_presented.');
        }

        $resultsCountRow = $this->db->fetchOne('SELECT COUNT(*) AS total FROM TW4_live.results');
        $resultsRowsCleared = (int) ($resultsCountRow['total'] ?? 0);

        $cardCountRow = $this->db->fetchOne(
            'SELECT COUNT(*) AS total
             FROM TW4_live.card
             WHERE row_id_round = ?',
            [$roundId]
        );
        $cardCount = (int) ($cardCountRow['total'] ?? 0);

        $this->db->beginTransaction();
        try {
            $this->db->query('DELETE FROM TW4_live.results');
            $this->db->query(
                "UPDATE TW4_live.round
                 SET workflow_step = 'card_entry_open',
                     card_count = ?,
                     results_presented_at = NULL,
                     finished_at = NULL,
                     locked_by_staff_id = NULL,
                     lock_acquired_at = NULL,
                     lock_expires_at = NULL,
                     lock_released_at = NOW(),
                     lock_release_reason = 'admin_forced',
                     updated_by = ?
                 WHERE row_id = ?",
                [$cardCount, $updatedBy, $roundId]
            );

            $seasonYear = trim((string) ($round['season_year'] ?? ''));
            $roundNumber = (int) ($round['round_number'] ?? 0);
            if ($seasonYear !== '' && $roundNumber > 0) {
                $this->db->query(
                    'DELETE FROM TW4_history.best_five
                     WHERE season_year = ? AND number_round_snapshot = ?',
                    [$seasonYear, $roundNumber]
                );
            }
            $this->db->query('DELETE FROM TW4_live.best_five');

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
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

        $this->ensureBestFiveTables();

        $this->db->beginTransaction();

        try {
            $this->applyHandicapUpdatesBeforeHistory($updatedBy, $seasonYear, $numberRound);
            $this->refreshBestFiveForFinish($seasonYear, $numberRound, $updatedBy);
            $this->replaceHistorySnapshot($roundId, $seasonYear, $numberRound, $updatedBy);

            $this->db->query(
                "UPDATE TW4_base.roster
                 SET status = 'active', updated_by = ?
                 WHERE status = 'scored'",
                [$updatedBy]
            );

            $stmt = $this->db->query(
                "UPDATE TW4_live.round
                 SET workflow_step = 'not_started',
                     round_date = NULL,
                     course_played_id = NULL,
                     card_count = 0,
                     results_presented_at = NULL,
                     finished_at = NULL,
                     locked_by_staff_id = NULL,
                     lock_acquired_at = NULL,
                     lock_expires_at = NULL,
                     lock_released_by_staff_id = NULL,
                     lock_released_at = NOW(),
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

    private function applyHandicapUpdatesBeforeHistory(string $updatedBy, string $seasonYear, int $numberRound): void
    {
        $method = $this->getConfiguredHandicapMethod();

        if ($method === 'none') {
            $this->db->query(
                'UPDATE TW4_live.card
                 SET handicap_updated = handicap_applied,
                     updated_by = ?
                 WHERE handicap_updated IS NULL OR handicap_updated <> handicap_applied',
                [$updatedBy]
            );

            $this->syncRosterHandicapsFromLiveCards($updatedBy, $seasonYear, $numberRound);
            return;
        }

        $rows = $this->db->fetchAll(
            'SELECT c.row_id AS card_row_id,
                    c.handicap_applied,
                    COALESCE(SUM(cbh.points), 0) AS pts_scored,
                    COALESCE(SUM(CASE WHEN cbh.points = 0 THEN 1 ELSE cbh.points END), 0) AS pts_adjusted
             FROM TW4_live.card c
             LEFT JOIN TW4_live.card_by_hole cbh ON cbh.row_id_card = c.row_id
             GROUP BY c.row_id, c.handicap_applied'
        );

        foreach ($rows as $row) {
            $cardId = (int) ($row['card_row_id'] ?? 0);
            $handicapApplied = (int) ($row['handicap_applied'] ?? 0);
            $ptsAdjusted = (int) ($row['pts_adjusted'] ?? 0);

            $change = $method === 'modern'
                ? $this->calculateModernHandicapChange($ptsAdjusted)
                : $this->calculateLegacyHandicapChange($ptsAdjusted);

            $handicapUpdated = $this->clampHandicap($handicapApplied + $change);

            $this->db->query(
                'UPDATE TW4_live.card
                 SET handicap_updated = ?,
                     updated_by = ?
                 WHERE row_id = ?',
                [$handicapUpdated, $updatedBy, $cardId]
            );
        }

        $this->syncRosterHandicapsFromLiveCards($updatedBy, $seasonYear, $numberRound);
    }

    private function getConfiguredHandicapMethod(): string
    {
        $row = $this->db->fetchOne(
            'SELECT LOWER(TRIM(config_value_string)) AS method
             FROM TW4_base.config_application
             WHERE config_name = ?
             LIMIT 1',
            ['handicap_method']
        );

        $value = (string) ($row['method'] ?? 'legacy');

        if (in_array($value, ['none', 'n'], true)) {
            return 'none';
        }

        if (in_array($value, ['modern', 'm'], true)) {
            return 'modern';
        }

        return 'legacy';
    }

    private function calculateModernHandicapChange(int $ptsAdjusted): int
    {
        if ($ptsAdjusted < 16) {
            return 16 - $ptsAdjusted;
        }

        if ($ptsAdjusted > 20) {
            return 20 - $ptsAdjusted;
        }

        return 0;
    }

    private function calculateLegacyHandicapChange(int $ptsAdjusted): int
    {
        return match (true) {
            $ptsAdjusted >= 9 && $ptsAdjusted <= 12 => 2,
            $ptsAdjusted >= 13 && $ptsAdjusted <= 16 => 1,
            $ptsAdjusted >= 17 && $ptsAdjusted <= 21 => 0,
            $ptsAdjusted >= 22 && $ptsAdjusted <= 23 => -1,
            $ptsAdjusted >= 24 && $ptsAdjusted <= 25 => -2,
            $ptsAdjusted >= 26 && $ptsAdjusted <= 27 => -3,
            default => -4,
        };
    }

    private function clampHandicap(int $value): int
    {
        return max(0, min(54, $value));
    }

    private function syncRosterHandicapsFromLiveCards(string $updatedBy, string $seasonYear, int $numberRound): void
    {
        $changedRows = $this->db->fetchAll(
            'SELECT r.row_id AS row_id_player,
                    r.handicap AS handicap_previous,
                    c.handicap_updated AS handicap_new
             FROM TW4_base.roster r
             INNER JOIN TW4_live.card c ON c.row_id_player = r.row_id
             WHERE c.handicap_updated IS NOT NULL
               AND (r.handicap IS NULL OR r.handicap <> c.handicap_updated)'
        );

        $this->db->query(
            'UPDATE TW4_base.roster r
             INNER JOIN TW4_live.card c ON c.row_id_player = r.row_id
             SET r.handicap = c.handicap_updated,
                 r.updated_by = ?
             WHERE c.handicap_updated IS NOT NULL
               AND (r.handicap IS NULL OR r.handicap <> c.handicap_updated)',
            [$updatedBy]
        );

        foreach ($changedRows as $row) {
            $this->db->insert('TW4_base.handicap_audit', [
                'row_id_player' => (int) ($row['row_id_player'] ?? 0),
                'handicap_previous' => (int) ($row['handicap_previous'] ?? 0),
                'handicap_new' => (int) ($row['handicap_new'] ?? 0),
                'handicap_source' => 'card_scoring',
                'season_year' => $seasonYear,
                'number_round' => $numberRound,
                'reason' => 'finish_round_card_scoring',
                'changed_by' => $updatedBy,
                'updated_by' => $updatedBy,
            ]);
        }
    }

    private function replaceHistorySnapshot(int $roundId, string $seasonYear, int $numberRound, string $updatedBy): void
    {
        // Replace strategy: remove existing snapshot rows for this business key,
        // then insert fresh rows from current TW4_live tables.
        $this->db->query(
            'DELETE FROM TW4_history.card_by_hole
             WHERE season_year = ? AND number_round = ?',
            [$seasonYear, $numberRound]
        );

        $this->db->query(
            'DELETE FROM TW4_history.results
             WHERE season_year = ? AND number_round = ?',
            [$seasonYear, $numberRound]
        );

        $this->db->query(
            'DELETE FROM TW4_history.card
             WHERE season_year = ? AND number_round = ?',
            [$seasonYear, $numberRound]
        );

        $this->db->query(
            'DELETE FROM TW4_history.round
             WHERE season_year = ? AND number_round = ?',
            [$seasonYear, $numberRound]
        );

        $this->db->query(
            'DELETE FROM TW4_history.best_five
             WHERE season_year = ? AND number_round_snapshot = ?',
            [$seasonYear, $numberRound]
        );

        $this->db->query(
            'INSERT INTO TW4_history.round
                (season_year, number_round, round_date, course_played_id, card_count,
                 results_presented_at, finished_at, updated_by, updated_ts,
                 hist_updated_by, hist_updated_ts)
             SELECT season_year, number_round, round_date, course_played_id, card_count,
                    results_presented_at, finished_at, updated_by, updated_ts,
                    ?, NOW()
             FROM TW4_live.round
             WHERE row_id = ?',
            [$updatedBy, $roundId]
        );

        $this->db->query(
            'INSERT INTO TW4_history.card
                (season_year, number_round, row_id_round, row_id_player, handicap_applied, score, points,
                 handicap_updated, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
             SELECT ?, ?, hr.row_id, lc.row_id_player, lc.handicap_applied, lc.score, lc.points,
                    lc.handicap_updated, lc.updated_by, lc.updated_ts, ?, NOW()
             FROM TW4_live.card lc
             INNER JOIN TW4_history.round hr
                ON hr.season_year = ?
               AND hr.number_round = ?',
                [$seasonYear, $numberRound, $updatedBy, $seasonYear, $numberRound]
        );

        $this->db->query(
            'INSERT INTO TW4_history.card_by_hole
                (season_year, number_round, row_id_card, hole, score, shots, points,
                 updated_by, updated_ts, hist_updated_by, hist_updated_ts)
             SELECT ?, ?, hc.row_id, lcbh.hole, lcbh.score, lcbh.shots, lcbh.points,
                    lcbh.updated_by, lcbh.updated_ts, ?, NOW()
             FROM TW4_live.card_by_hole lcbh
             INNER JOIN TW4_live.card lc
                ON lc.row_id = lcbh.row_id_card
             INNER JOIN TW4_history.card hc
                ON hc.season_year = ?
               AND hc.number_round = ?
               AND hc.row_id_player = lc.row_id_player',
                [$seasonYear, $numberRound, $updatedBy, $seasonYear, $numberRound]
        );

        $this->db->query(
            'INSERT INTO TW4_history.results
                (season_year, number_round, type_result, number_result, player_identifier, value_result,
                 updated_by, updated_ts, hist_updated_by, hist_updated_ts)
             SELECT ?, ?, type_result, number_result, player_identifier, value_result,
                    updated_by, updated_ts, ?, NOW()
             FROM TW4_live.results',
            [$seasonYear, $numberRound, $updatedBy]
        );

        $this->db->query(
            'INSERT INTO TW4_history.best_five
                (season_year, number_round_snapshot, row_id_player, number_round_movement,
                 points_total, points_best_1, points_best_2, points_best_3, points_best_4, points_best_5,
                 round_best_1, round_best_2, round_best_3, round_best_4, round_best_5,
                 points_movement, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
             SELECT season_year, ?, row_id_player, number_round_movement,
                    points_total, points_best_1, points_best_2, points_best_3, points_best_4, points_best_5,
                    round_best_1, round_best_2, round_best_3, round_best_4, round_best_5,
                    points_movement, updated_by, updated_ts, ?, NOW()
             FROM TW4_live.best_five
             WHERE season_year = ?',
            [$numberRound, $updatedBy, $seasonYear]
        );
    }

    private function stageBestFiveForRoundStart(string $seasonYear, string $updatedBy): void
    {
        $this->db->query('DELETE FROM TW4_holding.best_five');
        $this->db->query(
            'INSERT INTO TW4_holding.best_five
                (season_year, row_id_player, number_round_movement,
                 points_total, points_best_1, points_best_2, points_best_3, points_best_4, points_best_5,
                 round_best_1, round_best_2, round_best_3, round_best_4, round_best_5,
                 points_movement, updated_by)
             SELECT season_year, row_id_player, number_round_movement,
                    points_total, points_best_1, points_best_2, points_best_3, points_best_4, points_best_5,
                    round_best_1, round_best_2, round_best_3, round_best_4, round_best_5,
                    points_movement, ?
             FROM TW4_live.best_five
             WHERE season_year = ?',
            [$updatedBy, $seasonYear]
        );
        $this->db->query('DELETE FROM TW4_live.best_five');
    }

    private function refreshBestFiveForFinish(string $seasonYear, int $numberRound, string $updatedBy): void
    {
        $holdingRows = $this->db->fetchAll(
            'SELECT season_year, row_id_player, number_round_movement,
                    points_total, points_best_1, points_best_2, points_best_3, points_best_4, points_best_5,
                    round_best_1, round_best_2, round_best_3, round_best_4, round_best_5,
                    points_movement
             FROM TW4_holding.best_five
             WHERE season_year = ?',
            [$seasonYear]
        );

        $cardRows = $this->db->fetchAll(
            'SELECT row_id_player, points
             FROM TW4_live.card'
        );

        $holdingByPlayer = [];
        foreach ($holdingRows as $row) {
            $playerId = (int) ($row['row_id_player'] ?? 0);
            if ($playerId > 0) {
                $holdingByPlayer[$playerId] = $row;
            }
        }

        $cardPointsByPlayer = [];
        foreach ($cardRows as $row) {
            $playerId = (int) ($row['row_id_player'] ?? 0);
            if ($playerId > 0) {
                $cardPointsByPlayer[$playerId] = (int) ($row['points'] ?? 0);
            }
        }

        $playerIds = array_values(array_unique(array_merge(array_keys($holdingByPlayer), array_keys($cardPointsByPlayer))));
        sort($playerIds);

        $this->db->query('DELETE FROM TW4_live.best_five');

        foreach ($playerIds as $playerId) {
            $holding = $holdingByPlayer[$playerId] ?? null;
            $hasCard = array_key_exists($playerId, $cardPointsByPlayer);

            if (!$hasCard && $holding === null) {
                continue;
            }

            $oldPoints = [0, 0, 0, 0, 0];
            $oldRounds = [0, 0, 0, 0, 0];
            $oldTotal = 0;
            $oldMovementRound = 0;
            $oldMovementPoints = 0;

            if ($holding !== null) {
                $oldPoints = [
                    (int) ($holding['points_best_1'] ?? 0),
                    (int) ($holding['points_best_2'] ?? 0),
                    (int) ($holding['points_best_3'] ?? 0),
                    (int) ($holding['points_best_4'] ?? 0),
                    (int) ($holding['points_best_5'] ?? 0),
                ];
                $oldRounds = [
                    (int) ($holding['round_best_1'] ?? 0),
                    (int) ($holding['round_best_2'] ?? 0),
                    (int) ($holding['round_best_3'] ?? 0),
                    (int) ($holding['round_best_4'] ?? 0),
                    (int) ($holding['round_best_5'] ?? 0),
                ];
                $oldTotal = (int) ($holding['points_total'] ?? 0);
                $oldMovementRound = (int) ($holding['number_round_movement'] ?? 0);
                $oldMovementPoints = (int) ($holding['points_movement'] ?? 0);
            }

            $newPoints = $oldPoints;
            $newRounds = $oldRounds;

            if ($hasCard) {
                [$newPoints, $newRounds] = $this->mergeBestFiveScores(
                    $oldPoints,
                    $oldRounds,
                    (int) $cardPointsByPlayer[$playerId],
                    $numberRound
                );
            }

            $newTotal = array_sum($newPoints);
            $pointsMovement = $newTotal - $oldTotal;

            $movementRound = $oldMovementRound;
            $movementPoints = $oldMovementPoints;
            if ($holding === null || $pointsMovement !== 0) {
                $movementRound = $numberRound;
                $movementPoints = $pointsMovement;
            }

            $this->db->insert('TW4_live.best_five', [
                'season_year' => $seasonYear,
                'row_id_player' => $playerId,
                'number_round_movement' => $movementRound,
                'points_total' => $newTotal,
                'points_best_1' => $newPoints[0],
                'points_best_2' => $newPoints[1],
                'points_best_3' => $newPoints[2],
                'points_best_4' => $newPoints[3],
                'points_best_5' => $newPoints[4],
                'round_best_1' => $newRounds[0],
                'round_best_2' => $newRounds[1],
                'round_best_3' => $newRounds[2],
                'round_best_4' => $newRounds[3],
                'round_best_5' => $newRounds[4],
                'points_movement' => $movementPoints,
                'updated_by' => $updatedBy,
            ]);
        }
    }

    /**
     * @return array{0: array<int,int>, 1: array<int,int>}
     */
    private function mergeBestFiveScores(array $points, array $rounds, int $newPoints, int $newRound): array
    {
        $entries = [];
        for ($i = 0; $i < 5; $i++) {
            $entries[] = [
                'points' => (int) ($points[$i] ?? 0),
                'round' => (int) ($rounds[$i] ?? 0),
            ];
        }
        $entries[] = ['points' => $newPoints, 'round' => $newRound];

        usort(
            $entries,
            static fn(array $a, array $b): int =>
                ($b['points'] <=> $a['points'])
                ?: ($a['round'] <=> $b['round'])
        );

        $entries = array_slice($entries, 0, 5);

        $newBestPoints = [];
        $newBestRounds = [];
        foreach ($entries as $entry) {
            $newBestPoints[] = (int) $entry['points'];
            $newBestRounds[] = (int) $entry['round'];
        }

        while (count($newBestPoints) < 5) {
            $newBestPoints[] = 0;
            $newBestRounds[] = 0;
        }

        return [$newBestPoints, $newBestRounds];
    }

    private function ensureBestFiveTables(): void
    {
        $this->db->query(
            "CREATE DATABASE IF NOT EXISTS TW4_holding
             CHARACTER SET utf8mb4
             COLLATE utf8mb4_general_ci"
        );

        $tableDdl = "
            CREATE TABLE IF NOT EXISTS %s.best_five (
                row_id INT NOT NULL AUTO_INCREMENT,
                season_year CHAR(5) NOT NULL,
                row_id_player INT NOT NULL,
                number_round_movement INT NOT NULL DEFAULT 0,
                points_total INT NOT NULL DEFAULT 0,
                points_best_1 INT NOT NULL DEFAULT 0,
                points_best_2 INT NOT NULL DEFAULT 0,
                points_best_3 INT NOT NULL DEFAULT 0,
                points_best_4 INT NOT NULL DEFAULT 0,
                points_best_5 INT NOT NULL DEFAULT 0,
                round_best_1 INT NOT NULL DEFAULT 0,
                round_best_2 INT NOT NULL DEFAULT 0,
                round_best_3 INT NOT NULL DEFAULT 0,
                round_best_4 INT NOT NULL DEFAULT 0,
                round_best_5 INT NOT NULL DEFAULT 0,
                points_movement INT NOT NULL DEFAULT 0,
                updated_by VARCHAR(100) NOT NULL,
                updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (row_id),
                UNIQUE KEY uk_best_five_season_player (season_year, row_id_player),
                KEY idx_best_five_player (row_id_player),
                KEY idx_best_five_season (season_year)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";

        $this->db->query(sprintf($tableDdl, 'TW4_live'));
        $this->db->query(sprintf($tableDdl, 'TW4_holding'));

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS TW4_history.best_five (
                row_id INT NOT NULL AUTO_INCREMENT,
                season_year CHAR(5) NOT NULL,
                number_round_snapshot INT NOT NULL,
                row_id_player INT NOT NULL,
                number_round_movement INT NOT NULL DEFAULT 0,
                points_total INT NOT NULL DEFAULT 0,
                points_best_1 INT NOT NULL DEFAULT 0,
                points_best_2 INT NOT NULL DEFAULT 0,
                points_best_3 INT NOT NULL DEFAULT 0,
                points_best_4 INT NOT NULL DEFAULT 0,
                points_best_5 INT NOT NULL DEFAULT 0,
                round_best_1 INT NOT NULL DEFAULT 0,
                round_best_2 INT NOT NULL DEFAULT 0,
                round_best_3 INT NOT NULL DEFAULT 0,
                round_best_4 INT NOT NULL DEFAULT 0,
                round_best_5 INT NOT NULL DEFAULT 0,
                points_movement INT NOT NULL DEFAULT 0,
                updated_by VARCHAR(100) NOT NULL,
                updated_ts TIMESTAMP NULL DEFAULT NULL,
                hist_updated_by VARCHAR(100) NOT NULL,
                hist_updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (row_id),
                UNIQUE KEY uk_history_best_five_snapshot_player (season_year, number_round_snapshot, row_id_player),
                KEY idx_history_best_five_snapshot (season_year, number_round_snapshot),
                KEY idx_history_best_five_player (row_id_player)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }

    public function validateCanPresentResults(int $roundId): bool
    {
        return $this->getCardCount($roundId) >= 4;
    }

    public function validateCanFinishRound(int $roundId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT workflow_step FROM TW4_live.round WHERE row_id = ?',
            [$roundId]
        );

        return $row && ($row['workflow_step'] ?? '') === 'results_presented';
    }

    public function getMenuState(?int $roundId, int $staffId): array
    {
        if ($roundId === null) {
            return [
                'active_round' => null,
                'card_count' => 0,
                'lock' => null,
                'steps' => [
                    1 => ['label' => 'Start a New Round', 'status' => 'waiting', 'enabled' => true, 'route' => '/rounds/start'],
                    2 => ['label' => 'Enter Cards', 'status' => 'waiting', 'enabled' => false, 'route' => '/scores/enter'],
                    3 => ['label' => 'Present Results', 'status' => 'waiting', 'enabled' => false, 'route' => '/scores/present-results'],
                    4 => ['label' => 'Finish the Round', 'status' => 'waiting', 'enabled' => false, 'route' => '/rounds/finish'],
                ],
            ];
        }

        $round = $this->db->fetchOne(
            'SELECT row_id AS round_id, season_year, number_round AS round_number, round_date, course_played_id, workflow_step, card_count
             FROM TW4_live.round WHERE row_id = ?',
            [$roundId]
        );
        $step = $round['workflow_step'] ?? 'not_started';
        $cardCount = $this->getCardCount($roundId);
        $lock = $this->lockService->getLockStatus($roundId, $staffId);
        $lockBlocked = $lock && !empty($lock['blocked']);

        $steps = [
            1 => ['label' => 'Start a New Round', 'status' => 'waiting', 'enabled' => false, 'route' => '/rounds/start'],
            2 => ['label' => 'Enter Cards', 'status' => 'waiting', 'enabled' => false, 'route' => '/scores/enter'],
            3 => ['label' => 'Present Results', 'status' => 'waiting', 'enabled' => false, 'route' => '/scores/present-results'],
            4 => ['label' => 'Finish the Round', 'status' => 'waiting', 'enabled' => false, 'route' => '/rounds/finish'],
        ];

        if ($step === 'not_started') {
            $steps[1]['enabled'] = !$lockBlocked;
        } elseif ($step === 'card_entry_open') {
            $steps[1]['status'] = 'completed';
            $steps[2]['status'] = 'in_progress';
            $steps[2]['enabled'] = !$lockBlocked;
            $steps[3]['enabled'] = !$lockBlocked && $cardCount >= 4;
        } elseif ($step === 'results_presented') {
            $steps[1]['status'] = 'completed';
            $steps[2]['status'] = 'completed';
            $steps[3]['status'] = 'completed';
            $steps[4]['enabled'] = !$lockBlocked;
        } elseif ($step === 'finished') {
            $steps[1]['status'] = 'completed';
            $steps[2]['status'] = 'completed';
            $steps[3]['status'] = 'completed';
            $steps[4]['status'] = 'completed';
        }

        return [
            'active_round' => $round,
            'card_count' => $cardCount,
            'lock' => $lock,
            'steps' => $steps,
        ];
    }

    public function getCardCount(int $roundId): int
    {
        $row = $this->db->fetchOne('SELECT card_count FROM TW4_live.round WHERE row_id = ?', [$roundId]);
        return (int) ($row['card_count'] ?? 0);
    }

    private function determineDefaultCoursePlayedId(array $courses, string $date, int $clubNumber): ?int
    {
        if (empty($courses)) {
            return null;
        }

        if ($clubNumber === 294) {
            $preferredCourse = ((int) date('j', strtotime($date)) % 2 === 1) ? 'Whites' : 'Blues';
            foreach ($courses as $course) {
                if (strcasecmp((string) $course['name_course'], $preferredCourse) === 0) {
                    return (int) $course['row_id'];
                }
            }
        }

        return (int) $courses[0]['row_id'];
    }

    private function determineDefaultRoundNumber(?array $round, string $seasonYear): int
    {
        if (($round['season_year'] ?? null) !== $seasonYear) {
            return 1;
        }

        return ((int) ($round['round_number'] ?? 0)) + 1;
    }

    private function getConfiguredSeasonYear(): string
    {
        $row = $this->db->fetchOne(
            'SELECT config_value_string
             FROM TW4_base.config_application
             WHERE config_name = ?',
            ['season_year']
        );

        $seasonYear = trim((string) ($row['config_value_string'] ?? ''));
        if (preg_match('/^\d{2}_\d{2}$/', $seasonYear) !== 1) {
            throw new \RuntimeException('Missing or invalid season_year in config_application.');
        }

        return $seasonYear;
    }

    private function getConfiguredClubNumber(): int
    {
        $row = $this->db->fetchOne(
            'SELECT COALESCE(config_value_int, CAST(config_value_string AS SIGNED)) AS club_number
             FROM TW4_base.config_application
             WHERE config_name = ?',
            ['club_number']
        );

        return (int) ($row['club_number'] ?? 0);
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
