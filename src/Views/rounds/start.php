<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?> — Start Round</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php
$courses = $formData['courses'] ?? [];
$defaultDate = $old['round_date'] ?? ($formData['default_round_date'] ?? date('Y-m-d'));
$defaultRoundNumber = $old['round_number'] ?? ($formData['default_round_number'] ?? 1);
$defaultCourseId = $old['course_played_id'] ?? ($formData['default_course_played_id'] ?? '');
$clubNumber = (int) ($formData['club_number'] ?? 0);
?>
<div class="container py-5" style="max-width: 720px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Start a New Round</h1>
        <a href="/scorer/menu" class="btn btn-outline-secondary">Back to Scorer Menu</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="/rounds">
                <div class="mb-3">
                    <label for="round_date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="round_date" name="round_date"
                           value="<?php echo htmlspecialchars((string) $defaultDate); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="round_number" class="form-label">Round Number</label>
                    <input type="number" min="1" class="form-control" id="round_number" name="round_number"
                           value="<?php echo htmlspecialchars((string) $defaultRoundNumber); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="course_played_id" class="form-label">Course Played</label>
                    <select class="form-select" id="course_played_id" name="course_played_id" required <?php echo empty($courses) ? 'disabled' : ''; ?>>
                        <option value="">Select a course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo (int) $course['row_id']; ?>"
                                <?php echo ((string) $defaultCourseId === (string) $course['row_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['name_club'] . ' - ' . $course['name_course']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($clubNumber === 294): ?>
                        <div class="form-text">
                            Default course follows club 294 rule: odd day = Whites, even day = Blues.
                        </div>
                    <?php endif; ?>
                    <?php if (empty($courses)): ?>
                        <div class="form-text text-danger">No course_played rows are available yet.</div>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="/scorer/menu" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" <?php echo empty($courses) ? 'disabled' : ''; ?>>Start Round</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>