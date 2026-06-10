<?php

namespace App\Services;

use App\Core\Database;

class ResultsArchiveService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getArchiveTree(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT hr.season_year,
                    hr.number_round,
                    hr.round_date,
                    hr.card_count,
                    cp.name_course,
                    cp.name_club
             FROM TW4_history.round hr
             LEFT JOIN TW4_base.course_played cp ON cp.row_id = hr.course_played_id
             ORDER BY hr.season_year DESC, hr.number_round DESC'
        );

        $tree = [];
        foreach ($rows as $row) {
            $season = (string) ($row['season_year'] ?? 'unknown');
            $roundNumber = (int) ($row['number_round'] ?? 0);
            $roundDate = isset($row['round_date']) ? (string) $row['round_date'] : null;
            $roundSlug = SnapshotExportService::buildRoundSlug($roundNumber, $roundDate);

            if (!isset($tree[$season])) {
                $tree[$season] = [
                    'season_year' => $season,
                    'rounds' => [],
                ];
            }

            $tree[$season]['rounds'][] = [
                'number_round' => $roundNumber,
                'round_date' => $roundDate,
                'round_slug' => $roundSlug,
                'card_count' => (int) ($row['card_count'] ?? 0),
                'name_course' => (string) ($row['name_course'] ?? ''),
                'name_club' => (string) ($row['name_club'] ?? ''),
                'snapshots' => $this->buildSnapshotLinks($season, $roundSlug),
            ];
        }

        return array_values($tree);
    }

    private function buildSnapshotLinks(string $seasonYear, string $roundSlug): array
    {
        $result = [];
        $publicRoot = $this->getPublicRoot();
        foreach (SnapshotExportService::snapshotDefinitions() as $snapshot) {
            $relativePath = 'reports/' . $seasonYear . '/' . $roundSlug . '/' . $snapshot['filename'];
            $absolutePath = $publicRoot . '/' . $relativePath;

            $result[] = [
                'filename' => $snapshot['filename'],
                'label' => $snapshot['label'],
                'href' => '/' . $relativePath,
                'exists' => file_exists($absolutePath),
            ];
        }

        return $result;
    }

    private function getPublicRoot(): string
    {
        return dirname(__DIR__, 2) . '/public';
    }
}
