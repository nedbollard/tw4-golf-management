<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Scoring State Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../../../public/assets/css/style.css')); ?>" rel="stylesheet">
</head>
<body class="page-scoring-state">
    <?php
    $workflowStep = (string) ($round['workflow_step'] ?? 'not_started');
    $roundNumber = (string) ($round['round_number'] ?? '0');
    $roundDate = (string) ($round['round_date'] ?? 'not set');
    $sessionUsername = (string) ($_SESSION['username'] ?? 'admin');
    $sessionRole = (string) ($_SESSION['role'] ?? 'admin');
    ?>

    <div class="scoring-state-layout">
        <header class="scoring-state-header">
            <h1>Twilight Golf Scoring</h1>
        </header>

        <main>
            <div class="scoring-state-card">
                <h2 class="scoring-state-card-title"><?php echo htmlspecialchars($app_title); ?> Scoring State Management</h2>
                <div class="scoring-state-card-body">
                    <div class="section-title-wrap text-center">
                        <h2>Scoring Process State</h2>
                        <div class="section-title-accent"></div>
                    </div>

                    <div class="scoring-state-session" role="status" aria-live="polite">
                        <strong>Session Active</strong>
                        <span>
                            Signed in as <?php echo htmlspecialchars($sessionUsername); ?>
                            (<?php echo htmlspecialchars(ucfirst($sessionRole)); ?>)
                        </span>
                    </div>

                    <?php if (!empty($success)): ?>
                        <div class="scoring-state-alert scoring-state-alert-success" role="status">
                            <?php echo htmlspecialchars((string) $success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="scoring-state-alert scoring-state-alert-error" role="alert">
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo htmlspecialchars((string) $error); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="scoring-state-panel mb-3">
                        <h3>Current Live Round Status</h3>
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <div class="text-muted small">Round number</div>
                                <div class="fw-bold"><?php echo htmlspecialchars($roundNumber); ?></div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-muted small">Round date</div>
                                <div class="fw-bold"><?php echo htmlspecialchars($roundDate); ?></div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-muted small">Workflow step</div>
                                <div class="fw-bold"><?php echo htmlspecialchars($workflowStep); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="scoring-state-panel mb-3">
                        <h3>Item 1: Unlock Scoring Process</h3>
                        <p class="mb-3">Force release the scorer lock on the current live round.</p>
                        <form method="POST" action="/admin/scoring-state/unlock" onsubmit="return confirm('Force unlock scoring process for the live round?');">
                            <button type="submit" class="btn-action-primary">Unlock Scoring Process</button>
                        </form>
                    </div>

                    <div class="scoring-state-panel mb-3 scoring-state-panel-danger">
                        <h3>Item 2: Reset Results Complete to Cards Entry Open</h3>
                        <p class="mb-2">Moves workflow from <strong>results_presented</strong> back to <strong>card_entry_open</strong>.</p>
                        <p class="mb-3">This clears <strong>TW4_live.results</strong> and logs the admin action.</p>
                        <form method="POST" action="/admin/scoring-state/reset-results" onsubmit="return confirm('Reset from results complete to cards entry open and clear live results?');">
                            <button
                                type="submit"
                                class="btn-action-destructive"
                                <?php echo $workflowStep === 'results_presented' ? '' : 'disabled aria-disabled="true"'; ?>
                            >
                                Reset to Cards Entry Open
                            </button>
                        </form>
                        <?php if ($workflowStep !== 'results_presented'): ?>
                            <div class="form-text text-danger mt-2">
                                This action is enabled only when workflow step is results_presented.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="scoring-state-panel">
                        <h3>Item 3: Future Scoring-State Actions (Stub)</h3>
                        <p class="mb-3">Reserved for additional admin scoring-state transitions to be implemented later.</p>
                        <button type="button" class="btn btn-outline-secondary" disabled aria-disabled="true">Coming soon</button>
                    </div>

                    <div class="scoring-state-toolbar scoring-state-toolbar-bottom">
                        <a href="/admin/menu" class="btn-secondary-pill">Back to Admin Menu</a>
                    </div>
                </div>
            </div>

            <footer class="scoring-state-footer">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
            </footer>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
