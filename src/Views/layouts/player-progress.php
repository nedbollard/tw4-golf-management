<?php
/**
 * Shared layout for the Player Progress feature (selector page and chart page).
 * Callers must build $content (raw HTML string, e.g. via ob_start()/ob_get_clean())
 * before requiring this file, and set:
 *   $pageHeading    - string, e.g. "Player Progress Selector"
 *   $pageStepLabel  - string, e.g. "Page 1 of 2 — choose a player"
 *   $pageCardTitle  - optional string, e.g. "Scorecard" (defaults to "Player Progress")
 * $app_title is provided automatically by BaseController::render().
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - <?php echo htmlspecialchars($pageHeading ?? 'Player Progress'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="page-player-progress">
<div class="player-progress-layout">
    <header class="player-progress-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="player-progress-card">
            <h2 class="player-progress-card-title"><?php echo htmlspecialchars($app_title); ?> <?php echo htmlspecialchars($pageCardTitle ?? 'Player Progress'); ?></h2>
            <div class="player-progress-card-body">
                <div class="section-title-wrap text-center">
                    <h2><?php echo htmlspecialchars($pageHeading ?? 'Player Progress'); ?></h2>
                    <div class="section-title-accent"></div>
                </div>

                <?php if (!empty($pageStepLabel)): ?>
                    <p class="player-progress-intro"><?php echo htmlspecialchars($pageStepLabel); ?></p>
                <?php endif; ?>

                <?php echo $content; ?>
            </div>
        </div>

        <footer class="player-progress-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
