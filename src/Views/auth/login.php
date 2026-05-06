<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TW4 Golf Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="page-login">
    <?php $old = $_SESSION['old'] ?? []; ?>
    <div class="login-layout">
        <header class="login-header">
            <h1>Twilight Golf Scoring</h1>
        </header>

        <main>
            <div class="login-card">
                <h2 class="login-card-title">Staff Login</h2>
                <div class="login-card-body">
                    <div class="section-title-wrap text-center">
                        <h2>Welcome Back</h2>
                        <div class="section-title-accent"></div>
                    </div>

                    <p class="login-intro">Sign in to continue to the scoring tools.</p>

                    <form method="POST" action="/login">
                        <div class="tw-form-group">
                            <label for="username" class="tw-form-label">Username</label>
                            <input
                                type="text"
                                class="tw-form-input"
                                id="username"
                                name="username"
                                value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>"
                                required
                            >
                        </div>
                        <div class="tw-form-group">
                            <label for="password" class="tw-form-label">Password</label>
                            <input type="password" class="tw-form-input" id="password" name="password" required>
                        </div>
                        <div class="login-actions">
                            <button type="submit" class="btn-primary-pill">Login</button>
                            <a href="/" class="btn-secondary-pill text-center">Back to Main Menu</a>
                        </div>
                    </form>

                    <?php if (isset($_SESSION['errors'])): ?>
                    <div class="login-error">
                        <?php foreach ($_SESSION['errors'] as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                    <?php unset($_SESSION['errors']); ?>
                    <?php endif; ?>

                    <?php unset($_SESSION['old']); ?>
                </div>
            </div>

            <footer class="login-footer">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
            </footer>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
