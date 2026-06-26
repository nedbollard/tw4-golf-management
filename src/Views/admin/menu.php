<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Admin Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../../../public/assets/css/style.css')); ?>" rel="stylesheet">
</head>
<body class="page-admin-menu">
<?php
$sessionUsername = (string) ($_SESSION['username'] ?? 'admin');
$sessionRole = (string) ($_SESSION['role'] ?? 'admin');
?>
<div class="admin-layout">
    <header class="admin-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="admin-card">
            <h2 class="admin-card-title"><?php echo htmlspecialchars($app_title); ?> Admin Menu</h2>
            <div class="admin-card-body">
                <div class="section-title-wrap text-center">
                    <h2>Administrator Options</h2>
                    <div class="section-title-accent"></div>
                </div>

                <p class="admin-intro">System administration functions</p>

                <div class="admin-session" role="status" aria-live="polite">
                    <strong>Session Active</strong>
                    <span>
                        Signed in as <?php echo htmlspecialchars($sessionUsername); ?>
                        (<?php echo htmlspecialchars(ucfirst($sessionRole)); ?>)
                    </span>
                </div>

                <?php if (!empty($success)): ?>
                    <div class="admin-alert admin-alert-success" role="status">
                        <?php echo htmlspecialchars((string) $success); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="admin-alert admin-alert-error" role="alert">
                        <?php
                        $errorMessages = is_array($errors) ? $errors : [$errors];
                        foreach ($errorMessages as $error):
                        ?>
                            <div><?php echo htmlspecialchars((string) $error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="admin-grid" role="list" aria-label="Admin actions">
                    <div class="admin-panel" role="listitem">
                        <h3>Configure System</h3>
                        <p>Manage system settings and configuration.</p>
                        <a href="/config" class="btn-admin-config">Configure System</a>
                    </div>

                    <div class="admin-panel" role="listitem">
                        <h3>Scoring State Management</h3>
                        <p>Admin-only controls for workflow and lock state.</p>
                        <a href="/admin/scoring-state" class="btn-admin-scoring">Manage Scoring State</a>
                    </div>

                    <div class="admin-panel" role="listitem">
                        <h3>View Logs</h3>
                        <p>View system logs and key audit events.</p>
                        <a href="/logs" class="btn-admin-logs">View Logs</a>
                    </div>

                    <div class="admin-panel" role="listitem">
                        <h3>Staff Management</h3>
                        <p>Add, update, and manage staff accounts.</p>
                        <a href="/staff" class="btn-admin-staff">Manage Staff</a>
                    </div>

                    <div class="admin-panel" role="listitem">
                        <h3>Course Club Management</h3>
                        <p>Manage course holes and club configuration.</p>
                        <a href="/course-club" class="btn-admin-course">Manage Course</a>
                    </div>

                    <div class="admin-panel" role="listitem">
                        <h3>Course Played Management</h3>
                        <p>Create and maintain played 9-hole course definitions.</p>
                        <a href="/course-played" class="btn-admin-played">Manage Course Played</a>
                    </div>
                </div>

                <div class="admin-actions">
                    <a href="/" class="btn-secondary-pill">Back to Main Menu</a>
                    <a href="/logout" class="btn-primary-pill">Logout</a>
                </div>
            </div>
        </div>

        <footer class="admin-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
