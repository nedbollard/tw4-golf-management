<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../../../public/assets/css/style.css')); ?>" rel="stylesheet">
</head>
<body class="page-roster-show">
<?php
$sessionUsername = (string) ($_SESSION['username'] ?? 'scorer');
$sessionRole = (string) ($_SESSION['role'] ?? 'scorer');
?>
<div class="roster-layout">
    <header class="roster-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="roster-card">
            <h2 class="roster-card-title"><?php echo htmlspecialchars($title); ?></h2>
            <div class="roster-card-body">
                <div class="section-title-wrap text-center">
                    <h2>Player Details</h2>
                    <div class="section-title-accent"></div>
                </div>

                <div class="roster-session" role="status" aria-live="polite">
                    <strong>Session Active</strong>
                    <span>
                        Signed in as <?php echo htmlspecialchars($sessionUsername); ?>
                        (<?php echo htmlspecialchars(ucfirst($sessionRole)); ?>)
                    </span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <h6>Basic Information</h6>
                        <table class="table table-sm table-striped roster-detail-table">
                            <tr>
                                <td><strong>Player ID:</strong></td>
                                <td><?php echo htmlspecialchars($player['player_identifier']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></td>
                            </tr>
                            <?php if (!empty($player['alias'])): ?>
                                <tr>
                                    <td><strong>Alias:</strong></td>
                                    <td><?php echo htmlspecialchars($player['alias']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td><strong>Gender:</strong></td>
                                <td><?php echo ucfirst(htmlspecialchars($player['gender'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $player['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($player['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Golf Information</h6>
                        <table class="table table-sm table-striped roster-detail-table">
                            <tr>
                                <td><strong>Handicap:</strong></td>
                                <td><?php echo htmlspecialchars($player['handicap']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Created:</strong></td>
                                <td><?php echo htmlspecialchars($player['created_at'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Date First Played:</strong></td>
                                <td>
                                    <?php if (!empty($player['date_first_played'])): ?>
                                        <?php echo htmlspecialchars($player['date_first_played']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not yet played</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Updated By:</strong></td>
                                <td>
                                    <?php if (!empty($player['updated_by'])): ?>
                                        <?php echo htmlspecialchars($player['updated_by']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Last Updated:</strong></td>
                                <td><?php echo htmlspecialchars($player['updated_at'] ?? 'N/A'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="roster-toolbar roster-toolbar-bottom">
                    <a href="/roster" class="btn-secondary-pill">Back to Roster</a>
                    <a href="/roster/<?php echo $player['row_id']; ?>/edit" class="btn-action-primary">Edit Entry</a>
                </div>
            </div>
        </div>

        <footer class="roster-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
