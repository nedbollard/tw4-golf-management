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
        $eclecticContextByRound = $this->loadRoundEclecticContexts();
        $rows = $this->db->fetchAll(
            'SELECT hr.season_year,
                    hr.number_round,
                    hr.round_date,
                    hr.course_played_id,
                    hr.card_count,
                    cp.name_course,
                    cp.name_club,
                    cp.ident_eclectic
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
                'ident_eclectic' => (string) ($row['ident_eclectic'] ?? ''),
                'snapshots' => $this->buildSnapshotLinks(
                    $season,
                    $roundSlug,
                    $row,
                    $eclecticContextByRound[$this->buildRoundKey($season, $roundNumber)] ?? null
                ),
            ];
        }

        return array_values($tree);
    }

    private function buildSnapshotLinks(string $seasonYear, string $roundSlug, array $round, ?array $eclecticContext = null): array
    {
        $result = [];
        $publicRoot = $this->getPublicRoot();
        foreach (SnapshotExportService::snapshotDefinitions() as $snapshot) {
            $filename = $this->resolveSnapshotFilename((string) ($snapshot['filename'] ?? ''), $round, $eclecticContext);
            if ($filename === null) {
                continue;
            }
            $relativePath = 'reports/' . $seasonYear . '/' . $roundSlug . '/' . $filename;
            $absolutePath = $publicRoot . '/' . $relativePath;

            $result[] = [
                'filename' => $filename,
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

    private function resolveSnapshotFilename(string $template, array $round, ?array $eclecticContext = null): ?string
    {
        $isEclecticTemplate = str_contains($template, 'Eclectic');
        if ($eclecticContext !== null && $isEclecticTemplate) {
            $includeEclectic = (int) ($eclecticContext['include_eclectic'] ?? 0) === 1;
            if (!$includeEclectic) {
                return null;
            }

            if (str_contains($template, '49_Eclectic_')) {
                $combined = trim((string) ($eclecticContext['combined_report_filename'] ?? ''));
                if ($combined !== '') {
                    return $combined;
                }
            }

            $json = (string) ($eclecticContext['course_report_files_json'] ?? '');
            if ($json !== '' && str_contains($template, '41_Eclectic_')) {
                $decoded = json_decode($json, true);
                if (is_array($decoded) && !empty($decoded)) {
                    return (string) ($decoded[0] ?? '');
                }
            }

            if ($json !== '' && str_contains($template, '42_Eclectic_')) {
                // Hide legacy second-course slot when the round context is explicit.
                return null;
            }
        }

        $playedCourse = $this->slugifyCourseName((string) ($round['name_course'] ?? 'Course'));
        $combinedName = trim((string) ($round['ident_eclectic'] ?? ''));
        if ($combinedName === '') {
            $combinedName = 'Eclectic';
        }

        $combinedCourse = $this->slugifyCourseName($combinedName);
        $otherCourse = $this->slugifyCourseName($this->resolveAlternateEclecticCourseName($round));

        return str_replace(
            ['%COURSE_A%', '%COURSE_B%', '%COURSE_C%'],
            [$playedCourse, $otherCourse, $combinedCourse],
            $template
        );
    }

    private function loadRoundEclecticContexts(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT season_year,
                    number_round,
                    include_eclectic,
                    course_report_files_json,
                    combined_report_filename
             FROM TW4_history.round_eclectic_context'
        );

        $result = [];
        foreach ($rows as $row) {
            $season = (string) ($row['season_year'] ?? '');
            $numberRound = (int) ($row['number_round'] ?? 0);
            if ($season === '' || $numberRound < 1) {
                continue;
            }

            $result[$this->buildRoundKey($season, $numberRound)] = $row;
        }

        return $result;
    }

    private function buildRoundKey(string $seasonYear, int $roundNumber): string
    {
        return $seasonYear . '|' . $roundNumber;
    }

    private function resolveAlternateEclecticCourseName(array $round): string
    {
        $played = trim((string) ($round['name_course'] ?? ''));
        $ident = trim((string) ($round['ident_eclectic'] ?? ''));
        $coursePlayedId = (int) ($round['course_played_id'] ?? 0);

        if ($ident !== '') {
            $row = $this->db->fetchOne(
                'SELECT cp.name_course
                 FROM TW4_base.course_played cp
                 WHERE cp.ident_eclectic = ?
                   AND cp.row_id <> ?
                 ORDER BY cp.name_course ASC
                 LIMIT 1',
                [$ident, $coursePlayedId]
            );

            $other = trim((string) ($row['name_course'] ?? ''));
            if ($other !== '') {
                return $other;
            }
        }

        return $played !== '' ? $played : 'Course';
    }

    private function slugifyCourseName(string $name): string
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
