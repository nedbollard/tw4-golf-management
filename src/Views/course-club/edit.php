<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course Hole - <?php echo htmlspecialchars($app_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="/assets/css/style.css?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../../../public/assets/css/style.css')); ?>" rel="stylesheet">
</head>
<body class="page-course-club-form">
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
                                <pre class="tw-scroll-box"><?php 
                                    echo htmlspecialchars(json_encode($_SESSION['debug'], JSON_PRETTY_PRINT)); 
                                    unset($_SESSION['debug']);
                                ?></pre>
                            </div>
                        <?php endif; ?>
                        

<?php 
    // Use relative URL for production-ready solution
    $updateUrl = '/course-club/' . $courseClub->getCourseClubId() . '/update';
?>
<form method="POST" action="<?php echo $updateUrl; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
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
                                               value="<?php echo htmlspecialchars($old['name_hole'] ?? $courseClub->getNameHole()); ?>" 
                                               maxlength="24" required>
                                        <div class="form-text">Descriptive hole name</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="par" class="form-label">Par *</label>
                                        <input type="number" class="form-control" id="par" name="par" 
                                               value="<?php echo htmlspecialchars($old['par'] ?? $courseClub->getPar()); ?>" 
                                               min="3" max="5" required>
                                        <div class="form-text">3-5 (Par 3, 4, or 5)</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="stroke" class="form-label">Stroke Index *</label>
                                        <input type="number" class="form-control" id="stroke" name="stroke" 
                                               value="<?php echo htmlspecialchars($old['stroke'] ?? $courseClub->getStroke()); ?>" 
                                               min="1" max="18" required>
                                        <div class="form-text">1-18 (1 = hardest, 18 = easiest)</div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <a href="/course-club" class="btn btn-secondary btn-sm me-2">
                                    Cancel
                                </a>
                                <button type="submit" class="btn-action-primary btn-sm">
                                    Update Hole
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style nonce="<?php echo htmlspecialchars($csp_nonce, ENT_QUOTES, 'UTF-8'); ?>">
        .btn-normal {
            padding: 6px 12px !important;
            font-size: 0.875rem !important;
            line-height: 1.2 !important;
            border-radius: 25px;
            font-weight: 500;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="<?php echo htmlspecialchars($csp_nonce, ENT_QUOTES, 'UTF-8'); ?>">
        // Auto-focus hole name field
        document.getElementById('name_hole').focus();
    </script>
</body>
</html>
