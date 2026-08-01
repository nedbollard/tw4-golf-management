<?php

namespace App\Services;

use App\Core\Database;

class CardChartQueryService
{
    public function __construct(private Database $db)
    {
    }

    public function getChartData(int $playerId): ?array
    {
        $player = $this->db->fetchOne(
            'SELECT row_id, player_identifier, alias, gender, handicap, first_name, last_name
             FROM TW4_base.roster WHERE row_id = ?',
            [$playerId]
        );
        if (!$player) {
            return null;
        }

        $card = $this->db->fetchOne(
            'SELECT row_id, handicap_applied, score, points
             FROM TW4_live.card WHERE row_id_player = ?',
            [$playerId]
        );
        if (!$card) {
            return null;
        }

        $holes = $this->db->fetchAll(
            'SELECT hole, score, shots, points
             FROM TW4_live.card_by_hole
             WHERE row_id_card = ? ORDER BY hole ASC',
            [(int) $card['row_id']]
        );

        $genderCode = strtolower((string) ($player['gender'] ?? 'male')) === 'female' ? 'F' : 'M';
        $round = $this->db->fetchOne(
            'SELECT course_played_id, season_year, number_round FROM TW4_live.round ORDER BY row_id DESC LIMIT 1'
        );

        if ($round && empty($round['course_played_id'])
            && !empty($round['season_year']) && (int) ($round['number_round'] ?? 0) > 0) {
            $historyRound = $this->db->fetchOne(
                'SELECT course_played_id
                 FROM TW4_history.round
                 WHERE season_year = ? AND number_round = ?
                 ORDER BY hist_updated_ts DESC LIMIT 1',
                [(string) $round['season_year'], (int) $round['number_round']]
            );
            if ($historyRound && !empty($historyRound['course_played_id'])) {
                $round['course_played_id'] = $historyRound['course_played_id'];
            }
        }

        $courseByPlayedHole = [];
        if ($round && !empty($round['course_played_id'])) {
            $courseHoles = $this->db->fetchAll(
                'SELECT cph.number_hole_played, cph.number_hole_course, cc.par, cc.stroke
                 FROM TW4_base.course_played_hole cph
                 INNER JOIN TW4_base.course_played cp ON cp.row_id = cph.course_played_id
                 INNER JOIN TW4_base.course_club cc
                         ON cc.name_club = cp.name_club
                        AND cc.number_hole = cph.number_hole_course
                        AND cc.gender = ?
                 WHERE cph.course_played_id = ?
                 ORDER BY cph.number_hole_played ASC',
                [$genderCode, (int) $round['course_played_id']]
            );
            foreach ($courseHoles as $courseHole) {
                $courseByPlayedHole[(int) $courseHole['number_hole_played']] = $courseHole;
            }
        }

        $chartHoles = [];
        foreach ($holes as $hole) {
            $playedHole = (int) $hole['hole'];
            $courseData = $courseByPlayedHole[$playedHole] ?? null;
            $chartHoles[] = [
                'hole_display' => $courseData ? (int) $courseData['number_hole_course'] : $playedHole,
                'par' => $courseData ? (int) $courseData['par'] : 0,
                'stroke' => $courseData ? (int) $courseData['stroke'] : 0,
                'score' => (int) $hole['score'],
                'shots' => (int) $hole['shots'],
                'points' => (int) $hole['points'],
            ];
        }

        return [
            'player' => $player,
            'card' => $card,
            'holes' => $chartHoles,
            'round' => [
                'season_year' => (string) ($round['season_year'] ?? ''),
                'number_round' => (int) ($round['number_round'] ?? 0),
            ],
        ];
    }
}