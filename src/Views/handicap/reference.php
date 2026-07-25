<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> - Find Handicap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="page-scorer-menu page-handicap-reference">
<div class="scorer-layout">
    <header class="scorer-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <section class="scorer-card">
            <h2 class="scorer-card-title"><?php echo htmlspecialchars($app_title); ?> Find Handicap</h2>
            <div class="scorer-card-body">
                <div class="section-title-wrap text-center">
                    <h2>Handicap Reference</h2>
                    <div class="section-title-accent"></div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="scorer-alert scorer-alert-error" role="alert">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars((string) $error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($tees)): ?>
                    <div class="scorer-alert scorer-alert-error" role="alert">
                        No handicap references are configured for club <?php echo (int) $clubNumber; ?>.
                    </div>
                <?php else: ?>
                    <form method="GET" action="/handicap-reference" class="handicap-reference-form">
                        <div class="handicap-reference-field">
                            <label for="club_number">Club</label>
                            <input id="club_number" type="text" value="<?php echo (int) $clubNumber; ?>" readonly>
                        </div>

                        <fieldset class="handicap-reference-field">
                            <legend>Gender</legend>
                            <div class="handicap-reference-segmented">
                                <input type="radio" id="gender_m" name="gender" value="M" <?php echo $gender === 'M' ? 'checked' : ''; ?>>
                                <label for="gender_m">Men</label>
                                <input type="radio" id="gender_f" name="gender" value="F" <?php echo $gender === 'F' ? 'checked' : ''; ?>>
                                <label for="gender_f">Women</label>
                            </div>
                        </fieldset>

                        <div class="handicap-reference-field">
                            <label for="tee_id">Tees</label>
                            <select id="tee_id" name="tee_id" required>
                                <option value="">Select tees</option>
                                <?php foreach ($tees as $tee): ?>
                                    <option value="<?php echo (int) $tee['row_id']; ?>"
                                            data-gender="<?php echo htmlspecialchars((string) $tee['gender']); ?>"
                                            <?php echo $teeId === (int) $tee['row_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars((string) $tee['tee_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <fieldset class="handicap-reference-field">
                            <legend>Index Type</legend>
                            <div class="handicap-reference-segmented">
                                <input type="radio" id="index_standard" name="index_type" value="standard" <?php echo $indexType === 'standard' ? 'checked' : ''; ?>>
                                <label for="index_standard">Standard</label>
                                <input type="radio" id="index_plus" name="index_type" value="plus" <?php echo $indexType === 'plus' ? 'checked' : ''; ?>>
                                <label for="index_plus">Plus</label>
                            </div>
                        </fieldset>

                        <div class="handicap-reference-field">
                            <label for="handicap_index">Handicap Index</label>
                            <input id="handicap_index" name="handicap_index" type="number" min="0" max="54" step="0.1"
                                   class="handicap-reference-index-input" inputmode="decimal"
                                   value="<?php echo htmlspecialchars($indexValue); ?>" required>
                        </div>

                        <div class="handicap-reference-action-row">
                            <button type="submit" name="calculate" value="1" class="btn-primary-pill handicap-reference-submit">Find Handicap</button>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if ($result !== null && $selectedTee !== null): ?>
                    <section class="handicap-reference-result" aria-live="polite">
                        <div class="handicap-reference-result-value">
                            <span>Playing Handicap</span>
                            <strong><?php echo htmlspecialchars((string) $result['published_display']); ?></strong>
                            <?php if ((int) $result['published_handicap'] < 0): ?>
                                <small>TW4: Scratch</small>
                            <?php endif; ?>
                        </div>
                        <dl class="handicap-reference-facts">
                            <div><dt>Course Rating</dt><dd><?php echo number_format((float) $selectedTee['course_rating'], 1); ?></dd></div>
                            <div><dt>Par</dt><dd><?php echo (int) $selectedTee['par']; ?></dd></div>
                            <div><dt>Slope</dt><dd><?php echo (int) $selectedTee['slope']; ?></dd></div>
                            <div><dt>Effective</dt><dd><?php echo date('j M Y', strtotime((string) $selectedTee['effective_from'])); ?></dd></div>
                        </dl>
                    </section>
                <?php endif; ?>

                <div class="handicap-reference-actions">
                    <a href="/scorer/menu" class="btn-secondary-pill">Back to Scorer Menu</a>
                </div>
            </div>
        </section>

        <footer class="scorer-footer">
            <p>&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

<script>
(() => {
    const teeSelect = document.getElementById('tee_id');
    const genderInputs = document.querySelectorAll('input[name="gender"]');
    if (!teeSelect || genderInputs.length === 0) return;

    const filterTees = () => {
        const gender = document.querySelector('input[name="gender"]:checked').value;
        let selectedVisible = false;
        let firstVisible = null;
        for (const option of teeSelect.options) {
            if (!option.dataset.gender) continue;
            option.hidden = option.dataset.gender !== gender;
            option.disabled = option.hidden;
            if (!option.hidden && firstVisible === null) firstVisible = option;
            if (option.selected && !option.hidden) selectedVisible = true;
        }
        if (!selectedVisible) teeSelect.value = firstVisible ? firstVisible.value : '';
    };

    genderInputs.forEach((input) => input.addEventListener('change', filterTees));
    filterTees();
})();
</script>
</body>
</html>