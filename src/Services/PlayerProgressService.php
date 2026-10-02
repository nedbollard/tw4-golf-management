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

    /**
     * @return list<array{row_id: int|string, player_identifier: string, alias: string|null, handicap: int|string}>
     */
    public function getEligiblePlayersWithHistory(): array
    {
        return $this->db->fetchAll(
            'SELECT r.row_id,
                    r.player_identifier,
                    r.alias,
                    r.handicap
             FROM TW4_base.roster r
             INNER JOIN (
                 SELECT DISTINCT hc.row_id_player
                 FROM TW4_history.card hc
                 INNER JOIN TW4_history.round hr
                    ON hr.season_year = hc.season_year
                   AND hr.number_round = hc.number_round
                 WHERE hc.points IS NOT NULL
             ) hc ON hc.row_id_player = r.row_id
             WHERE r.status = "active"
             ORDER BY COALESCE(NULLIF(TRIM(r.alias), ""), r.player_identifier, CONCAT("player_", r.row_id)) ASC'
        );
    }

    /**
     * @return array{
     *     player: array<string, mixed>|null,
     *     seasons: list<string>,
     *     rounds: list<array{
     *         season_year: string,
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
    public function getPlayerProgress(int $playerId): array
    {
        $player = $this->db->fetchOne(
            'SELECT row_id, player_identifier, alias, handicap, status
             FROM TW4_base.roster
             WHERE row_id = ? AND status = "active"
             LIMIT 1',
            [$playerId]
        );

        if (!$player) {
            return [
                'player' => null,
                'seasons' => [],
                'rounds' => [],
            ];
        }

        // History is archived at Finish Round. Include missed-round slots only
        // within seasons where this player has recorded results.
        $roundRows = $this->db->fetchAll(
            'SELECT hr.season_year,
                    hr.number_round,
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
                      AND ha2.season_year = hr.season_year COLLATE utf8mb4_0900_ai_ci
                      AND ha2.number_round = hr.number_round
                )
             LEFT JOIN TW4_base.course_played cp
                ON cp.row_id = hr.course_played_id
             WHERE EXISTS (
                 SELECT 1
                 FROM TW4_history.card season_card
                 INNER JOIN TW4_history.round season_round
                    ON season_round.season_year = season_card.season_year
                   AND season_round.number_round = season_card.number_round
                 WHERE season_card.row_id_player = ?
                   AND season_card.season_year = hr.season_year
                   AND season_card.points IS NOT NULL
             )
             ORDER BY hr.season_year ASC, hr.number_round ASC',
            [$playerId, $playerId, $playerId]
        );

        $rounds = [];
        $seasons = [];
        // Each season's first recorded handicap starts at the reference level;
        // subsequent rounds carry forward that season's handicap movement.
        $currentLevel = self::HANDICAP_BASELINE_LEVEL;
        $previousSeason = null;
        foreach ($roundRows as $row) {
            $seasonYear = (string) $row['season_year'];
            if ($seasonYear !== $previousSeason) {
                $seasons[] = $seasonYear;
                $currentLevel = self::HANDICAP_BASELINE_LEVEL;
                $previousSeason = $seasonYear;
            }
            $played = $row['points'] !== null;

            if (!$played) {
                // Player missed this round: show its slot on the chart with no bar and
                // no handicap markers, and leave the running handicap level untouched.
                $rounds[] = [
                    'season_year' => $seasonYear,
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
                'season_year' => $seasonYear,
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
            'seasons' => $seasons,
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
