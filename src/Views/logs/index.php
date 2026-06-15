<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../../../public/assets/css/style.css')); ?>" rel="stylesheet">
</head>
<body class="page-logs">
    <?php
    $sessionUsername = (string) ($_SESSION['username'] ?? 'admin');
    $sessionRole = (string) ($_SESSION['role'] ?? 'admin');
    ?>

    <div class="logs-layout">
        <header class="logs-header">
            <h1>Twilight Golf Scoring</h1>
        </header>

        <main>
            <div class="logs-card">
                <div class="logs-card-title d-flex justify-content-between align-items-center">
                    <span><?php echo htmlspecialchars($app_title); ?> System Logs</span>
                    <a href="/admin/menu" class="btn btn-sm btn-outline-light">Back</a>
                </div>
                <div class="logs-card-body">
                    <div class="section-title-wrap text-center">
                        <h2>View Logs</h2>
                        <div class="section-title-accent"></div>
                    </div>

                    <?php if (!empty($loadError)): ?>
                        <div class="alert alert-warning" role="alert">
                            <?php echo htmlspecialchars($loadError); ?>
                        </div>
                    <?php endif; ?>

                    <div class="logs-session" role="status" aria-live="polite">
                        <strong>Session Active</strong>
                        <span>
                            Signed in as <?php echo htmlspecialchars($sessionUsername); ?>
                            (<?php echo htmlspecialchars(ucfirst($sessionRole)); ?>)
                        </span>
                    </div>

                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label for="level" class="form-label">Log Level</label>
                                <select class="form-select" id="level" name="level">
                                    <option value="">All Levels</option>
                                    <?php foreach ($filterOptions['levels'] as $level): ?>
                                        <option value="<?php echo $level; ?>" <?php echo $filters['level'] === $level ? 'selected' : ''; ?>>
                                            <?php echo $level; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="event_type" class="form-label">Event Type</label>
                                <select class="form-select" id="event_type" name="event_type">
                                    <option value="">All Events</option>
                                    <?php foreach ($filterOptions['event_types'] as $type): ?>
                                        <option value="<?php echo $type; ?>" <?php echo $filters['event_type'] === $type ? 'selected' : ''; ?>>
                                            <?php echo $type; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="username" class="form-label">Username</label>
                                <select class="form-select" id="username" name="username">
                                    <option value="">All Users</option>
                                    <?php foreach ($filterOptions['usernames'] as $username): ?>
                                        <option value="<?php echo $username; ?>" <?php echo $filters['username'] === $username ? 'selected' : ''; ?>>
                                            <?php echo $username; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search...">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="btn-group" role="group">
                                    <button type="submit" class="btn-action-primary">Apply Filters</button>
                                    <a href="/logs" class="btn btn-secondary">Clear</a>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="btn-group" role="group">
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['order' => 'DESC'])); ?>" class="btn btn-outline-secondary <?php echo $order === 'DESC' ? 'active' : ''; ?>">
                                        Newest First ↓
                                    </a>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['order' => 'ASC'])); ?>" class="btn btn-outline-secondary <?php echo $order === 'ASC' ? 'active' : ''; ?>">
                                        Oldest First ↑
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="logs-summary">
                        <strong><?php echo number_format($pagination['count']); ?></strong> log entries found
                        <?php if (!empty($filters['level']) || !empty($filters['event_type']) || !empty($filters['username']) || !empty($search)): ?>
                            (filtered)
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($logs)): ?>
                        <div class="table-responsive tw-table-wrap">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Level</th>
                                        <th>Event</th>
                                        <th>Message</th>
                                        <th>User</th>
                                        <th>IP Address</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td>
                                                <small><?php echo date('M j, Y H:i:s', strtotime($log['timestamp'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo getLevelColor($log['level']); ?>">
                                                    <?php echo $log['level']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $log['event_type']; ?></span>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($log['message']); ?>
                                                <?php if (!empty($log['context']) && is_array($log['context'])): ?>
                                                    <br><small class="text-muted">
                                                        <?php echo htmlspecialchars(formatContext($log['context'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $log['username'] ? '<span class="badge bg-info">' . htmlspecialchars($log['username']) . '</span>' : '-'; ?>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($log['ip_address']); ?></small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info" onclick="toggleDetails(<?php echo $log['row_id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr id="details-<?php echo $log['row_id']; ?>" style="display: none;">
                                            <td colspan="7">
                                                <div class="alert alert-light">
                                                    <strong>Full Details:</strong><br>
                                                    <strong>User Agent:</strong> <?php echo htmlspecialchars($log['user_agent'] ?? 'N/A'); ?><br>
                                                    <?php if (!empty($log['context']) && is_array($log['context'])): ?>
                                                        <strong>Context:</strong><br>
                                                        <pre><?php echo htmlspecialchars(json_encode($log['context'], JSON_PRETTY_PRINT)); ?></pre>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($pagination['total'] > 1): ?>
                            <nav class="mt-3">
                                <ul class="pagination justify-content-center">
                                    <?php if ($pagination['current'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current'] - 1])); ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $pagination['current'] - 2); $i <= min($pagination['total'], $pagination['current'] + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $pagination['current'] ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($pagination['current'] < $pagination['total']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current'] + 1])); ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="logs-empty text-center">
                            <i class="fas fa-search fa-3x mb-3"></i>
                            <h5>No logs found</h5>
                            <p>Try adjusting your filters or search criteria.</p>
                        </div>
                    <?php endif; ?>

                    <div class="logs-toolbar logs-toolbar-bottom">
                        <a href="/admin/menu" class="btn-secondary-pill">Back to Admin Menu</a>
                        <a href="/logs/export?<?php echo http_build_query(array_merge($_GET, ['export' => 1])); ?>" class="btn-action-primary">Export CSV</a>
                    </div>
                </div>
            </div>

            <footer class="logs-footer">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
            </footer>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        function toggleDetails(logId) {
            const detailsRow = document.getElementById('details-' + logId);
            detailsRow.style.display = detailsRow.style.display === 'none' ? 'table-row' : 'none';
        }
    </script>
</body>
</html>

<?php
// Helper functions for the view
function getLevelColor(string $level): string
{
    return match ($level) {
        'DEBUG' => 'secondary',
        'INFO' => 'info',
        'WARNING' => 'warning',
        'ERROR' => 'danger',
        'CRITICAL' => 'dark',
        default => 'secondary'
    };
}

function formatContext(array $context): string
{
    $parts = [];
    foreach ($context as $key => $value) {
        if (is_array($value)) {
            $parts[] = "$key: " . json_encode($value);
        } else {
            $parts[] = "$key: $value";
        }
    }
    return implode(', ', $parts);
}
?>
