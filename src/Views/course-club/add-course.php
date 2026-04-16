<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course - <?php echo htmlspecialchars($app_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Add New Course</h4>
                        <a href="/course-club" class="btn btn-sm btn-outline-light">Cancel</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error:</strong>
                                <?php 
                                $errorMessages = is_array($errors) ? $errors : [$errors];
                                foreach ($errorMessages as $message): 
                                ?>
                                    <?php echo htmlspecialchars($message); ?><br>
                                <?php endforeach; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/course-club/store-course">
                            <div class="mb-3">
                                <label for="course_name" class="form-label">Course/Club Name *</label>
                                <input type="text" class="form-control" id="course_name" name="course_name" 
                                       value="<?php echo htmlspecialchars($_SESSION['old']['course_name'] ?? ''); ?>" 
                                       maxlength="16" required autofocus>
                                <div class="form-text">Enter the course/club abbreviation (e.g., OVGC, PV, TCC). Maximum 16 characters.</div>
                                <small id="charCount" class="text-muted">0/16 characters</small>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-muted">Existing Courses:</h6>
                                <?php if (!empty($clubNames)): ?>
                                    <div class="list-group">
                                        <?php foreach ($clubNames as $club): ?>
                                            <div class="list-group-item">
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($club); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No courses yet. This will be the first one!</p>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-plus"></i> Create Course
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous"></script>
    <script>
        // Character counter for course name
        document.getElementById('course_name').addEventListener('input', function() {
            const charCount = document.getElementById('charCount');
            charCount.textContent = this.value.length + '/16 characters';
        });
        
        // Trigger on page load in case there's old data
        document.getElementById('course_name').dispatchEvent(new Event('input'));
    </script>
</body>
</html>
