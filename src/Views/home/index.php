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
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="page-welcome">
    <div class="welcome-layout">
        <header class="welcome-header">
            <h1>Twilight Golf Scoring</h1>
        </header>

        <main>
            <div class="welcome-card">
                <h2 class="welcome-card-title"><?php echo htmlspecialchars($app_title); ?></h2>
                <div class="welcome-card-body">
                    <div class="section-title-wrap text-center">
                        <h2>Welcome</h2>
                        <div class="section-title-accent"></div>
                    </div>

                    <p class="welcome-intro">Please select an option below:</p>

                    <div class="welcome-grid">
                        <div class="welcome-panel">
                            <?php if ($isLoggedIn): ?>
                                <h3>Logged In</h3>
                                <p class="welcome-user">
                                    Welcome back, <?php echo htmlspecialchars($user['username']); ?>!<br>
                                    Role: <?php echo ucfirst(htmlspecialchars($user['user_role'])); ?>
                                </p>
                                <div class="welcome-actions">
                                    <?php if ($user['user_role'] === 'admin'): ?>
                                        <a href="/admin/menu" class="btn-gold-pill">Admin Menu</a>
                                    <?php elseif ($user['user_role'] === 'scorer'): ?>
                                        <a href="/scorer/menu" class="btn-accent-pill">Scorer Menu</a>
                                    <?php endif; ?>
                                    <a href="/logout" class="btn-secondary-pill">Logout</a>
                                </div>
                            <?php else: ?>
                                <h3>Staff Login</h3>
                                <p>For administrators and scorers</p>
                                <a href="/login" class="btn-primary-pill">Login</a>
                            <?php endif; ?>
                        </div>

                        <div class="welcome-panel">
                            <h3>Recent Results</h3>
                            <p>View latest competition results</p>
                            <a href="/results" class="btn-gold-pill">View Results</a>
                        </div>

                        <div class="welcome-panel">
                            <h3>Leaderboard</h3>
                            <p>Live progress for the round currently being scored</p>
                            <a href="/leaderboard" class="btn-accent-pill">View Leaderboard</a>
                        </div>

                        <div class="welcome-panel">
                            <h3>Watch This Space</h3>
                            <p>Reserved for the next phase of scoring features.</p>
                            <span class="btn-disabled-pill">Coming Soon</span>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="welcome-footer">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
            </footer>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
