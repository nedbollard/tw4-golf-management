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
<body class="page-staff-form">
<?php
$sessionUsername = (string) ($_SESSION['username'] ?? 'admin');
$sessionRole = (string) ($_SESSION['role'] ?? 'admin');
?>
<div class="staff-form-layout">
    <header class="staff-form-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="staff-form-card">
            <h2 class="staff-form-card-title"><?php echo htmlspecialchars($app_title); ?> Add Staff Member</h2>
            <div class="staff-form-card-body">
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

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php
                        $successMessages = is_array($success) ? $success : [$success];
                        foreach ($successMessages as $message):
                        ?>
                            <?php echo htmlspecialchars($message); ?><br>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Errors:</strong><br>
                        <?php
                        $errorMessages = is_array($errors) ? $errors : [$errors];
                        foreach ($errorMessages as $error):
                        ?>
                            <?php echo htmlspecialchars($error); ?><br>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/staff/add">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username"
                               value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>" required>
                        <small class="form-text text-muted">Display name for staff member</small>
                        <?php if (isset($errors['username'])): ?>
                            <div class="text-danger small"><?php echo htmlspecialchars($errors['username']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="admin" <?php echo ($old['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="scorer" <?php echo ($old['role'] ?? '') === 'scorer' ? 'selected' : ''; ?>>Scorer</option>
                            </select>
                            <?php if (isset($errors['role'])): ?>
                                <div class="text-danger small"><?php echo htmlspecialchars($errors['role']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">&nbsp;</div>
                    </div>

                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                               value="<?php echo htmlspecialchars($old['first_name'] ?? ''); ?>">
                        <small class="form-text text-muted">First name of staff member</small>
                        <?php if (isset($errors['first_name'])): ?>
                            <div class="text-danger small"><?php echo htmlspecialchars($errors['first_name']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                               value="<?php echo htmlspecialchars($old['last_name'] ?? ''); ?>">
                        <small class="form-text text-muted">Last name of staff member</small>
                        <?php if (isset($errors['last_name'])): ?>
                            <div class="text-danger small"><?php echo htmlspecialchars($errors['last_name']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <?php if (isset($errors['password'])): ?>
                            <div class="text-danger small"><?php echo htmlspecialchars($errors['password']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="/staff" class="btn btn-secondary me-md-2">Cancel</a>
                        <button type="submit" class="btn-action-primary">Add Staff Member</button>
                    </div>
                </form>
            </div>
        </div>

        <footer class="staff-form-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
