<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Played Management - <?php echo htmlspecialchars($app_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Course Played Management</h4>
                        <a href="/admin/menu" class="btn btn-sm btn-outline-light">Admin Menu</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php
                                $successMessages = is_array($success) ? $success : [$success];
                                foreach ($successMessages as $message):
                                ?>
                                    <?php echo htmlspecialchars($message); ?><br>
                                <?php endforeach; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

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

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">Defined Played Courses</h5>
                            <a href="/course-played/create" class="btn btn-primary">Add Course Played</a>
                        </div>

                        <?php if (empty($coursesPlayed)): ?>
                            <div class="text-center py-5">
                                <h6 class="text-muted mb-3">No Course Played definitions found.</h6>
                                <a href="/course-played/create" class="btn btn-primary">Create First Course Played</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
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
                                                    <a href="/course-played/<?php echo (int) $course['row_id']; ?>/edit" class="btn btn-outline-primary btn-sm me-1">Edit</a>
                                                    <form method="POST" action="/course-played/<?php echo (int) $course['row_id']; ?>/delete" class="d-inline" onsubmit="return confirm('Delete this Course Played and all its hole mappings?');">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
