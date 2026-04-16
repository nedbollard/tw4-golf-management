<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course Hole - <?php echo htmlspecialchars($app_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Add Course Hole</h4>
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

                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <strong>Need to add a new course first?</strong> 
                            <a href="/course-club/add-course" class="alert-link">Create a new course/club</a> before adding holes.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>

                        <form method="POST" action="/course-club/store">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name_club" class="form-label">Club Name *</label>
                                        <input type="text" class="form-control" id="name_club" name="name_club" 
                                               value="<?php echo htmlspecialchars($_SESSION['old']['name_club'] ?? $newCourse ?? ''); ?>" 
                                               maxlength="16" required>
                                        <div class="form-text">Club abbreviation (e.g., OVGC)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="number_hole" class="form-label">Hole Number *</label>
                                        <input type="number" class="form-control" id="number_hole" name="number_hole" 
                                               value="<?php echo htmlspecialchars($_SESSION['old']['number_hole'] ?? ''); ?>" 
                                               min="1" max="18" required>
                                        <div class="form-text">1-18</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name_hole" class="form-label">Hole Name *</label>
                                        <input type="text" class="form-control" id="name_hole" name="name_hole" 
                                               value="<?php echo htmlspecialchars($_SESSION['old']['name_hole'] ?? ''); ?>" 
                                               maxlength="24" required>
                                        <div class="form-text">Descriptive hole name</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="gender" class="form-label">Gender *</label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="M" <?php echo (($_SESSION['old']['gender'] ?? '') === 'M') ? 'selected' : ''; ?>>
                                                Male
                                            </option>
                                            <option value="F" <?php echo (($_SESSION['old']['gender'] ?? '') === 'F') ? 'selected' : ''; ?>>
                                                Female
                                            </option>
                                        </select>
                                        <div class="form-text">M for Male, F for Female</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="par" class="form-label">Par *</label>
                                        <input type="number" class="form-control" id="par" name="par" 
                                               value="<?php echo htmlspecialchars($_SESSION['old']['par'] ?? ''); ?>" 
                                               min="3" max="5" required>
                                        <div class="form-text">3-5 (Par 3, 4, or 5)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="stroke" class="form-label">Stroke Index *</label>
                                        <input type="number" class="form-control" id="stroke" name="stroke" 
                                               value="<?php echo htmlspecialchars($_SESSION['old']['stroke'] ?? ''); ?>" 
                                               min="1" max="20" required>
                                        <div class="form-text">1-20 (1 = hardest, 18 = easiest)</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="/course-club" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Hole
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        // Auto-focus first field
        document.getElementById('name_club').focus();
        
        // Clear session errors on form submission
        document.querySelector('form').addEventListener('submit', function() {
            <?php unset($_SESSION['errors'], $_SESSION['old']); ?>
        });
    </script>
</body>
</html>
