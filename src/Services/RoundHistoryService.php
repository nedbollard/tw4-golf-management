<?php

namespace App\Services;

use App\Core\Database;

class RoundHistoryService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function replaceHistorySnapshot(int $roundId, string $seasonYear, int $numberRound, string $updatedBy): void
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
}