<?php
use App\Services\PlayerProgressService;

$rounds = (array) ($progress['rounds'] ?? []);
$player = $selectedPlayer ?? null;
$playerName = $player ? htmlspecialchars((string) ($player['alias'] ?? $player['player_identifier'] ?? 'Unknown player')) : 'Unknown player';
$seasonYear = (string) ($seasonYear ?? ($progress['season_year'] ?? ''));
$playedRounds = array_values(array_filter($rounds, static fn(array $round): bool => (bool) ($round['played'] ?? true)));
$roundCount = count($playedRounds);
$pointsTotal = array_sum(array_map(static fn(array $round): int => (int) ($round['points'] ?? 0), $playedRounds));
$latestRound = $playedRounds !== [] ? $playedRounds[array_key_last($playedRounds)] : null;
$latestHandicap = (int) ($latestRound['handicap_updated'] ?? ($player['handicap'] ?? 0));

$chartMax = PlayerProgressService::POINTS_MAX;
$baselineLevel = PlayerProgressService::HANDICAP_BASELINE_LEVEL;
$plotHeight = 280;
$plotTop = 30;
$baseY = $plotTop + $plotHeight;
$scale = $plotHeight / $chartMax;
$groupWidth = 68;
$barWidth = 26;
$leftMargin = 72;
$chartWidth = max(780, $leftMargin + max(1, count($rounds)) * $groupWidth + 54);
$chartHeight = 380;

// Handicap markers are schematic (relative movement only), so they use their own
// pixel-per-level scale - independent of the points-bar scale - sized to guarantee
// a visible gap between the two circles even for a common 1-point change.
$handicapRadius = 11;
$handicapMinGapPx = 6;
$markerScale = (2 * $handicapRadius) + $handicapMinGapPx;
$baselineY = $baseY - (int) round($baselineLevel * $scale);
$legendItems = [
    ['class' => 'legend-points', 'label' => 'Points (Stableford)'],
    ['class' => 'legend-handicap-start', 'label' => 'Starting handicap'],
    ['class' => 'legend-handicap-change', 'label' => 'Handicap change'],
    ['class' => 'legend-handicap-base', 'label' => 'Handicap base (13)'],
];

ob_start();
?>
<div class="player-progress-panel player-progress-panel-controls">
    <div class="player-progress-summary">
        <div><strong>Season</strong> <?php echo htmlspecialchars($seasonYear !== '' ? $seasonYear : '—'); ?></div>
        <div><strong>Player</strong> <?php echo $playerName; ?></div>
        <div><strong>Rounds</strong> <?php echo $roundCount; ?></div>
        <div><strong>Total Points</strong> <?php echo $pointsTotal; ?></div>
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
        <?php if ($player && $seasonYear !== ''): ?>
            <?php
            // First pass: bars + collect handicap marker points (in chart order) so the
            // trend line/circles can be drawn afterwards, on top of every bar.
            $markerPoints = [];
            $bars = [];
            foreach ($rounds as $index => $round) {
                $x = $leftMargin + ($index * $groupWidth);
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
                    $rawY = $baselineY - round((((float) $marker['level']) - $baselineLevel) * $markerScale);
                    $clampedY = (int) max($plotTop + $handicapRadius + 4, min($baseY - $handicapRadius - 4, $rawY));
                    $markerPoints[] = [
                        'x' => $centerX,
                        'y' => $clampedY,
                        'type' => $marker['type'],
                        'value' => $marker['value'],
                    ];
                }
            }
            ?>
            <svg class="progress-chart" viewBox="0 0 <?php echo $chartWidth; ?> <?php echo $chartHeight; ?>" role="img" aria-labelledby="progress-chart-title progress-chart-desc" xmlns="http://www.w3.org/2000/svg">
                <title id="progress-chart-title"><?php echo htmlspecialchars($playerName); ?> season progress</title>
                <desc id="progress-chart-desc">Blue bars show Stableford points per round. A black open circle marks the starting handicap at the 13 baseline; white open circles mark subsequent handicap changes, moved up or down from that baseline.</desc>

                <rect x="0" y="0" width="<?php echo $chartWidth; ?>" height="<?php echo $chartHeight; ?>" rx="16" fill="#f8fffb" stroke="#d1fae5" />

                <?php for ($grid = 0; $grid <= $chartMax; $grid += 9): ?>
                    <?php $gridY = $baseY - (int) round($grid * $scale); ?>
                    <line x1="56" y1="<?php echo $gridY; ?>" x2="<?php echo $chartWidth - 16; ?>" y2="<?php echo $gridY; ?>" class="progress-grid" />
                    <text x="18" y="<?php echo $gridY + 4; ?>" class="progress-axis-label"><?php echo $grid; ?></text>
                <?php endfor; ?>

                <line x1="56" y1="<?php echo $baselineY; ?>" x2="<?php echo $chartWidth - 16; ?>" y2="<?php echo $baselineY; ?>" class="progress-handicap-base-line" />
                <text x="<?php echo $chartWidth - 20; ?>" y="<?php echo $baselineY - 6; ?>" class="progress-handicap-base-label" text-anchor="end">Handicap base (<?php echo $baselineLevel; ?>)</text>

                <line x1="56" y1="<?php echo $baseY; ?>" x2="<?php echo $chartWidth - 16; ?>" y2="<?php echo $baseY; ?>" class="progress-base-line" />
                <text x="18" y="<?php echo $baseY + 4; ?>" class="progress-axis-label">0</text>

                <?php foreach ($bars as $bar): ?>
                    <?php
                    $round = $bar['round'];
                    $score = max(0, (int) ($round['score'] ?? 0));
                    $handicapApplied = (int) ($round['handicap_applied'] ?? 0);
                    $handicapUpdated = (int) ($round['handicap_updated'] ?? $handicapApplied);
                    $roundLabel = 'R' . (int) ($round['number_round'] ?? 0);
                    $roundDate = $round['round_date'] !== '' ? date('d/m', strtotime((string) $round['round_date'])) : '';
                    $courseName = trim((string) ($round['course_name'] ?? ''));
                    ?>
                    <g>
                        <?php if ($bar['played']): ?>
                            <rect x="<?php echo $bar['x']; ?>" y="<?php echo $bar['barY']; ?>" width="<?php echo $barWidth; ?>" height="<?php echo $bar['barHeight']; ?>" rx="5" class="progress-bar" />
                            <text x="<?php echo $bar['centerX']; ?>" y="<?php echo $baseY - 10; ?>" class="progress-bar-label"><?php echo $bar['points']; ?></text>
                        <?php endif; ?>

                        <text x="<?php echo $bar['centerX']; ?>" y="<?php echo $baseY + 22; ?>" class="progress-round-label"><?php echo htmlspecialchars($roundLabel); ?></text>
                        <?php if ($roundDate !== ''): ?>
                            <text x="<?php echo $bar['centerX']; ?>" y="<?php echo $baseY + 38; ?>" class="progress-round-date"><?php echo htmlspecialchars($roundDate); ?></text>
                        <?php endif; ?>
                        <?php if (!$bar['played']): ?>
                            <title>Missed round<?php echo $courseName !== '' ? ' | ' . htmlspecialchars($courseName) : ''; ?></title>
                        <?php elseif ($courseName !== ''): ?>
                            <title><?php echo htmlspecialchars($courseName); ?> | Score <?php echo $score; ?> | Points <?php echo $bar['points']; ?> | Handicap <?php echo $handicapApplied; ?> → <?php echo $handicapUpdated; ?></title>
                        <?php else: ?>
                            <title>Score <?php echo $score; ?> | Points <?php echo $bar['points']; ?> | Handicap <?php echo $handicapApplied; ?> → <?php echo $handicapUpdated; ?></title>
                        <?php endif; ?>
                    </g>
                <?php endforeach; ?>

                <?php if (count($markerPoints) > 1): ?>
                    <polyline
                        points="<?php echo implode(' ', array_map(static fn(array $p): string => $p['x'] . ',' . $p['y'], $markerPoints)); ?>"
                        class="progress-handicap-trend-line" />
                <?php endif; ?>

                <?php foreach ($markerPoints as $marker): ?>
                    <?php $markerClass = $marker['type'] === 'start' ? 'progress-handicap-start' : 'progress-handicap-change'; ?>
                    <circle cx="<?php echo $marker['x']; ?>" cy="<?php echo $marker['y']; ?>" r="<?php echo $handicapRadius; ?>" class="progress-handicap-circle <?php echo $markerClass; ?>" />
                    <text x="<?php echo $marker['x']; ?>" y="<?php echo $marker['y'] + 4; ?>" class="progress-handicap-label"><?php echo (int) $marker['value']; ?></text>
                <?php endforeach; ?>
            </svg>
        <?php else: ?>
            <div class="progress-empty">Select a player to load their progress chart.</div>
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
