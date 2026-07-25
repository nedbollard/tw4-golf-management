<?php

namespace App\Services;

use App\Core\Database;

class CardPersistenceService
{
    public function __construct(private Database $db)
    {
    }

    public function save(int $roundId, int $playerId, array $entryData, string $username): void
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
}