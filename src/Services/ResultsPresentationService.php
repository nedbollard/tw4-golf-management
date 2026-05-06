<?php

namespace App\Services;

use App\Core\Database;

class ResultsPresentationService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function buildPresentationData(int $roundId): array
    {
        $cards = $this->db->fetchAll(
            'SELECT c.row_id AS row_id_card,
                    c.row_id_player,
                    r.player_identifier,
                    r.first_name,
                    r.last_name,
                    r.alias,
                    c.points,
                    c.score,
                    c.handicap_applied
             FROM TW4_live.card c
             INNER JOIN TW4_base.roster r ON r.row_id = c.row_id_player
             ORDER BY r.last_name, r.first_name'
        );

        if (empty($cards)) {
            return [
                'leaderboard' => [],
                'prizes' => [1 => 0, 2 => 0, 3 => 0],
                'closest_to_pin_options' => [
                    ['identifier' => 'not taker', 'label' => 'not taker'],
                ],
                'fee_entry' => 0,
                'prize_pool' => 0,
            ];
        }

        $byCard = [];
        foreach ($cards as $card) {
            $cardId = (int) $card['row_id_card'];
            $identifier = (string) ($card['player_identifier'] ?? '');
            $displayName = trim((string) (($card['first_name'] ?? '') . ' ' . ($card['last_name'] ?? '')));
            if ($displayName === '') {
                $displayName = $identifier;
            }

            $byCard[$cardId] = [
                'row_id_card' => $cardId,
                'row_id_player' => (int) $card['row_id_player'],
                'player_identifier' => $identifier,
                'display_name' => $displayName,
                'alias' => (string) ($card['alias'] ?? ''),
                'points' => (int) $card['points'],
                'score' => (int) $card['score'],
                'handicap' => (int) ($card['handicap_applied'] ?? 0),
                'countback1' => 0,
                'countback3' => 0,
                'countback6' => 0,
                'coin_toss' => $this->coinTossValue($roundId, $cardId, $identifier),
                'countback_decision' => 'n/a',
                'position' => 0,
                'twos_count' => 0,
                'prize' => 0,
            ];
        }

        $holes = $this->db->fetchAll(
            'SELECT cbh.row_id_card, cbh.hole, cbh.score, cbh.points
             FROM TW4_live.card_by_hole cbh
             INNER JOIN TW4_live.card c ON c.row_id = cbh.row_id_card
             ORDER BY cbh.row_id_card, cbh.hole'
        );

        foreach ($holes as $hole) {
            $cardId = (int) $hole['row_id_card'];
            if (!isset($byCard[$cardId])) {
                continue;
            }

            $holeNo = (int) $hole['hole'];
            $points = (int) $hole['points'];
            $score = (int) $hole['score'];

            if ($holeNo === 9) {
                $byCard[$cardId]['countback1'] += $points;
            }
            if ($holeNo > 6) {
                $byCard[$cardId]['countback3'] += $points;
            }
            if ($holeNo > 3) {
                $byCard[$cardId]['countback6'] += $points;
            }
            if ($score === 2) {
                $byCard[$cardId]['twos_count']++;
            }
        }

        $leaderboard = array_values($byCard);

        usort($leaderboard, static function (array $a, array $b): int {
            $cmp = $b['points'] <=> $a['points'];
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = $b['countback1'] <=> $a['countback1'];
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = $b['countback3'] <=> $a['countback3'];
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = $b['countback6'] <=> $a['countback6'];
            if ($cmp !== 0) {
                return $cmp;
            }

            return $b['coin_toss'] <=> $a['coin_toss'];
        });

        $previous = null;
        foreach ($leaderboard as $idx => &$entry) {
            $entry['position'] = $idx + 1;

            if ($idx === 0 || $previous === null || $entry['points'] !== $previous['points']) {
                $entry['countback_decision'] = 'n/a';
            } elseif ($entry['countback1'] !== $previous['countback1']) {
                $entry['countback_decision'] = 'last 1';
            } elseif ($entry['countback3'] !== $previous['countback3']) {
                $entry['countback_decision'] = 'last 3';
            } elseif ($entry['countback6'] !== $previous['countback6']) {
                $entry['countback_decision'] = 'last 6';
            } else {
                $entry['countback_decision'] = 'coin toss';
            }

            $previous = $entry;
        }
        unset($entry);

        $prizeData = $this->calculatePrizes(count($leaderboard));
        $prizes = $prizeData['prizes'];

        foreach ($leaderboard as &$entry) {
            $entry['prize'] = $prizes[$entry['position']] ?? 0;
        }
        unset($entry);

        $options = [
            ['identifier' => 'not taker', 'label' => 'not taker'],
        ];
        foreach ($leaderboard as $entry) {
            $identifier = (string) $entry['player_identifier'];
            $label = (string) ($entry['display_name'] ?? $identifier);
            $knownIdentifiers = array_column($options, 'identifier');
            if ($identifier !== '' && !in_array($identifier, $knownIdentifiers, true)) {
                $options[] = [
                    'identifier' => $identifier,
                    'label' => $label,
                ];
            }
        }

        return [
            'leaderboard' => $leaderboard,
            'prizes' => $prizes,
            'closest_to_pin_options' => $options,
            'fee_entry' => $prizeData['fee_entry'],
            'prize_pool' => $prizeData['prize_pool'],
        ];
    }

    public function saveResults(int $roundId, array $presentationData, string $closestToPinIdentifier, string $updatedBy): void
    {
        $leaderboard = $presentationData['leaderboard'] ?? [];
        $prizes = $presentationData['prizes'] ?? [1 => 0, 2 => 0, 3 => 0];

        $this->db->beginTransaction();

        try {
            $this->db->query('DELETE FROM TW4_live.results');

            foreach ([1, 2, 3] as $place) {
                $entry = $leaderboard[$place - 1] ?? null;
                if ($entry === null) {
                    continue;
                }

                $this->db->insert('TW4_live.results', [
                    'type_result' => 'Place',
                    'number_result' => $place,
                    'player_identifier' => (string) $entry['player_identifier'],
                    'value_result' => (int) ($prizes[$place] ?? 0),
                    'updated_by' => $updatedBy,
                ]);
            }

            $this->db->insert('TW4_live.results', [
                'type_result' => 'C_P',
                'number_result' => 1,
                'player_identifier' => $closestToPinIdentifier,
                'value_result' => 1,
                'updated_by' => $updatedBy,
            ]);

            foreach ($leaderboard as $entry) {
                $twosCount = (int) ($entry['twos_count'] ?? 0);
                if ($twosCount < 1) {
                    continue;
                }

                $this->db->insert('TW4_live.results', [
                    'type_result' => 'Twos',
                    'number_result' => 1,
                    'player_identifier' => (string) $entry['player_identifier'],
                    'value_result' => $twosCount,
                    'updated_by' => $updatedBy,
                ]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function calculatePrizes(int $cardCount): array
    {
        $feeEntry = $this->getFeeEntry();
        $prizePool = $cardCount * $feeEntry;

        $prizeUnit = $prizePool / 6;
        $p1 = (int) round($prizeUnit * 3);
        $p2 = (int) round($prizeUnit * 2);
        $p3 = (int) ($prizePool - ($p1 + $p2));

        return [
            'fee_entry' => $feeEntry,
            'prize_pool' => $prizePool,
            'prizes' => [
                1 => $p1,
                2 => $p2,
                3 => $p3,
            ],
        ];
    }

    private function getFeeEntry(): int
    {
        $row = $this->db->fetchOne(
            'SELECT COALESCE(config_value_int, CAST(config_value_string AS SIGNED)) AS fee_entry
             FROM TW4_base.config_application
             WHERE config_name = ?',
            ['entry_fee']
        );

        if (!$row) {
            throw new \RuntimeException('Missing entry_fee in config_application.');
        }

        return max(0, (int) ($row['fee_entry'] ?? 0));
    }

    private function coinTossValue(int $roundId, int $cardId, string $identifier): int
    {
        return (int) sprintf('%u', crc32($roundId . ':' . $cardId . ':' . $identifier));
    }
}
