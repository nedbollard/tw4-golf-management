<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> — Scorer's Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="page-scorer-menu">

<?php
// ─── Helpers ────────────────────────────────────────────────────────────────
function stepBadge(string $status): string {
    $map = [
        'waiting'     => ['badge-waiting',  '⬤', 'Waiting'],
        'in_progress' => ['badge-inprog',   '⬤', 'In Progress'],
        'completed'   => ['badge-done',     '⬤', 'Completed'],
    ];
    [$cls, $dot, $label] = $map[$status] ?? $map['waiting'];
    return '<span class="status-badge ' . $cls . '"><span class="sb-dot"></span>'
         . htmlspecialchars($label) . '</span>';
}

function numClass(string $status): string {
    return match($status) {
        'in_progress' => 'num-inprog',
        'completed'   => 'num-done',
        default       => 'num-waiting',
    };
}

$steps       = $roundState['steps']       ?? [];
$lock        = $roundState['lock']        ?? null;
$cardCount   = $roundState['card_count']  ?? 0;
$activeRound = $roundState['active_round'] ?? null;
$displayRound = $activeRound && (($activeRound['workflow_step'] ?? 'not_started') !== 'not_started')
    ? $activeRound
    : null;
$errors      = $_SESSION['errors'] ?? [];
$success     = $_SESSION['success'] ?? null;

unset($_SESSION['errors'], $_SESSION['success']);
?>

<div class="scorer-layout">
    <header class="scorer-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="scorer-card mb-3">
            <h2 class="scorer-card-title"><?php echo htmlspecialchars($app_title); ?> Scorer's Menu</h2>
            <div class="scorer-card-body">
                <div class="section-title-wrap text-center">
                    <h2>Scorer Workflow</h2>
                    <div class="section-title-accent"></div>
                </div>

                <div class="scorer-toolbar" aria-label="Scorer navigation">
                    <a href="/" class="btn-toolbar-main">Main Menu</a>
                    <a href="/roster" class="btn-toolbar-roster">View Roster</a>
                    <a href="/leaderboard" class="btn-toolbar-leader">Leaderboard</a>
                    <?php if ($displayRound): ?>
                        <a href="/results" class="btn-toolbar-results">View Results</a>
                    <?php endif; ?>
                </div>

        <?php if (!empty($success)): ?>
            <div class="scorer-alert scorer-alert-success" role="status">
                <?php echo htmlspecialchars((string) $success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="scorer-alert scorer-alert-error" role="alert">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars((string) $error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($lock && isset($lock['blocked']) && $lock['blocked']): ?>
            <!-- Another scorer holds the lock -->
            <div class="lock-banner lock-blocked" role="alert">
                <span class="lock-icon">🔒</span>
                Scoring is currently locked by
                <strong><?php echo htmlspecialchars($lock['holder_name'] ?? 'another scorer'); ?></strong>.
                You cannot perform scoring actions until the lock is released.
            </div>
        <?php elseif ($lock && isset($lock['holder_name'])): ?>
            <!-- Current scorer holds the lock - informational -->
            <div class="lock-banner" role="status">
                <span class="lock-icon">🔓</span>
                You hold the scoring lock for this round.
            </div>
        <?php endif; ?>

                <div class="scorer-section-label">Core scoring functions — complete in order</div>

                <div class="step-list">

                <?php foreach ($steps as $num => $step):
                    $status   = $step['status']  ?? 'waiting';
                    $enabled  = $step['enabled'] ?? false;
                    $route    = $step['route']   ?? '#';
                    $label    = $step['label']   ?? "Step {$num}";
                    $rowClass = $enabled ? 'step-row' : 'step-row step-disabled';

                    $hints = [
                        1 => 'Creates the live round context',
                        2 => 'Only step that can be In Progress',
                        3 => 'Allowed once ≥ 4 cards are entered',
                        4 => 'Finalises and publishes the round',
                    ];
                    $hint = $hints[$num] ?? '';
                ?>
                    <div class="<?php echo $rowClass; ?>"
                         role="listitem"
                         aria-label="Step <?php echo $num; ?>: <?php echo htmlspecialchars($label); ?>">

                        <div class="step-num <?php echo numClass($status); ?>"
                             aria-hidden="true"><?php echo $num; ?></div>

                        <div class="step-info">
                            <p class="step-title"><?php echo htmlspecialchars($label); ?></p>
                            <p class="step-hint"><?php echo htmlspecialchars($hint); ?></p>
                        </div>

                        <div class="step-status">
                            <?php echo stepBadge($status); ?>
                        </div>

                        <div class="step-action">
                            <?php if ($enabled): ?>
                                <a href="<?php echo htmlspecialchars($route); ?>"
                                   class="btn-step-go">Go</a>
                            <?php else: ?>
                                <button class="btn-step-disabled"
                                        disabled aria-disabled="true">Go</button>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>

                </div>

                <div class="scorer-meta-stack">
                    <p>
                        Season <strong><?php echo htmlspecialchars((string) ($activeRound['season_year'] ?? '—')); ?></strong>
                    </p>
                    <?php if ($displayRound): ?>
                    <p>
                        Round <strong><?php echo htmlspecialchars((string) ($displayRound['round_number'] ?? '—')); ?></strong>
                    </p>
                    <?php else: ?>
                    <p>
                        <strong>No round active</strong>
                    </p>
                    <?php endif; ?>

                    <?php if ($displayRound && !empty($displayRound['round_date'])): ?>
                    <p>
                        Date <strong><?php echo htmlspecialchars(date('d/m/Y', strtotime((string) $displayRound['round_date']))); ?></strong>
                    </p>
                    <?php endif; ?>

                    <p>
                        Cards entered: <strong><?php echo (int) $cardCount; ?></strong>
                        <?php if ($cardCount < 4): ?>
                            — at least 4 required to present results
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <footer class="scorer-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
