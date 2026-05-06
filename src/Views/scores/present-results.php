<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Present Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="page-present-results">
<?php
$roundNumber = (int) ($round['round_number'] ?? 0);
$leaderboard = $resultsData['leaderboard'] ?? [];
$options = $resultsData['closest_to_pin_options'] ?? [['identifier' => 'not taker', 'label' => 'not taker']];
$feeEntry = (int) ($resultsData['fee_entry'] ?? 0);
$prizePool = (int) ($resultsData['prize_pool'] ?? 0);
$selectedClosestToPin = (string) ($old['closest_to_pin_identifier'] ?? 'not taker');
?>
<div class="present-results-layout">
    <header class="present-results-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="present-results-card">
            <h2 class="present-results-card-title"><?php echo htmlspecialchars($app_title); ?> — Present Results</h2>
            <div class="present-results-card-body">
                <div class="section-title-wrap text-center">
                    <h2>Round <?php echo $roundNumber; ?> Results</h2>
                    <div class="section-title-accent"></div>
                </div>

                <div class="present-results-chips">
                    <span class="present-results-chip">Cards: <strong><?php echo count($leaderboard); ?></strong></span>
                    <span class="present-results-chip">Entry fee: <strong><?php echo $feeEntry; ?></strong></span>
                    <span class="present-results-chip">Prize pool: <strong><?php echo $prizePool; ?></strong></span>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="present-results-alert present-results-alert-error" role="alert">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars((string) $error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($leaderboard)): ?>
                    <div class="section-title-wrap">
                        <h3>Finishing Order and Countback</h3>
                        <div class="section-title-accent"></div>
                    </div>
                    <div class="table-responsive mb-4">
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

                    <div class="section-title-wrap">
                        <h3>Closest to Pin</h3>
                        <div class="section-title-accent"></div>
                    </div>
                    <form method="POST" action="/scores/present-results">
                        <div class="present-results-form-group">
                            <label for="closest_to_pin_identifier" class="present-results-form-label">Winner</label>
                            <select id="closest_to_pin_identifier" name="closest_to_pin_identifier" class="present-results-form-select" required>
                                <?php foreach ($options as $option): ?>
                                    <?php
                                        $identifier = (string) ($option['identifier'] ?? '');
                                        $label = (string) ($option['label'] ?? $identifier);
                                    ?>
                                    <option value="<?php echo htmlspecialchars($identifier); ?>"
                                        <?php echo ($selectedClosestToPin === $identifier) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="present-results-form-text">Use "not taker" when no player lands on the nominated green.</div>
                        </div>

                        <div class="present-results-actions">
                            <a href="/scorer/menu" class="btn-secondary-pill">Cancel</a>
                            <button type="submit" class="btn-gold-pill">Store Results and Present</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="present-results-alert present-results-alert-warning">
                        No cards are available for this round.
                    </div>
                    <div class="present-results-actions">
                        <a href="/scorer/menu" class="btn-secondary-pill">Back to Scorer Menu</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <footer class="present-results-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
