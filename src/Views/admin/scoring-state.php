<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Scoring State Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php
    $workflowStep = (string) ($round['workflow_step'] ?? 'not_started');
    $roundNumber = (string) ($round['round_number'] ?? '0');
    $roundDate = (string) ($round['round_date'] ?? 'not set');
    ?>

    <div class="container py-4" style="max-width: 860px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Scoring Process State</h2>
            <a href="/admin/menu" class="btn btn-secondary">Back to Admin Menu</a>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="status">
                <?php echo htmlspecialchars((string) $success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars((string) $error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-dark text-white">
                <strong>Current Live Round Status</strong>
            </div>
            <div class="card-body">
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
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-warning">
                <strong>Item 1: Unlock Scoring Process</strong>
            </div>
            <div class="card-body">
                <p class="mb-3">Force release the scorer lock on the current live round.</p>
                <form method="POST" action="/admin/scoring-state/unlock" onsubmit="return confirm('Force unlock scoring process for the live round?');">
                    <button type="submit" class="btn btn-warning">Unlock Scoring Process</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-danger text-white">
                <strong>Item 2: Reset Results Complete to Cards Entry Open</strong>
            </div>
            <div class="card-body">
                <p class="mb-2">Moves workflow from <strong>results_presented</strong> back to <strong>card_entry_open</strong>.</p>
                <p class="mb-3">This clears <strong>TW4_live.results</strong> and logs the admin action.</p>
                <form method="POST" action="/admin/scoring-state/reset-results" onsubmit="return confirm('Reset from results complete to cards entry open and clear live results?');">
                    <button
                        type="submit"
                        class="btn btn-danger"
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
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <strong>Item 3: Future Scoring-State Actions (Stub)</strong>
            </div>
            <div class="card-body">
                <p class="mb-3">Reserved for additional admin scoring-state transitions to be implemented later.</p>
                <button type="button" class="btn btn-outline-secondary" disabled aria-disabled="true">Coming soon</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
