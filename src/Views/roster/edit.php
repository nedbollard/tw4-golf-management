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
<body class="page-roster-form">
<?php
$sessionUsername = (string) ($_SESSION['username'] ?? 'scorer');
$sessionRole = (string) ($_SESSION['role'] ?? 'scorer');
?>
<div class="roster-form-layout">
    <header class="roster-form-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="roster-form-card">
            <h2 class="roster-form-card-title"><?php echo htmlspecialchars($title); ?></h2>
            <div class="roster-form-card-body">
                <div class="section-title-wrap text-center">
                    <h2>Edit Player</h2>
                    <div class="section-title-accent"></div>
                </div>

                <div class="roster-session" role="status" aria-live="polite">
                    <strong>Session Active</strong>
                    <span>
                        Signed in as <?php echo htmlspecialchars($sessionUsername); ?>
                        (<?php echo htmlspecialchars(ucfirst($sessionRole)); ?>)
                    </span>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="roster-alert roster-alert-error" role="alert">
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/roster/<?php echo $player['row_id']; ?>/update">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                               value="<?php echo htmlspecialchars($player['first_name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                               value="<?php echo htmlspecialchars($player['last_name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender *</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo $player['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo $player['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="handicap" class="form-label">Handicap</label>
                        <input type="number" class="form-control" id="handicap" name="handicap"
                               value="<?php echo htmlspecialchars($player['handicap']); ?>" min="0" max="54">
                        <div class="form-text">Golf handicap index (0-54).</div>
                    </div>

                    <div class="mb-3">
                        <label for="alias" class="form-label">Alias/Nickname</label>
                        <input type="text" class="form-control" id="alias" name="alias"
                               value="<?php echo htmlspecialchars($player['alias'] ?? ''); ?>"
                               placeholder="Optional nickname for display">
                        <div class="form-text">This will be displayed instead of the player identifier if provided.</div>
                    </div>

                    <div class="mb-3">
                        <label for="player_identifier" class="form-label">Player Identifier</label>
                        <input type="text" class="form-control" id="player_identifier" name="player_identifier"
                               value="<?php echo htmlspecialchars($player['player_identifier']); ?>"
                               <?php echo !empty($player['date_first_played']) ? 'readonly' : ''; ?>>
                        <div class="form-text">
                            <?php if (!empty($player['date_first_played'])): ?>
                                Player identifier cannot be changed after first game played.
                            <?php else: ?>
                                Player identifier can be edited until first game is played. Must be unique among all players and aliases.
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo $player['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $player['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="roster-form-actions">
                        <a href="/roster/<?php echo $player['row_id']; ?>" class="btn-secondary-pill">Cancel</a>
                        <button type="submit" class="btn-action-primary" id="save-button">Update Entry</button>
                    </div>
                </form>
            </div>
        </div>

        <footer class="roster-form-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

<script>
(() => {
    const form = document.querySelector('form');
    const saveButton = document.getElementById('save-button');

    if (!form || !saveButton) {
        return;
    }

    const lockSave = () => {
        form.dataset.saveLocked = 'true';
        saveButton.textContent = 'Saving ...';
        saveButton.style.pointerEvents = 'none';
        saveButton.style.opacity = '1';
        saveButton.setAttribute('aria-disabled', 'true');
        saveButton.disabled = true;
    };

    form.addEventListener('submit', (e) => {
        if (form.dataset.saveLocked === 'true') {
            e.preventDefault();
            return;
        }

        if (!form.reportValidity()) {
            e.preventDefault();
            return;
        }

        lockSave();
    });

    saveButton.addEventListener('click', (e) => {
        if (form.dataset.saveLocked === 'true') {
            e.preventDefault();
        }
    });
})();
</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
