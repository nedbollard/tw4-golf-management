<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - System Configuration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../../../public/assets/css/style.css')); ?>" rel="stylesheet">
</head>
<body class="page-config">
<?php
$sessionUsername = (string) ($_SESSION['username'] ?? 'admin');
$sessionRole = (string) ($_SESSION['role'] ?? 'admin');
?>
<div class="config-layout">
    <header class="config-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="config-card">
            <h2 class="config-card-title"><?php echo htmlspecialchars($app_title); ?> System Configuration</h2>
            <div class="config-card-body">
                <div class="section-title-wrap text-center">
                    <h2>Configuration Settings</h2>
                    <div class="section-title-accent"></div>
                </div>

                <p class="config-intro">Update core system settings used by scoring and administration.</p>

                <div class="config-session" role="status" aria-live="polite">
                    <strong>Session Active</strong>
                    <span>
                        Signed in as <?php echo htmlspecialchars($sessionUsername); ?>
                        (<?php echo htmlspecialchars(ucfirst($sessionRole)); ?>)
                    </span>
                </div>

                <?php if (!empty($success)): ?>
                    <div class="config-alert config-alert-success" role="status">
                        <?php
                        $successMessages = is_array($success) ? $success : [$success];
                        foreach ($successMessages as $message):
                        ?>
                            <div><?php echo htmlspecialchars((string) $message); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="config-alert config-alert-error" role="alert">
                        <?php
                        $errorMessages = is_array($errors) ? $errors : [$errors];
                        foreach ($errorMessages as $error):
                        ?>
                            <div><?php echo htmlspecialchars((string) $error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/config">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="config-table-wrap">
                        <table class="config-table">
                            <thead>
                                <tr>
                                    <th>Configuration Name</th>
                                    <th>Type</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sortedConfigs = $configs;
                                usort($sortedConfigs, static function (array $a, array $b): int {
                                    $nameA = (string) ($a['config_name'] ?? '');
                                    $nameB = (string) ($b['config_name'] ?? '');

                                    $weights = [
                                        'team_haggle_state' => 100,
                                        'ident_eclectic' => 101,
                                    ];

                                    $weightA = $weights[$nameA] ?? 1000;
                                    $weightB = $weights[$nameB] ?? 1000;

                                    if ($weightA !== $weightB) {
                                        return $weightA <=> $weightB;
                                    }

                                    return strcmp($nameA, $nameB);
                                });
                                ?>
                                <?php foreach ($sortedConfigs as $config): ?>
                                    <?php if ($config['config_name'] === 'config_status') continue; ?>
                                    <?php
                                    $rawName = (string) $config['config_name'];
                                    $displayName = in_array($rawName, ['handicap_sytem', 'handicap_system'], true)
                                        ? 'handicap_method'
                                        : ($rawName === 'ident_eclectic' ? 'Eclectic Competition' : $rawName);
                                    ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="name_<?php echo $config['row_id']; ?>" value="<?php echo htmlspecialchars($rawName); ?>">
                                            <strong><?php echo htmlspecialchars($displayName); ?></strong>
                                            <?php if (in_array($displayName, ['club_name', 'competition_name', 'season_year'], true)): ?>
                                                <span class="config-badge config-badge-critical">Critical</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <input type="hidden" name="type_<?php echo $config['row_id']; ?>" value="<?php echo htmlspecialchars($config['config_type']); ?>">
                                            <span class="config-badge <?php echo $config['config_type'] === 'int' ? 'config-badge-int' : 'config-badge-string'; ?>">
                                                <?php echo htmlspecialchars($config['config_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (in_array($rawName, ['handicap_method', 'handicap_sytem', 'handicap_system'], true)): ?>
                                                <?php
                                                $handicapMethod = strtolower(trim((string) ($config['config_value_string'] ?? '')));
                                                if ($handicapMethod === 'm') {
                                                    $handicapMethod = 'modern';
                                                } elseif ($handicapMethod === 'l') {
                                                    $handicapMethod = 'legacy';
                                                } elseif ($handicapMethod === 'n') {
                                                    $handicapMethod = 'none';
                                                }
                                                if (!in_array($handicapMethod, ['modern', 'legacy', 'none'], true)) {
                                                    $handicapMethod = 'modern';
                                                }
                                                ?>
                                                <select class="config-input" name="config_<?php echo $config['row_id']; ?>">
                                                    <option value="modern" <?php echo $handicapMethod === 'modern' ? 'selected' : ''; ?>>modern</option>
                                                    <option value="legacy" <?php echo $handicapMethod === 'legacy' ? 'selected' : ''; ?>>legacy</option>
                                                    <option value="none" <?php echo $handicapMethod === 'none' ? 'selected' : ''; ?>>none</option>
                                                </select>
                                            <?php elseif ($rawName === 'team_haggle_state'): ?>
                                                <select class="config-input" name="config_<?php echo $config['row_id']; ?>">
                                                    <option value="F" <?php echo $config['config_value_string'] === 'F' ? 'selected' : ''; ?>>F - Floating</option>
                                                    <option value="L" <?php echo $config['config_value_string'] === 'L' ? 'selected' : ''; ?>>L - Locked</option>
                                                </select>
                                            <?php else: ?>
                                                <input
                                                    type="<?php echo $config['config_type'] === 'int' ? 'number' : 'text'; ?>"
                                                    class="config-input"
                                                    name="config_<?php echo $config['row_id']; ?>"
                                                    value="<?php echo htmlspecialchars((string) $config['config_value_string']); ?>"
                                                    <?php echo $rawName === 'season_year' ? 'pattern="\\d{2}_\\d{2}" maxlength="5"' : ''; ?>
                                                    <?php echo $config['config_type'] === 'int' ? 'step="1"' : ''; ?>
                                                >
                                            <?php endif; ?>

                                            <?php if ($rawName === 'season_year'): ?>
                                                <div class="config-help">Use format NN_NN, for example 25_26.</div>
                                            <?php endif; ?>

                                            <?php if (isset($errors["config_{$config['row_id']}"])): ?>
                                                <div class="config-error-inline"><?php echo htmlspecialchars((string) $errors["config_{$config['row_id']}"]); ?></div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="config-actions">
                        <a href="/admin/menu" class="btn-secondary-pill">Back to Admin Menu</a>
                        <button type="submit" class="btn-primary-pill">Save Configuration</button>
                    </div>
                </form>

                <div class="config-notes">
                    <h3>Configuration Notes</h3>
                    <ul>
                        <li>String values can contain any text.</li>
                        <li>Integer values must be whole numbers.</li>
                        <li>Season year uses the format NN_NN, for example 25_26.</li>
                        <li>handicap_method supports modern, legacy, and none.</li>
                        <li>All changes are logged with user attribution.</li>
                    </ul>
                </div>
            </div>
        </div>

        <footer class="config-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
