<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Leaderboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <style>
        .board-shell {
            max-width: 1040px;
        }
        .board-title {
            text-align: center;
            margin-bottom: 1rem;
        }
        .board-table th,
        .board-table td {
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }
        .board-table td.player-col,
        .board-table th.player-col {
            text-align: left;
            white-space: normal;
            min-width: 160px;
        }
        .status-chip {
            border-radius: 999px;
            padding: 0.25rem 0.65rem;
            font-size: 0.8rem;
            font-weight: 600;
            background: #e7f1ff;
            color: #0a58ca;
            border: 1px solid #b6d4fe;
        }
        .button-row {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 1.25rem;
        }
        @media (max-width: 576px) {
            .board-title {
                font-size: 1.35rem;
            }
            .button-row {
                flex-direction: column;
            }
            .button-row .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body class="bg-light">
<?php
$leaderboard = $resultsData['leaderboard'] ?? [];
$roundNumber = (int) ($round['round_number'] ?? 0);
$workflowStep = (string) ($round['workflow_step'] ?? 'not_started');
?>

<div class="container py-4 board-shell">
    <div class="d-flex justify-content-center mb-2">
        <span class="status-chip">Live Round <?php echo $roundNumber > 0 ? $roundNumber : '—'; ?> | <?php echo htmlspecialchars($workflowStep); ?></span>
    </div>

    <h2 class="board-title">Leaderboard</h2>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-info" role="status">
            <?php echo htmlspecialchars((string) $notice); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($showPublishedResultsNudge)): ?>
        <div class="alert alert-secondary" role="status">
            Looking for completed competition outcomes?
            <a href="/results" class="alert-link">View published results</a>.
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h3 class="h4 text-center mb-3">Scoring Progress</h3>
            <div class="table-responsive">
                <table class="table table-bordered table-striped board-table mb-0">
                    <thead class="table-light">
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
                                <td colspan="8">No cards scored yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($leaderboard as $entry): ?>
                                <?php
                                    $name = (string) ($entry['display_name'] ?? $entry['player_identifier'] ?? '');
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
    </div>

    <div class="button-row">
        <a href="/" class="btn btn-primary">Home</a>
    </div>

    <div class="text-center mt-3 text-muted" style="font-size: 0.85rem;">
        Auto-refresh is on (every 20 seconds); share this URL for live spectator viewing.
    </div>
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
