<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> — Scorer's Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <style>
        /* Scorer menu overrides */
        /* Page: dark charcoal — clearly different from both navbar and cards */
        body { background: linear-gradient(160deg, #1e293b 0%, #0f172a 100%); }

        /* Navbar: rich teal/green — the scorer identity colour */
        .scorer-navbar {
            background: linear-gradient(90deg, #065f46 0%, #047857 100%);
            border-bottom: 2px solid #34d399;
        }

        .scorer-navbar .navbar-brand {
            font-weight: 700;
            font-size: 1.2rem;
            color: #ecfdf5;   /* near-white on teal */
        }

        .scorer-navbar .btn-outline-light {
            border-width: 1px;
            font-size: .875rem;
            padding: .3rem .75rem;
        }

        /* Individual navbar button colours */
        .btn-nav-main       { background:#1e293b; color:#f1f5f9; border:1px solid #475569; font-weight:700; font-size:.875rem; padding:.3rem .75rem; border-radius:.375rem; }
        .btn-nav-main:hover { background:#334155; color:#fff; }
        .btn-nav-roster       { background:#7c3aed; color:#fff; border:1px solid #6d28d9; font-size:.875rem; padding:.3rem .75rem; border-radius:.375rem; }
        .btn-nav-roster:hover { background:#6d28d9; color:#fff; }
        .btn-nav-leader       { background:#b45309; color:#fff; border:1px solid #92400e; font-size:.875rem; padding:.3rem .75rem; border-radius:.375rem; }
        .btn-nav-leader:hover { background:#92400e; color:#fff; }
        .btn-nav-results       { background:#0e7490; color:#fff; border:1px solid #0c5d73; font-size:.875rem; padding:.3rem .75rem; border-radius:.375rem; }
        .btn-nav-results:hover { background:#0c6d84; color:#fff; }

        .scorer-main { padding: 2rem 0 3rem; }

        /* Workflow step rows — solid white, dark text inside */
        .step-row {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: .85rem 1.1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform .15s, box-shadow .15s;
        }
        .step-row:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0,0,0,.25); }
        .step-row.step-disabled {
            background: #f8fafc;
            border-color: #e2e8f0;
            opacity: 1;           /* keep full opacity; use muted colours instead */
            pointer-events: none;
        }
        .step-row.step-disabled .step-title { color: #6b7280; }
        .step-row.step-disabled .step-hint  { color: #9ca3af; }

        .step-num {
            width: 36px; height: 36px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .9rem;
            flex-shrink: 0;
        }
        .step-num.num-waiting  { background: #d1d5db; color: #374151; }
        .step-num.num-inprog   { background: #2563eb; color: #fff; }
        .step-num.num-done     { background: #16a34a; color: #fff; }

        .step-info { flex: 1; min-width: 0; }
        .step-info .step-title  { font-weight: 600; font-size: .95rem; color: #111827; margin: 0; line-height: 1.3; }
        .step-info .step-hint   { font-size: .78rem; color: #4b5563; margin: 0; }

        .step-status { flex-shrink: 0; }

        .status-badge {
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .3rem .65rem;
            border-radius: 999px;
            font-size: .75rem; font-weight: 700;
        }
        .status-badge .sb-dot {
            width: .5rem; height: .5rem; border-radius: 50%;
        }
        .status-badge.badge-waiting  { background: #e9ecef;         color: #495057; }
        .status-badge.badge-waiting  .sb-dot { background: #adb5bd; }
        .status-badge.badge-inprog   { background: #cfe2ff;         color: #0a58ca; }
        .status-badge.badge-inprog   .sb-dot { background: #0d6efd; }
        .status-badge.badge-done     { background: #d1e7dd;         color: #0f5132; }
        .status-badge.badge-done     .sb-dot { background: #198754; }

        .step-action { flex-shrink: 0; }

        /* Section card — slate-blue panel, distinct from both charcoal bg and white rows */
        .section-card {
            background: #1e3a5f;
            border: 1px solid #3b82f6;
            border-radius: 16px;
            padding: 1.25rem 1.35rem;
            color: #fff;
        }
        .section-card .section-label {
            font-size: .7rem; font-weight: 700; letter-spacing: .1em;
            text-transform: uppercase; color: #93c5fd;   /* sky blue — legible on navy panel */
            margin-bottom: .85rem;
        }

        /* Lock / warning banner */
        .lock-banner {
            background: #78350f;
            border: 1px solid #fbbf24;
            border-radius: 10px;
            padding: .65rem 1rem;
            font-size: .875rem;
            color: #fef3c7;
            margin-bottom: 1rem;
        }
        .lock-banner.lock-blocked {
            background: #7f1d1d;
            border-color: #ef4444;
            color: #fee2e2;
        }
        .lock-banner .lock-icon { font-weight: 700; margin-right: .35rem; }
    </style>
</head>
<body>

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

$steps      = $roundState['steps']      ?? [];
$lock       = $roundState['lock']       ?? null;
$cardCount  = $roundState['card_count'] ?? 0;
$activeRound = $roundState['active_round'] ?? null;
?>

<!-- ─── Header / Navbar ───────────────────────────────────────────────────── -->
<nav class="scorer-navbar navbar" aria-label="Scorer navigation">
    <div class="container-fluid px-3">
        <span class="navbar-brand">Scorer's Menu</span>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="/" class="btn btn-nav-main">← Main Menu</a>
            <a href="/roster" class="btn btn-nav-roster">View Roster</a>
            <a href="/leaderboard" class="btn btn-nav-leader">Leaderboard</a>
            <?php if ($activeRound): ?>
                <a href="/results" class="btn btn-nav-results">View Results</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ─── Main body ─────────────────────────────────────────────────────────── -->
<div class="scorer-main">
    <div class="container" style="max-width:720px;">

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

        <!-- ── Core Scoring Functions ──────────────────────────────────────── -->
        <div class="section-card mb-3">
            <div class="section-label">Core Scoring Functions — complete in order</div>

            <div class="d-flex flex-column gap-2">

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
                                   class="btn btn-success btn-sm">Go</a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm"
                                        disabled aria-disabled="true">Go</button>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>

            </div><!-- /steps -->

            <!-- Validity note when a round is active -->
            <?php if ($activeRound): ?>
                <p class="mt-2 mb-0 text-white-50" style="font-size:.78rem;">
                    Cards entered: <strong class="text-white"><?php echo (int)$cardCount; ?></strong>
                    <?php if ($cardCount < 4): ?>
                        — at least 4 required to present results
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div><!-- /section-card core -->

        <!-- ── Round info strip (only when active) ────────────────────────── -->
        <?php if ($activeRound): ?>
        <div class="section-card" style="font-size:.875rem;">
            <div class="section-label">Active round</div>
            <div class="d-flex flex-wrap gap-3 text-white-50">
                <span>Round <strong class="text-white"><?php echo htmlspecialchars((string)($activeRound['round_number'] ?? '—')); ?></strong></span>
                <span>Date <strong class="text-white"><?php echo htmlspecialchars((string)($activeRound['round_date'] ?? '—')); ?></strong></span>
                <span>Status <strong class="text-white"><?php echo htmlspecialchars((string)($activeRound['workflow_step'] ?? '—')); ?></strong></span>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /container -->
</div><!-- /scorer-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
