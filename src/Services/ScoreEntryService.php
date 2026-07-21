<?php

namespace App\Services;

use App\Core\Database;

class ScoreEntryService
{
    private Database $db;
    private RoundLockService $lockService;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->lockService = new RoundLockService($db);
    }

    public function getSelectablePlayers(int $roundId): array
    {
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
                      WHERE r.status = "active"
             ORDER BY r.last_name, r.first_name'
        );
    }

    public function assertEntryLock(int $roundId, int $staffId): bool
    {
        return $this->lockService->assertLockHeld($roundId, $staffId);
    }

    public function buildEntryData(int $roundId, int $playerId): ?array
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
             LIMIT 9',
            [$genderCode, (int) $round['course_played_id']]
        );

        if (count($holes) !== 9) {
            $gender = strtolower((string) ($player['gender'] ?? 'male')) === 'female' ? 'Female' : 'Male';
            throw new \RuntimeException(
                "Course hole configuration incomplete for player {$player['player_identifier']}: "
                . "Expected 9 holes for $gender, found " . count($holes) . "."
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

    public function calculateCard(array $entryData, array $postedScores): array
    {
        $errors = [];
        $handicap = max(0, (int) ($entryData['player']['handicap'] ?? 0));

        $totalScore = 0;
        $totalShots = 0;
        $totalNet = 0;
        $totalPoints = 0;

        foreach ($entryData['holes'] as $idx => &$hole) {
            $holeNo = $idx + 1;
            $raw = $postedScores[$holeNo] ?? '';
            $raw = is_string($raw) ? trim($raw) : $raw;

            if ($raw === '' || $raw === null) {
                $errors[] = "Hole {$holeNo}: score is required.";
                $hole['score'] = null;
                $hole['shots'] = null;
                $hole['net'] = null;
                $hole['points'] = null;
                continue;
            }

            $score = null;
            if (is_string($raw) && strcasecmp($raw, 'x') === 0) {
                $score = 10;
            } elseif (is_string($raw) && strlen($raw) === 1 && ctype_digit($raw) && $raw !== '0') {
                $score = (int) $raw;
            }

            if ($score === null) {
                $errors[] = "Hole {$holeNo}: score must be 1-9 or X.";
                $hole['score'] = $score;
                $hole['shots'] = null;
                $hole['net'] = null;
                $hole['points'] = null;
                continue;
            }

            $strokeIndex = (int) ($hole['stroke'] ?? 18);
            $shots = intdiv($handicap, 18);
            $shots += ($strokeIndex <= ($handicap % 18)) ? 1 : 0;

            $net = $score - $shots;
            $points = max(0, 2 + ((int) $hole['par'] - $net));

            $hole['score'] = $score;
            $hole['shots'] = $shots;
            $hole['net'] = $net;
            $hole['points'] = $points;

            $totalScore += $score;
            $totalShots += $shots;
            $totalNet += $net;
            $totalPoints += $points;
        }
        unset($hole);

        $entryData['errors'] = $errors;
        $entryData['totals']['score'] = empty($errors) ? $totalScore : null;
        $entryData['totals']['shots'] = empty($errors) ? $totalShots : null;
        $entryData['totals']['net'] = empty($errors) ? $totalNet : null;
        $entryData['totals']['points'] = empty($errors) ? $totalPoints : null;

        return $entryData;
    }

    public function saveCard(int $roundId, int $playerId, array $entryData, string $username): void
    {
        if (!empty($entryData['errors'])) {
            throw new \RuntimeException('Cannot save card with validation errors.');
        }

        $handicap = (int) ($entryData['player']['handicap'] ?? 0);
        $totalScore = (int) ($entryData['totals']['score'] ?? 0);
        $totalPoints = (int) ($entryData['totals']['points'] ?? 0);

        $this->db->beginTransaction();
        try {
            $card = $this->db->fetchOne(
                'SELECT row_id, handicap_updated
                 FROM TW4_live.card
                 WHERE row_id_player = ?',
                [$playerId]
            );

            if ($card) {
                $cardId = (int) $card['row_id'];
                $this->db->query(
                    'UPDATE TW4_live.card
                     SET handicap_applied = ?, score = ?, points = ?, handicap_updated = ?, updated_by = ?
                     WHERE row_id = ?',
                    [$handicap, $totalScore, $totalPoints, $handicap, $username, $cardId]
                );
            } else {
                $cardId = $this->db->insert('TW4_live.card', [
                    'row_id_player' => $playerId,
                    'handicap_applied' => $handicap,
                    'handicap_updated' => $handicap,
                    'score' => $totalScore,
                    'points' => $totalPoints,
                    'updated_by' => $username,
                ]);
            }

            foreach ($entryData['holes'] as $hole) {
                $existing = $this->db->fetchOne(
                    'SELECT row_id
                     FROM TW4_live.card_by_hole
                     WHERE row_id_card = ? AND hole = ?',
                    [$cardId, (int) $hole['hole']]
                );

                if ($existing) {
                    $this->db->query(
                        'UPDATE TW4_live.card_by_hole
                         SET score = ?, shots = ?, points = ?, updated_by = ?
                         WHERE row_id = ?',
                        [
                            (int) $hole['score'],
                            (int) $hole['shots'],
                            (int) $hole['points'],
                            $username,
                            (int) $existing['row_id'],
                        ]
                    );
                } else {
                    $this->db->insert('TW4_live.card_by_hole', [
                        'row_id_card' => $cardId,
                        'hole' => (int) $hole['hole'],
                        'score' => (int) $hole['score'],
                        'shots' => (int) $hole['shots'],
                        'points' => (int) $hole['points'],
                        'updated_by' => $username,
                    ]);
                }
            }

            $countRow = $this->db->fetchOne(
                'SELECT COUNT(*) AS card_count
                 FROM TW4_live.card'
            );

            $this->db->query(
                'UPDATE TW4_live.round
                 SET card_count = ?, updated_by = ?
                 WHERE row_id = ?',
                [(int) ($countRow['card_count'] ?? 0), $username, $roundId]
            );

            $this->db->query(
                'UPDATE TW4_base.roster
                 SET status = ?, updated_by = ?
                 WHERE row_id = ? AND status = ?',
                ['scored', $username, $playerId, 'active']
            );

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getCardsForDeletion(int $roundId): array
    {
        $round = $this->db->fetchOne(
            'SELECT row_id, workflow_step
             FROM TW4_live.round
             WHERE row_id = ?
             LIMIT 1',
            [$roundId]
        );

        if (!$round || (string) ($round['workflow_step'] ?? '') !== 'card_entry_open') {
            throw new \RuntimeException('Cards can only be deleted while the round is open for card entry.');
        }

        return $this->db->fetchAll(
            'SELECT c.row_id AS card_id,
                    c.row_id_player,
                    c.handicap_applied,
                    c.handicap_updated,
                    c.score,
                    c.points,
                    c.updated_ts,
                    COALESCE(NULLIF(TRIM(r.alias), ""), r.player_identifier, CONCAT("player_", r.row_id)) AS display_player,
                    r.player_identifier
             FROM TW4_live.card c
             INNER JOIN TW4_base.roster r ON r.row_id = c.row_id_player
             ORDER BY c.row_id ASC'
        );
    }

    public function deleteCards(int $roundId, array $cardIds, string $updatedBy): array
    {
        $cleanCardIds = array_values(array_unique(array_filter(array_map(
            static fn(mixed $value): int => (int) $value,
            $cardIds
        ), static fn(int $value): bool => $value > 0)));

        if (empty($cleanCardIds)) {
            throw new \RuntimeException('Please select at least one card to delete.');
        }

        $placeholders = implode(',', array_fill(0, count($cleanCardIds), '?'));

        $this->db->beginTransaction();
        try {
            $cards = $this->db->fetchAll(
                'SELECT c.row_id AS card_id,
                        c.row_id_player,
                        c.handicap_applied,
                        c.score,
                        c.points,
                        COALESCE(NULLIF(TRIM(r.alias), ""), r.player_identifier, CONCAT("player_", r.row_id)) AS display_player,
                        r.player_identifier
                 FROM TW4_live.card c
                 INNER JOIN TW4_base.roster r ON r.row_id = c.row_id_player
                                 WHERE c.row_id IN (' . $placeholders . ')
                 ORDER BY c.row_id ASC
                 FOR UPDATE',
                                $cleanCardIds
            );

            if (count($cards) !== count($cleanCardIds)) {
                throw new \RuntimeException('One or more selected cards could not be found.');
            }

            $deletedPlayers = [];
            foreach ($cards as $card) {
                $playerId = (int) ($card['row_id_player'] ?? 0);
                if ($playerId < 1) {
                    continue;
                }

                $deletedPlayers[$playerId] = [
                    'display_player' => (string) ($card['display_player'] ?? 'player_' . $playerId),
                    'player_identifier' => (string) ($card['player_identifier'] ?? ''),
                    'handicap_applied' => (int) ($card['handicap_applied'] ?? 0),
                ];
            }

            foreach ($cards as $card) {
                $cardId = (int) ($card['card_id'] ?? 0);
                if ($cardId < 1) {
                    continue;
                }

                $this->db->delete('TW4_live.card', ['row_id' => $cardId]);
            }

            foreach ($deletedPlayers as $playerId => $playerData) {
                $this->db->query(
                    'UPDATE TW4_base.roster
                     SET handicap = ?,
                         status = ?,
                         updated_by = ?
                     WHERE row_id = ?',
                    [
                        (int) $playerData['handicap_applied'],
                        'active',
                        $updatedBy,
                        (int) $playerId,
                    ]
                );
            }

            $remainingRow = $this->db->fetchOne('SELECT COUNT(*) AS total FROM TW4_live.card');
            $remainingCount = (int) ($remainingRow['total'] ?? 0);

            $this->db->query(
                'UPDATE TW4_live.round
                 SET card_count = ?,
                     updated_by = ?
                 WHERE row_id = ?',
                [$remainingCount, $updatedBy, $roundId]
            );

            $this->db->commit();

            return [
                'deleted_cards' => $cards,
                'deleted_players' => array_values($deletedPlayers),
                'remaining_card_count' => $remainingCount,
            ];
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
