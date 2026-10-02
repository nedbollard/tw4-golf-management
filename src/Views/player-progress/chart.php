<?php
use App\Services\PlayerProgressService;

$rounds = (array) ($progress['rounds'] ?? []);
$player = $selectedPlayer ?? null;
$playerLabel = trim((string) ($player['alias'] ?? ''));
$playerName = htmlspecialchars($playerLabel !== '' ? $playerLabel : (string) ($player['player_identifier'] ?? 'Unknown player'));
$seasonLabels = array_map(static fn(string $season): string => str_replace('_', '/', $season), (array) ($progress['seasons'] ?? []));
$playedRounds = array_values(array_filter($rounds, static fn(array $round): bool => (bool) ($round['played'] ?? true)));
$roundCount = count($playedRounds);
$pointsTotal = array_sum(array_map(static fn(array $round): int => (int) ($round['points'] ?? 0), $playedRounds));
$latestRound = $playedRounds !== [] ? $playedRounds[array_key_last($playedRounds)] : null;
$latestHandicap = (int) ($latestRound['handicap_updated'] ?? ($player['handicap'] ?? 0));
$maxHandicapValue = 0;
foreach ($playedRounds as $playedRound) {
    $maxHandicapValue = max(
        $maxHandicapValue,
        max(0, (int) ($playedRound['handicap_applied'] ?? 0)),
        max(0, (int) ($playedRound['handicap_updated'] ?? 0))
    );
}
$chartMax = max(PlayerProgressService::POINTS_MAX, (int) (ceil($maxHandicapValue / 9) * 9));
$referenceLevel = PlayerProgressService::HANDICAP_BASELINE_LEVEL;
$plotHeight = 280;
$plotTop = 54;
$baseY = $plotTop + $plotHeight;
$scale = $plotHeight / $chartMax;
$groupWidth = 68;
$barWidth = 26;
$leftMargin = 72;
$seasonGap = 28;
$seasonSections = [];
$roundPositions = [];
$nextSectionX = $leftMargin;
foreach ($rounds as $index => $round) {
    $season = (string) $round['season_year'];
    if (!isset($seasonSections[$season])) {
        if ($seasonSections !== []) {
            $previousSection = $seasonSections[array_key_last($seasonSections)];
            $nextSectionX = $previousSection['x'] + $previousSection['width'] + $seasonGap;
        }
        $seasonSections[$season] = [
            'x' => $nextSectionX,
            'width' => 160,
            'count' => 0,
            'baseline' => null,
        ];
    }
    $section = &$seasonSections[$season];
    $roundPositions[$index] = $section['x'] + $section['count'] * $groupWidth;
    $section['count']++;
    $section['width'] = max(160, $section['count'] * $groupWidth);
    if ($section['baseline'] === null && (bool) ($round['played'] ?? true)) {
        $section['baseline'] = (int) $round['handicap_applied'];
    }
    unset($section);
}
$lastSection = $seasonSections !== [] ? $seasonSections[array_key_last($seasonSections)] : null;
$chartWidth = max(780, $lastSection !== null ? $lastSection['x'] + $lastSection['width'] + 54 : 780);
$chartHeight = 404;

// Handicap markers are plotted on the same numeric axis as points so baseline
// and movement align with chart graduations.
$handicapRadius = 9;
$handicapRoundXOffset = 7;
$legendItems = [
    ['class' => 'legend-points', 'label' => 'Points (Stableford)'],
    ['class' => 'legend-handicap-start', 'label' => 'Starting handicap'],
    ['class' => 'legend-handicap-change', 'label' => 'Adjusted handicap'],
];

ob_start();
?>
<div class="player-progress-panel player-progress-panel-controls">
    <h3 class="player-progress-subheading">Player: <?php echo $playerName; ?></h3>
    <div class="player-progress-summary">
        <div><strong>Seasons shown</strong> <?php echo htmlspecialchars(implode(', ', $seasonLabels)); ?></div>
        <div><strong>Rounds (all seasons)</strong> <?php echo $roundCount; ?></div>
        <div><strong>Total Points (all seasons)</strong> <?php echo $pointsTotal; ?></div>
        <div><strong>Latest Handicap</strong> <?php echo $latestHandicap; ?></div>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="player-progress-alert" role="status">
            <?php echo htmlspecialchars((string) $notice); ?>
        </div>
    <?php endif; ?>
</div>

<div class="player-progress-panel player-progress-panel-chart">
    <div class="player-progress-legend" aria-label="Chart legend">
        <?php foreach ($legendItems as $item): ?>
            <span class="player-progress-legend-item"><span class="legend-swatch <?php echo $item['class']; ?>"></span><?php echo htmlspecialchars($item['label']); ?></span>
        <?php endforeach; ?>
    </div>

    <div class="progress-chart-scroll">
        <?php if ($player && $rounds !== []): ?>
            <?php
            // First pass: bars + collect raw handicap marker levels (in chart order).
            $bars = [];
            $rawMarkers = [];
            foreach ($rounds as $index => $round) {
                $season = (string) $round['season_year'];
                $plotBaselineLevel = (float) ($seasonSections[$season]['baseline'] ?? $referenceLevel);
                $x = $roundPositions[$index];
                $centerX = $x + (int) floor($barWidth / 2);
                $played = (bool) ($round['played'] ?? true);
                $points = max(0, (int) ($round['points'] ?? 0));
                $pointsHeight = (int) round(min($points, $chartMax) * $scale);
                $barHeight = max(1, $pointsHeight);
                $barY = $baseY - $barHeight;

                $bars[] = [
                    'x' => $x,
                    'centerX' => $centerX,
                    'barY' => $barY,
                    'barHeight' => $barHeight,
                    'points' => $points,
                    'round' => $round,
                    'played' => $played,
                ];

                foreach ((array) ($round['handicap_markers'] ?? []) as $marker) {
                    $relativeLevel = (float) ($marker['level'] ?? $referenceLevel);
                    $absoluteLevel = $plotBaselineLevel + ($relativeLevel - $referenceLevel);
                    $rawMarkers[] = [
                        'x' => $centerX,
                        'season' => $season,
                        // Convert schematic marker levels from the internal reference
                        // frame into a chart frame anchored at the player's real start.
                        'level' => $absoluteLevel,
                        'type' => (string) ($marker['type'] ?? 'end'),
                        'value' => (int) ($marker['value'] ?? 0),
                    ];
                }
            }

            $markerPoints = [];
            $lastMarkerIndexByX = [];
            foreach ($rawMarkers as $marker) {
                $rawY = $baseY - (((float) ($marker['level'] ?? 0.0)) * $scale);

                $x = (int) ($marker['x'] ?? 0);
                $y = (int) round($rawY);

                // Same-round start/end markers share the same round center x. Split them
                // horizontally so both are readable while preserving true y-values.
                if (isset($lastMarkerIndexByX[$x])) {
                    $prevIndex = $lastMarkerIndexByX[$x];
                    $markerPoints[$prevIndex]['x'] = (int) ($x - $handicapRoundXOffset);
                    $x = (int) ($x + $handicapRoundXOffset);
                }

                $markerPoints[] = [
                    'x' => $x,
                    'y' => $y,
                    'season' => $marker['season'],
                    'type' => (string) ($marker['type'] ?? 'end'),
                    'value' => (int) ($marker['value'] ?? 0),
                ];

                $lastMarkerIndexByX[(int) ($marker['x'] ?? 0)] = array_key_last($markerPoints);
            }

            // Trend line follows the displayed marker sequence so it always passes
            // through the rendered circles and labels.
            $trendPointsBySeason = [];
            foreach ($markerPoints as $point) {
                $trendPointsBySeason[$point['season']][] = ['x' => $point['x'], 'y' => $point['y']];
            }
            ?>
            <svg class="progress-chart" width="<?php echo $chartWidth; ?>" height="<?php echo $chartHeight; ?>" viewBox="0 0 <?php echo $chartWidth; ?> <?php echo $chartHeight; ?>" role="img" aria-labelledby="progress-chart-title progress-chart-desc" xmlns="http://www.w3.org/2000/svg">
                <title id="progress-chart-title"><?php echo $playerName; ?> progress across seasons <?php echo htmlspecialchars(implode(', ', $seasonLabels)); ?></title>
                <desc id="progress-chart-desc">Blue bars show Stableford points per archived round. Labelled sections separate seasons; gaps indicate missed rounds. Black open circles show starting handicaps and white open circles show adjusted handicaps. Each season starts at its first recorded applied handicap; trend lines do not connect across seasons. Unfinished live rounds are excluded.</desc>

                <rect x="0" y="0" width="<?php echo $chartWidth; ?>" height="<?php echo $chartHeight; ?>" rx="16" fill="#f8fffb" stroke="#d1fae5" />

                <?php $sectionIndex = 0; ?>
                <?php foreach ($seasonSections as $season => $section): ?>
                    <rect x="<?php echo $section['x'] - 10; ?>" y="8" width="<?php echo $section['width']; ?>" height="<?php echo $chartHeight - 16; ?>" rx="8" fill="<?php echo $sectionIndex % 2 === 0 ? '#ecfdf5' : '#eff6ff'; ?>" />
                    <text x="<?php echo $section['x']; ?>" y="28" class="progress-season-label">Season <?php echo htmlspecialchars(str_replace('_', '/', $season)); ?></text>
                    <?php if ($sectionIndex > 0): ?>
                        <line x1="<?php echo $section['x'] - 24; ?>" y1="8" x2="<?php echo $section['x'] - 24; ?>" y2="<?php echo $chartHeight - 16; ?>" class="progress-season-separator" />
                    <?php endif; ?>
                    <?php $sectionIndex++; ?>
                <?php endforeach; ?>

                <?php for ($grid = 0; $grid <= $chartMax; $grid += 9): ?>
                    <?php $gridY = $baseY - (int) round($grid * $scale); ?>
                    <line x1="56" y1="<?php echo $gridY; ?>" x2="<?php echo $chartWidth - 16; ?>" y2="<?php echo $gridY; ?>" class="progress-grid" />
                    <text x="18" y="<?php echo $gridY + 4; ?>" class="progress-axis-label"><?php echo $grid; ?></text>
                <?php endfor; ?>

                <line x1="56" y1="<?php echo $baseY; ?>" x2="<?php echo $chartWidth - 16; ?>" y2="<?php echo $baseY; ?>" class="progress-base-line" />
                <text x="18" y="<?php echo $baseY + 4; ?>" class="progress-axis-label">0</text>

                <?php foreach ($bars as $bar): ?>
                    <?php
                    $round = $bar['round'];
                    $score = max(0, (int) ($round['score'] ?? 0));
                    $pointsScored = max(0, (int) ($round['points_scored'] ?? $round['points'] ?? 0));
                    $pointsEffective = max($pointsScored, (int) ($round['points_effective'] ?? $pointsScored));
                    $pointsAdjustment = $pointsEffective - $pointsScored;
                    $pointsScoredY = $baseY - 14;
                    $pointsAdjustedY = $pointsScoredY - 16;
                    $handicapApplied = (int) ($round['handicap_applied'] ?? 0);
                    $handicapUpdated = (int) ($round['handicap_updated'] ?? $handicapApplied);
                    $roundLabel = 'R' . (int) ($round['number_round'] ?? 0);
                    $roundDate = $round['round_date'] !== '' ? date('d/m', strtotime((string) $round['round_date'])) : '';
                    $fullRoundDate = $round['round_date'] !== '' ? date('d/m/Y', strtotime((string) $round['round_date'])) : '';
                    $roundContext = 'Season ' . str_replace('_', '/', (string) $round['season_year']) . ' | ' . $roundLabel;
                    if ($fullRoundDate !== '') {
                        $roundContext .= ' | ' . $fullRoundDate;
                    }
                    $courseName = trim((string) ($round['course_name'] ?? ''));
                    ?>
                    <g>
                        <?php if ($bar['played']): ?>
                            <rect x="<?php echo $bar['x']; ?>" y="<?php echo $bar['barY']; ?>" width="<?php echo $barWidth; ?>" height="<?php echo $bar['barHeight']; ?>" rx="5" class="progress-bar" />
                            <circle cx="<?php echo $bar['centerX']; ?>" cy="<?php echo $pointsScoredY; ?>" r="7" class="progress-handicap-circle progress-handicap-start progress-audit-points-circle" />
                            <text x="<?php echo $bar['centerX']; ?>" y="<?php echo $pointsScoredY + 3; ?>" class="progress-handicap-label progress-audit-points-label"><?php echo $pointsScored; ?></text>
                            <?php if ($pointsAdjustment > 0): ?>
                                <circle cx="<?php echo $bar['centerX']; ?>" cy="<?php echo $pointsAdjustedY; ?>" r="7" class="progress-handicap-circle progress-handicap-change progress-audit-points-circle" />
                                <text x="<?php echo $bar['centerX']; ?>" y="<?php echo $pointsAdjustedY + 3; ?>" class="progress-handicap-label progress-audit-points-label">+<?php echo $pointsAdjustment; ?></text>
                            <?php endif; ?>
                        <?php endif; ?>

                        <text x="<?php echo $bar['centerX']; ?>" y="<?php echo $baseY + 22; ?>" class="progress-round-label"><?php echo htmlspecialchars($roundLabel); ?></text>
                        <?php if ($roundDate !== ''): ?>
                            <text x="<?php echo $bar['centerX']; ?>" y="<?php echo $baseY + 38; ?>" class="progress-round-date"><?php echo htmlspecialchars($roundDate); ?></text>
                        <?php endif; ?>
                        <?php if (!$bar['played']): ?>
                            <title><?php echo htmlspecialchars($roundContext); ?> | Missed round<?php echo $courseName !== '' ? ' | ' . htmlspecialchars($courseName) : ''; ?></title>
                        <?php elseif ($courseName !== ''): ?>
                            <title><?php echo htmlspecialchars($roundContext); ?> | <?php echo htmlspecialchars($courseName); ?> | Score <?php echo $score; ?> | Points <?php echo $bar['points']; ?> | Effective <?php echo $pointsEffective; ?> | Handicap <?php echo $handicapApplied; ?> → <?php echo $handicapUpdated; ?></title>
                        <?php else: ?>
                            <title><?php echo htmlspecialchars($roundContext); ?> | Score <?php echo $score; ?> | Points <?php echo $bar['points']; ?> | Effective <?php echo $pointsEffective; ?> | Handicap <?php echo $handicapApplied; ?> → <?php echo $handicapUpdated; ?></title>
                        <?php endif; ?>
                    </g>
                <?php endforeach; ?>

                <?php foreach ($trendPointsBySeason as $trendPoints): ?>
                    <?php if (count($trendPoints) > 1): ?>
                        <polyline
                            points="<?php echo implode(' ', array_map(static fn(array $p): string => $p['x'] . ',' . $p['y'], $trendPoints)); ?>"
                            class="progress-handicap-trend-line" />
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php foreach ($markerPoints as $marker): ?>
                    <?php $markerClass = $marker['type'] === 'start' ? 'progress-handicap-start' : 'progress-handicap-change'; ?>
                    <circle cx="<?php echo $marker['x']; ?>" cy="<?php echo $marker['y']; ?>" r="<?php echo $handicapRadius; ?>" class="progress-handicap-circle <?php echo $markerClass; ?>" />
                    <text x="<?php echo $marker['x']; ?>" y="<?php echo $marker['y'] + 4; ?>" class="progress-handicap-label"><?php echo (int) $marker['value']; ?></text>
                <?php endforeach; ?>
            </svg>
        <?php else: ?>
            <div class="progress-empty">No recorded round history is available for this player.</div>
        <?php endif; ?>
    </div>

    <div class="player-progress-control-row player-progress-control-row-center">
        <a href="/player-progress?player_id=<?php echo (int) ($selectedPlayerId ?? 0); ?>" class="btn-secondary-pill">Back to Selector</a>
        <a href="/player-progress/chart?player_id=<?php echo (int) ($selectedPlayerId ?? 0); ?>" class="btn-primary-pill">Refresh Chart</a>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageHeading = 'Player Progress Chart';
require __DIR__ . '/../layouts/player-progress.php';
