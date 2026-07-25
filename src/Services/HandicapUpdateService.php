<?php

namespace App\Services;

use App\Core\Database;

class HandicapUpdateService
{
    public function __construct(private Database $db)
    {
    }

    public function applyForFinishedRound(string $updatedBy, string $seasonYear, int $numberRound): void
    {
        $method = $this->getConfiguredMethod();

        if ($method === 'none') {
            $this->db->query(
                'UPDATE TW4_live.card
                 SET handicap_updated = handicap_applied, updated_by = ?
                 WHERE handicap_updated IS NULL OR handicap_updated <> handicap_applied',
                [$updatedBy]
            );
            $this->syncRoster($updatedBy, $seasonYear, $numberRound);
            return;
        }

        $rows = $this->db->fetchAll(
            'SELECT c.row_id AS card_row_id, c.handicap_applied,
                    COALESCE(SUM(cbh.points), 0) AS pts_scored,
                    COALESCE(SUM(CASE WHEN cbh.points = 0 THEN 1 ELSE cbh.points END), 0) AS pts_adjusted
             FROM TW4_live.card c
             LEFT JOIN TW4_live.card_by_hole cbh ON cbh.row_id_card = c.row_id
             GROUP BY c.row_id, c.handicap_applied'
        );

        foreach ($rows as $row) {
            $points = (int) ($row['pts_adjusted'] ?? 0);
            $change = $method === 'modern'
                ? $this->modernChange($points)
                : $this->legacyChange($points);
            $updatedHandicap = max(0, min(54, (int) ($row['handicap_applied'] ?? 0) + $change));

            $this->db->query(
                'UPDATE TW4_live.card SET handicap_updated = ?, updated_by = ? WHERE row_id = ?',
                [$updatedHandicap, $updatedBy, (int) ($row['card_row_id'] ?? 0)]
            );
        }

        $this->syncRoster($updatedBy, $seasonYear, $numberRound);
    }

    private function getConfiguredMethod(): string
    {
        $row = $this->db->fetchOne(
            'SELECT LOWER(TRIM(config_value_string)) AS method
             FROM TW4_base.config_application WHERE config_name = ? LIMIT 1',
            ['handicap_method']
        );
        $value = (string) ($row['method'] ?? 'legacy');
        if (in_array($value, ['none', 'n'], true)) {
            return 'none';
        }
        return in_array($value, ['modern', 'm'], true) ? 'modern' : 'legacy';
    }

    private function modernChange(int $points): int
    {
        if ($points < 16) {
            return 16 - $points;
        }
        return $points > 20 ? 20 - $points : 0;
    }

    private function legacyChange(int $points): int
    {
        return match (true) {
            $points >= 9 && $points <= 12 => 2,
            $points >= 13 && $points <= 16 => 1,
            $points >= 17 && $points <= 21 => 0,
            $points >= 22 && $points <= 23 => -1,
            $points >= 24 && $points <= 25 => -2,
            $points >= 26 && $points <= 27 => -3,
            default => -4,
        };
    }

    private function syncRoster(string $updatedBy, string $seasonYear, int $numberRound): void
    {
        $changedRows = $this->db->fetchAll(
            'SELECT r.row_id AS row_id_player, r.handicap AS handicap_previous,
                    c.handicap_updated AS handicap_new,
                    COALESCE(SUM(cbh.points), 0) AS points_scored,
                    COALESCE(SUM(CASE WHEN cbh.points = 0 THEN 1 ELSE cbh.points END), 0) AS points_effective
             FROM TW4_base.roster r
             INNER JOIN TW4_live.card c ON c.row_id_player = r.row_id
             LEFT JOIN TW4_live.card_by_hole cbh ON cbh.row_id_card = c.row_id
             WHERE c.handicap_updated IS NOT NULL
               AND (r.handicap IS NULL OR r.handicap <> c.handicap_updated)
             GROUP BY r.row_id, r.handicap, c.handicap_updated'
        );

        $this->db->query(
            'UPDATE TW4_base.roster r
             INNER JOIN TW4_live.card c ON c.row_id_player = r.row_id
             SET r.handicap = c.handicap_updated, r.updated_by = ?
             WHERE c.handicap_updated IS NOT NULL
               AND (r.handicap IS NULL OR r.handicap <> c.handicap_updated)',
            [$updatedBy]
        );

        foreach ($changedRows as $row) {
            $this->db->insert('TW4_base.handicap_audit', [
                'row_id_player' => (int) ($row['row_id_player'] ?? 0),
                'handicap_previous' => (int) ($row['handicap_previous'] ?? 0),
                'handicap_new' => (int) ($row['handicap_new'] ?? 0),
                'handicap_source' => 'card_scoring',
                'season_year' => $seasonYear,
                'number_round' => $numberRound,
                'points_scored' => (int) ($row['points_scored'] ?? 0),
                'points_effective' => (int) ($row['points_effective'] ?? 0),
                'reason' => 'finish_round_card_scoring',
                'updated_by' => $updatedBy,
            ]);
        }
    }
}