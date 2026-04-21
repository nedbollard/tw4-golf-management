<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Results Recorded</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <style>
        .results-shell {
            max-width: 1020px;
        }
        .results-title {
            text-align: center;
            margin-bottom: 1.25rem;
        }
        .results-table th,
        .results-table td {
            text-align: center;
            vertical-align: middle;
        }
        .btn-row {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body class="bg-light">
<?php
$roundNumber = (int) ($round['round_number'] ?? 0);
$podium = $recordedData['podium'] ?? [];
$ballWinners = $recordedData['ball_winners'] ?? [];
$commiserations = $recordedData['commiserations'] ?? [];
?>

<div class="container py-4 results-shell">
    <?php if (!empty($success)): ?>
        <div class="alert alert-success" role="status">
            <?php echo htmlspecialchars((string) $success); ?>
        </div>
    <?php endif; ?>

    <h2 class="results-title">Results Recorded - Round <?php echo $roundNumber; ?></h2>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h3 class="h4 text-center mb-3">Results</h3>
            <div class="table-responsive">
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
                            <tr>
                                <td colspan="8">No podium data available.</td>
                            </tr>
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
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h3 class="h4 text-center mb-3">Ball Winners</h3>
            <div class="table-responsive">
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
                            <tr>
                                <td colspan="3">No ball winners recorded.</td>
                            </tr>
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
        </div>
    </div>

    <?php if (!empty($commiserations)): ?>
        <div class="card shadow-sm mb-4 border-warning">
            <div class="card-body">
                <h3 class="h5 mb-2">Commiserations</h3>
                <p class="mb-3 text-muted">Players tied on points for third place but missing out after countback.</p>
                <ol class="mb-0">
                    <?php foreach ($commiserations as $entry): ?>
                        <li>
                            <?php
                                $name = (string) ($entry['display_name'] ?? $entry['player_identifier'] ?? '');
                                $decision = (string) ($entry['countback_decision'] ?? 'n/a');
                                $points = (int) ($entry['points'] ?? 0);
                                echo htmlspecialchars($name . ' - ' . $points . ' points (' . $decision . ')');
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    <?php endif; ?>

    <div class="btn-row">
        <a href="/scorer/menu" class="btn btn-outline-secondary">Back</a>
        <a href="/" class="btn btn-primary">Home</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
