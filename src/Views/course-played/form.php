<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode === 'create' ? 'Create' : 'Edit'; ?> Course Played - <?php echo htmlspecialchars($app_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><?php echo $mode === 'create' ? 'Create' : 'Edit'; ?> Course Played</h4>
                        <a href="/course-played" class="btn btn-sm btn-outline-light">Back</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php
                                $errorMessages = is_array($errors) ? $errors : [$errors];
                                foreach ($errorMessages as $message):
                                ?>
                                    <?php echo htmlspecialchars($message); ?><br>
                                <?php endforeach; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php
                        $existingNumberHoles = array_map(static function ($holeRow) {
                            return (int) $holeRow['number_hole'];
                        }, $holes ?? []);

                        $baseData = $coursePlayed ?? [];
                        $formData = !empty($old) ? $old : $baseData;
                        ?>

                        <form method="POST" action="<?php echo $mode === 'create' ? '/course-played/store' : '/course-played/' . (int) $coursePlayed['row_id'] . '/update'; ?>">
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="name_course" class="form-label">Course Name *</label>
                                    <input type="text" id="name_course" name="name_course" class="form-control" maxlength="16" required
                                           value="<?php echo htmlspecialchars($formData['name_course'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="name_club" class="form-label">Club *</label>
                                    <select id="name_club" name="name_club" class="form-select" required>
                                        <option value="">Select Club</option>
                                        <?php foreach ($clubs as $club): ?>
                                            <option value="<?php echo htmlspecialchars($club); ?>" <?php echo (($formData['name_club'] ?? '') === $club) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($club); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="ident_eclectic" class="form-label">Eclectic Identifier *</label>
                                    <input type="text" id="ident_eclectic" name="ident_eclectic" class="form-control" maxlength="16" required
                                           value="<?php echo htmlspecialchars($formData['ident_eclectic'] ?? ''); ?>">
                                </div>
                            </div>

                            <h6 class="mb-3">Select 9 Club Holes (1-18)</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-sm align-middle">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th style="width: 40%;">Selection</th>
                                                    <th style="width: 60%;">Number Hole</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php for ($i = 1; $i <= 9; $i++): ?>
                                                    <?php
                                                    $oldHoles = $old['holes'] ?? [];
                                                    $selected = isset($oldHoles[$i])
                                                        ? (int) $oldHoles[$i]
                                                        : ($existingNumberHoles[$i - 1] ?? $i);
                                                    ?>
                                                    <tr>
                                                        <td><span class="badge bg-primary"><?php echo $i; ?></span></td>
                                                        <td>
                                                            <select class="form-select form-select-sm" name="holes[<?php echo $i; ?>]" required>
                                                                <option value="">Select</option>
                                                                <?php for ($h = 1; $h <= 18; $h++): ?>
                                                                    <option value="<?php echo $h; ?>" <?php echo $selected === $h ? 'selected' : ''; ?>>
                                                                        <?php echo $h; ?>
                                                                    </option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                <?php endfor; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="/course-played" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-danger"><?php echo $mode === 'create' ? 'Create Course Played' : 'Update Course Played'; ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
