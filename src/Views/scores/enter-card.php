<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Enter Card</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <style>
        body {
            font-size: 0.94rem;
        }
        .enter-card-navbar {
            background: linear-gradient(90deg, #7c2d12 0%, #c2410c 100%);
            border-bottom: 2px solid #fdba74;
            color: #fff7ed;
        }
        .enter-card-navbar .navbar-title {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }
        .enter-card-navbar .btn {
            padding: 0.3rem 0.78rem;
            font-size: 0.9rem;
        }
        .compact-wrap {
            max-width: 860px;
        }
        .compact-title {
            font-size: 1.45rem;
            margin-bottom: 0;
        }
        .player-line {
            margin-bottom: 0.6rem;
            margin-top: 0.1rem;
            font-size: 0.98rem;
        }
        .player-banner {
            background: #78350f;
            border: 1px solid #fbbf24;
            border-radius: 10px;
            padding: .55rem 1rem;
            font-size: .95rem;
            color: #fef3c7;
            margin-bottom: 0.75rem;
        }
        .player-banner strong {
            font-size: 1rem;
        }
        .compact-card .card-body {
            padding: 0.65rem;
        }
        .score-grid th, .score-grid td {
            text-align: center;
            vertical-align: middle;
            padding: 0.3rem 0.35rem;
            line-height: 1.1;
            font-size: 0.94rem;
        }
        .score-grid thead th {
            font-weight: 600;
            padding: 0.32rem 0.35rem;
        }
        .score-grid .score-input {
            width: 42px;
            min-height: 30px;
            margin: 0 auto;
            text-align: center;
            padding: 0.15rem 0.2rem;
            font-size: 0.93rem;
        }
        .score-grid .score-input.sfp-0,
        .score-grid .score-input.sfp-1,
        .score-grid .score-input.sfp-2,
        .score-grid .score-input.sfp-3,
        .score-grid .score-input.sfp-4,
        .score-grid .score-input.sfp-5 {
            font-weight: 700;
            border-width: 1px;
        }
        .score-grid td.sfp-cell {
            font-weight: 700;
            border-width: 1px;
        }
        .score-grid td.sfp-0 { background: #1e3a8a; color: #eff6ff; }
        .score-grid td.sfp-1 { background: #1d4ed8; color: #eff6ff; }
        .score-grid td.sfp-2 { background: #0f766e; color: #f0fdfa; }
        .score-grid td.sfp-3 { background: #15803d; color: #f0fdf4; }
        .score-grid td.sfp-4 { background: #a16207; color: #fffbeb; }
        .score-grid td.sfp-5 { background: #6d28d9; color: #f5f3ff; }
        .score-grid .score-input.sfp-0 { background: #1e3a8a; color: #eff6ff; border-color: #1e40af; }
        .score-grid .score-input.sfp-1 { background: #1d4ed8; color: #eff6ff; border-color: #2563eb; }
        .score-grid .score-input.sfp-2 { background: #0f766e; color: #f0fdfa; border-color: #0d9488; }
        .score-grid .score-input.sfp-3 { background: #15803d; color: #f0fdf4; border-color: #16a34a; }
        .score-grid .score-input.sfp-4 { background: #a16207; color: #fffbeb; border-color: #ca8a04; }
        .score-grid .score-input.sfp-5 { background: #6d28d9; color: #f5f3ff; border-color: #7c3aed; }
        .score-grid .score-input::placeholder {
            color: inherit;
        }
        .compact-actions .btn {
            padding: 0.34rem 0.82rem;
            font-size: 0.92rem;
        }
        @media (max-height: 860px) {
            .compact-screen {
                padding-top: 0.85rem !important;
                padding-bottom: 0.85rem !important;
            }
            .compact-head {
                margin-bottom: 0.55rem !important;
            }
            .player-line {
                margin-bottom: 0.35rem;
            }
            .score-grid th, .score-grid td {
                padding: 0.24rem 0.28rem;
                font-size: 0.9rem;
            }
            .score-grid .score-input {
                width: 40px;
                min-height: 28px;
                font-size: 0.88rem;
            }
            .compact-actions {
                margin-top: 0.25rem;
            }
        }
        @media (max-width: 768px) {
            .compact-title {
                font-size: 1.18rem;
            }
            .score-grid th, .score-grid td {
                font-size: 0.86rem;
                padding: 0.22rem 0.22rem;
            }
            .score-grid .score-input {
                width: 38px;
            }
        }
    </style>
</head>
<body class="bg-light">
<?php
$roundNumber = (int) ($round['round_number'] ?? 0);
$player = $entry['player'] ?? [];
$playerDisplay = trim((string) (($player['first_name'] ?? '') . ' ' . ($player['last_name'] ?? '')));
$playerIdentifier = (string) ($player['alias'] ?? '');
if ($playerIdentifier === '') {
    $playerIdentifier = (string) ($player['player_identifier'] ?? '');
}

$sfpClass = static function ($points): string {
    if ($points === null || $points === '') {
        return '';
    }

    $value = (int) $points;
    if ($value < 0) {
        $value = 0;
    }
    if ($value > 5) {
        $value = 5;
    }

    return 'sfp-' . $value;
};

$sfpTotalClass = static function ($points): string {
    if ($points === null || $points === '') {
        return '';
    }

    $value = (int) $points;

    if ($value >= 21) {
        return 'sfp-5';
    }
    if ($value === 20) {
        return 'sfp-4';
    }
    if ($value === 19) {
        return 'sfp-3';
    }
    if ($value === 18) {
        return 'sfp-2';
    }
    if ($value === 17) {
        return 'sfp-1';
    }

    return 'sfp-0';
};
?>
<nav class="enter-card-navbar navbar" aria-label="Enter card navigation">
    <div class="container-fluid px-3 py-2 d-flex justify-content-between align-items-center">
        <span class="navbar-title">Enter Card</span>
        <div class="d-flex gap-2">
            <a href="/scores/enter" class="btn btn-outline-light">Cancel</a>
            <a href="/scores/enter" class="btn btn-outline-light">Back</a>
            <a href="/scorer/menu" class="btn btn-light text-dark">Home</a>
        </div>
    </div>
</nav>
<div class="container py-5 compact-screen compact-wrap">
    <div class="player-banner compact-head">
        <strong>Round <?php echo $roundNumber; ?></strong>
        &nbsp;·&nbsp;
        Player : <?php echo htmlspecialchars($playerDisplay); ?> :
        <?php echo htmlspecialchars($playerIdentifier); ?> :
        [<?php echo (strtolower((string) ($player['gender'] ?? 'male')) === 'female') ? 'F' : 'M'; ?>] :
        Handicap : [<?php echo (int) ($player['handicap'] ?? 0); ?>]
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?php echo htmlspecialchars((string) $error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm compact-card">
        <div class="card-body">
            <form method="POST" action="/scores/enter/<?php echo (int) ($player['row_id'] ?? 0); ?>" id="score-entry-form">
                <input type="hidden" name="action" id="form-action" value="">
                <div class="table-responsive mb-2">
                    <table class="table table-bordered score-grid align-middle mb-2">
                        <thead class="table-light">
                            <tr>
                                <th>Hole</th>
                                <th>Par</th>
                                <th>Stroke</th>
                                <th>Score</th>
                                <th>Shots</th>
                                <th>Net</th>
                                <th>SFP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($entry['holes'] ?? []) as $hole): ?>
                                <?php $holeSfpClass = $sfpClass($hole['points'] ?? null); ?>
                                <tr>
                                    <td><?php echo (int) $hole['hole']; ?></td>
                                    <td><?php echo (int) $hole['par']; ?></td>
                                    <td><?php echo (int) $hole['stroke']; ?></td>
                                    <td>
                                        <input
                                            type="text"
                                            inputmode="numeric"
                                            maxlength="1"
                                            class="form-control score-input <?php echo $holeSfpClass; ?>"
                                            name="scores[<?php echo (int) $hole['hole']; ?>]"
                                            value="<?php echo htmlspecialchars(((int) ($hole['score'] ?? 0) === 10) ? 'X' : (string) ($hole['score'] ?? '')); ?>"
                                            pattern="[1-9xX]"
                                            title="Enter 1-9 or X"
                                            required
                                        >
                                    </td>
                                    <td><?php echo ($hole['shots'] === null) ? '' : (int) $hole['shots']; ?></td>
                                    <td class="sfp-cell <?php echo $holeSfpClass; ?>"><?php echo ($hole['net'] === null) ? '' : (int) $hole['net']; ?></td>
                                    <td class="sfp-cell <?php echo $holeSfpClass; ?>"><?php echo ($hole['points'] === null) ? '' : (int) $hole['points']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <?php $totalSfpClass = $sfpTotalClass($entry['totals']['points'] ?? null); ?>
                            <tr class="table-light fw-bold">
                                <td>Total</td>
                                <td><?php echo (int) (($entry['totals']['par'] ?? 0)); ?></td>
                                <td></td>
                                <td class="sfp-cell <?php echo $totalSfpClass; ?>"><?php echo ($entry['totals']['score'] === null) ? '' : (int) $entry['totals']['score']; ?></td>
                                <td><?php echo ($entry['totals']['shots'] === null) ? '' : (int) $entry['totals']['shots']; ?></td>
                                <td class="sfp-cell <?php echo $totalSfpClass; ?>"><?php echo ($entry['totals']['net'] === null) ? '' : (int) $entry['totals']['net']; ?></td>
                                <td class="sfp-cell <?php echo $totalSfpClass; ?>"><?php echo ($entry['totals']['points'] === null) ? '' : (int) $entry['totals']['points']; ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex gap-2 justify-content-center compact-actions">
                    <button type="button" class="btn btn-primary" id="calculate-button">Calculate</button>
                    <button type="button" class="btn btn-success" id="save-button">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(() => {
    const form = document.getElementById('score-entry-form');
    const saveButton = document.getElementById('save-button');
    const calculateButton = document.getElementById('calculate-button');
    const actionField = document.getElementById('form-action');
    const scoreInputs = Array.from(document.querySelectorAll('.score-input'));

    if (!form || !saveButton || !calculateButton || !actionField) {
        return;
    }

    const normalizeScoreInput = (input) => {
        if (!input) {
            return '';
        }

        const raw = String(input.value || '').trim();
        if (raw === '') {
            input.value = '';
            return '';
        }

        const first = raw.charAt(0);
        if (/[1-9]/.test(first)) {
            input.value = first;
            input.setCustomValidity('');
            return first;
        }

        if (/[xX]/.test(first)) {
            input.value = 'X';
            input.setCustomValidity('');
            return 'X';
        }

        input.value = '';
        input.setCustomValidity('Enter 1-9 or X');
        return '';
    };

    scoreInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            const normalized = normalizeScoreInput(input);
            if (normalized === '') {
                return;
            }

            if (index < scoreInputs.length - 1) {
                scoreInputs[index + 1].focus();
                scoreInputs[index + 1].select();
            } else {
                calculateButton.focus();
            }
        });

        input.addEventListener('focus', () => {
            input.select();
            input.setCustomValidity('');
        });
    });

    if (scoreInputs.length > 0) {
        scoreInputs[0].focus();
        scoreInputs[0].select();
    }

    const submitForm = (action) => {
        actionField.value = action;

        scoreInputs.forEach((input) => {
            normalizeScoreInput(input);
        });

        if (!form.reportValidity()) {
            return;
        }

        form.submit();
    };

    calculateButton.addEventListener('click', () => {
        if (form.dataset.saveLocked === 'true') {
            return;
        }

        submitForm('calculate');
    });

    saveButton.addEventListener('click', () => {
        if (form.dataset.saveLocked === 'true') {
            return;
        }

        actionField.value = 'save';
        if (!form.reportValidity()) {
            return;
        }

        form.dataset.saveLocked = 'true';
        saveButton.classList.remove('btn-success');
        saveButton.classList.add('btn-danger');
        saveButton.textContent = 'Saving ...';
        saveButton.style.pointerEvents = 'none';
        saveButton.style.opacity = '1';
        saveButton.setAttribute('aria-disabled', 'true');

        calculateButton.style.pointerEvents = 'none';
        calculateButton.style.opacity = '0.65';
        calculateButton.setAttribute('aria-disabled', 'true');

        window.setTimeout(() => {
            form.submit();
        }, 900);
    });
})();
</script>
</body>
</html>
