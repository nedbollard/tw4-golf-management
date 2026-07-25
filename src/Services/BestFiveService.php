<?php

namespace App\Services;

use App\Core\Database;

class BestFiveService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function ensureBestFiveTables(): void
    {
        $this->db->query(
            "CREATE DATABASE IF NOT EXISTS TW4_holding
             CHARACTER SET utf8mb4
             COLLATE utf8mb4_general_ci"
        );

        $tableDdl = "
            CREATE TABLE IF NOT EXISTS %s.best_five_scores (
                row_id INT NOT NULL AUTO_INCREMENT,
                season_year CHAR(5) NOT NULL,
                row_id_player INT NOT NULL,
                number_round_movement INT NOT NULL DEFAULT 0,
                points_total INT NOT NULL DEFAULT 0,
                points_best_1 INT NOT NULL DEFAULT 0,
                points_best_2 INT NOT NULL DEFAULT 0,
                points_best_3 INT NOT NULL DEFAULT 0,
                points_best_4 INT NOT NULL DEFAULT 0,
                points_best_5 INT NOT NULL DEFAULT 0,
                round_best_1 INT NOT NULL DEFAULT 0,
                round_best_2 INT NOT NULL DEFAULT 0,
                round_best_3 INT NOT NULL DEFAULT 0,
                round_best_4 INT NOT NULL DEFAULT 0,
                round_best_5 INT NOT NULL DEFAULT 0,
                points_movement INT NOT NULL DEFAULT 0,
                updated_by VARCHAR(100) NOT NULL,
                updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (row_id),
                UNIQUE KEY uk_best_five_scores_season_player (season_year, row_id_player),
                KEY idx_best_five_scores_player (row_id_player),
                KEY idx_best_five_scores_season (season_year)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";

        $this->db->query(sprintf($tableDdl, 'TW4_live'));
        $this->db->query(sprintf($tableDdl, 'TW4_holding'));

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS TW4_history.best_five_scores (
                row_id INT NOT NULL AUTO_INCREMENT,
                season_year CHAR(5) NOT NULL,
                row_id_player INT NOT NULL,
                number_round_movement INT NOT NULL DEFAULT 0,
                points_total INT NOT NULL DEFAULT 0,
                points_best_1 INT NOT NULL DEFAULT 0,
                points_best_2 INT NOT NULL DEFAULT 0,
                points_best_3 INT NOT NULL DEFAULT 0,
                points_best_4 INT NOT NULL DEFAULT 0,
                points_best_5 INT NOT NULL DEFAULT 0,
                round_best_1 INT NOT NULL DEFAULT 0,
                round_best_2 INT NOT NULL DEFAULT 0,
                round_best_3 INT NOT NULL DEFAULT 0,
                round_best_4 INT NOT NULL DEFAULT 0,
                round_best_5 INT NOT NULL DEFAULT 0,
                points_movement INT NOT NULL DEFAULT 0,
                updated_by VARCHAR(100) NOT NULL,
                updated_ts TIMESTAMP NULL DEFAULT NULL,
                hist_updated_by VARCHAR(100) NOT NULL,
                hist_updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (row_id),
                UNIQUE KEY uk_history_best_five_scores_movement_player (season_year, number_round_movement, row_id_player),
                KEY idx_history_best_five_scores_movement (season_year, number_round_movement),
                KEY idx_history_best_five_scores_player (row_id_player)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }

    public function stageForRoundStart(string $seasonYear, string $updatedBy): void
    {
        $this->db->query('DELETE FROM TW4_holding.best_five_scores');
        $this->db->query(
            'INSERT INTO TW4_holding.best_five_scores
                (season_year, row_id_player, number_round_movement,
                 points_total, points_best_1, points_best_2, points_best_3, points_best_4, points_best_5,
                 round_best_1, round_best_2, round_best_3, round_best_4, round_best_5,
                 points_movement, updated_by)
             SELECT season_year, row_id_player, number_round_movement,
                    points_total, points_best_1, points_best_2, points_best_3, points_best_4, points_best_5,
                    round_best_1, round_best_2, round_best_3, round_best_4, round_best_5,
                    points_movement, ?
             FROM TW4_live.best_five_scores
             WHERE season_year = ?',
            [$updatedBy, $seasonYear]
        );
        $this->db->query('DELETE FROM TW4_live.best_five_scores');
    }

    public function refreshForFinish(string $seasonYear, int $numberRound, string $updatedBy): void
    {
        $holdingRows = $this->db->fetchAll(
            'SELECT season_year, row_id_player, number_round_movement,
                    points_total, points_best_1, points_best_2, points_best_3, points_best_4, points_best_5,
                    round_best_1, round_best_2, round_best_3, round_best_4, round_best_5,
                    points_movement
             FROM TW4_holding.best_five_scores
             WHERE season_year = ?',
            [$seasonYear]
        );

        $cardRows = $this->db->fetchAll(
            'SELECT row_id_player, points
             FROM TW4_live.card'
        );

        $holdingByPlayer = [];
        foreach ($holdingRows as $row) {
            $playerId = (int) ($row['row_id_player'] ?? 0);
            if ($playerId > 0) {
                $holdingByPlayer[$playerId] = $row;
            }
        }

        $cardPointsByPlayer = [];
        foreach ($cardRows as $row) {
            $playerId = (int) ($row['row_id_player'] ?? 0);
            if ($playerId > 0) {
                $cardPointsByPlayer[$playerId] = (int) ($row['points'] ?? 0);
            }
        }

        $playerIds = array_values(array_unique(array_merge(array_keys($holdingByPlayer), array_keys($cardPointsByPlayer))));
        sort($playerIds);

        $this->db->query('DELETE FROM TW4_live.best_five_scores');

        foreach ($playerIds as $playerId) {
            $holding = $holdingByPlayer[$playerId] ?? null;
            $hasCard = array_key_exists($playerId, $cardPointsByPlayer);

            if (!$hasCard && $holding === null) {
                continue;
            }

            $oldPoints = [0, 0, 0, 0, 0];
            $oldRounds = [0, 0, 0, 0, 0];
            $oldTotal = 0;
            $oldMovementRound = 0;
            $oldMovementPoints = 0;

            if ($holding !== null) {
                $oldPoints = [
                    (int) ($holding['points_best_1'] ?? 0),
                    (int) ($holding['points_best_2'] ?? 0),
                    (int) ($holding['points_best_3'] ?? 0),
                    (int) ($holding['points_best_4'] ?? 0),
                    (int) ($holding['points_best_5'] ?? 0),
                ];
                $oldRounds = [
                    (int) ($holding['round_best_1'] ?? 0),
                    (int) ($holding['round_best_2'] ?? 0),
                    (int) ($holding['round_best_3'] ?? 0),
                    (int) ($holding['round_best_4'] ?? 0),
                    (int) ($holding['round_best_5'] ?? 0),
                ];
                $oldTotal = (int) ($holding['points_total'] ?? 0);
                $oldMovementRound = (int) ($holding['number_round_movement'] ?? 0);
                $oldMovementPoints = (int) ($holding['points_movement'] ?? 0);
            }

            $newPoints = $oldPoints;
            $newRounds = $oldRounds;

            if ($hasCard) {
                [$newPoints, $newRounds] = $this->mergeBestFiveScores(
                    $oldPoints,
                    $oldRounds,
                    (int) $cardPointsByPlayer[$playerId],
                    $numberRound
                );
            }

            $newTotal = array_sum($newPoints);
            $pointsMovement = $newTotal - $oldTotal;

            $movementRound = $oldMovementRound;
            $movementPoints = $oldMovementPoints;
            if ($holding === null || $pointsMovement !== 0) {
                $movementRound = $numberRound;
                $movementPoints = $pointsMovement;
            }

            $this->db->insert('TW4_live.best_five_scores', [
                'season_year' => $seasonYear,
                'row_id_player' => $playerId,
                'number_round_movement' => $movementRound,
                'points_total' => $newTotal,
                'points_best_1' => $newPoints[0],
                'points_best_2' => $newPoints[1],
                'points_best_3' => $newPoints[2],
                'points_best_4' => $newPoints[3],
                'points_best_5' => $newPoints[4],
                'round_best_1' => $newRounds[0],
                'round_best_2' => $newRounds[1],
                'round_best_3' => $newRounds[2],
                'round_best_4' => $newRounds[3],
                'round_best_5' => $newRounds[4],
                'points_movement' => $movementPoints,
                'updated_by' => $updatedBy,
            ]);
        }
    }

    /**
     * @return array{0: array<int,int>, 1: array<int,int>}
     */
    private function mergeBestFiveScores(array $points, array $rounds, int $newPoints, int $newRound): array
    {
        $entries = [];
        for ($i = 0; $i < 5; $i++) {
            $entries[] = [
                'points' => (int) ($points[$i] ?? 0),
                'round' => (int) ($rounds[$i] ?? 0),
            ];
        }
        $entries[] = ['points' => $newPoints, 'round' => $newRound];

        usort(
            $entries,
            static fn(array $a, array $b): int =>
                ($b['points'] <=> $a['points'])
                ?: ($a['round'] <=> $b['round'])
        );

        $entries = array_slice($entries, 0, 5);

        $newBestPoints = [];
        $newBestRounds = [];
        foreach ($entries as $entry) {
            $newBestPoints[] = (int) $entry['points'];
            $newBestRounds[] = (int) $entry['round'];
        }

        while (count($newBestPoints) < 5) {
            $newBestPoints[] = 0;
            $newBestRounds[] = 0;
        }

        return [$newBestPoints, $newBestRounds];
    }
}