<?php

namespace App\Services;

use App\Core\Database;

class TeamHaggleSeriousService
{
    private const KNOWN_MAKEUP_IDENTIFIERS = ['AverageJoe', 'MedianMick', 'TailEndCharlie'];

    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->ensureInfrastructure();
    }

    public function isSeriousMode(): bool
    {
        return $this->getTeamHaggleState() === 'serious';
    }

    public function buildEditorState(?array $draftTeams = null, array $messages = []): array
    {
        $teamSize = $this->getTeamSize();
        $makeupMethod = $this->getMakeupMethod();
        $playerPool = $this->getPlayerPool();

        $liveTeams = $this->getLiveTeams($teamSize);
        if (empty($liveTeams)) {
            $generated = $this->generateFloatingDraft($playerPool, $teamSize, $makeupMethod);
            $liveTeams = $generated['teams'];
        }

        $draft = $draftTeams ?? $liveTeams;
        $draft = $this->normalizeDraft($draft, $teamSize);

        $assigned = [];
        foreach ($draft as $teamRows) {
            foreach ($teamRows as $slotData) {
                $identifier = trim((string) ($slotData['player_identifier'] ?? ''));
                if ($identifier !== '' && !$this->isMakeupIdentifier($identifier)) {
                    $assigned[$identifier] = true;
                }
            }
        }

        $replacements = [];
        foreach ($playerPool as $identifier => $player) {
            if (!isset($assigned[$identifier]) && !$this->isMakeupIdentifier($identifier)) {
                $replacements[] = $player;
            }
        }

        return [
            'round' => $this->getRoundIdentity(),
            'team_size' => $teamSize,
            'makeup_method' => $makeupMethod,
            'revision' => $this->getSeriousRevision(),
            'teams' => $draft,
            'player_pool' => $playerPool,
            'replacement_players' => $replacements,
            'messages' => $messages,
        ];
    }

    public function applyReplacements(array $draftTeams, array $removedOrder, array $replacementIds): array
    {
        $teamSize = $this->getTeamSize();
        $draft = $this->normalizeDraft($draftTeams, $teamSize);
        $playerPool = $this->getPlayerPool();

        $slotQueue = [];
        foreach ($removedOrder as $slotRef) {
            if (!preg_match('/^(\d+):(\d+)$/', $slotRef, $matches)) {
                continue;
            }
            $teamNumber = (int) $matches[1];
            $slotNumber = (int) $matches[2];
            if (!isset($draft[$teamNumber][$slotNumber])) {
                continue;
            }
            $slotQueue[] = [$teamNumber, $slotNumber];
        }

        if (empty($slotQueue)) {
            return [
                'teams' => $draft,
                'messages' => ['No team member slots were selected.'],
            ];
        }

        $replacementQueue = [];
        foreach ($replacementIds as $identifier) {
            $normalized = trim((string) $identifier);
            if ($normalized === '' || !isset($playerPool[$normalized])) {
                continue;
            }
            $replacementQueue[] = $normalized;
        }

        $messages = [];
        foreach ($slotQueue as [$teamNumber, $slotNumber]) {
            if (!empty($replacementQueue)) {
                $identifier = array_shift($replacementQueue);
                $draft[$teamNumber][$slotNumber] = [
                    'player_identifier' => $identifier,
                    'player_points_total' => (int) ($playerPool[$identifier]['points_total'] ?? 0),
                    'rounds_played' => (int) ($playerPool[$identifier]['rounds_played'] ?? 0),
                    'display_name' => (string) ($playerPool[$identifier]['display_name'] ?? $identifier),
                ];
                continue;
            }

            $makeupIdentifier = $this->makeupIdentifierForMethod($this->getMakeupMethod());
            $makeupPoints = $this->calculateMakeupPointsFromPool($playerPool, $this->getMakeupMethod());
            $draft[$teamNumber][$slotNumber] = [
                'player_identifier' => $makeupIdentifier,
                'player_points_total' => $makeupPoints,
                'rounds_played' => 0,
                'display_name' => $makeupIdentifier,
            ];
        }

        if (!empty($replacementQueue)) {
            $messages[] = 'Some selected replacements were not used because fewer slots were selected.';
        }

        return [
            'teams' => $draft,
            'messages' => $messages,
        ];
    }

    public function saveTeams(array $draftTeams, int $postedRevision, string $updatedBy): array
    {
        $teamSize = $this->getTeamSize();
        $draft = $this->normalizeDraft($draftTeams, $teamSize);
        $currentRevision = $this->getSeriousRevision();

        if ($postedRevision !== $currentRevision) {
            throw new \RuntimeException('Team haggle data changed in another session. Reload and try again.');
        }

        $validationError = $this->validateDraft($draft);
        if ($validationError !== null) {
            throw new \RuntimeException($validationError);
        }

        $before = $this->getLiveTeams($teamSize);
        $playerPool = $this->getPlayerPool();
        $round = $this->getRoundIdentity();

        $this->db->beginTransaction();
        try {
            $this->db->query('DELETE FROM TW4_live.best_five_team_member');
            $this->db->query('DELETE FROM TW4_live.best_five_team');

            ksort($draft);
            foreach ($draft as $teamNumber => $slots) {
                $teamPoints = 0;
                $firstIdentifier = '';

                for ($slot = 1; $slot <= $teamSize; $slot++) {
                    $slotData = $slots[$slot] ?? ['player_identifier' => ''];
                    $identifier = trim((string) ($slotData['player_identifier'] ?? ''));
                    if ($identifier === '') {
                        throw new \RuntimeException('Each team slot must contain a player identifier.');
                    }

                    if ($firstIdentifier === '') {
                        $firstIdentifier = $identifier;
                    }

                    $points = $this->resolvePlayerPoints($identifier, $playerPool);
                    $teamPoints += $points;

                    $this->db->insert('TW4_live.best_five_team_member', [
                        'team_number' => (int) $teamNumber,
                        'player_identifier' => $identifier,
                        'player_points_total' => $points,
                        'updated_by' => $updatedBy,
                    ]);
                }

                $this->db->insert('TW4_live.best_five_team', [
                    'team_number' => (int) $teamNumber,
                    'team_name' => 'Team ' . $firstIdentifier,
                    'team_points_total' => $teamPoints,
                    'updated_by' => $updatedBy,
                ]);
            }

            $newRevision = $currentRevision + 1;
            $this->setSeriousRevision($newRevision, $updatedBy);
            $this->auditMembershipChanges($before, $draft, $round, $newRevision, $updatedBy);

            $this->db->commit();

            return [
                'revision' => $newRevision,
                'teams_saved' => count($draft),
            ];
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function refreshSeriousTeamPointsForFinish(string $seasonYear, int $numberRound, string $updatedBy): void
    {
        if (!$this->isSeriousMode()) {
            return;
        }

        $teamSize = $this->getTeamSize();
        $current = $this->getLiveTeams($teamSize);
        if (empty($current)) {
            return;
        }

        $playerPool = $this->getPlayerPool();
        $makeupMethod = $this->getMakeupMethod();
        $makeupPoints = $this->calculateMakeupPointsFromPool($playerPool, $makeupMethod);
        $revision = $this->getSeriousRevision();

        $liveRows = $this->db->fetchAll(
            'SELECT row_id, team_number, player_identifier, player_points_total
             FROM TW4_live.best_five_team_member
             ORDER BY team_number ASC, row_id ASC'
        );

        $slotByTeam = [];
        foreach ($liveRows as $row) {
            $teamNumber = (int) ($row['team_number'] ?? 0);
            if (!isset($slotByTeam[$teamNumber])) {
                $slotByTeam[$teamNumber] = 0;
            }
            $slotByTeam[$teamNumber]++;
            $slotNumber = $slotByTeam[$teamNumber];

            $identifier = trim((string) ($row['player_identifier'] ?? ''));
            $oldPoints = (int) ($row['player_points_total'] ?? 0);
            $newPoints = $this->resolvePlayerPoints($identifier, $playerPool, $makeupPoints);

            if ($newPoints !== $oldPoints) {
                $this->db->query(
                    'UPDATE TW4_live.best_five_team_member
                     SET player_points_total = ?, updated_by = ?
                     WHERE row_id = ?',
                    [$newPoints, $updatedBy, (int) $row['row_id']]
                );

                $this->insertAuditRow([
                    'season_year' => $seasonYear,
                    'number_round' => $numberRound,
                    'serious_revision' => $revision,
                    'team_number' => $teamNumber,
                    'slot_number' => $slotNumber,
                    'action_type' => 'finish_refresh',
                    'old_player_identifier' => $identifier,
                    'new_player_identifier' => $identifier,
                    'old_player_points' => $oldPoints,
                    'new_player_points' => $newPoints,
                    'note' => 'Membership retained; points refreshed at finish.',
                    'updated_by' => $updatedBy,
                ]);
            }
        }

        $this->db->query(
            'UPDATE TW4_live.best_five_team t
             INNER JOIN (
                SELECT team_number, COALESCE(SUM(player_points_total), 0) AS team_points_total
                FROM TW4_live.best_five_team_member
                GROUP BY team_number
             ) m ON m.team_number = t.team_number
             SET t.team_points_total = m.team_points_total,
                 t.updated_by = ?',
            [$updatedBy]
        );
    }

    private function ensureInfrastructure(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS TW4_history.best_five_team_member_audit (
                row_id INT NOT NULL AUTO_INCREMENT,
                season_year CHAR(5) NOT NULL,
                number_round INT NOT NULL,
                serious_revision INT NOT NULL DEFAULT 0,
                team_number INT NOT NULL,
                slot_number INT NOT NULL,
                action_type ENUM('assign','replace','remove','makeup','finish_refresh') NOT NULL,
                old_player_identifier VARCHAR(100) DEFAULT NULL,
                new_player_identifier VARCHAR(100) DEFAULT NULL,
                old_player_points INT NOT NULL DEFAULT 0,
                new_player_points INT NOT NULL DEFAULT 0,
                note VARCHAR(255) DEFAULT NULL,
                updated_by VARCHAR(100) NOT NULL,
                updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (row_id),
                KEY idx_best_five_team_member_audit_round (season_year, number_round, serious_revision),
                KEY idx_best_five_team_member_audit_team_slot (team_number, slot_number),
                KEY idx_best_five_team_member_audit_action (action_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $this->db->query(
            "INSERT INTO TW4_base.config_application
                (config_name, config_value_string, config_value_int, config_type, updated_by)
             SELECT 'team_haggle_serious_revision', '0', 0, 'int', 'system'
             WHERE NOT EXISTS (
                SELECT 1 FROM TW4_base.config_application WHERE config_name = 'team_haggle_serious_revision'
             )"
        );
    }

    private function getTeamHaggleState(): string
    {
        $row = $this->db->fetchOne(
            'SELECT LOWER(TRIM(config_value_string)) AS state
             FROM TW4_base.config_application
             WHERE config_name = ?
             LIMIT 1',
            ['team_haggle_state']
        );

        $value = (string) ($row['state'] ?? 'floating');
        if (in_array($value, ['serious', 's', 'l'], true)) {
            return 'serious';
        }

        return 'floating';
    }

    private function getTeamSize(): int
    {
        $row = $this->db->fetchOne(
            'SELECT COALESCE(config_value_int, CAST(config_value_string AS SIGNED)) AS team_size
             FROM TW4_base.config_application
             WHERE config_name = ?
             LIMIT 1',
            ['team_haggle_team_size']
        );

        return max(1, (int) ($row['team_size'] ?? 4));
    }

    private function getMakeupMethod(): string
    {
        $row = $this->db->fetchOne(
            'SELECT LOWER(TRIM(config_value_string)) AS makeup_method
             FROM TW4_base.config_application
             WHERE config_name = ?
             LIMIT 1',
            ['team_haggle_makeup_method']
        );

        return match ((string) ($row['makeup_method'] ?? 'average')) {
            'lowest' => 'lowest',
            'median' => 'median',
            default => 'average',
        };
    }

    private function getSeriousRevision(): int
    {
        $row = $this->db->fetchOne(
            'SELECT COALESCE(config_value_int, CAST(config_value_string AS SIGNED)) AS revision
             FROM TW4_base.config_application
             WHERE config_name = ?
             LIMIT 1',
            ['team_haggle_serious_revision']
        );

        return max(0, (int) ($row['revision'] ?? 0));
    }

    private function setSeriousRevision(int $revision, string $updatedBy): void
    {
        $this->db->query(
            'UPDATE TW4_base.config_application
             SET config_value_int = ?,
                 config_value_string = ?,
                 updated_by = ?
             WHERE config_name = ?',
            [$revision, (string) $revision, $updatedBy, 'team_haggle_serious_revision']
        );
    }

    private function getRoundIdentity(): array
    {
        $row = $this->db->fetchOne(
            'SELECT season_year, number_round, workflow_step
             FROM TW4_live.round
             ORDER BY row_id ASC
             LIMIT 1'
        );

        return [
            'season_year' => (string) ($row['season_year'] ?? ''),
            'number_round' => (int) ($row['number_round'] ?? 0),
            'workflow_step' => (string) ($row['workflow_step'] ?? 'between_rounds'),
        ];
    }

    private function getPlayerPool(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT
                COALESCE(r.player_identifier, CONCAT("player_", bfs.row_id_player)) AS player_identifier,
                COALESCE(NULLIF(TRIM(CONCAT(r.first_name, " ", r.last_name)), ""), COALESCE(r.player_identifier, CONCAT("player_", bfs.row_id_player))) AS display_name,
                bfs.points_total,
                (CASE WHEN bfs.round_best_1 > 0 THEN 1 ELSE 0 END
                 + CASE WHEN bfs.round_best_2 > 0 THEN 1 ELSE 0 END
                 + CASE WHEN bfs.round_best_3 > 0 THEN 1 ELSE 0 END
                 + CASE WHEN bfs.round_best_4 > 0 THEN 1 ELSE 0 END
                 + CASE WHEN bfs.round_best_5 > 0 THEN 1 ELSE 0 END) AS rounds_played
             FROM TW4_live.best_five_scores bfs
             LEFT JOIN TW4_base.roster r ON r.row_id = bfs.row_id_player
             WHERE r.status IS NULL OR r.status IN ("active", "scored")
             ORDER BY bfs.points_total DESC, player_identifier ASC'
        );

        $pool = [];
        foreach ($rows as $row) {
            $identifier = trim((string) ($row['player_identifier'] ?? ''));
            if ($identifier === '') {
                continue;
            }

            $pool[$identifier] = [
                'player_identifier' => $identifier,
                'display_name' => (string) ($row['display_name'] ?? $identifier),
                'points_total' => (int) ($row['points_total'] ?? 0),
                'rounds_played' => (int) ($row['rounds_played'] ?? 0),
            ];
        }

        return $pool;
    }

    private function getLiveTeams(int $teamSize): array
    {
        $rows = $this->db->fetchAll(
            'SELECT team_number, player_identifier, player_points_total
             FROM TW4_live.best_five_team_member
             ORDER BY team_number ASC, row_id ASC'
        );

        $pool = $this->getPlayerPool();
        $teams = [];
        $slotByTeam = [];

        foreach ($rows as $row) {
            $teamNumber = (int) ($row['team_number'] ?? 0);
            if ($teamNumber < 1) {
                continue;
            }

            if (!isset($slotByTeam[$teamNumber])) {
                $slotByTeam[$teamNumber] = 0;
            }
            $slotByTeam[$teamNumber]++;
            $slotNumber = $slotByTeam[$teamNumber];

            $identifier = trim((string) ($row['player_identifier'] ?? ''));
            $teams[$teamNumber][$slotNumber] = [
                'player_identifier' => $identifier,
                'player_points_total' => (int) ($row['player_points_total'] ?? 0),
                'rounds_played' => (int) ($pool[$identifier]['rounds_played'] ?? 0),
                'display_name' => (string) ($pool[$identifier]['display_name'] ?? $identifier),
            ];
        }

        return $this->normalizeDraft($teams, $teamSize);
    }

    private function generateFloatingDraft(array $playerPool, int $teamSize, string $makeupMethod): array
    {
        $players = array_values($playerPool);
        usort(
            $players,
            static fn(array $a, array $b): int =>
                ((int) ($b['points_total'] ?? 0) <=> (int) ($a['points_total'] ?? 0))
                ?: strcmp((string) ($a['player_identifier'] ?? ''), (string) ($b['player_identifier'] ?? ''))
        );

        $countPlayers = count($players);
        if ($countPlayers === 0) {
            return ['teams' => []];
        }

        $countTeams = (int) ceil($countPlayers / $teamSize);
        $requiredSlots = $countTeams * $teamSize;
        $missing = $requiredSlots - $countPlayers;

        if ($missing > 0) {
            $makeupIdentifier = $this->makeupIdentifierForMethod($makeupMethod);
            $makeupPoints = $this->calculateMakeupPointsFromPool($playerPool, $makeupMethod);

            for ($i = 0; $i < $missing; $i++) {
                $players[] = [
                    'player_identifier' => $makeupIdentifier,
                    'display_name' => $makeupIdentifier,
                    'points_total' => $makeupPoints,
                    'rounds_played' => 0,
                ];
            }
        }

        $pattern = array_merge(range(1, $countTeams), array_reverse(range(1, $countTeams)));
        $patternLength = count($pattern);

        $teams = [];
        $slotByTeam = [];
        foreach ($players as $index => $player) {
            $teamNumber = (int) $pattern[$index % $patternLength];
            if (!isset($slotByTeam[$teamNumber])) {
                $slotByTeam[$teamNumber] = 0;
            }
            $slotByTeam[$teamNumber]++;
            $slotNumber = $slotByTeam[$teamNumber];

            $identifier = (string) ($player['player_identifier'] ?? '');
            $teams[$teamNumber][$slotNumber] = [
                'player_identifier' => $identifier,
                'player_points_total' => (int) ($player['points_total'] ?? 0),
                'rounds_played' => (int) ($player['rounds_played'] ?? 0),
                'display_name' => (string) ($player['display_name'] ?? $identifier),
            ];
        }

        return ['teams' => $this->normalizeDraft($teams, $teamSize)];
    }

    private function normalizeDraft(array $draft, int $teamSize): array
    {
        if (empty($draft)) {
            return [];
        }

        $normalized = [];
        ksort($draft);

        foreach ($draft as $teamNumber => $slots) {
            $team = (int) $teamNumber;
            if ($team < 1) {
                continue;
            }

            $normalized[$team] = [];
            for ($slot = 1; $slot <= $teamSize; $slot++) {
                $slotData = $slots[$slot] ?? null;
                $identifier = trim((string) ($slotData['player_identifier'] ?? ''));
                $normalized[$team][$slot] = [
                    'player_identifier' => $identifier,
                    'player_points_total' => (int) ($slotData['player_points_total'] ?? 0),
                    'rounds_played' => (int) ($slotData['rounds_played'] ?? 0),
                    'display_name' => (string) ($slotData['display_name'] ?? $identifier),
                ];
            }
        }

        return $normalized;
    }

    private function validateDraft(array $draft): ?string
    {
        if (empty($draft)) {
            return 'At least one team is required before saving.';
        }

        $seen = [];
        foreach ($draft as $teamNumber => $slots) {
            foreach ($slots as $slotNumber => $slotData) {
                $identifier = trim((string) ($slotData['player_identifier'] ?? ''));
                if ($identifier === '') {
                    return sprintf('Team %d slot %d is empty.', (int) $teamNumber, (int) $slotNumber);
                }

                if ($this->isMakeupIdentifier($identifier)) {
                    continue;
                }

                if (isset($seen[$identifier])) {
                    return sprintf('Player %s is assigned more than once.', $identifier);
                }
                $seen[$identifier] = true;
            }
        }

        return null;
    }

    private function auditMembershipChanges(array $before, array $after, array $round, int $revision, string $updatedBy): void
    {
        $teamSize = $this->getTeamSize();

        $teamNumbers = array_unique(array_merge(array_keys($before), array_keys($after)));
        sort($teamNumbers);

        foreach ($teamNumbers as $teamNumber) {
            for ($slot = 1; $slot <= $teamSize; $slot++) {
                $old = $before[$teamNumber][$slot] ?? ['player_identifier' => '', 'player_points_total' => 0];
                $new = $after[$teamNumber][$slot] ?? ['player_identifier' => '', 'player_points_total' => 0];

                $oldIdentifier = trim((string) ($old['player_identifier'] ?? ''));
                $newIdentifier = trim((string) ($new['player_identifier'] ?? ''));
                $oldPoints = (int) ($old['player_points_total'] ?? 0);
                $newPoints = (int) ($new['player_points_total'] ?? 0);

                if ($oldIdentifier === $newIdentifier && $oldPoints === $newPoints) {
                    continue;
                }

                $actionType = 'assign';
                if ($oldIdentifier !== '' && $newIdentifier === '') {
                    $actionType = 'remove';
                } elseif ($oldIdentifier !== '' && $newIdentifier !== '') {
                    $actionType = $this->isMakeupIdentifier($newIdentifier) ? 'makeup' : 'replace';
                } elseif ($this->isMakeupIdentifier($newIdentifier)) {
                    $actionType = 'makeup';
                }

                $this->insertAuditRow([
                    'season_year' => (string) ($round['season_year'] ?? ''),
                    'number_round' => (int) ($round['number_round'] ?? 0),
                    'serious_revision' => $revision,
                    'team_number' => (int) $teamNumber,
                    'slot_number' => $slot,
                    'action_type' => $actionType,
                    'old_player_identifier' => $oldIdentifier !== '' ? $oldIdentifier : null,
                    'new_player_identifier' => $newIdentifier !== '' ? $newIdentifier : null,
                    'old_player_points' => $oldPoints,
                    'new_player_points' => $newPoints,
                    'note' => 'Admin team-haggle update.',
                    'updated_by' => $updatedBy,
                ]);
            }
        }
    }

    private function insertAuditRow(array $data): void
    {
        $this->db->insert('TW4_history.best_five_team_member_audit', $data);
    }

    private function resolvePlayerPoints(string $identifier, array $pool, ?int $fallbackMakeupPoints = null): int
    {
        if (isset($pool[$identifier])) {
            return (int) ($pool[$identifier]['points_total'] ?? 0);
        }

        if ($this->isMakeupIdentifier($identifier)) {
            return $fallbackMakeupPoints ?? $this->calculateMakeupPointsFromPool($pool, $this->getMakeupMethod());
        }

        return 0;
    }

    private function calculateMakeupPointsFromPool(array $pool, string $makeupMethod): int
    {
        if (empty($pool)) {
            return 0;
        }

        $points = array_map(static fn(array $row): int => (int) ($row['points_total'] ?? 0), $pool);
        if ($makeupMethod === 'lowest') {
            return min($points);
        }

        if ($makeupMethod === 'median') {
            sort($points);
            $count = count($points);
            $middle = intdiv($count, 2);
            if (($count % 2) === 1) {
                return (int) $points[$middle];
            }

            return (int) round((($points[$middle - 1] + $points[$middle]) / 2), 0, PHP_ROUND_HALF_UP);
        }

        return (int) round((array_sum($points) / count($points)), 0, PHP_ROUND_HALF_UP);
    }

    private function makeupIdentifierForMethod(string $makeupMethod): string
    {
        return match ($makeupMethod) {
            'lowest' => 'TailEndCharlie',
            'median' => 'MedianMick',
            default => 'AverageJoe',
        };
    }

    private function isMakeupIdentifier(string $identifier): bool
    {
        return in_array($identifier, self::KNOWN_MAKEUP_IDENTIFIERS, true);
    }
}
