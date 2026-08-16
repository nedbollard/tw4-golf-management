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
                    <h2>Add Player</h2>
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

                <form method="POST" action="/roster/create">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                               value="<?php echo htmlspecialchars($old['first_name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                               value="<?php echo htmlspecialchars($old['last_name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender *</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo ($old['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($old['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="handicap" class="form-label">Handicap</label>
                        <input type="number" class="form-control" id="handicap" name="handicap"
                               value="<?php echo htmlspecialchars($old['handicap'] ?? '0'); ?>" min="0" max="54">
                        <div class="form-text">Golf handicap index (0-54). Leave as 0 if not known.</div>
                    </div>

                    <div class="mb-3">
                        <label for="alias" class="form-label">Alias/Nickname</label>
                        <input type="text" class="form-control" id="alias" name="alias"
                               value="<?php echo htmlspecialchars($old['alias'] ?? ''); ?>"
                               placeholder="Optional nickname for display">
                        <div class="form-text">This will be displayed instead of the player identifier if provided.</div>
                    </div>

                    <div class="roster-form-actions">
                        <a href="/roster" class="btn-secondary-pill">Cancel</a>
                        <button type="submit" class="btn-action-primary" id="save-button">Add to Roster</button>
                    </div>
                </form>
            </div>
        </div>

        <footer class="roster-form-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

<script nonce="<?php echo htmlspecialchars($csp_nonce, ENT_QUOTES, 'UTF-8'); ?>">
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
