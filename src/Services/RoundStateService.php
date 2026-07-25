<?php

namespace App\Services;

use App\Core\Database;

class RoundStateService
{
    private RoundLockService $lockService;

    public function __construct(private Database $db)
    {
        $this->lockService = new RoundLockService($db);
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
        $steps = [
            1 => ['label' => 'Start a New Round', 'status' => 'waiting', 'enabled' => $roundId === null, 'route' => '/rounds/start'],
            2 => ['label' => 'Enter Cards', 'status' => 'waiting', 'enabled' => false, 'route' => '/scores/enter'],
            3 => ['label' => 'Present Results', 'status' => 'waiting', 'enabled' => false, 'route' => '/scores/present-results'],
            4 => ['label' => 'Finish the Round', 'status' => 'waiting', 'enabled' => false, 'route' => '/rounds/finish'],
        ];

        if ($roundId === null) {
            return ['active_round' => null, 'card_count' => 0, 'lock' => null, 'steps' => $steps];
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

        if (in_array($step, ['between_rounds', 'not_started'], true)) {
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
            foreach ($steps as &$workflowStep) {
                $workflowStep['status'] = 'completed';
            }
            unset($workflowStep);
        }

        return ['active_round' => $round, 'card_count' => $cardCount, 'lock' => $lock, 'steps' => $steps];
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
        $history = $this->db->fetchOne(
            'SELECT MAX(number_round) AS latest_round_number
             FROM TW4_history.round
             WHERE season_year = ?',
            [$seasonYear]
        );
        $latestRoundNumber = (int) ($history['latest_round_number'] ?? 0);

        if (($round['season_year'] ?? null) === $seasonYear) {
            $latestRoundNumber = max($latestRoundNumber, (int) ($round['round_number'] ?? 0));
        }

        return $latestRoundNumber + 1;
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
}