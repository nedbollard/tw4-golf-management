<?php
/**
 * Leaderboard card chart — single-column design.
 *
 * Per hole (one bar):
 *  PAR BLOCK   : pale rectangle from baseline up to par height (may extend above the score bar)
 *  SCORE BAR   : solid blue bar from baseline up to score height, drawn over the par block
 *  SHOT DOTS   : pink dot markers, one per handicap shot, starting at the top of the score bar
 *                and descending one score-line per shot
 *  POINT DOTS  : green dot markers, one per Stableford point, stacked from the baseline upward
 *  TOOLTIP     : transparent hit-rect covers the full column incl. empty space above the bar
 */

// ── Geometry ────────────────────────────────────────────────────────────────
$chartMax    = 10;          // y-axis max (max shots per hole)
$scale       = 24;          // px per 1 shot/point unit
$dotRadius   = 6;           // radius of shot/point dot markers
$dotOffset   = 9;           // horizontal offset of shot/point dots either side of centre
$blockR      = 4;           // corner radius on bar/par blocks
$barWidth    = 44;          // width of the single score bar
$parWidth    = (int) round($barWidth * 1.4); // par shadow block — double the previous overlap either side
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
$totalNet     = $totalScore - $handicap;
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
        <span class="card-chart-summary-sep">:</span>
        <span><strong>Gross</strong> <?php echo $totalScore; ?></span>
        <span><strong>Net</strong> <?php echo $totalNet; ?></span>
        <span><strong>Handicap</strong> <?php echo $handicap; ?></span>
        <span><strong>Points</strong> <?php echo $totalPoints; ?></span>
    </div>

    <?php if (empty($holes)): ?>
        <div class="card-chart-empty">No card data available for this player.</div>
    <?php else: ?>

    <div class="card-chart-legend" aria-label="Chart legend">
        <span class="card-chart-legend-item">
            <span class="card-chart-swatch card-chart-swatch-par"></span>Par
        </span>
        <span class="card-chart-legend-item">
            <span class="card-chart-swatch card-chart-swatch-score"></span>Score
        </span>
        <span class="card-chart-legend-item">
            <span class="card-chart-swatch card-chart-swatch-shots"></span>Handicap shots
        </span>
        <span class="card-chart-legend-item">
            <span class="card-chart-swatch card-chart-swatch-points"></span>Stableford points
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
                Blue bar = score. Pale block = par. Pink dots, descending from the top of the bar,
                = handicap shots received. Green dots, stacked from the baseline, = Stableford
                points earned. Hover a hole for details.
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
                $stroke = (int) ($hole['stroke']  ?? 0);
                $holeD  = (int) ($hole['hole_display'] ?? ($index + 1));

                $groupX  = $leftMargin + $index * $groupWidth;
                $barX    = $groupX + intdiv($groupWidth - $barWidth, 2);
                $centerX = $barX + intdiv($barWidth, 2);
                $parX    = $centerX - intdiv($parWidth, 2);
                $shotCX  = $centerX - $dotOffset;
                $pointCX = $centerX + $dotOffset;
                $scoreLabel = ($score === 10) ? 'X' : (string) $score;

                // Key Y positions
                $barTopY  = ($score > 0) ? $baseY - $score * $scale : $baseY;
                $parTopY  = ($par  > 0) ? $baseY - $par  * $scale : $baseY;
                $pointsTopY = ($pts > 0) ? $baseY - $pts * $scale : $baseY;

                // Number of shot dots that fit inside the bar (shots are allocated against scored strokes)
                $shotsInBar = min($shots, $score);

                // Hit-rect covers all visual elements incl. the wider par shadow and empty space above the bar
                $hitX     = min($barX, $parX);
                $hitW     = max($barX + $barWidth, $parX + $parWidth) - $hitX;
                $hitTopY  = min($barTopY, $parTopY, $pointsTopY) - $dotRadius - 2;
                $hitH     = $baseY - $hitTopY;

                // Tooltip text
                $tip = 'Hole ' . $holeD
                     . ' | Par ' . $par
                     . ' | Stroke ' . $stroke
                     . ' | Score ' . $scoreLabel
                     . ' | Shots allowed ' . $shots
                     . ' | Points ' . $pts;
            ?>
                <g>
                    <?php // ── 1. Pale par block — background reference, ~20% wider than the score bar ──── ?>
                    <?php if ($par > 0): ?>
                    <rect x="<?php echo $parX; ?>" y="<?php echo $parTopY; ?>"
                          width="<?php echo $parWidth; ?>" height="<?php echo $par * $scale; ?>"
                          rx="<?php echo $blockR; ?>" class="card-chart-par-block" />
                    <?php endif; ?>

                    <?php // ── 2. Blue score bar — drawn over the par block ─────────── ?>
                    <?php if ($score > 0): ?>
                    <rect x="<?php echo $barX; ?>" y="<?php echo $barTopY; ?>"
                          width="<?php echo $barWidth; ?>" height="<?php echo $score * $scale; ?>"
                          rx="<?php echo $blockR; ?>" fill="#2563eb" />
                    <?php endif; ?>

                    <?php // ── 3. Green Stableford-point dots — right of centre, ascending from the "1" line ── ?>
                    <?php for ($p = 0; $p < $pts; $p++):
                        $pointCY = $baseY - ($p + 1) * $scale;
                    ?>
                    <circle cx="<?php echo $pointCX; ?>" cy="<?php echo $pointCY; ?>"
                            r="<?php echo $dotRadius; ?>" class="card-chart-point-dot" />
                    <?php endfor; ?>

                    <?php // ── 4. Pink handicap-shot dots — left of centre, descending from the score line ── ?>
                    <?php for ($s = 0; $s < $shotsInBar; $s++):
                        $shotCY = $barTopY + $s * $scale;
                    ?>
                    <circle cx="<?php echo $shotCX; ?>" cy="<?php echo $shotCY; ?>"
                            r="<?php echo $dotRadius; ?>" class="card-chart-shot-dot" />
                    <?php endfor; ?>

                    <?php // ── 5. Transparent hit-rect for tooltip ───────────────── ?>
                    <rect x="<?php echo $hitX; ?>" y="<?php echo $hitTopY; ?>"
                          width="<?php echo $hitW; ?>" height="<?php echo $hitH; ?>"
                          rx="<?php echo $blockR; ?>" fill="transparent">
                        <title><?php echo htmlspecialchars($tip); ?></title>
                    </rect>

                    <?php // ── 6. Hole and par labels below baseline ─────────────── ?>
                    <text x="<?php echo $centerX; ?>" y="<?php echo $baseY + 18; ?>"
                          class="card-chart-hole-label">H<?php echo $holeD; ?></text>
                    <?php if ($par > 0): ?>
                    <text x="<?php echo $centerX; ?>" y="<?php echo $baseY + 34; ?>"
                          class="card-chart-par-label">P<?php echo $par; ?><?php echo $stroke > 0 ? ',S' . $stroke : ''; ?></text>
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
$pageHeading   = htmlspecialchars($playerName) . ' — Scorecard';
$pageStepLabel = '';
$pageCardTitle = 'Scorecard';
require __DIR__ . '/../layouts/player-progress.php';

