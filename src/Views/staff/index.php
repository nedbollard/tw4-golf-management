<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../../../public/assets/css/style.css')); ?>" rel="stylesheet">
</head>
<body class="page-staff">
    <?php
    $sessionUsername = (string) ($_SESSION['username'] ?? 'admin');
    $sessionRole = (string) ($_SESSION['role'] ?? 'admin');
    ?>

    <div class="staff-layout">
        <header class="staff-header">
            <h1>Twilight Golf Scoring</h1>
        </header>

        <main>
            <div class="staff-card">
                <h2 class="staff-card-title"><?php echo htmlspecialchars($app_title); ?> Staff Management</h2>
                <div class="staff-card-body">
                    <div class="section-title-wrap text-center">
                        <h2>Manage Staff</h2>
                        <div class="section-title-accent"></div>
                    </div>

                    <div class="staff-session" role="status" aria-live="polite">
                        <strong>Session Active</strong>
                        <span>
                            Signed in as <?php echo htmlspecialchars($sessionUsername); ?>
                            (<?php echo htmlspecialchars(ucfirst($sessionRole)); ?>)
                        </span>
                    </div>

                    <div class="staff-toolbar">
                        <a href="/staff/add" class="btn-action-primary">Add Staff</a>
                    </div>

                    <?php if (!empty($success)): ?>
                        <div class="staff-alert staff-alert-success" role="status">
                            <?php
                            $successMessages = is_array($success) ? $success : [$success];
                            foreach ($successMessages as $message):
                            ?>
                                <div><?php echo htmlspecialchars($message); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="staff-alert staff-alert-error" role="alert">
                            <?php
                            $errorMessages = is_array($errors) ? $errors : [$errors];
                            foreach ($errorMessages as $error):
                            ?>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive tw-table-wrap">
                        <table class="table table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Staff ID</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staff as $member): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member->getStaffId() ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($member->getUsername()); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $member->isAdmin() ? 'danger' : 'primary'; ?>">
                                                <?php echo htmlspecialchars($member->getRole()); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $member->isActive() ? 'success' : 'secondary'; ?>">
                                                <?php echo $member->isActive() ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="/staff/edit/<?php echo $member->getStaffId(); ?>" class="btn-action-primary btn-sm">Edit</a>
                                            <?php if ($member->getUsername() !== ($_SESSION['username'] ?? '')): ?>
                                                <a href="/staff/delete/<?php echo $member->getStaffId(); ?>"
                                                   class="btn-action-destructive btn-sm confirm-delete-link"
                                                   data-confirm-message="Are you sure you want to delete <?php echo htmlspecialchars($member->getUsername(), ENT_QUOTES, 'UTF-8'); ?>? This will retain them for audit purposes.">
                                                    Delete
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (empty($staff)): ?>
                        <div class="text-center py-4 staff-empty">
                            <p class="text-muted">No staff members found.</p>
                            <a href="/staff/add" class="btn-action-primary">Add First Staff Member</a>
                        </div>
                    <?php endif; ?>

                    <div class="staff-toolbar staff-toolbar-bottom">
                        <a href="/admin/menu" class="btn-secondary-pill">Back to Admin Menu</a>
                    </div>
                </div>
            </div>

            <footer class="staff-footer">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
            </footer>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo htmlspecialchars($csp_nonce, ENT_QUOTES, 'UTF-8'); ?>">
        document.querySelectorAll('.confirm-delete-link').forEach((link) => {
            link.addEventListener('click', (event) => {
                const message = link.dataset.confirmMessage || 'Are you sure?';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
