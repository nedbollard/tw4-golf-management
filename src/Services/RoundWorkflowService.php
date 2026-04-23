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

        $this->db->beginTransaction();

        try {
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

        $this->db->beginTransaction();

        try {
            $this->db->query('DELETE FROM TW4_live.card');
            $this->db->query('DELETE FROM TW4_live.results');
            $this->db->query(
                "UPDATE TW4_base.roster
                 SET status = 'active', updated_by = ?
                 WHERE status = 'scored'",
                [$_SESSION['username'] ?? 'system']
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
                     lock_released_at = NOW(),
                     lock_release_reason = 'finished',
                     updated_by = ?
                 WHERE row_id = ?",
                [$_SESSION['username'] ?? 'system', $roundId]
            );

            $this->db->commit();
            return $stmt->rowCount() >= 0;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
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
            'SELECT row_id AS round_id, number_round AS round_number, round_date, course_played_id, workflow_step, card_count
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
