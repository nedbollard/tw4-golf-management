<?php
/**
 * Leaderboard card chart — split-column design.
 *
 * Per hole:
 *  LEFT  col   : blue bar = actual score; pink blocks fill top of bar (handicap shots)
 *  RIGHT col   : SFP stacked blocks rising from base (one per Stableford point, all same colour = total points)
 *  FULL WIDTH  : dotted outline rect = par (may extend above the blue bar for birdies/eagles)
 *  TOOLTIP     : transparent hit-rect covers full column incl. empty dotted space above bar
 */

// SFP colour palette (matches the enter-card panel)
$sfpPalette = [
    1 => ['bg' => '#E64A19', 'text' => '#fff7f3'],
    2 => ['bg' => '#FBC02D', 'text' => '#3d2f00'],
    3 => ['bg' => '#388E3C', 'text' => '#f3fff3'],
    4 => ['bg' => '#1976D2', 'text' => '#f4f9ff'],
    5 => ['bg' => '#6A1B9A', 'text' => '#fbf5ff'],
];

// ── Geometry ────────────────────────────────────────────────────────────────
$chartMax    = 10;          // y-axis max (max shots per hole)
$scale       = 24;          // px per 1 shot/point unit
$blockHeight = $scale;      // 1 unit = 24 px (shot blocks inside bar have no internal gap)
$sfpGap      = 2;           // gap between stacked SFP blocks (right column)
$blockR      = 4;           // corner radius on all blocks
$halfWidth   = 28;          // width of each half-column
$halfGap     = 8;           // gap between left and right half-columns
$fullBarW    = $halfWidth * 2 + $halfGap; // 64 px — par outline and hit-rect span this
$groupWidth  = 72;          // column pitch per hole
$leftMargin  = 52;
$rightMargin = 20;
$plotTop     = 72;          // headroom above max-height bar for score label
$plotHeight  = $chartMax * $scale;        // 240 px
$baseY       = $plotTop + $plotHeight;    // 312
$chartWidth  = $leftMargin + 9 * $groupWidth + $rightMargin; // 720
$chartHeight = $baseY + 82;              // 394

$holes   = (array) ($chartData['holes'] ?? []);
$player  = (array) ($chartData['player'] ?? []);
$card    = (array) ($chartData['card']   ?? []);
$round   = (array) ($chartData['round']  ?? []);

$playerName = trim((string) ($player['alias'] ?? ''));
if ($playerName === '') {
    $playerName = (string) ($player['player_identifier'] ?? 'Unknown');
}

$totalScore   = (int)    ($card['score']           ?? 0);
$totalPoints  = (int)    ($card['points']          ?? 0);
$handicap     = (int)    ($card['handicap_applied'] ?? 0);
$seasonYear   = (string) ($round['season_year']    ?? '');
$roundNumber  = (int)    ($round['number_round']   ?? 0);

ob_start();
?>
<div class="card-chart-panel">

    <div class="card-chart-summary">
        <?php if ($seasonYear !== ''): ?>
        <span><strong>Season</strong> <?php echo htmlspecialchars($seasonYear); ?></span>
        <?php endif; ?>
        <?php if ($roundNumber > 0): ?>
        <span><strong>Round</strong> <?php echo $roundNumber; ?></span>
        <?php endif; ?>
        <span><strong>Handicap</strong> <?php echo $handicap; ?></span>
        <span><strong>Gross</strong> <?php echo $totalScore; ?></span>
        <span><strong>Points</strong> <?php echo $totalPoints; ?></span>
    </div>

    <?php if (empty($holes)): ?>
        <div class="card-chart-empty">No card data available for this player.</div>
    <?php else: ?>

    <div class="card-chart-legend" aria-label="Chart legend">
        <span class="card-chart-legend-item">
            <span class="card-chart-swatch card-chart-swatch-score"></span>Score
        </span>
        <span class="card-chart-legend-item">
            <span class="card-chart-swatch card-chart-swatch-shots"></span>Handicap shots
        </span>
        <span class="card-chart-legend-item">
            <span class="card-chart-swatch card-chart-swatch-sfp3"></span>Stableford points
        </span>
        <span class="card-chart-legend-item">
            <span class="card-chart-swatch card-chart-swatch-par"></span>Par
        </span>
    </div>

    <div class="card-chart-scroll">
        <svg class="card-chart-svg"
             viewBox="0 0 <?php echo $chartWidth; ?> <?php echo $chartHeight; ?>"
             role="img"
             aria-describedby="card-chart-desc"
             xmlns="http://www.w3.org/2000/svg">
            <desc id="card-chart-desc">
                Per-hole scorecard chart for <?php echo htmlspecialchars($playerName); ?>.
                Left bar = score; pink section at top of bar = handicap shots allowed.
                Right column = Stableford points (colour = hole total). Dotted outline = par.
                Hover a hole for details.
            </desc>

            <!-- Chart background -->
            <rect x="0" y="0" width="<?php echo $chartWidth; ?>" height="<?php echo $chartHeight; ?>"
                  rx="16" fill="#f8fffb" stroke="#d1fae5" />

            <!-- Y-axis grid lines and labels (every 2 units) -->
            <?php for ($g = 0; $g <= $chartMax; $g += 2): ?>
                <?php $gY = $baseY - $g * $scale; ?>
                <line x1="<?php echo $leftMargin - 8; ?>" y1="<?php echo $gY; ?>"
                      x2="<?php echo $chartWidth - $rightMargin; ?>" y2="<?php echo $gY; ?>"
                      class="card-chart-grid" />
                <text x="<?php echo $leftMargin - 12; ?>" y="<?php echo $gY + 4; ?>"
                      class="card-chart-axis-label"><?php echo $g; ?></text>
            <?php endfor; ?>

            <!-- Baseline -->
            <line x1="<?php echo $leftMargin - 8; ?>" y1="<?php echo $baseY; ?>"
                  x2="<?php echo $chartWidth - $rightMargin; ?>" y2="<?php echo $baseY; ?>"
                  class="card-chart-base-line" />

            <?php foreach ($holes as $index => $hole):
                $score  = min((int) ($hole['score']  ?? 0), $chartMax);
                $shots  = (int) ($hole['shots']  ?? 0);
                $pts    = (int) ($hole['points'] ?? 0);
                $par    = (int) ($hole['par']    ?? 0);
                $holeD  = (int) ($hole['hole_display'] ?? ($index + 1));

                $leftX   = $leftMargin + $index * $groupWidth;
                $rightX  = $leftX + $halfWidth + $halfGap;
                $centerX = $leftX + intdiv($fullBarW, 2);
                $leftCX  = $leftX + intdiv($halfWidth, 2);
                $rightCX = $rightX + intdiv($halfWidth, 2);
                $scoreLabel = ($score === 10) ? 'X' : (string) $score;

                // Key Y positions
                $barTopY  = ($score > 0) ? $baseY - $score * $scale : $baseY;
                $parTopY  = ($par  > 0) ? $baseY - $par  * $scale : $baseY;
                $sfpTopY  = ($pts  > 0) ? $baseY - $pts * $blockHeight - ($pts - 1) * $sfpGap : $baseY;

                // Hit-rect covers all visual elements incl. dotted empty space above bar
                $hitTopY  = min($barTopY, $parTopY, $sfpTopY) - 2;
                $hitH     = $baseY - $hitTopY;

                // Number of shot blocks that fit inside the bar
                $shotsInBar = min($shots, $score);

                // Tooltip text
                $tip = 'Hole ' . $holeD
                     . ' | Par ' . $par
                     . ' | Score ' . $scoreLabel
                     . ' | Shots allowed ' . $shots
                     . ' | Points ' . $pts;
            ?>
                <g>
                    <?php // ── 1. Blue score bar — full group width ───────────────── ?>
                    <?php if ($score > 0): ?>
                    <rect x="<?php echo $leftX; ?>" y="<?php echo $barTopY; ?>"
                          width="<?php echo $fullBarW; ?>" height="<?php echo $score * $scale; ?>"
                          rx="<?php echo $blockR; ?>" fill="#2563eb" />
                    <?php endif; ?>

                    <?php // ── 2. Pink shot blocks — left half, top of bar, stacked down ── ?>
                    <?php for ($s = 0; $s < $shotsInBar; $s++):
                        $shotY = $barTopY + $s * ($blockHeight + $sfpGap);
                    ?>
                    <rect x="<?php echo $leftX; ?>" y="<?php echo $shotY; ?>"
                          width="<?php echo $halfWidth; ?>" height="<?php echo $blockHeight; ?>"
                          rx="<?php echo $blockR; ?>" class="card-chart-shot-block" />
                    <?php endfor; ?>

                    <?php // ── 3. Par dotted outline (full group width, no fill) ────── ?>
                    <?php if ($par > 0): ?>
                    <rect x="<?php echo $leftX; ?>" y="<?php echo $parTopY; ?>"
                          width="<?php echo $fullBarW; ?>" height="<?php echo $par * $scale; ?>"
                          rx="<?php echo $blockR; ?>" class="card-chart-par-outline" />
                    <?php endif; ?>

                    <?php // ── 4. SFP point blocks — right half, rising from base ───── ?>
                    <?php
                        $sfpKey = min($pts, 5);
                        $sfpBg  = $sfpPalette[$sfpKey]['bg'] ?? '#6A1B9A';
                    ?>
                    <?php for ($p = 0; $p < $pts; $p++):
                        $sfpY  = $baseY - ($p + 1) * $blockHeight - $p * $sfpGap;
                    ?>
                    <rect x="<?php echo $rightX; ?>" y="<?php echo $sfpY; ?>"
                          width="<?php echo $halfWidth; ?>" height="<?php echo $blockHeight; ?>"
                          rx="<?php echo $blockR; ?>" fill="<?php echo $sfpBg; ?>" />
                    <?php endfor; ?>

                    <?php // ── 5. (score label removed — chart is self-explanatory) ──── ?>

                    <?php // ── 6. Transparent hit-rect for tooltip ───────────────── ?>
                    <rect x="<?php echo $leftX; ?>" y="<?php echo $hitTopY; ?>"
                          width="<?php echo $fullBarW; ?>" height="<?php echo $hitH; ?>"
                          rx="<?php echo $blockR; ?>" fill="transparent">
                        <title><?php echo htmlspecialchars($tip); ?></title>
                    </rect>

                    <?php // ── 7. Hole and par labels below baseline ─────────────── ?>
                    <text x="<?php echo $centerX; ?>" y="<?php echo $baseY + 18; ?>"
                          class="card-chart-hole-label">H<?php echo $holeD; ?></text>
                    <?php if ($par > 0): ?>
                    <text x="<?php echo $centerX; ?>" y="<?php echo $baseY + 34; ?>"
                          class="card-chart-par-label">P<?php echo $par; ?></text>
                    <?php endif; ?>
                </g>
            <?php endforeach; ?>

        </svg>
    </div>
    <?php endif; ?>

    <div class="card-chart-actions">
        <a href="/leaderboard" class="btn-secondary-pill">Back to Leaderboard</a>
    </div>

</div>
<?php
$content = ob_get_clean();
$pageHeading  = htmlspecialchars($playerName) . ' — Scorecard';
$pageStepLabel = '';
require __DIR__ . '/../layouts/player-progress.php';

