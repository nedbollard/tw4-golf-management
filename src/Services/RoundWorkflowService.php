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
            "SELECT r.row_id AS round_id,
                    r.season_year,
                    r.number_round AS round_number,
                    r.round_date,
                    r.course_played_id,
                    r.workflow_step,
                    r.card_count,
                    r.locked_by_staff_id,
                    r.lock_acquired_at,
                    r.lock_expires_at,
                    r.results_presented_at,
                    r.finished_at,
                    COALESCE(cp.name_course, '') AS course_name,
                    COALESCE(cp.name_club, '') AS club_name
             FROM TW4_live.round r
             LEFT JOIN TW4_base.course_played cp ON cp.row_id = r.course_played_id
             ORDER BY r.row_id ASC
             LIMIT 1"
        );

        if ($round) {
            return $round;
        }

        $roundId = $this->db->insert('TW4_live.round', [
            'number_round' => 0,
            'workflow_step' => 'between_rounds',
            'card_count' => 0,
            'updated_by' => 'system',
        ]);

        return $this->db->fetchOne(
            "SELECT r.row_id AS round_id,
                    r.season_year,
                    r.number_round AS round_number,
                    r.round_date,
                    r.course_played_id,
                    r.workflow_step,
                    r.card_count,
                    r.locked_by_staff_id,
                    r.lock_acquired_at,
                    r.lock_expires_at,
                    r.results_presented_at,
                    r.finished_at,
                    COALESCE(cp.name_course, '') AS course_name,
                    COALESCE(cp.name_club, '') AS club_name
             FROM TW4_live.round r
             LEFT JOIN TW4_base.course_played cp ON cp.row_id = r.course_played_id
             WHERE r.row_id = ?",
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

        $this->ensureBestFiveTables();
        $this->ensureEclecticTables();

        $this->db->beginTransaction();

        try {
            $this->stageBestFiveForRoundStart($seasonYear, $_SESSION['username'] ?? 'system');
            $this->stageEclecticForRoundStart($seasonYear, $_SESSION['username'] ?? 'system');
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
                $this->db->query(
                    'UPDATE TW4_base.roster r
                     SET r.handicap = (
                         SELECT c.handicap_applied
                         FROM TW4_live.card c
                         WHERE c.row_id_player = r.row_id
                           AND c.number_round = ?
                         LIMIT 1
                     )
                     WHERE r.row_id IN (
                         SELECT DISTINCT row_id_player
                         FROM TW4_live.card
                         WHERE number_round = ?
                     )',
                    [$roundNumber, $roundNumber]
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
        $this->ensureEclecticTables();

        $this->db->beginTransaction();

        try {
            $eclecticContext = $this->buildRoundEclecticContext($roundId);
            $this->applyHandicapUpdatesBeforeHistory($updatedBy, $seasonYear, $numberRound);
            $this->refreshBestFiveForFinish($seasonYear, $numberRound, $updatedBy);
            // Always refresh per-course eclectic tracks; include_eclectic controls combined reporting.
            $this->refreshEclecticForFinish($roundId, $seasonYear, $numberRound, $updatedBy, $eclecticContext);
            $this->replaceHistorySnapshot($roundId, $seasonYear, $numberRound, $updatedBy);
            $this->persistRoundEclecticContext($seasonYear, $numberRound, $eclecticContext, $updatedBy);

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
                                        c.handicap_updated AS handicap_new,
                                        COALESCE(SUM(cbh.points), 0) AS points_scored,
                                        COALESCE(SUM(CASE WHEN cbh.points = 0 THEN 1 ELSE cbh.points END), 0) AS points_effective
             FROM TW4_base.roster r
             INNER JOIN TW4_live.card c ON c.row_id_player = r.row_id
                         LEFT JOIN TW4_live.card_by_hole cbh ON cbh.row_id_card = c.row_id
             WHERE c.handicap_updated IS NOT NULL
                             AND (r.handicap IS NULL OR r.handicap <> c.handicap_updated)
                         GROUP BY r.row_id, r.handicap, c.handicap_updated'
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
                'points_scored' => (int) ($row['points_scored'] ?? 0),
                'points_effective' => (int) ($row['points_effective'] ?? 0),
                'reason' => 'finish_round_card_scoring',
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
            'DELETE FROM TW4_history.best_five_scores
             WHERE season_year = ? AND number_round_movement = ?',
            [$seasonYear, $numberRound]
        );

        $this->db->query(
            'DELETE FROM TW4_history.eclectic_scores
             WHERE season_year = ? AND number_round_movement = ?',
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
            'INSERT INTO TW4_history.best_five_scores
                (season_year, row_id_player, number_round_movement,
                 points_total, points_best_1, points_best_2, points_best_3, points_best_4, points_best_5,
                 round_best_1, round_best_2, round_best_3, round_best_4, round_best_5,
                 points_movement, updated_by, updated_ts, hist_updated_by, hist_updated_ts)
             SELECT season_year, row_id_player, number_round_movement,
                    points_total, points_best_1, points_best_2, points_best_3, points_best_4, points_best_5,
                    round_best_1, round_best_2, round_best_3, round_best_4, round_best_5,
                    points_movement, updated_by, updated_ts, ?, NOW()
             FROM TW4_live.best_five_scores
             WHERE season_year = ?
               AND points_movement > 0
               AND number_round_movement = ?',
            [$updatedBy, $seasonYear, $numberRound]
        );

        $this->db->query(
            'INSERT INTO TW4_history.eclectic_scores
                (ident_eclectic, season_year, row_id_player, number_round_movement,
                 score_total, score_hole_1, score_hole_2, score_hole_3, score_hole_4,
                 score_hole_5, score_hole_6, score_hole_7, score_hole_8, score_hole_9,
                 updated_by, updated_ts, hist_updated_by, hist_updated_ts)
             SELECT ident_eclectic, season_year, row_id_player, number_round_movement,
                    score_total, score_hole_1, score_hole_2, score_hole_3, score_hole_4,
                    score_hole_5, score_hole_6, score_hole_7, score_hole_8, score_hole_9,
                    updated_by, updated_ts, ?, NOW()
             FROM TW4_live.eclectic_scores
             WHERE season_year = ?
               AND number_round_movement = ?',
            [$updatedBy, $seasonYear, $numberRound]
        );
    }

    private function stageBestFiveForRoundStart(string $seasonYear, string $updatedBy): void
    {
        $this->db->query('DELETE FROM TW4_holding.best_five_scores');
        $this->db->query(
            'INSERT INTO TW4_holding.best_five_scores
                (season_year, row_id_player, number_round_movement,
                 points_total, points_best_1, points_best_2, points_best_3, points_best_4, points_best_5,
                 round_best_1, round_best_2, round_best_3, round_best_4, round_best_5,
                 points_movement, updated_by)
             SELECT season_year, row_id_player, number_round_movement,
                    points_total, points_best_1, points_best_2, points_best_3, points_best_4, points_best_5,
                    round_best_1, round_best_2, round_best_3, round_best_4, round_best_5,
                    points_movement, ?
             FROM TW4_live.best_five_scores
             WHERE season_year = ?',
            [$updatedBy, $seasonYear]
        );
        $this->db->query('DELETE FROM TW4_live.best_five_scores');
    }

    private function stageEclecticForRoundStart(string $seasonYear, string $updatedBy): void
    {
        $this->db->query('DELETE FROM TW4_holding.eclectic_scores');
        $this->db->query(
            'INSERT INTO TW4_holding.eclectic_scores
                (ident_eclectic, season_year, row_id_player, number_round_movement,
                 score_total, score_hole_1, score_hole_2, score_hole_3, score_hole_4,
                 score_hole_5, score_hole_6, score_hole_7, score_hole_8, score_hole_9,
                 updated_by)
             SELECT ident_eclectic, season_year, row_id_player, number_round_movement,
                    score_total, score_hole_1, score_hole_2, score_hole_3, score_hole_4,
                    score_hole_5, score_hole_6, score_hole_7, score_hole_8, score_hole_9,
                    ?
             FROM TW4_live.eclectic_scores
             WHERE season_year = ?',
            [$updatedBy, $seasonYear]
        );
        $this->db->query('DELETE FROM TW4_live.eclectic_scores');
    }

    private function refreshBestFiveForFinish(string $seasonYear, int $numberRound, string $updatedBy): void
    {
        $holdingRows = $this->db->fetchAll(
            'SELECT season_year, row_id_player, number_round_movement,
                    points_total, points_best_1, points_best_2, points_best_3, points_best_4, points_best_5,
                    round_best_1, round_best_2, round_best_3, round_best_4, round_best_5,
                    points_movement
             FROM TW4_holding.best_five_scores
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

        $this->db->query('DELETE FROM TW4_live.best_five_scores');

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

            $this->db->insert('TW4_live.best_five_scores', [
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

    private function refreshEclecticForFinish(int $roundId, string $seasonYear, int $numberRound, string $updatedBy, array $eclecticContext): void
    {
        $idents = $this->getEclecticIdentsForRound($roundId, $eclecticContext);

        $holdingRows = $this->db->fetchAll(
            'SELECT ident_eclectic, season_year, row_id_player, number_round_movement,
                    score_total, score_hole_1, score_hole_2, score_hole_3, score_hole_4,
                    score_hole_5, score_hole_6, score_hole_7, score_hole_8, score_hole_9,
                    updated_by
             FROM TW4_holding.eclectic_scores
             WHERE season_year = ?',
            [$seasonYear]
        );

        $cardRows = $this->db->fetchAll(
            'SELECT c.row_id_player, cbh.hole, cbh.score
             FROM TW4_live.card c
             INNER JOIN TW4_live.card_by_hole cbh ON cbh.row_id_card = c.row_id
             ORDER BY c.row_id_player, cbh.hole'
        );

        $holdingByKey = [];
        foreach ($holdingRows as $row) {
            $ident = $this->normalizeEclecticIdent((string) ($row['ident_eclectic'] ?? ''));
            $playerId = (int) ($row['row_id_player'] ?? 0);
            if ($ident === '' || $playerId < 1) {
                continue;
            }
            $row['ident_eclectic'] = $ident;
            $holdingByKey[$ident . '|' . $playerId] = $row;
        }

        $scoresByPlayer = [];
        foreach ($cardRows as $row) {
            $playerId = (int) ($row['row_id_player'] ?? 0);
            $hole = (int) ($row['hole'] ?? 0);
            $score = (int) ($row['score'] ?? 0);
            if ($playerId > 0 && $hole >= 1 && $hole <= 9 && $score > 0) {
                $scoresByPlayer[$playerId][$hole] = $score;
            }
        }

        $computed = [];
        foreach ($holdingByKey as $key => $row) {
            $computed[$key] = [
                'ident_eclectic' => (string) $row['ident_eclectic'],
                'season_year' => $seasonYear,
                'row_id_player' => (int) $row['row_id_player'],
                'number_round_movement' => (int) ($row['number_round_movement'] ?? 0),
                'score_total' => (int) ($row['score_total'] ?? 0),
                'score_hole_1' => (int) ($row['score_hole_1'] ?? 0),
                'score_hole_2' => (int) ($row['score_hole_2'] ?? 0),
                'score_hole_3' => (int) ($row['score_hole_3'] ?? 0),
                'score_hole_4' => (int) ($row['score_hole_4'] ?? 0),
                'score_hole_5' => (int) ($row['score_hole_5'] ?? 0),
                'score_hole_6' => (int) ($row['score_hole_6'] ?? 0),
                'score_hole_7' => (int) ($row['score_hole_7'] ?? 0),
                'score_hole_8' => (int) ($row['score_hole_8'] ?? 0),
                'score_hole_9' => (int) ($row['score_hole_9'] ?? 0),
                'updated_by' => $updatedBy,
            ];
        }

        foreach ($idents as $ident) {
            $ident = $this->normalizeEclecticIdent($ident);
            if ($ident === '') {
                continue;
            }

            foreach ($scoresByPlayer as $playerId => $scores) {
                if (count($scores) < 9) {
                    continue;
                }

                $key = $ident . '|' . $playerId;
                $existing = $holdingByKey[$key] ?? null;

                $mergedHoles = [];
                $moved = $existing === null;

                for ($i = 1; $i <= 9; $i++) {
                    $newScore = (int) ($scores[$i] ?? 0);
                    if ($newScore < 1) {
                        $newScore = 0;
                    }

                    $oldScore = $existing === null
                        ? 0
                        : (int) ($existing['score_hole_' . $i] ?? 0);

                    if ($oldScore <= 0) {
                        $merged = $newScore;
                    } elseif ($newScore > 0 && $newScore < $oldScore) {
                        $merged = $newScore;
                        $moved = true;
                    } else {
                        $merged = $oldScore;
                    }

                    if ($existing !== null && !$moved && $merged !== $oldScore) {
                        $moved = true;
                    }

                    $mergedHoles[$i] = $merged;
                }

                $newTotal = array_sum($mergedHoles);
                $oldTotal = $existing === null ? 0 : (int) ($existing['score_total'] ?? 0);
                if ($existing !== null && $newTotal < $oldTotal) {
                    $moved = true;
                }

                $movementRound = $existing === null
                    ? $numberRound
                    : (int) ($existing['number_round_movement'] ?? 0);
                if ($moved) {
                    $movementRound = $numberRound;
                }

                $computed[$key] = [
                    'ident_eclectic' => $ident,
                    'season_year' => $seasonYear,
                    'row_id_player' => (int) $playerId,
                    'number_round_movement' => $movementRound,
                    'score_total' => $newTotal,
                    'score_hole_1' => $mergedHoles[1],
                    'score_hole_2' => $mergedHoles[2],
                    'score_hole_3' => $mergedHoles[3],
                    'score_hole_4' => $mergedHoles[4],
                    'score_hole_5' => $mergedHoles[5],
                    'score_hole_6' => $mergedHoles[6],
                    'score_hole_7' => $mergedHoles[7],
                    'score_hole_8' => $mergedHoles[8],
                    'score_hole_9' => $mergedHoles[9],
                    'updated_by' => $updatedBy,
                ];
            }
        }

        $this->db->query('DELETE FROM TW4_live.eclectic_scores');

        ksort($computed);
        foreach ($computed as $row) {
            $this->db->insert('TW4_live.eclectic_scores', $row);
        }
    }

    /**
     * @return array<int, string>
     */
    private function getEclecticIdentsForRound(int $roundId, array $eclecticContext): array
    {
        $idents = [
            trim((string) ($eclecticContext['played_course_name'] ?? '')),
            trim((string) ($eclecticContext['combined_name'] ?? '')),
        ];

        if ($idents[0] === '' && $idents[1] === '') {
            $row = $this->db->fetchOne(
                'SELECT cp.name_course, cp.ident_eclectic
                 FROM TW4_live.round r
                 LEFT JOIN TW4_base.course_played cp ON cp.row_id = r.course_played_id
                 WHERE r.row_id = ?
                 LIMIT 1',
                [$roundId]
            );

            $idents = [
                trim((string) ($row['name_course'] ?? '')),
                trim((string) ($row['ident_eclectic'] ?? '')),
            ];
        }

        return $this->dedupeEclecticIdentsCaseInsensitive($idents);
    }

    /**
     * @param array<int, string> $idents
     * @return array<int, string>
     */
    private function dedupeEclecticIdentsCaseInsensitive(array $idents): array
    {
        $result = [];
        $seen = [];

        foreach ($idents as $ident) {
            $value = $this->normalizeEclecticIdent((string) $ident);
            if ($value === '') {
                continue;
            }

            $key = $value;
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $value;
        }

        return $result;
    }

    private function normalizeEclecticIdent(string $ident): string
    {
        return strtolower(trim($ident));
    }

    private function buildRoundEclecticContext(int $roundId): array
    {
        $row = $this->db->fetchOne(
            'SELECT cp.name_course, cp.ident_eclectic
             FROM TW4_live.round r
             LEFT JOIN TW4_base.course_played cp ON cp.row_id = r.course_played_id
             WHERE r.row_id = ?
             LIMIT 1',
            [$roundId]
        );

        $playedCourseName = trim((string) ($row['name_course'] ?? ''));
        $courseIdent = trim((string) ($row['ident_eclectic'] ?? ''));
        $includeEclectic = $this->shouldIncludeEclectic($courseIdent);

        $combinedName = $courseIdent;
        if ($combinedName === '') {
            $combinedName = 'Eclectic';
        }

        $playedSlug = $this->slugifyReportName($playedCourseName === '' ? 'Course' : $playedCourseName);
        $combinedSlug = $this->slugifyReportName($combinedName);

        return [
            'include_eclectic' => $includeEclectic,
            'configured_ident_eclectic' => '',
            'played_course_name' => $playedCourseName,
            'combined_name' => $combinedName,
            'course_report_files' => [
                '41_Eclectic_' . $playedSlug . '.html',
            ],
            'combined_report_filename' => '49_Eclectic_' . $combinedSlug . '.html',
        ];
    }

    private function persistRoundEclecticContext(string $seasonYear, int $numberRound, array $context, string $updatedBy): void
    {
        $this->db->query(
            'DELETE FROM TW4_history.round_eclectic_context
             WHERE season_year = ? AND number_round = ?',
            [$seasonYear, $numberRound]
        );

        $this->db->insert('TW4_history.round_eclectic_context', [
            'season_year' => $seasonYear,
            'number_round' => $numberRound,
            'include_eclectic' => (int) ((bool) ($context['include_eclectic'] ?? false)),
            'configured_ident_eclectic' => (string) ($context['configured_ident_eclectic'] ?? ''),
            'played_course_name' => (string) ($context['played_course_name'] ?? ''),
            'combined_name' => (string) ($context['combined_name'] ?? ''),
            'course_report_files_json' => json_encode($context['course_report_files'] ?? []),
            'combined_report_filename' => (string) ($context['combined_report_filename'] ?? ''),
            'updated_by' => $updatedBy,
            'updated_ts' => date('Y-m-d H:i:s'),
            'hist_updated_by' => $updatedBy,
        ]);
    }

    private function shouldIncludeEclectic(string $courseIdent): bool
    {
        $course = strtolower(trim($courseIdent));
        if ($course === '') {
            return false;
        }

        if (in_array($course, ['none', 'nil', 'n/a', 'na', 'off'], true)) {
            return false;
        }

        return true;
    }

    private function slugifyReportName(string $name): string
    {
        $value = trim($name);
        if ($value === '') {
            return 'Course';
        }

        $value = preg_replace('/[^A-Za-z0-9]+/', '_', $value) ?? $value;
        $value = trim($value, '_');
        return $value === '' ? 'Course' : $value;
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
            CREATE TABLE IF NOT EXISTS %s.best_five_scores (
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
                UNIQUE KEY uk_best_five_scores_season_player (season_year, row_id_player),
                KEY idx_best_five_scores_player (row_id_player),
                KEY idx_best_five_scores_season (season_year)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";

        $this->db->query(sprintf($tableDdl, 'TW4_live'));
        $this->db->query(sprintf($tableDdl, 'TW4_holding'));

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS TW4_history.best_five_scores (
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
                updated_ts TIMESTAMP NULL DEFAULT NULL,
                hist_updated_by VARCHAR(100) NOT NULL,
                hist_updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (row_id),
                UNIQUE KEY uk_history_best_five_scores_movement_player (season_year, number_round_movement, row_id_player),
                KEY idx_history_best_five_scores_movement (season_year, number_round_movement),
                KEY idx_history_best_five_scores_player (row_id_player)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }

    private function ensureEclecticTables(): void
    {
        $this->db->query(
            "CREATE DATABASE IF NOT EXISTS TW4_holding
             CHARACTER SET utf8mb4
             COLLATE utf8mb4_general_ci"
        );

        $tableDdl = "
            CREATE TABLE IF NOT EXISTS %s.eclectic_scores (
                row_id INT NOT NULL AUTO_INCREMENT,
                ident_eclectic VARCHAR(16) NOT NULL,
                season_year CHAR(5) NOT NULL,
                row_id_player INT NOT NULL,
                number_round_movement INT NOT NULL DEFAULT 0,
                score_total INT NOT NULL DEFAULT 0,
                score_hole_1 INT NOT NULL DEFAULT 0,
                score_hole_2 INT NOT NULL DEFAULT 0,
                score_hole_3 INT NOT NULL DEFAULT 0,
                score_hole_4 INT NOT NULL DEFAULT 0,
                score_hole_5 INT NOT NULL DEFAULT 0,
                score_hole_6 INT NOT NULL DEFAULT 0,
                score_hole_7 INT NOT NULL DEFAULT 0,
                score_hole_8 INT NOT NULL DEFAULT 0,
                score_hole_9 INT NOT NULL DEFAULT 0,
                updated_by VARCHAR(100) NOT NULL,
                updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (row_id),
                UNIQUE KEY uk_eclectic_scores_season_ident_player (season_year, ident_eclectic, row_id_player),
                KEY idx_eclectic_scores_player (row_id_player),
                KEY idx_eclectic_scores_season (season_year),
                KEY idx_eclectic_scores_ident (ident_eclectic)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";

        $this->db->query(sprintf($tableDdl, 'TW4_live'));
        $this->db->query(sprintf($tableDdl, 'TW4_holding'));

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS TW4_history.eclectic_scores (
                row_id INT NOT NULL AUTO_INCREMENT,
                ident_eclectic VARCHAR(16) NOT NULL,
                season_year CHAR(5) NOT NULL,
                row_id_player INT NOT NULL,
                number_round_movement INT NOT NULL DEFAULT 0,
                score_total INT NOT NULL DEFAULT 0,
                score_hole_1 INT NOT NULL DEFAULT 0,
                score_hole_2 INT NOT NULL DEFAULT 0,
                score_hole_3 INT NOT NULL DEFAULT 0,
                score_hole_4 INT NOT NULL DEFAULT 0,
                score_hole_5 INT NOT NULL DEFAULT 0,
                score_hole_6 INT NOT NULL DEFAULT 0,
                score_hole_7 INT NOT NULL DEFAULT 0,
                score_hole_8 INT NOT NULL DEFAULT 0,
                score_hole_9 INT NOT NULL DEFAULT 0,
                updated_by VARCHAR(100) NOT NULL,
                updated_ts TIMESTAMP NULL DEFAULT NULL,
                hist_updated_by VARCHAR(100) NOT NULL,
                hist_updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (row_id),
                UNIQUE KEY uk_history_eclectic_scores_movement_ident_player (ident_eclectic, season_year, number_round_movement, row_id_player),
                KEY idx_history_eclectic_scores_movement_ident (ident_eclectic, season_year, number_round_movement),
                KEY idx_history_eclectic_scores_player (row_id_player)
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
        $step = $round['workflow_step'] ?? 'between_rounds';
        $cardCount = $this->getCardCount($roundId);
        $lock = $this->lockService->getLockStatus($roundId, $staffId);
        $lockBlocked = $lock && !empty($lock['blocked']);

        $steps = [
            1 => ['label' => 'Start a New Round', 'status' => 'waiting', 'enabled' => false, 'route' => '/rounds/start'],
            2 => ['label' => 'Enter Cards', 'status' => 'waiting', 'enabled' => false, 'route' => '/scores/enter'],
            3 => ['label' => 'Present Results', 'status' => 'waiting', 'enabled' => false, 'route' => '/scores/present-results'],
            4 => ['label' => 'Finish the Round', 'status' => 'waiting', 'enabled' => false, 'route' => '/rounds/finish'],
        ];

        if ($this->isBetweenRoundsStep((string) $step)) {
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

    private function isBetweenRoundsStep(string $step): bool
    {
        return in_array($step, ['between_rounds', 'not_started'], true);
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
