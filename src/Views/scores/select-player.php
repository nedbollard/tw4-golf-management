<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Select Player</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <style>
        .scorer-navbar {
            background: linear-gradient(90deg, #065f46 0%, #047857 100%);
            border-bottom: 2px solid #34d399;
        }
        .scorer-navbar .navbar-brand {
            font-weight: 700;
            font-size: 1.05rem;
            color: #ecfdf5;
        }
        .btn-nav-main       { background:#1e293b; color:#f1f5f9; border:1px solid #475569; font-weight:700; font-size:.875rem; padding:.3rem .75rem; border-radius:.375rem; }
        .btn-nav-main:hover { background:#334155; color:#fff; }
        .btn-nav-home       { background:#f8fafc; color:#111827; border:1px solid #cbd5e1; font-size:.875rem; padding:.3rem .75rem; border-radius:.375rem; }
        .btn-nav-home:hover { background:#e2e8f0; color:#111827; }
        .compact-select-wrap {
            max-width: 860px;
        }
        .select-player-title {
            white-space: nowrap;
        }
        @media (max-height: 860px) {
            .compact-select-screen {
                padding-top: 1.2rem !important;
                padding-bottom: 1.2rem !important;
            }
            .compact-select-head {
                margin-bottom: 0.75rem !important;
            }
            .compact-select-card .card-body {
                padding: 0.85rem 1rem;
            }
        }
        @media (max-width: 768px) {
            .select-player-title {
                white-space: normal;
            }
        }
    </style>
</head>
<body class="bg-light">
<nav class="scorer-navbar navbar" aria-label="Select player navigation">
    <div class="container-fluid px-3">
        <span class="navbar-brand">Enter Cards</span>
        <div class="d-flex gap-2 align-items-center">
            <a href="/scorer/menu" class="btn btn-nav-main">← Scorer Menu</a>
            <a href="/" class="btn btn-nav-home">Home</a>
        </div>
    </div>
</nav>
<div class="container py-5 compact-select-screen compact-select-wrap">
    <div class="d-flex justify-content-between align-items-center mb-3 compact-select-head">
        <h1 class="h3 mb-0 select-player-title">Round <?php echo (int) ($round['round_number'] ?? 0); ?> : Select a Player</h1>
        <span class="text-muted small">Choose the player whose card you want to enter or amend.</span>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars((string) $success); ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?php echo htmlspecialchars((string) $error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm compact-select-card">
        <div class="card-body p-4">
            <?php if (empty($players)): ?>
                <div class="alert alert-warning mb-0">No active players found in roster.</div>
            <?php else: ?>
                <form method="GET" action="/scores/enter" onsubmit="return false;">
                    <div class="mb-0">
                        <select id="player_id" class="form-select" onchange="if(this.value){window.location='/scores/enter/' + this.value;}">
                            <option value="">Select a player</option>
                            <?php foreach ($players as $player): ?>
                                <?php
                                $displayName = trim((string) (($player['last_name'] ?? '') . '__' . ($player['first_name'] ?? '')));
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
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
