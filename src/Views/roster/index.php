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
<body class="page-roster">
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
                    <h2>Roster and Players</h2>
                    <div class="section-title-accent"></div>
                </div>

                <div class="roster-session" role="status" aria-live="polite">
                    <strong>Session Active</strong>
                    <span>
                        Signed in as <?php echo htmlspecialchars($sessionUsername); ?>
                        (<?php echo htmlspecialchars(ucfirst($sessionRole)); ?>)
                    </span>
                </div>

                <div class="roster-toolbar">
                    <a href="/roster/create" class="btn-action-primary">Add New Player</a>
                </div>

                <?php if (empty($roster)): ?>
                    <div class="roster-empty text-center">
                        <i class="fas fa-info-circle"></i>
                        <p class="mb-0">No players found. <a href="/roster/create">Add your first player</a>.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive tw-table-wrap">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Player ID</th>
                                    <th>Name</th>
                                    <th>Alias</th>
                                    <th>Gender</th>
                                    <th>Status</th>
                                    <th>Handicap</th>
                                    <th>Date First Played</th>
                                    <th>Updated By</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roster as $player): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($player['player_identifier']); ?></td>
                                        <td><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></td>
                                        <td>
                                            <?php if (!empty($player['alias'])): ?>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($player['alias']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $player['gender'] === 'male' ? 'primary' : 'danger'; ?>">
                                                <?php echo ucfirst($player['gender']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $player['status'] === 'active' ? 'success' : ($player['status'] === 'scored' ? 'warning text-dark' : 'secondary'); ?>">
                                                <?php echo ucfirst($player['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($player['handicap']); ?></td>
                                        <td>
                                            <?php if (!empty($player['date_first_played'])): ?>
                                                <?php echo date('M j, Y', strtotime($player['date_first_played'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($player['updated_by'])): ?>
                                                <?php echo htmlspecialchars($player['updated_by']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($player['updated_at'])): ?>
                                                <?php echo date('M j, Y g:i A', strtotime($player['updated_at'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="/roster/<?php echo $player['row_id']; ?>" class="btn btn-sm btn-outline-secondary">View</a>
                                                <a href="/roster/<?php echo $player['row_id']; ?>/edit" class="btn-action-primary btn-sm">Edit</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="roster-toolbar roster-toolbar-bottom">
                    <a href="/scorer/menu" class="btn-secondary-pill">Back to Scorer Menu</a>
                </div>
            </div>
        </div>

        <footer class="roster-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
