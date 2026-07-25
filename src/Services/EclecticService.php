<?php

namespace App\Services;

use App\Core\Database;

class EclecticService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function ensureEclecticTables(): void
    {
        $this->db->query(
            "CREATE DATABASE IF NOT EXISTS TW4_holding
             CHARACTER SET utf8mb4
             COLLATE utf8mb4_general_ci"
        );

        $tableDdl = "
            CREATE TABLE IF NOT EXISTS %s.eclectic_scores (
                row_id INT NOT NULL AUTO_INCREMENT,
                ident_eclectic VARCHAR(16) NOT NULL,
                season_year CHAR(5) NOT NULL,
                row_id_player INT NOT NULL,
                number_round_movement INT NOT NULL DEFAULT 0,
                score_total INT NOT NULL DEFAULT 0,
                score_hole_1 INT NOT NULL DEFAULT 0,
                score_hole_2 INT NOT NULL DEFAULT 0,
                score_hole_3 INT NOT NULL DEFAULT 0,
                score_hole_4 INT NOT NULL DEFAULT 0,
                score_hole_5 INT NOT NULL DEFAULT 0,
                score_hole_6 INT NOT NULL DEFAULT 0,
                score_hole_7 INT NOT NULL DEFAULT 0,
                score_hole_8 INT NOT NULL DEFAULT 0,
                score_hole_9 INT NOT NULL DEFAULT 0,
                updated_by VARCHAR(100) NOT NULL,
                updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (row_id),
                UNIQUE KEY uk_eclectic_scores_season_ident_player (season_year, ident_eclectic, row_id_player),
                KEY idx_eclectic_scores_player (row_id_player),
                KEY idx_eclectic_scores_season (season_year),
                KEY idx_eclectic_scores_ident (ident_eclectic)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";

        $this->db->query(sprintf($tableDdl, 'TW4_live'));
        $this->db->query(sprintf($tableDdl, 'TW4_holding'));

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS TW4_history.eclectic_scores (
                row_id INT NOT NULL AUTO_INCREMENT,
                ident_eclectic VARCHAR(16) NOT NULL,
                season_year CHAR(5) NOT NULL,
                row_id_player INT NOT NULL,
                number_round_movement INT NOT NULL DEFAULT 0,
                score_total INT NOT NULL DEFAULT 0,
                score_hole_1 INT NOT NULL DEFAULT 0,
                score_hole_2 INT NOT NULL DEFAULT 0,
                score_hole_3 INT NOT NULL DEFAULT 0,
                score_hole_4 INT NOT NULL DEFAULT 0,
                score_hole_5 INT NOT NULL DEFAULT 0,
                score_hole_6 INT NOT NULL DEFAULT 0,
                score_hole_7 INT NOT NULL DEFAULT 0,
                score_hole_8 INT NOT NULL DEFAULT 0,
                score_hole_9 INT NOT NULL DEFAULT 0,
                updated_by VARCHAR(100) NOT NULL,
                updated_ts TIMESTAMP NULL DEFAULT NULL,
                hist_updated_by VARCHAR(100) NOT NULL,
                hist_updated_ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (row_id),
                UNIQUE KEY uk_history_eclectic_scores_movement_ident_player (ident_eclectic, season_year, number_round_movement, row_id_player),
                KEY idx_history_eclectic_scores_movement_ident (ident_eclectic, season_year, number_round_movement),
                KEY idx_history_eclectic_scores_player (row_id_player)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }

    public function stageForRoundStart(string $seasonYear, string $updatedBy): void
    {
        $this->db->query('DELETE FROM TW4_holding.eclectic_scores');
        $this->db->query(
            'INSERT INTO TW4_holding.eclectic_scores
                (ident_eclectic, season_year, row_id_player, number_round_movement,
                 score_total, score_hole_1, score_hole_2, score_hole_3, score_hole_4,
                 score_hole_5, score_hole_6, score_hole_7, score_hole_8, score_hole_9,
                 updated_by)
             SELECT ident_eclectic, season_year, row_id_player, number_round_movement,
                    score_total, score_hole_1, score_hole_2, score_hole_3, score_hole_4,
                    score_hole_5, score_hole_6, score_hole_7, score_hole_8, score_hole_9,
                    ?
             FROM TW4_live.eclectic_scores
             WHERE season_year = ?',
            [$updatedBy, $seasonYear]
        );
        $this->db->query('DELETE FROM TW4_live.eclectic_scores');
    }

    public function refreshForFinish(int $roundId, string $seasonYear, int $numberRound, string $updatedBy, array $eclecticContext): void
    {
        $idents = $this->getEclecticIdentsForRound($roundId, $eclecticContext);

        $holdingRows = $this->db->fetchAll(
            'SELECT ident_eclectic, season_year, row_id_player, number_round_movement,
                    score_total, score_hole_1, score_hole_2, score_hole_3, score_hole_4,
                    score_hole_5, score_hole_6, score_hole_7, score_hole_8, score_hole_9,
                    updated_by
             FROM TW4_holding.eclectic_scores
             WHERE season_year = ?',
            [$seasonYear]
        );

        $cardRows = $this->db->fetchAll(
            'SELECT c.row_id_player, cbh.hole, cbh.score
             FROM TW4_live.card c
             INNER JOIN TW4_live.card_by_hole cbh ON cbh.row_id_card = c.row_id
             ORDER BY c.row_id_player, cbh.hole'
        );

        $holdingByKey = [];
        foreach ($holdingRows as $row) {
            $ident = $this->normalizeEclecticIdent((string) ($row['ident_eclectic'] ?? ''));
            $playerId = (int) ($row['row_id_player'] ?? 0);
            if ($ident === '' || $playerId < 1) {
                continue;
            }
            $row['ident_eclectic'] = $ident;
            $holdingByKey[$ident . '|' . $playerId] = $row;
        }

        $scoresByPlayer = [];
        foreach ($cardRows as $row) {
            $playerId = (int) ($row['row_id_player'] ?? 0);
            $hole = (int) ($row['hole'] ?? 0);
            $score = (int) ($row['score'] ?? 0);
            if ($playerId > 0 && $hole >= 1 && $hole <= 9 && $score > 0) {
                $scoresByPlayer[$playerId][$hole] = $score;
            }
        }

        $computed = [];
        foreach ($holdingByKey as $key => $row) {
            $computed[$key] = [
                'ident_eclectic' => (string) $row['ident_eclectic'],
                'season_year' => $seasonYear,
                'row_id_player' => (int) $row['row_id_player'],
                'number_round_movement' => (int) ($row['number_round_movement'] ?? 0),
                'score_total' => (int) ($row['score_total'] ?? 0),
                'score_hole_1' => (int) ($row['score_hole_1'] ?? 0),
                'score_hole_2' => (int) ($row['score_hole_2'] ?? 0),
                'score_hole_3' => (int) ($row['score_hole_3'] ?? 0),
                'score_hole_4' => (int) ($row['score_hole_4'] ?? 0),
                'score_hole_5' => (int) ($row['score_hole_5'] ?? 0),
                'score_hole_6' => (int) ($row['score_hole_6'] ?? 0),
                'score_hole_7' => (int) ($row['score_hole_7'] ?? 0),
                'score_hole_8' => (int) ($row['score_hole_8'] ?? 0),
                'score_hole_9' => (int) ($row['score_hole_9'] ?? 0),
                'updated_by' => $updatedBy,
            ];
        }

        foreach ($idents as $ident) {
            $ident = $this->normalizeEclecticIdent($ident);
            if ($ident === '') {
                continue;
            }

            foreach ($scoresByPlayer as $playerId => $scores) {
                if (count($scores) < 9) {
                    continue;
                }

                $key = $ident . '|' . $playerId;
                $existing = $holdingByKey[$key] ?? null;

                $mergedHoles = [];
                $moved = $existing === null;

                for ($i = 1; $i <= 9; $i++) {
                    $newScore = (int) ($scores[$i] ?? 0);
                    if ($newScore < 1) {
                        $newScore = 0;
                    }

                    $oldScore = $existing === null
                        ? 0
                        : (int) ($existing['score_hole_' . $i] ?? 0);

                    if ($oldScore <= 0) {
                        $merged = $newScore;
                    } elseif ($newScore > 0 && $newScore < $oldScore) {
                        $merged = $newScore;
                        $moved = true;
                    } else {
                        $merged = $oldScore;
                    }

                    if ($existing !== null && !$moved && $merged !== $oldScore) {
                        $moved = true;
                    }

                    $mergedHoles[$i] = $merged;
                }

                $newTotal = array_sum($mergedHoles);
                $oldTotal = $existing === null ? 0 : (int) ($existing['score_total'] ?? 0);
                if ($existing !== null && $newTotal < $oldTotal) {
                    $moved = true;
                }

                $movementRound = $existing === null
                    ? $numberRound
                    : (int) ($existing['number_round_movement'] ?? 0);
                if ($moved) {
                    $movementRound = $numberRound;
                }

                $computed[$key] = [
                    'ident_eclectic' => $ident,
                    'season_year' => $seasonYear,
                    'row_id_player' => (int) $playerId,
                    'number_round_movement' => $movementRound,
                    'score_total' => $newTotal,
                    'score_hole_1' => $mergedHoles[1],
                    'score_hole_2' => $mergedHoles[2],
                    'score_hole_3' => $mergedHoles[3],
                    'score_hole_4' => $mergedHoles[4],
                    'score_hole_5' => $mergedHoles[5],
                    'score_hole_6' => $mergedHoles[6],
                    'score_hole_7' => $mergedHoles[7],
                    'score_hole_8' => $mergedHoles[8],
                    'score_hole_9' => $mergedHoles[9],
                    'updated_by' => $updatedBy,
                ];
            }
        }

        $this->db->query('DELETE FROM TW4_live.eclectic_scores');

        ksort($computed);
        foreach ($computed as $row) {
            $this->db->insert('TW4_live.eclectic_scores', $row);
        }
    }

    public function buildRoundContext(int $roundId): array
    {
        $row = $this->db->fetchOne(
            'SELECT cp.name_course, cp.ident_eclectic
             FROM TW4_live.round r
             LEFT JOIN TW4_base.course_played cp ON cp.row_id = r.course_played_id
             WHERE r.row_id = ?
             LIMIT 1',
            [$roundId]
        );

        $playedCourseName = trim((string) ($row['name_course'] ?? ''));
        $courseIdent = trim((string) ($row['ident_eclectic'] ?? ''));
        $includeEclectic = $this->shouldIncludeEclectic($courseIdent);

        $combinedName = $courseIdent;
        if ($combinedName === '') {
            $combinedName = 'Eclectic';
        }

        $playedSlug = $this->slugifyReportName($playedCourseName === '' ? 'Course' : $playedCourseName);
        $combinedSlug = $this->slugifyReportName($combinedName);

        return [
            'include_eclectic' => $includeEclectic,
            'configured_ident_eclectic' => '',
            'played_course_name' => $playedCourseName,
            'combined_name' => $combinedName,
            'course_report_files' => [
                '41_Eclectic_' . $playedSlug . '.html',
            ],
            'combined_report_filename' => '49_Eclectic_' . $combinedSlug . '.html',
        ];
    }

    public function persistRoundContext(string $seasonYear, int $numberRound, array $context, string $updatedBy): void
    {
        $this->db->query(
            'DELETE FROM TW4_history.round_eclectic_context
             WHERE season_year = ? AND number_round = ?',
            [$seasonYear, $numberRound]
        );

        $this->db->insert('TW4_history.round_eclectic_context', [
            'season_year' => $seasonYear,
            'number_round' => $numberRound,
            'include_eclectic' => (int) ((bool) ($context['include_eclectic'] ?? false)),
            'configured_ident_eclectic' => (string) ($context['configured_ident_eclectic'] ?? ''),
            'played_course_name' => (string) ($context['played_course_name'] ?? ''),
            'combined_name' => (string) ($context['combined_name'] ?? ''),
            'course_report_files_json' => json_encode($context['course_report_files'] ?? []),
            'combined_report_filename' => (string) ($context['combined_report_filename'] ?? ''),
            'updated_by' => $updatedBy,
            'updated_ts' => date('Y-m-d H:i:s'),
            'hist_updated_by' => $updatedBy,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function getEclecticIdentsForRound(int $roundId, array $eclecticContext): array
    {
        $idents = [
            trim((string) ($eclecticContext['played_course_name'] ?? '')),
            trim((string) ($eclecticContext['combined_name'] ?? '')),
        ];

        if ($idents[0] === '' && $idents[1] === '') {
            $row = $this->db->fetchOne(
                'SELECT cp.name_course, cp.ident_eclectic
                 FROM TW4_live.round r
                 LEFT JOIN TW4_base.course_played cp ON cp.row_id = r.course_played_id
                 WHERE r.row_id = ?
                 LIMIT 1',
                [$roundId]
            );

            $idents = [
                trim((string) ($row['name_course'] ?? '')),
                trim((string) ($row['ident_eclectic'] ?? '')),
            ];
        }

        return $this->dedupeEclecticIdentsCaseInsensitive($idents);
    }

    /**
     * @param array<int, string> $idents
     * @return array<int, string>
     */
    private function dedupeEclecticIdentsCaseInsensitive(array $idents): array
    {
        $result = [];
        $seen = [];

        foreach ($idents as $ident) {
            $value = $this->normalizeEclecticIdent((string) $ident);
            if ($value === '') {
                continue;
            }

            $key = $value;
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $value;
        }

        return $result;
    }

    private function normalizeEclecticIdent(string $ident): string
    {
        return strtolower(trim($ident));
    }

    private function shouldIncludeEclectic(string $courseIdent): bool
    {
        $course = strtolower(trim($courseIdent));
        if ($course === '') {
            return false;
        }

        if (in_array($course, ['none', 'nil', 'n/a', 'na', 'off'], true)) {
            return false;
        }

        return true;
    }

    private function slugifyReportName(string $name): string
    {
        $value = trim($name);
        if ($value === '') {
            return 'Course';
        }

        $value = preg_replace('/[^A-Za-z0-9]+/', '_', $value) ?? $value;
        $value = trim($value, '_');
        return $value === '' ? 'Course' : $value;
    }
}