<?php

namespace App\Services;

use App\Core\Database;

class CardEntryQueryService
{
    private const HOLES_PER_ROUND = 9;

    public function __construct(private Database $db)
    {
    }

    public function getSelectablePlayers(int $roundId): array
    {
        $round = $this->db->fetchOne(
            'SELECT card_entry_reopened
             FROM TW4_live.round
             WHERE row_id = ?',
            [$roundId]
        );
        $statusFilter = !empty($round['card_entry_reopened'])
            ? 'r.status IN ("active", "scored")'
            : 'r.status = "active"';

        return $this->db->fetchAll(
            'SELECT r.row_id,
                    r.first_name,
                    r.last_name,
                    r.alias,
                    r.player_identifier,
                    r.gender,
                    r.handicap,
                    c.row_id AS card_id
             FROM TW4_base.roster r
             LEFT JOIN TW4_live.card c
                      ON c.row_id_player = r.row_id
                             WHERE ' . $statusFilter . '
                  ORDER BY r.last_name, r.first_name'
        );
    }

    public function buildEntryData(int $roundId, int $playerId): array
    {
        $round = $this->db->fetchOne(
            'SELECT row_id, number_round, round_date, course_played_id
             FROM TW4_live.round
             WHERE row_id = ?',
            [$roundId]
        );

        if (!$round || empty($round['course_played_id'])) {
            throw new \RuntimeException('Round configuration incomplete: course not selected.');
        }

        $player = $this->db->fetchOne(
            'SELECT row_id, first_name, last_name, alias, player_identifier, gender, handicap
             FROM TW4_base.roster
             WHERE row_id = ? AND status IN ("active", "scored")',
            [$playerId]
        );

        if (!$player) {
            throw new \RuntimeException('Player not found or no longer active.');
        }

        $genderCode = strtolower((string) ($player['gender'] ?? 'male')) === 'female' ? 'F' : 'M';
        $holes = $this->db->fetchAll(
            'SELECT cph.row_id,
                  cph.number_hole_course,
                  cph.number_hole_played,
                    cc.par,
                    cc.stroke
             FROM TW4_base.course_played_hole cph
             INNER JOIN TW4_base.course_played cp ON cp.row_id = cph.course_played_id
             INNER JOIN TW4_base.course_club cc
                     ON cc.name_club = cp.name_club
                  AND cc.number_hole = cph.number_hole_course
                    AND cc.gender = ?
             WHERE cph.course_played_id = ?
              ORDER BY cph.number_hole_played ASC
             LIMIT ' . self::HOLES_PER_ROUND,
            [$genderCode, (int) $round['course_played_id']]
        );

        if (count($holes) !== self::HOLES_PER_ROUND) {
            $gender = strtolower((string) ($player['gender'] ?? 'male')) === 'female' ? 'Female' : 'Male';
            throw new \RuntimeException(
                "Course hole configuration incomplete for player {$player['player_identifier']}: "
                . "Expected " . self::HOLES_PER_ROUND . " holes for $gender, found " . count($holes) . "."
            );
        }

        $existingByHole = [];
        $existing = $this->db->fetchAll(
            'SELECT cbh.hole, cbh.score
             FROM TW4_live.card_by_hole cbh
             INNER JOIN TW4_live.card c ON c.row_id = cbh.row_id_card
             WHERE c.row_id_player = ?',
            [$playerId]
        );

        foreach ($existing as $row) {
            $existingByHole[(int) $row['hole']] = (int) $row['score'];
        }

        $entryHoles = [];
        foreach ($holes as $index => $hole) {
            $holeNo = (int) $hole['number_hole_course'];
            $playedHole = (int) ($hole['number_hole_played'] ?? ($index + 1));
            $entryHoles[] = [
                'hole' => $playedHole,
                'par' => (int) $hole['par'],
                'stroke' => (int) $hole['stroke'],
                'score' => $existingByHole[$playedHole] ?? null,
                'shots' => null,
                'net' => null,
                'points' => null,
                'course_hole' => $holeNo,
            ];
        }

        return [
            'round' => $round,
            'player' => $player,
            'holes' => $entryHoles,
            'totals' => [
                'par' => array_sum(array_column($entryHoles, 'par')),
                'score' => null,
                'shots' => null,
                'net' => null,
                'points' => null,
            ],
            'errors' => [],
        ];
    }
}