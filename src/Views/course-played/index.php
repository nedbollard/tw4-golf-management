<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Played Management - <?php echo htmlspecialchars($app_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../../../public/assets/css/style.css')); ?>" rel="stylesheet">
</head>
<body class="page-course-played">
<?php
$sessionUsername = (string) ($_SESSION['username'] ?? 'admin');
$sessionRole = (string) ($_SESSION['role'] ?? 'admin');
?>
<div class="course-played-layout">
    <header class="course-played-header">
        <h1>Twilight Golf Scoring</h1>
    </header>

    <main>
        <div class="course-played-card">
            <h2 class="course-played-card-title"><?php echo htmlspecialchars($app_title); ?> Course Played Management</h2>
            <div class="course-played-card-body">
                <div class="section-title-wrap text-center">
                    <h2>Manage Course Played</h2>
                    <div class="section-title-accent"></div>
                </div>

                <p class="course-played-intro">Create and maintain 9-hole course played definitions and mappings.</p>

                <div class="course-played-session" role="status" aria-live="polite">
                    <strong>Session Active</strong>
                    <span>
                        Signed in as <?php echo htmlspecialchars($sessionUsername); ?>
                        (<?php echo htmlspecialchars(ucfirst($sessionRole)); ?>)
                    </span>
                </div>

                        <?php if (!empty($success)): ?>
                            <div class="course-played-alert course-played-alert-success" role="status">
                                <?php
                                $successMessages = is_array($success) ? $success : [$success];
                                foreach ($successMessages as $message):
                                ?>
                                    <div><?php echo htmlspecialchars($message); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="course-played-alert course-played-alert-error" role="alert">
                                <?php
                                $errorMessages = is_array($errors) ? $errors : [$errors];
                                foreach ($errorMessages as $message):
                                ?>
                                    <div><?php echo htmlspecialchars($message); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">Defined Played Courses</h5>
                            <a href="/course-played/create" class="btn-action-primary">Add Course Played</a>
                        </div>

                        <?php if (empty($coursesPlayed)): ?>
                            <div class="text-center py-5 course-played-empty">
                                <h6 class="text-muted mb-3">No Course Played definitions found.</h6>
                                <a href="/course-played/create" class="btn-action-primary">Create First Course Played</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive tw-table-wrap">
                                <table class="table table-striped table-hover align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Course Name</th>
                                            <th>Club</th>
                                            <th>Eclectic</th>
                                            <th class="text-center">Mapped Holes</th>
                                            <th>Updated By</th>
                                            <th>Updated</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($coursesPlayed as $course): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($course['name_course']); ?></td>
                                                <td><?php echo htmlspecialchars($course['name_club']); ?></td>
                                                <td><?php echo htmlspecialchars($course['ident_eclectic']); ?></td>
                                                <td class="text-center"><?php echo (int) $course['mapped_holes']; ?>/9</td>
                                                <td><?php echo htmlspecialchars($course['updated_by']); ?></td>
                                                <td><?php echo htmlspecialchars($course['updated_ts']); ?></td>
                                                <td class="text-center">
                                                    <a href="/course-played/<?php echo (int) $course['row_id']; ?>/edit" class="btn-action-primary btn-sm me-1">Edit</a>
                                                    <form method="POST" action="/course-played/<?php echo (int) $course['row_id']; ?>/delete" class="d-inline" data-confirm="Delete this Course Played and all its hole mappings?">
                                                        <button type="submit" class="btn-action-destructive btn-sm">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <div class="course-played-toolbar course-played-toolbar-bottom">
                            <a href="/admin/menu" class="btn-secondary-pill">Back to Admin Menu</a>
                        </div>
            </div>
        </div>

        <footer class="course-played-footer">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Twilight Golf Scoring &bull; 2nd Wind Software</p>
        </footer>
    </main>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/confirm-submit.js"></script>
</body>
</html>
