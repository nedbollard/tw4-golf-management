<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Present Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <style>
        .present-navbar {
            background: linear-gradient(90deg, #0f172a 0%, #1e293b 100%);
            border-bottom: 2px solid #475569;
            color: #f8fafc;
        }
        .present-navbar .navbar-title {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }
        .present-wrap {
            max-width: 980px;
        }
        .summary-chip {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
            border-radius: 999px;
            padding: 0.3rem 0.72rem;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .results-grid th,
        .results-grid td {
            vertical-align: middle;
            text-align: center;
        }
        .results-grid td.name-col,
        .results-grid th.name-col {
            text-align: left;
        }
        .place-row-1 {
            background: #fff8dc;
        }
        .place-row-2 {
            background: #f5f7fa;
        }
        .place-row-3 {
            background: #fff1e8;
        }
    </style>
</head>
<body class="bg-light">
<?php
$roundNumber = (int) ($round['round_number'] ?? 0);
$leaderboard = $resultsData['leaderboard'] ?? [];
$options = $resultsData['closest_to_pin_options'] ?? ['not taker'];
$feeEntry = (int) ($resultsData['fee_entry'] ?? 0);
$prizePool = (int) ($resultsData['prize_pool'] ?? 0);
$selectedClosestToPin = (string) ($old['closest_to_pin_identifier'] ?? 'not taker');
?>
<nav class="present-navbar navbar" aria-label="Present results navigation">
    <div class="container-fluid px-3 py-2 d-flex justify-content-between align-items-center">
        <span class="navbar-title">Present Results</span>
        <div class="d-flex gap-2">
            <a href="/scorer/menu" class="btn btn-outline-light">Cancel</a>
        </div>
    </div>
</nav>

<div class="container py-4 present-wrap">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="summary-chip">Round <?php echo $roundNumber; ?></span>
        <span class="summary-chip">Cards <?php echo count($leaderboard); ?></span>
        <span class="summary-chip">Entry fee <?php echo $feeEntry; ?></span>
        <span class="summary-chip">Prize pool <?php echo $prizePool; ?></span>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?php echo htmlspecialchars((string) $error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($leaderboard)): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h5 mb-3">Finishing Order and Countback</h2>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm results-grid mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Place</th>
                                <th class="name-col">Player</th>
                                <th>Identifier</th>
                                <th>Points</th>
                                <th>Last 1</th>
                                <th>Last 3</th>
                                <th>Last 6</th>
                                <th>Decision</th>
                                <th>Prize</th>
                                <th>Twos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaderboard as $entry): ?>
                                <?php $placeClass = 'place-row-' . (int) ($entry['position'] ?? 0); ?>
                                <tr class="<?php echo htmlspecialchars($placeClass); ?>">
                                    <td><?php echo (int) ($entry['position'] ?? 0); ?></td>
                                    <td class="name-col"><?php echo htmlspecialchars((string) ($entry['display_name'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($entry['player_identifier'] ?? '')); ?></td>
                                    <td><?php echo (int) ($entry['points'] ?? 0); ?></td>
                                    <td><?php echo (int) ($entry['countback1'] ?? 0); ?></td>
                                    <td><?php echo (int) ($entry['countback3'] ?? 0); ?></td>
                                    <td><?php echo (int) ($entry['countback6'] ?? 0); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($entry['countback_decision'] ?? 'n/a')); ?></td>
                                    <td><?php echo (int) ($entry['prize'] ?? 0); ?></td>
                                    <td><?php echo (int) ($entry['twos_count'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Closest to Pin</h2>
                <form method="POST" action="/scores/present-results">
                    <div class="mb-3">
                        <label for="closest_to_pin_identifier" class="form-label">Winner</label>
                        <select id="closest_to_pin_identifier" name="closest_to_pin_identifier" class="form-select" required>
                            <?php foreach ($options as $identifier): ?>
                                <option value="<?php echo htmlspecialchars((string) $identifier); ?>"
                                    <?php echo ($selectedClosestToPin === (string) $identifier) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string) $identifier); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Use "not taker" when no player lands on the nominated green.</div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/scorer/menu" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success">Store Results and Present</button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning mb-0">No cards are available for this round.</div>
    <?php endif; ?>
</div>
</body>
</html>
