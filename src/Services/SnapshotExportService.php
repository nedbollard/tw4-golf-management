<?php

namespace App\Services;

use App\Core\Database;

class SnapshotExportService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public static function snapshotDefinitions(): array
    {
        return [
            ['filename' => '10_Results.html', 'label' => 'Results'],
            ['filename' => '20_Movements.html', 'label' => 'Movements'],
            ['filename' => '31_Best_5_Scores.html', 'label' => 'Best 5 Scores'],
            ['filename' => '33_Eclectic.html', 'label' => 'Eclectic'],
            ['filename' => '35_Teams_Haggle.html', 'label' => 'Teams Haggle'],
            ['filename' => '37_Small_Beer.html', 'label' => 'Small Beer'],
            ['filename' => '39_Handicaps.html', 'label' => 'Handicaps'],
        ];
    }

    public static function buildRoundSlug(int $roundNumber, ?string $roundDate): string
    {
        $prefix = str_pad((string) max(0, $roundNumber), 3, '0', STR_PAD_LEFT);

        if (empty($roundDate)) {
            return $prefix;
        }

        $ts = strtotime($roundDate);
        if ($ts === false) {
            return $prefix;
        }

        return $prefix . '_' . date('M_d', $ts);
    }

    public function exportRoundSnapshots(string $seasonYear, int $roundNumber, bool $overwrite = true): array
    {
        $context = $this->loadContext($seasonYear, $roundNumber);
        $roundSlug = self::buildRoundSlug($roundNumber, $context['round_date']);

        $targetDir = dirname(__DIR__, 2) . '/public/reports/' . $seasonYear . '/' . $roundSlug;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Unable to create reports directory: ' . $targetDir);
        }

        $files = [
            '10_Results.html' => $this->renderResults($context),
            '20_Movements.html' => $this->renderPlaceholder($context, 'Movements', 'Movement tracking export is not available yet in TW4.'),
            '31_Best_5_Scores.html' => $this->renderBest5($context),
            '33_Eclectic.html' => $this->renderPlaceholder($context, 'Eclectic', 'Eclectic export is queued for a later increment.'),
            '35_Teams_Haggle.html' => $this->renderPlaceholder($context, 'Teams Haggle', 'Teams Haggle export is queued for a later increment.'),
            '37_Small_Beer.html' => $this->renderSmallBeer($context),
            '39_Handicaps.html' => $this->renderHandicaps($context),
        ];

        $written = [];
        foreach ($files as $filename => $html) {
            $path = $targetDir . '/' . $filename;
            if (!$overwrite && file_exists($path)) {
                continue;
            }
            if (file_put_contents($path, $html) === false) {
                throw new \RuntimeException('Failed writing snapshot: ' . $path);
            }
            $written[] = $filename;
        }

        return [
            'season_year' => $seasonYear,
            'number_round' => $roundNumber,
            'round_slug' => $roundSlug,
            'directory' => $targetDir,
            'written' => $written,
        ];
    }

    private function loadContext(string $seasonYear, int $roundNumber): array
    {
        $round = $this->db->fetchOne(
            'SELECT hr.season_year, hr.number_round, hr.round_date, cp.name_course, cp.name_club
             FROM TW4_history.round hr
             LEFT JOIN TW4_base.course_played cp ON cp.row_id = hr.course_played_id
             WHERE hr.season_year = ? AND hr.number_round = ? LIMIT 1',
            [$seasonYear, $roundNumber]
        );

        if (!$round) {
            throw new \RuntimeException('History round not found for export.');
        }

        $cards = $this->db->fetchAll(
            'SELECT hc.row_id, hc.points, hc.score, hc.handicap_applied, hc.handicap_updated,
                    COALESCE(r.player_identifier, CONCAT("player_", hc.row_id_player)) AS player_identifier,
                    COALESCE(NULLIF(TRIM(r.alias), ""), r.player_identifier, CONCAT("player_", hc.row_id_player)) AS display_player
             FROM TW4_history.card hc
             LEFT JOIN TW4_base.roster r ON r.row_id = hc.row_id_player
             WHERE hc.season_year = ? AND hc.number_round = ?
             ORDER BY hc.row_id ASC',
            [$seasonYear, $roundNumber]
        );

        $results = $this->db->fetchAll(
            'SELECT type_result, number_result, player_identifier, value_result
             FROM TW4_history.results
             WHERE season_year = ? AND number_round = ?
             ORDER BY type_result, number_result, player_identifier',
            [$seasonYear, $roundNumber]
        );

        $holes = $this->db->fetchAll(
            'SELECT row_id_card, hole, points
             FROM TW4_history.card_by_hole
             WHERE season_year = ? AND number_round = ?
             ORDER BY row_id_card, hole',
            [$seasonYear, $roundNumber]
        );

        $rosterRows = $this->db->fetchAll(
            'SELECT player_identifier, alias
             FROM TW4_base.roster'
        );

        $handicapSnapshot = $this->db->fetchAll(
            'SELECT
                r.row_id,
                r.player_identifier,
                r.alias,
                r.handicap AS current_handicap,
                ha.handicap_previous,
                ha.handicap_new,
                ha.handicap_source,
                ha.reason,
                ha.season_year AS audit_season_year,
                ha.number_round AS audit_number_round,
                ha.changed_at
             FROM TW4_base.roster r
             LEFT JOIN TW4_base.handicap_audit ha
               ON ha.row_id = (
                   SELECT ha2.row_id
                   FROM TW4_base.handicap_audit ha2
                   WHERE ha2.row_id_player = r.row_id
                   ORDER BY ha2.changed_at DESC, ha2.row_id DESC
                   LIMIT 1
               )
             WHERE r.status IN ("active", "scored")
             ORDER BY COALESCE(NULLIF(TRIM(r.alias), ""), r.player_identifier) ASC'
        );

        $bestFiveSnapshot = [];
        try {
            $bestFiveSnapshot = $this->db->fetchAll(
                'SELECT
                    bf.row_id_player,
                    bf.number_round_movement,
                    bf.points_total,
                    bf.points_best_1,
                    bf.points_best_2,
                    bf.points_best_3,
                    bf.points_best_4,
                    bf.points_best_5,
                    bf.round_best_1,
                    bf.round_best_2,
                    bf.round_best_3,
                    bf.round_best_4,
                    bf.round_best_5,
                    bf.points_movement,
                    COALESCE(NULLIF(TRIM(r.alias), ""), r.player_identifier, CONCAT("player_", bf.row_id_player)) AS display_player
                 FROM TW4_history.best_five bf
                 LEFT JOIN TW4_base.roster r ON r.row_id = bf.row_id_player
                 WHERE bf.season_year = ?
                   AND bf.number_round_snapshot = ?
                 ORDER BY bf.points_total DESC,
                          COALESCE(NULLIF(TRIM(r.alias), ""), r.player_identifier, CONCAT("player_", bf.row_id_player)) ASC',
                [$seasonYear, $roundNumber]
            );
        } catch (\Throwable $e) {
            $bestFiveSnapshot = [];
        }

        $aliasByIdentifier = [];
        foreach ($rosterRows as $rosterRow) {
            $identifier = trim((string) ($rosterRow['player_identifier'] ?? ''));
            $alias = trim((string) ($rosterRow['alias'] ?? ''));
            if ($identifier !== '' && $alias !== '') {
                $aliasByIdentifier[$identifier] = $alias;
            }
        }

        $leaderboard = [];
        $scores = [];
        $pointsArray = [];
        foreach ($cards as $index => $card) {
            $score = (int) ($card['score'] ?? 0);
            $points = (int) ($card['points'] ?? 0);
            $scores[] = $score;
            $pointsArray[] = $points;
            $leaderboard[] = [
                'position' => $index + 1,
                'row_id_card' => (int) ($card['row_id'] ?? 0),
                'player_identifier' => (string) ($card['player_identifier'] ?? ''),
                'display_player' => (string) ($card['display_player'] ?? ''),
                'points' => $points,
                'score' => $score,
                'handicap_applied' => (int) ($card['handicap_applied'] ?? 0),
                'handicap_updated' => isset($card['handicap_updated']) ? (int) $card['handicap_updated'] : null,
                'countback1' => 0,
                'countback3' => 0,
                'countback6' => 0,
                'countback_decision' => 'n/a',
            ];
        }

        $leaderboardByCard = [];
        foreach ($leaderboard as &$entry) {
            $leaderboardByCard[(int) $entry['row_id_card']] = &$entry;
        }
        unset($entry);

        foreach ($holes as $hole) {
            $cardId = (int) ($hole['row_id_card'] ?? 0);
            if (!isset($leaderboardByCard[$cardId])) {
                continue;
            }

            $holeNo = (int) ($hole['hole'] ?? 0);
            $holePoints = (int) ($hole['points'] ?? 0);

            if ($holeNo === 9) {
                $leaderboardByCard[$cardId]['countback1'] += $holePoints;
            }
            if ($holeNo > 6) {
                $leaderboardByCard[$cardId]['countback3'] += $holePoints;
            }
            if ($holeNo > 3) {
                $leaderboardByCard[$cardId]['countback6'] += $holePoints;
            }
        }

        usort(
            $leaderboard,
            static fn(array $a, array $b): int =>
                ($b['points'] <=> $a['points'])
                ?: ($b['countback1'] <=> $a['countback1'])
                ?: ($b['countback3'] <=> $a['countback3'])
                ?: ($b['countback6'] <=> $a['countback6'])
                ?: strcmp((string) ($a['display_player'] ?? ''), (string) ($b['display_player'] ?? ''))
        );

        $previous = null;
        foreach ($leaderboard as $index => &$entry) {
            $entry['position'] = $index + 1;

            if ($index === 0 || $previous === null || $entry['points'] !== $previous['points']) {
                $entry['countback_decision'] = 'n/a';
            } elseif ($entry['countback1'] !== $previous['countback1']) {
                $entry['countback_decision'] = 'last 1';
            } elseif ($entry['countback3'] !== $previous['countback3']) {
                $entry['countback_decision'] = 'last 3';
            } elseif ($entry['countback6'] !== $previous['countback6']) {
                $entry['countback_decision'] = 'last 6';
            } else {
                $entry['countback_decision'] = 'name order';
            }

            $previous = $entry;
        }
        unset($entry);

        $prizes = [];
        $twos = [];
        $closest = [];
        foreach ($results as $row) {
            $type = (string) ($row['type_result'] ?? '');
            $identifier = (string) ($row['player_identifier'] ?? '');
            $row['display_player'] = $aliasByIdentifier[$identifier] ?? $identifier;
            if ($type === 'Place') {
                $prizes[] = $row;
            } elseif ($type === 'Twos') {
                $twos[] = $row;
            } elseif ($type === 'C_P') {
                $closest[] = $row;
            }
        }

        // Calculate round stats
        $roundStats = $this->calculateRoundStats($scores, $pointsArray);

        return [
            'season_year' => (string) $round['season_year'],
            'number_round' => (int) $round['number_round'],
            'round_date' => isset($round['round_date']) ? (string) $round['round_date'] : null,
            'name_course' => (string) ($round['name_course'] ?? ''),
            'name_club' => (string) ($round['name_club'] ?? ''),
            'leaderboard' => $leaderboard,
            'prizes' => $prizes,
            'twos' => $twos,
            'closest' => $closest,
            'round_stats' => $roundStats,
            'handicap_snapshot' => $handicapSnapshot,
            'best_five_snapshot' => $bestFiveSnapshot,
        ];
    }

    private function calculateRoundStats(array $scores, array $points): array
    {
        if (empty($scores) || empty($points)) {
            return [
                'field_size' => 0,
                'min_score' => 0,
                'max_score' => 0,
                'median_score' => 0,
                'min_points' => 0,
                'max_points' => 0,
                'median_points' => 0,
            ];
        }

        sort($scores);
        sort($points);

        return [
            'field_size' => count($scores),
            'min_score' => min($scores),
            'max_score' => max($scores),
            'median_score' => $this->getMedian($scores),
            'min_points' => min($points),
            'max_points' => max($points),
            'median_points' => $this->getMedian($points),
        ];
    }

    private function getMedian(array $values): int
    {
        $count = count($values);
        if ($count === 0) {
            return 0;
        }
        $middle = floor($count / 2);
        return (int) (($count % 2) ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2);
    }

    private function renderRoundStats(array $stats): string
    {
        return '<div class="round-stats-section">'
            . '<h4>Round stats:</h4>'
            . '<table class="round-stats">'
            . '<tr>'
            . '<th>Field Size</th><th>Min Score</th><th>Max Score</th><th>Median</th>'
            . '<th>Min Points</th><th>Max Points</th><th>Median</th>'
            . '</tr>'
            . '<tr>'
            . '<td>' . (int) $stats['field_size'] . '</td>'
            . '<td>' . (int) $stats['min_score'] . '</td>'
            . '<td>' . (int) $stats['max_score'] . '</td>'
            . '<td>' . (int) $stats['median_score'] . '</td>'
            . '<td>' . (int) $stats['min_points'] . '</td>'
            . '<td>' . (int) $stats['max_points'] . '</td>'
            . '<td>' . (int) $stats['median_points'] . '</td>'
            . '</tr>'
            . '</table>'
            . '</div>';
    }

    private function renderResults(array $ctx): string
    {
        $rows = '';
        foreach ($ctx['leaderboard'] as $entry) {
            $countback = (int) ($entry['countback1'] ?? 0)
                . '-' . (int) ($entry['countback3'] ?? 0)
                . '-' . (int) ($entry['countback6'] ?? 0);
            $rows .= '<tr><td>' . (int) $entry['position'] . '</td><td>' . $this->e((string) ($entry['display_player'] ?? $entry['player_identifier'])) . '</td><td>' . (int) $entry['points'] . '</td><td>' . (int) $entry['score'] . '</td><td>' . (int) $entry['handicap_applied'] . '</td><td>' . $this->e($countback) . '</td><td>' . $this->e((string) ($entry['countback_decision'] ?? 'n/a')) . '</td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="7">No card data available.</td></tr>';
        }

        $payout = '';
        foreach ($ctx['prizes'] as $row) {
            $payout .= '<tr><td>' . $this->e((string) ($row['display_player'] ?? $row['player_identifier'] ?? '')) . '</td><td>$' . number_format((float) ($row['value_result'] ?? 0), 2) . '</td></tr>';
        }
        if ($payout === '') {
            $payout = '<tr><td colspan="2">No payout data.</td></tr>';
        }

        $twos = '';
        foreach ($ctx['twos'] as $row) {
            $twos .= '<tr><td>' . $this->e((string) ($row['display_player'] ?? $row['player_identifier'] ?? '')) . '</td><td>' . (int) ($row['value_result'] ?? 0) . '</td></tr>';
        }
        if ($twos === '') {
            $twos = '<tr><td colspan="2">No twos recorded.</td></tr>';
        }

        $closest = '';
        foreach ($ctx['closest'] as $row) {
            $closest .= '<tr><td>' . $this->e((string) ($row['display_player'] ?? $row['player_identifier'] ?? '')) . '</td></tr>';
        }
        if ($closest === '') {
            $closest = '<tr><td>No closest-to-pin result recorded.</td></tr>';
        }

        return $this->wrap(
            $ctx,
            'Results',
            '<h2>Results Sheet</h2>'
            . '<h3>Date: ' . $this->e((string) ($ctx['round_date'] ?? 'n/a')) . '</h3>'
            . '<h3>Course: ' . $this->e((string) ($ctx['name_course'] ?? 'n/a')) . '</h3>'
            . '<h4>The field:</h4><table><tr><th>Position</th><th>Player</th><th>Points</th><th>Gross</th><th>Handicap</th><th>Count Back ...</th><th>Decision ...</th></tr>' . $rows . '</table>'
            . '<h4>The Payout:</h4><table><tr><th>Player</th><th>Prize</th></tr>' . $payout . '</table>'
            . '<h4>Twos:</h4><table><tr><th>Player</th><th>Number</th></tr>' . $twos . '</table>'
            . '<h4>Closest to the PIN:</h4><table><tr><th>Player</th></tr>' . $closest . '</table>'
            . $this->renderRoundStats($ctx['round_stats'])
        );
    }

    private function renderBest5(array $ctx): string
    {
        $rows = $ctx['best_five_snapshot'] ?? [];

        $bodyRows = '';
        foreach ($rows as $idx => $row) {
            $bodyRows .= '<tr>'
                . '<td>' . ($idx + 1) . '</td>'
                . '<td>' . $this->e((string) ($row['display_player'] ?? ('player_' . (string) ($row['row_id_player'] ?? '')))) . '</td>'
                . '<td>' . (int) ($row['points_total'] ?? 0) . '</td>'
                . '<td>' . (int) ($row['points_best_1'] ?? 0) . '</td>'
                . '<td>' . (int) ($row['points_best_2'] ?? 0) . '</td>'
                . '<td>' . (int) ($row['points_best_3'] ?? 0) . '</td>'
                . '<td>' . (int) ($row['points_best_4'] ?? 0) . '</td>'
                . '<td>' . (int) ($row['points_best_5'] ?? 0) . '</td>'
                . '<td>' . (int) ($row['points_movement'] ?? 0) . '</td>'
                . '<td>' . (int) ($row['number_round_movement'] ?? 0) . '</td>'
                . '</tr>';
        }

        if ($bodyRows === '') {
            $bodyRows = '<tr><td colspan="10">No best five data available.</td></tr>';
        }

        return $this->wrap(
            $ctx,
            'Best 5 Scores',
            '<h2>Haggle: Best 5 scores</h2>'
            . '<h3>Date: ' . $this->e((string) ($ctx['round_date'] ?? 'n/a')) . '</h3>'
            . '<h3>Course: ' . $this->e((string) ($ctx['name_course'] ?? 'n/a')) . '</h3>'
            . '<table><tr><th>Standing</th><th>Player</th><th>Total Points</th><th>Best 1</th><th>Best 2</th><th>Best 3</th><th>Best 4</th><th>Best 5</th><th>Last Change</th><th>Round</th></tr>'
            . $bodyRows
            . '</table>'
        );
    }

    private function renderSmallBeer(array $ctx): string
    {
        $rows = '';
        foreach ($ctx['twos'] as $row) {
            $rows .= '<tr><td>' . $this->e((string) ($row['display_player'] ?? $row['player_identifier'] ?? '')) . '</td><td>' . (int) ($row['value_result'] ?? 0) . '</td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="2">No small beer entries for this round.</td></tr>';
        }

        return $this->wrap($ctx, 'Small Beer', '<h2>Small Beer</h2><table><tr><th>Player</th><th>Count</th></tr>' . $rows . '</table>');
    }

    private function renderHandicaps(array $ctx): string
    {
        $currentSeason = trim((string) ($ctx['season_year'] ?? ''));
        $rows = '';
        foreach (($ctx['handicap_snapshot'] ?? []) as $row) {
            $displayPlayer = trim((string) ($row['alias'] ?? ''));
            if ($displayPlayer === '') {
                $displayPlayer = (string) ($row['player_identifier'] ?? '');
            }

            $currentHandicap = (int) ($row['current_handicap'] ?? 0);
            $previousHandicap = isset($row['handicap_previous']) ? (int) $row['handicap_previous'] : null;
            $change = $previousHandicap === null ? null : ($currentHandicap - $previousHandicap);
            $changeText = $change === null ? 'n/a' : (($change > 0 ? '+' : '') . (string) $change);

            $auditSeason = trim((string) ($row['audit_season_year'] ?? ''));
            $auditRound = isset($row['audit_number_round']) ? (int) $row['audit_number_round'] : 0;
            $roundRef = 'n/a';
            if ($auditSeason !== '' && $auditRound > 0) {
                $roundRef = (string) $auditRound;
                if ($auditSeason !== $currentSeason) {
                    $roundRef .= ' [' . $auditSeason . ']';
                }
            }

            $source = trim((string) ($row['handicap_source'] ?? ''));
            $reason = trim((string) ($row['reason'] ?? ''));
            $reasonDisplay = $reason;
            if ($source === 'card_scoring' && $reason === 'finish_round_card_scoring') {
                $reasonDisplay = null;
            }

            $rows .= '<tr>'
                . '<td>' . $this->e($displayPlayer) . '</td>'
                . '<td>' . $currentHandicap . '</td>'
                . '<td>' . $this->e($changeText) . '</td>'
                . '<td>' . $this->e($roundRef) . '</td>'
                . '<td>' . $this->e($source !== '' ? $source : 'n/a') . '</td>'
                . '<td>' . ($reasonDisplay === null ? '' : $this->e($reasonDisplay !== '' ? $reasonDisplay : 'n/a')) . '</td>'
                . '</tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="6">No handicap updates recorded.</td></tr>';
        }

        return $this->wrap(
            $ctx,
            'Handicaps',
            '<h2>Handicaps, post round:</h2><table><tr><th>Player</th><th>Handicap</th><th>Last Change</th><th>@Round</th><th>Source</th><th>Reason</th></tr>' . $rows . '</table>'
        );
    }

    private function renderPlaceholder(array $ctx, string $title, string $message): string
    {
        return $this->wrap($ctx, $title, '<h2>' . $this->e($title) . '</h2><p class="note">' . $this->e($message) . '</p>');
    }

    private function wrap(array $ctx, string $title, string $content): string
    {
        $club = trim((string) ($ctx['name_club'] ?? ''));
        if ($club === '') {
            $club = 'Twilight Golf';
        }

        $roundSlug = self::buildRoundSlug((int) $ctx['number_round'], $ctx['round_date'] ?? null);

        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $this->e($club . ' - ' . $title) . '</title><style>'
                . 'body{margin:0;padding:16px;background:#eef1f4;font-family:Verdana,Arial,sans-serif;color:#102a43;}.wrap{max-width:980px;margin:0 auto;background:#fff;border:1px solid #d9e2ec;border-radius:12px;padding:16px;}h1{margin:0 0 8px 0;color:#1f7a1f;font-size:1.7rem;}h2{margin:8px 0;color:#1f7a1f;font-size:1.3rem;}h3{margin:8px 0;font-size:1.05rem;}h4{margin:14px 0 8px 0;font-size:1rem;}.meta{margin:8px 0 14px 0;color:#486581;font-weight:600;}table{width:100%;border-collapse:collapse;margin-bottom:14px;font-size:0.95rem;}th,td{border:1px solid #bcccdc;padding:8px;text-align:left;}th{background:#f0f4f8;}.note{background:#f8fafc;border:1px solid #d9e2ec;border-radius:8px;padding:10px;}.footer{margin-top:20px;color:#627d98;font-size:0.82rem;}.report-top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;}.report-nav{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;margin-bottom:8px;}.report-nav a{display:inline-block;background:#16a34a;color:#fff;text-decoration:none;padding:8px 14px;border-radius:999px;font-weight:700;font-size:0.85rem;border:1px solid #15803d;}.report-nav a.secondary{background:#0f766e;border-color:#0f766e;}.report-nav a:hover,.report-nav a:focus{background:#15803d;}.report-nav a.secondary:hover,.report-nav a.secondary:focus{background:#115e59;}@media (max-width:700px){.report-top{flex-direction:column;}.report-nav{width:100%;justify-content:flex-start;}}'
                . '</style></head><body><div class="wrap"><div class="report-top"><div><h1>' . $this->e($club) . '</h1><div class="meta">Season: ' . $this->e($ctx['season_year']) . ' | Round: ' . $this->e($roundSlug) . '</div></div><div class="report-nav"><a href="/results">Back to Reports</a><a href="/" class="secondary">Back to Main Menu</a></div></div>' . $content . '<div class="footer">Generated by TW4 snapshot exporter</div></div></body></html>';
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
