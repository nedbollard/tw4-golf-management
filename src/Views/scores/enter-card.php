<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Enter Card</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="page-enter-card">
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

<div class="enter-card-layout">
    <header class="enter-card-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <div class="enter-card-toolbar" aria-label="Enter card navigation">
        <a href="/scores/enter" class="btn-toolbar-cancel">Cancel</a>
        <a href="/scores/enter" class="btn-toolbar-back">Back</a>
        <a href="/scorer/menu" class="btn-toolbar-home">Home</a>
    </div>

    <main>
        <div class="enter-card-card">
            <h2 class="enter-card-card-title"><?php echo htmlspecialchars($app_title); ?> - Enter Card</h2>
            <div class="enter-card-card-body">
                <div class="enter-card-player-banner">
                    <strong>Round <?php echo $roundNumber; ?></strong>
                    &nbsp;·&nbsp;
                    Player : <?php echo htmlspecialchars($playerDisplay); ?> :
                    <?php echo htmlspecialchars($playerIdentifier); ?> :
                    [<?php echo (strtolower((string) ($player['gender'] ?? 'male')) === 'female') ? 'F' : 'M'; ?>] :
                    Handicap : [<?php echo (int) ($player['handicap'] ?? 0); ?>]
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="enter-card-alert" role="alert">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars((string) $error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

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
                                        <td class="meta-cell"><?php echo (int) $hole['hole']; ?></td>
                                        <td class="meta-cell"><?php echo (int) $hole['par']; ?></td>
                                        <td class="meta-cell"><?php echo (int) $hole['stroke']; ?></td>
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
                                        <td class="pink-cell"><?php echo ($hole['shots'] === null) ? '' : (int) $hole['shots']; ?></td>
                                        <td class="pink-cell"><?php echo ($hole['net'] === null) ? '' : (int) $hole['net']; ?></td>
                                        <td class="sfp-cell <?php echo $holeSfpClass; ?>"><?php echo ($hole['points'] === null) ? '' : (int) $hole['points']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light fw-bold">
                                    <td class="meta-cell">Total</td>
                                    <td class="total-green"><?php echo (int) (($entry['totals']['par'] ?? 0)); ?></td>
                                    <td></td>
                                    <td class="total-blue"><?php echo ($entry['totals']['score'] === null) ? '' : (int) $entry['totals']['score']; ?></td>
                                    <td class="total-green"><?php echo ($entry['totals']['shots'] === null) ? '' : (int) $entry['totals']['shots']; ?></td>
                                    <td class="total-green"><?php echo ($entry['totals']['net'] === null) ? '' : (int) $entry['totals']['net']; ?></td>
                                    <td class="total-blue"><?php echo ($entry['totals']['points'] === null) ? '' : (int) $entry['totals']['points']; ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="enter-card-actions">
                        <button type="button" id="calculate-button">Calculate</button>
                        <button type="button" id="save-button">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <footer class="enter-card-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
