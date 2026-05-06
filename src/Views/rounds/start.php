<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> — Start Round</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="page-start-round">
<?php
$courses = $formData['courses'] ?? [];
$defaultDate = $old['round_date'] ?? ($formData['default_round_date'] ?? date('Y-m-d'));
$defaultRoundNumber = $old['round_number'] ?? ($formData['default_round_number'] ?? 1);
$defaultCourseId = $old['course_played_id'] ?? ($formData['default_course_played_id'] ?? '');
$clubNumber = (int) ($formData['club_number'] ?? 0);
$seasonYear = (string) ($formData['current_season_year'] ?? '');
?>

<div class="start-round-layout">
    <header class="start-round-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="start-round-card">
            <h2 class="start-round-card-title"><?php echo htmlspecialchars($app_title); ?> Start Round</h2>
            <div class="start-round-card-body">
                <div class="section-title-wrap text-center">
                    <h2>Start a New Round</h2>
                    <div class="section-title-accent"></div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="start-round-alert start-round-alert-error" role="alert">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/rounds">
                    <div class="start-round-form-group">
                        <label class="start-round-form-label">Season</label>
                        <input type="text" class="start-round-form-readonly" value="<?php echo htmlspecialchars($seasonYear); ?>" readonly>
                        <div class="start-round-form-text">Update the season on the configuration screen before starting the first round of a new season.</div>
                    </div>

                    <div class="start-round-form-group">
                        <label for="round_date" class="start-round-form-label">Date</label>
                        <input type="date" class="start-round-form-input" id="round_date" name="round_date"
                               value="<?php echo htmlspecialchars((string) $defaultDate); ?>" required>
                    </div>

                    <div class="start-round-form-group">
                        <label for="round_number" class="start-round-form-label">Round Number</label>
                        <input type="number" min="1" class="start-round-form-input" id="round_number" name="round_number"
                               value="<?php echo htmlspecialchars((string) $defaultRoundNumber); ?>" required>
                    </div>

                    <div class="start-round-form-group">
                        <label for="course_played_id" class="start-round-form-label">Course Played</label>
                        <select class="start-round-form-select" id="course_played_id" name="course_played_id" required <?php echo empty($courses) ? 'disabled' : ''; ?>>
                            <option value="">Select a course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo (int) $course['row_id']; ?>"
                                    <?php echo ((string) $defaultCourseId === (string) $course['row_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['name_club'] . ' - ' . $course['name_course']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($clubNumber === 294): ?>
                            <div class="start-round-form-text">
                                Default course follows club 294 rule: odd day = Whites, even day = Blues.
                            </div>
                        <?php endif; ?>
                        <?php if (empty($courses)): ?>
                            <div class="start-round-form-text text-danger">No course_played rows are available yet.</div>
                        <?php endif; ?>
                    </div>

                    <div class="start-round-actions">
                        <a href="/scorer/menu" class="btn-secondary-pill">Cancel</a>
                        <button type="submit" class="btn-primary-pill" <?php echo empty($courses) ? 'disabled' : ''; ?>>Start Round</button>
                    </div>
                </form>
            </div>
        </div>

        <footer class="start-round-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>