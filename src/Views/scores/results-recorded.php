<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Results Recorded</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="page-results-recorded">
<?php
$roundNumber = (int) ($round['round_number'] ?? 0);
$podium = $recordedData['podium'] ?? [];
$ballWinners = $recordedData['ball_winners'] ?? [];
$commiserations = $recordedData['commiserations'] ?? [];
?>

<div class="results-recorded-layout">
    <header class="results-recorded-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="results-recorded-card">
            <h2 class="results-recorded-card-title"><?php echo htmlspecialchars($app_title); ?> — Results Recorded</h2>
            <div class="results-recorded-card-body">
                <div class="section-title-wrap text-center">
                    <h2>Round <?php echo $roundNumber; ?> — Results Recorded</h2>
                    <div class="section-title-accent"></div>
                </div>

                <?php if (!empty($success)): ?>
                    <div class="results-recorded-alert results-recorded-alert-success" role="status">
                        <?php echo htmlspecialchars((string) $success); ?>
                    </div>
                <?php endif; ?>

                <div class="section-title-wrap">
                    <h3>Results</h3>
                    <div class="section-title-accent"></div>
                </div>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped results-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Position</th>
                                <th>Prize</th>
                                <th>Player</th>
                                <th>Points</th>
                                <th>Gross</th>
                                <th>Handicap</th>
                                <th>Count Back ...</th>
                                <th>Decision ...</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($podium)): ?>
                                <tr><td colspan="8">No podium data available.</td></tr>
                            <?php else: ?>
                                <?php foreach ($podium as $entry): ?>
                                    <?php
                                        $name = (string) ($entry['display_name'] ?? $entry['player_identifier'] ?? '');
                                        $countBack = (int) ($entry['countback1'] ?? 0)
                                            . '-' . (int) ($entry['countback3'] ?? 0)
                                            . '-' . (int) ($entry['countback6'] ?? 0)
                                            . '-' . (int) ($entry['coin_toss'] ?? 0);
                                    ?>
                                    <tr>
                                        <td><?php echo (int) ($entry['position'] ?? 0); ?></td>
                                        <td>$<?php echo number_format((float) ($entry['prize'] ?? 0), 2); ?></td>
                                        <td><?php echo htmlspecialchars($name); ?></td>
                                        <td><?php echo (int) ($entry['points'] ?? 0); ?></td>
                                        <td><?php echo (int) ($entry['score'] ?? 0); ?></td>
                                        <td><?php echo (int) ($entry['handicap'] ?? 0); ?></td>
                                        <td><?php echo htmlspecialchars($countBack); ?></td>
                                        <td><?php echo htmlspecialchars((string) ($entry['countback_decision'] ?? 'n/a')); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="section-title-wrap">
                    <h3>Ball Winners</h3>
                    <div class="section-title-accent"></div>
                </div>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped results-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Who</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ballWinners)): ?>
                                <tr><td colspan="3">No ball winners recorded.</td></tr>
                            <?php else: ?>
                                <?php foreach ($ballWinners as $winner): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string) ($winner['type'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars((string) ($winner['who'] ?? '')); ?></td>
                                        <td><?php echo (int) ($winner['count'] ?? 0); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($commiserations)): ?>
                    <div class="section-title-wrap">
                        <h3>Commiserations</h3>
                        <div class="section-title-accent"></div>
                    </div>
                    <div class="results-recorded-commiserations">
                        <p class="results-recorded-commiserations-note">Players tied on points for third place but missing out after countback.</p>
                        <ol class="mb-0">
                            <?php foreach ($commiserations as $entry): ?>
                                <li>
                                    <?php
                                        $name = (string) ($entry['display_name'] ?? $entry['player_identifier'] ?? '');
                                        $decision = (string) ($entry['countback_decision'] ?? 'n/a');
                                        $points = (int) ($entry['points'] ?? 0);
                                        echo htmlspecialchars($name . ' — ' . $points . ' points (' . $decision . ')');
                                    ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                <?php endif; ?>

                <div class="results-recorded-actions">
                    <a href="/scorer/menu" class="btn-secondary-pill">Back to Scorer Menu</a>
                    <a href="/" class="btn-primary-pill">Home</a>
                </div>
            </div>
        </div>

        <footer class="results-recorded-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
