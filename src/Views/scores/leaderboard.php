<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Leaderboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="page-leaderboard">
<?php
$leaderboard = $resultsData['leaderboard'] ?? [];
$roundNumber = (int) ($round['round_number'] ?? 0);
$workflowStep = (string) ($round['workflow_step'] ?? 'not_started');
$fromScorerMenu = strpos($_SERVER['HTTP_REFERER'] ?? '', '/scorer/menu') !== false;
?>

<div class="leaderboard-layout">
    <header class="leaderboard-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="leaderboard-card">
            <h2 class="leaderboard-card-title"><?php echo htmlspecialchars($app_title); ?> Leaderboard</h2>
            <div class="leaderboard-card-body">
                <div class="section-title-wrap text-center">
                    <h2>Leaderboard</h2>
                    <div class="section-title-accent"></div>
                </div>

                <div class="leaderboard-status">
                    <span class="status-chip">Live Round <?php echo $roundNumber > 0 ? $roundNumber : '—'; ?> | <?php echo htmlspecialchars($workflowStep); ?></span>
                </div>

                <?php if (!empty($notice)): ?>
                    <div class="leaderboard-alert leaderboard-alert-info" role="status">
                        <?php echo htmlspecialchars((string) $notice); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($showPublishedResultsNudge)): ?>
                    <div class="leaderboard-alert leaderboard-alert-secondary" role="status">
                        Looking for completed competition outcomes?
                        <a href="/results">View published results</a>.
                    </div>
                <?php endif; ?>

                <div class="leaderboard-table-wrap">
                    <div class="table-responsive">
                        <table class="table leaderboard-table mb-0">
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th class="player-col">Player</th>
                                    <th>Points</th>
                                    <th>Gross</th>
                                    <th>Handicap</th>
                                    <th>Count Back ...</th>
                                    <th>Decision ...</th>
                                    <th>Twos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leaderboard)): ?>
                                    <tr>
                                        <td colspan="8" class="leaderboard-empty">No cards scored yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($leaderboard as $entry): ?>
                                        <?php
                                            $alias = trim((string) ($entry['alias'] ?? ''));
                                            $identifier = (string) ($entry['player_identifier'] ?? '');
                                            $name = $alias !== '' ? $alias : $identifier;
                                            $countBack = (int) ($entry['countback1'] ?? 0)
                                                . '-' . (int) ($entry['countback3'] ?? 0)
                                                . '-' . (int) ($entry['countback6'] ?? 0)
                                                . '-' . (int) ($entry['coin_toss'] ?? 0);
                                        ?>
                                        <tr>
                                            <td><?php echo (int) ($entry['position'] ?? 0); ?></td>
                                            <td class="player-col"><?php echo htmlspecialchars($name); ?></td>
                                            <td><?php echo (int) ($entry['points'] ?? 0); ?></td>
                                            <td><?php echo (int) ($entry['score'] ?? 0); ?></td>
                                            <td><?php echo (int) ($entry['handicap'] ?? 0); ?></td>
                                            <td><?php echo htmlspecialchars($countBack); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($entry['countback_decision'] ?? 'n/a')); ?></td>
                                            <td><?php echo (int) ($entry['twos_count'] ?? 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="leaderboard-actions">
                    <?php if ($fromScorerMenu): ?>
                        <a href="/scorer/menu" class="btn-secondary-pill">Back to Scorer Menu</a>
                    <?php endif; ?>
                    <a href="/" class="btn-primary-pill">Home</a>
                </div>

                <div class="leaderboard-note">
                    Auto-refresh is on (every 20 seconds); share this URL for live spectator viewing.
                </div>
            </div>
        </div>

        <footer class="leaderboard-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Live spectator mode: periodically reload to show latest scoring progress.
    setInterval(function () {
        window.location.reload();
    }, 20000);
</script>
</body>
</html>
