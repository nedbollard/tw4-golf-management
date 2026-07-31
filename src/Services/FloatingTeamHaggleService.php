<?php

namespace App\Services;

use App\Core\Database;

class FloatingTeamHaggleService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function refreshForFinish(string $seasonYear, string $updatedBy): void
    {
        if ($this->getConfiguredTeamHaggleState() === 'serious') {
            $round = $this->db->fetchOne(
                'SELECT number_round
                 FROM TW4_live.round
                 ORDER BY row_id ASC
                 LIMIT 1'
            );
            $numberRound = (int) ($round['number_round'] ?? 0);

            $service = new TeamHaggleSeriousService($this->db);
            $service->refreshSeriousTeamPointsForFinish($seasonYear, $numberRound, $updatedBy);
            return;
        }

        if ($this->getConfiguredTeamHaggleState() !== 'floating') {
            return;
        }

        if (!$this->hasTeamHaggleTables()) {
            throw new \RuntimeException(
                'Team haggle tables are missing in TW4_live. Run migration 039_team_haggle_floating_setup.sql.'
            );
        }

        $teamSize = $this->getConfiguredTeamHaggleTeamSize();
        $makeupMethod = $this->getConfiguredTeamHaggleMakeupMethod();

        $players = $this->db->fetchAll(
            'SELECT
                bf.row_id_player,
                bf.points_total,
                COALESCE(r.player_identifier, CONCAT("player_", bf.row_id_player)) AS player_identifier,
                COALESCE(r.handicap, 0) AS handicap,
                COALESCE(NULLIF(TRIM(r.first_name), ""), COALESCE(r.player_identifier, CONCAT("player_", bf.row_id_player))) AS first_name
             FROM TW4_live.best_five_scores bf
             LEFT JOIN TW4_base.roster r ON r.row_id = bf.row_id_player
             WHERE bf.season_year = ?',
            [$seasonYear]
        );

        usort(
            $players,
            static fn(array $a, array $b): int =>
                ((int) ($b['points_total'] ?? 0) <=> (int) ($a['points_total'] ?? 0))
                ?: strcmp((string) ($a['player_identifier'] ?? ''), (string) ($b['player_identifier'] ?? ''))
        );

        $countPlayers = count($players);
        $this->db->query('DELETE FROM TW4_live.best_five_team_member');
        $this->db->query('DELETE FROM TW4_live.best_five_team');

        if ($countPlayers === 0) {
            return;
        }

        $countTeams = (int) ceil($countPlayers / $teamSize);
        if ($countTeams < 1) {
            return;
        }

        $targetSlots = $countTeams * $teamSize;
        $makeupRequired = $targetSlots - $countPlayers;
        if ($makeupRequired > 0) {
            $makeupPoints = $this->calculateMakeupPoints($players, $makeupMethod);
            $makeupIdentifier = match ($makeupMethod) {
                'lowest' => 'TailEndCharlie',
                'median' => 'MedianMick',
                default => 'AverageJoe',
            };
            for ($i = 0; $i < $makeupRequired; $i++) {
                $players[] = [
                    'row_id_player' => null,
                    'points_total' => $makeupPoints,
                    'player_identifier' => $makeupIdentifier,
                    'handicap' => 0,
                    'first_name' => $makeupIdentifier,
                ];
            }
        }

        $pattern = array_merge(range(1, $countTeams), array_reverse(range(1, $countTeams)));
        $patternLength = count($pattern);
        $teamMembers = [];
        foreach ($players as $idx => $player) {
            $teamNumber = (int) $pattern[$idx % $patternLength];
            $teamMembers[$teamNumber][] = [
                'player_identifier' => (string) ($player['player_identifier'] ?? ''),
                'player_points_total' => (int) ($player['points_total'] ?? 0),
                'first_name' => (string) ($player['first_name'] ?? ''),
            ];
        }

        ksort($teamMembers);
        foreach ($teamMembers as $teamNumber => $members) {
            if (empty($members)) {
                continue;
            }

            $teamFirstName = trim((string) ($members[0]['first_name'] ?? ''));
            if ($teamFirstName === '') {
                $teamFirstName = (string) ($members[0]['player_identifier'] ?? 'Unknown');
            }

            $this->db->insert('TW4_live.best_five_team', [
                'team_number' => $teamNumber,
                'team_name' => 'Team ' . $teamFirstName,
                'team_points_total' => array_sum(array_map(static fn(array $row): int => (int) ($row['player_points_total'] ?? 0), $members)),
                'updated_by' => $updatedBy,
            ]);

            foreach ($members as $member) {
                $this->db->insert('TW4_live.best_five_team_member', [
                    'team_number' => $teamNumber,
                    'player_identifier' => (string) ($member['player_identifier'] ?? ''),
                    'player_points_total' => (int) ($member['player_points_total'] ?? 0),
                    'updated_by' => $updatedBy,
                ]);
            }
        }
    }

    public function clearLiveTables(): void
    {
        if (!$this->hasTeamHaggleTables()) {
            return;
        }

        // In serious mode the team membership is manually curated by the admin and must
        // survive a result reset. Only floating mode auto-generates teams on every finish,
        // so only floating mode needs the tables cleared here.
        if ($this->getConfiguredTeamHaggleState() === 'serious') {
            return;
        }

        $this->db->query('DELETE FROM TW4_live.best_five_team_member');
        $this->db->query('DELETE FROM TW4_live.best_five_team');
    }

    private function calculateMakeupPoints(array $players, string $makeupMethod): int
    {
        if (empty($players)) {
            return 0;
        }

        $points = array_map(static fn(array $row): int => (int) ($row['points_total'] ?? 0), $players);
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

            $median = ($points[$middle - 1] + $points[$middle]) / 2;
            return (int) round($median, 0, PHP_ROUND_HALF_UP);
        }

        $average = array_sum($points) / count($points);
        return (int) floor($average);
    }

    private function hasTeamHaggleTables(): bool
    {
        return $this->tableExists('TW4_live', 'best_five_team')
            && $this->tableExists('TW4_live', 'best_five_team_member');
    }

    private function tableExists(string $schema, string $table): bool
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS table_count
             FROM information_schema.tables
             WHERE table_schema = ?
               AND table_name = ?
             LIMIT 1',
            [$schema, $table]
        );

        return ((int) ($row['table_count'] ?? 0)) > 0;
    }

    private function getConfiguredTeamHaggleState(): string
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

    private function getConfiguredTeamHaggleTeamSize(): int
    {
        $row = $this->db->fetchOne(
            'SELECT COALESCE(config_value_int, CAST(config_value_string AS SIGNED)) AS team_size
             FROM TW4_base.config_application
             WHERE config_name = ?
             LIMIT 1',
            ['team_haggle_team_size']
        );

        $value = (int) ($row['team_size'] ?? 4);
        return max(1, $value);
    }

    private function getConfiguredTeamHaggleMakeupMethod(): string
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
}