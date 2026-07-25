<?php

namespace App\Services;

use App\Core\Database;

class CardDeletionService
{
    public function __construct(private Database $db)
    {
    }

    public function getCards(int $roundId): array
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

    public function delete(int $roundId, array $cardIds, string $updatedBy): array
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
                if ($cardId > 0) {
                    $this->db->delete('TW4_live.card', ['row_id' => $cardId]);
                }
            }

            foreach ($deletedPlayers as $playerId => $playerData) {
                $this->db->query(
                    'UPDATE TW4_base.roster
                     SET handicap = ?, status = ?, updated_by = ?
                     WHERE row_id = ?',
                    [(int) $playerData['handicap_applied'], 'active', $updatedBy, (int) $playerId]
                );
            }

            $remainingRow = $this->db->fetchOne('SELECT COUNT(*) AS total FROM TW4_live.card');
            $remainingCount = (int) ($remainingRow['total'] ?? 0);
            $this->db->query(
                'UPDATE TW4_live.round SET card_count = ?, updated_by = ? WHERE row_id = ?',
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