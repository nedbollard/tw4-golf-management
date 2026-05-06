<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Select Player</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="page-select-player">
<div class="select-player-layout">
    <header class="select-player-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="select-player-card">
            <h2 class="select-player-card-title"><?php echo htmlspecialchars($app_title); ?> - Select Player</h2>
            <div class="select-player-card-body">
                <div class="section-title-wrap text-center">
                    <h2>Round <?php echo (int) ($round['round_number'] ?? 0); ?></h2>
                    <div class="section-title-accent"></div>
                </div>

                <div class="select-player-intro">
                    Choose the player whose card you want to enter.
                </div>

                <?php if (!empty($success)): ?>
                    <div class="select-player-alert select-player-alert-success" role="status">
                        <?php echo htmlspecialchars((string) $success); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="select-player-alert select-player-alert-error" role="alert">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars((string) $error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($players)): ?>
                    <div class="select-player-alert select-player-alert-warning">
                        No active players found in roster.
                    </div>
                <?php else: ?>
                    <form method="GET" action="/scores/enter" onsubmit="return false;">
                        <label for="player_id" class="select-player-form-label">Select a Player</label>
                        <select id="player_id" class="select-player-form-select" onchange="if(this.value){window.location='/scores/enter/' + this.value;}">
                            <option value="">Select a player</option>
                            <?php foreach ($players as $player): ?>
                                <?php
                                $displayName = trim((string) (($player['last_name'] ?? '') . ', ' . ($player['first_name'] ?? '')));
                                $identifier = (string) ($player['player_identifier'] ?? '');
                                $alias = (string) ($player['alias'] ?? '');
                                if ($alias !== '') {
                                    $identifier = $alias;
                                }
                                $savedFlag = !empty($player['card_id']) ? ' [saved]' : '';
                                ?>
                                <option value="<?php echo (int) $player['row_id']; ?>">
                                    <?php echo htmlspecialchars($displayName . ' (' . $identifier . ')' . $savedFlag); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>

                <div class="select-player-actions">
                    <a href="/scorer/menu" class="btn-secondary-pill">Back to Scorer Menu</a>
                    <a href="/" class="btn-primary-pill">Home</a>
                </div>
            </div>
        </div>

        <footer class="select-player-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>
</body>
</html>
