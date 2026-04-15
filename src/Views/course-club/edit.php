<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course Hole - <?php echo htmlspecialchars($app_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">Edit Course Hole</h4>
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

                        <div class="alert alert-info">
                            <strong>Editing:</strong> Hole <?php echo $courseClub->getNumberHole(); ?> - 
                            <?php echo htmlspecialchars($courseClub->getNameHole()); ?> 
                            (<?php echo htmlspecialchars($courseClub->getNameClub()); ?>)
                        </div>
                        
                        <?php if (!empty($_SESSION['debug'])): ?>
                            <div class="alert alert-warning">
                                <strong>Debug Information:</strong>
                                <pre style="font-size: 12px; max-height: 200px; overflow-y: auto;"><?php 
                                    echo htmlspecialchars(json_encode($_SESSION['debug'], JSON_PRETTY_PRINT)); 
                                    unset($_SESSION['debug']);
                                ?></pre>
                            </div>
                        <?php endif; ?>
                        

<?php 
    // Use relative URL for production-ready solution
    $updateUrl = '/course-club/' . $courseClub->getRowId() . '/update';
?>
<form method="POST" action="<?php echo $updateUrl; ?>">
                            <div class="row">
                                <!-- Fixed Fields (Left Column) -->
                                <div class="col-md-6">
                                    <h5 class="text-muted mb-3">Fixed Fields</h5>
                                    
                                    <div class="mb-3">
                                        <label for="name_club" class="form-label">Club Name *</label>
                                        <input type="text" class="form-control bg-light" id="name_club" name="name_club" 
                                               value="<?php echo htmlspecialchars($courseClub->getNameClub()); ?>" 
                                               maxlength="16" readonly>
                                        <div class="form-text">Club abbreviation (cannot be changed)</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="number_hole" class="form-label">Hole Number *</label>
                                        <input type="number" class="form-control bg-light" id="number_hole" name="number_hole" 
                                               value="<?php echo htmlspecialchars($courseClub->getNumberHole()); ?>" 
                                               min="1" max="18" readonly>
                                        <div class="form-text">1-18 (cannot be changed)</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="gender" class="form-label">Gender *</label>
                                        <select class="form-select bg-light" id="gender" name="gender" disabled>
                                            <option value="">Select Gender</option>
                                            <option value="M" <?php echo $courseClub->getGender() === 'M' ? 'selected' : ''; ?>>
                                                Male
                                            </option>
                                            <option value="F" <?php echo $courseClub->getGender() === 'F' ? 'selected' : ''; ?>>
                                                Female
                                            </option>
                                        </select>
                                        <div class="form-text">M for Male, F for Female (cannot be changed)</div>
                                        <!-- Hidden field to submit the gender value -->
                                        <input type="hidden" name="gender" value="<?php echo htmlspecialchars($courseClub->getGender()); ?>">
                                    </div>
                                </div>
                                
                                <!-- Modifiable Fields (Right Column) -->
                                <div class="col-md-6">
                                    <h5 class="text-muted mb-3">Modifiable Fields</h5>
                                    
                                    <div class="mb-3">
                                        <label for="name_hole" class="form-label">Hole Name *</label>
                                        <input type="text" class="form-control" id="name_hole" name="name_hole" 
                                               value="<?php echo htmlspecialchars($_SESSION['old']['name_hole'] ?? $courseClub->getNameHole()); ?>" 
                                               maxlength="24" required>
                                        <div class="form-text">Descriptive hole name</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="par" class="form-label">Par *</label>
                                        <input type="number" class="form-control" id="par" name="par" 
                                               value="<?php echo htmlspecialchars($_SESSION['old']['par'] ?? $courseClub->getPar()); ?>" 
                                               min="3" max="5" required>
                                        <div class="form-text">3-5 (Par 3, 4, or 5)</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="stroke" class="form-label">Stroke Index *</label>
                                        <input type="number" class="form-control" id="stroke" name="stroke" 
                                               value="<?php echo htmlspecialchars($_SESSION['old']['stroke'] ?? $courseClub->getStroke()); ?>" 
                                               min="1" max="18" required>
                                        <div class="form-text">1-18 (1 = hardest, 18 = easiest)</div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <a href="/course-club" style="display: inline-block; padding: 2px 8px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px; font-size: 14px; margin-right: 8px; border: 1px solid #6c757d; line-height: 1;">
                                    Cancel
                                </a>
                                <button type="submit" style="padding: 2px 8px; background-color: #0d6efd; color: white; border: 1px solid #0d6efd; border-radius: 4px; font-size: 14px; line-height: 1;">
                                    Update Hole
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .btn-normal {
            padding: 6px 12px !important;
            font-size: 0.875rem !important;
            line-height: 1.2 !important;
            border-radius: 25px;
            font-weight: 500;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.com" crossorigin="anonymous"></script>
    <script>
        // Auto-focus hole name field
        document.getElementById('name_hole').focus();
        
        // Clear session errors on form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            <?php unset($_SESSION['errors'], $_SESSION['old']); ?>
        });
    </script>
</body>
</html>
