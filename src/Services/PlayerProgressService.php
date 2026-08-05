<?php

namespace App\Services;

use App\Core\Database;

class PlayerProgressService
{
    // Stableford points scale used for the chart's vertical axis (exceptions to be handled later).
    public const POINTS_MAX = 27;

    // Fixed reference line: a player's starting handicap for the season is always plotted here,
    // regardless of its real-world value. Handicap changes are then plotted as relative moves
    // (in points-scale units) up or down from this baseline, not as absolute handicap values.
    public const HANDICAP_BASELINE_LEVEL = 13;

    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getCurrentSeasonYear(): ?string
    {
        $row = $this->db->fetchOne(
            'SELECT season_year
             FROM TW4_live.round
             ORDER BY row_id ASC
             LIMIT 1'
        );

        $seasonYear = trim((string) ($row['season_year'] ?? ''));
        if ($seasonYear !== '') {
            return $seasonYear;
        }

        $row = $this->db->fetchOne(
            'SELECT season_year
             FROM TW4_history.round
             ORDER BY season_year DESC, number_round DESC
             LIMIT 1'
        );

        $seasonYear = trim((string) ($row['season_year'] ?? ''));
        return $seasonYear !== '' ? $seasonYear : null;
    }

    /**
     * @return list<array{row_id: int|string, player_identifier: string, alias: string|null, handicap: int|string}>
     */
    public function getEligiblePlayersWithHistory(string $seasonYear): array
    {
        return $this->db->fetchAll(
            'SELECT r.row_id,
                    r.player_identifier,
                    r.alias,
                    r.handicap
             FROM TW4_base.roster r
             INNER JOIN (
                 SELECT DISTINCT row_id_player
                 FROM TW4_history.card
                 WHERE season_year = ?
             ) hc ON hc.row_id_player = r.row_id
             WHERE r.status = "active"
             ORDER BY COALESCE(NULLIF(TRIM(r.alias), ""), r.player_identifier, CONCAT("player_", r.row_id)) ASC',
            [$seasonYear]
        );
    }

    /**
     * @return array{
     *     player: array<string, mixed>|null,
     *     season_year: string,
     *     rounds: list<array{
     *         number_round: int,
     *         round_date: string,
     *         course_name: string,
     *         score: int,
     *         points: int,
    *         points_scored: int,
    *         points_effective: int,
     *         handicap_applied: int,
     *         handicap_updated: int,
     *         handicap_changed: bool,
     *         handicap_markers: list<array{type: string, level: float|int, value: int}>,
     *         played: bool
     *     }>
     * }
     */
    public function getPlayerProgress(int $playerId, string $seasonYear): array
    {
        $player = $this->db->fetchOne(
            'SELECT row_id, player_identifier, alias, handicap, status
             FROM TW4_base.roster
             WHERE row_id = ?
             LIMIT 1',
            [$playerId]
        );

        if (!$player) {
            return [
                'player' => null,
                'season_year' => $seasonYear,
                'rounds' => [],
            ];
        }

        // Every round played this season is included (left join), even ones the
        // selected player missed, so the chart can show a gap instead of silently
        // skipping that round number.
        $roundRows = $this->db->fetchAll(
            'SELECT hr.number_round,
                    hr.round_date,
                    COALESCE(cp.name_course, "") AS course_name,
                    hc.score,
                    hc.points,
                    hc.handicap_applied,
                    hc.handicap_updated,
                    ha.points_scored,
                    ha.points_effective
             FROM TW4_history.round hr
             LEFT JOIN TW4_history.card hc
                ON hc.season_year = hr.season_year
               AND hc.number_round = hr.number_round
               AND hc.row_id_player = ?
             LEFT JOIN TW4_base.handicap_audit ha
                ON ha.row_id = (
                    SELECT MAX(ha2.row_id)
                    FROM TW4_base.handicap_audit ha2
                    WHERE ha2.row_id_player = ?
                                            AND ha2.season_year = ?
                      AND ha2.number_round = hr.number_round
                )
             LEFT JOIN TW4_base.course_played cp
                ON cp.row_id = hr.course_played_id
             WHERE hr.season_year = ?
             ORDER BY hr.number_round ASC',
            [$playerId, $playerId, $seasonYear, $seasonYear]
        );

        $rounds = [];
        // Round 1's starting handicap is always fixed at the baseline level; every
        // following round's starting marker carries on from wherever the previous
        // round's handicap ended up (which is the same level if it didn't change).
        $currentLevel = self::HANDICAP_BASELINE_LEVEL;
        foreach ($roundRows as $row) {
            $played = $row['points'] !== null;

            if (!$played) {
                // Player missed this round: show its slot on the chart with no bar and
                // no handicap markers, and leave the running handicap level untouched.
                $rounds[] = [
                    'number_round' => (int) ($row['number_round'] ?? 0),
                    'round_date' => (string) ($row['round_date'] ?? ''),
                    'course_name' => (string) ($row['course_name'] ?? ''),
                    'score' => 0,
                    'points' => 0,
                    'points_scored' => 0,
                    'points_effective' => 0,
                    'handicap_applied' => 0,
                    'handicap_updated' => 0,
                    'handicap_changed' => false,
                    'handicap_markers' => [],
                    'played' => false,
                ];
                continue;
            }

            $handicapApplied = max(0, (int) ($row['handicap_applied'] ?? 0));
            $handicapUpdated = max(0, (int) ($row['handicap_updated'] ?? $handicapApplied));
            $handicapChanged = $handicapApplied !== $handicapUpdated;
            $pointsScored = max(0, (int) ($row['points_scored'] ?? $row['points'] ?? 0));
            $pointsEffective = max($pointsScored, (int) ($row['points_effective'] ?? $pointsScored));

            $startLevel = $currentLevel;

            // Every round shows its own starting handicap as a black marker.
            $markers = [
                [
                    'type' => 'start',
                    'level' => $startLevel,
                    'value' => $handicapApplied,
                ],
            ];

            if ($handicapChanged) {
                // A handicap increase is plotted above the starting marker; a decrease
                // below it. Movement is relative to the round's own starting level, not
                // the absolute handicap value, so normal 1-2 point changes stay small.
                $delta = $handicapUpdated - $handicapApplied;
                $endLevel = $this->clampLevel($startLevel + $delta);

                $markers[] = [
                    'type' => 'end',
                    'level' => $endLevel,
                    'value' => $handicapUpdated,
                ];

                $currentLevel = $endLevel;
            }

            $rounds[] = [
                'number_round' => (int) ($row['number_round'] ?? 0),
                'round_date' => (string) ($row['round_date'] ?? ''),
                'course_name' => (string) ($row['course_name'] ?? ''),
                'score' => (int) ($row['score'] ?? 0),
                'points' => (int) ($row['points'] ?? 0),
                'points_scored' => $pointsScored,
                'points_effective' => $pointsEffective,
                'handicap_applied' => $handicapApplied,
                'handicap_updated' => $handicapUpdated,
                'handicap_changed' => $handicapChanged,
                'handicap_markers' => $markers,
                'played' => true,
            ];
        }

        return [
            'player' => $player,
            'season_year' => $seasonYear,
            'rounds' => $rounds,
        ];
    }

    /**
     * Keeps a handicap marker's plotted level within the visible chart area, leaving a small
     * margin at the top and bottom of the 0..POINTS_MAX axis.
     */
    private function clampLevel(float $level): float
    {
        return max(1.0, min(self::POINTS_MAX - 1, $level));
    }
}
