<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course Holes - <?php echo htmlspecialchars($app_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Add Course Holes - <?php echo htmlspecialchars($courseName); ?></h4>
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

                        <form method="POST" action="/course-club/bulk-store" id="bulkCreateForm">
                            <!-- Header Section -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="club_name" class="form-label">Course/Club Name *</label>
                                        <input type="text" class="form-control" id="club_name" name="club_name" 
                                               value="<?php echo htmlspecialchars($courseName); ?>" 
                                               readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="gender" class="form-label">Gender *</label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="M" <?php echo (($old['gender'] ?? '') === 'M') ? 'selected' : ''; ?>>Male</option>
                                            <option value="F" <?php echo (($old['gender'] ?? '') === 'F') ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Course Holes Input -->
                            <div class="row">
                                <!-- Front Nine Section -->
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3">Front Nine (Holes 1-9)</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover table-sm">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th style="width: 70px;">Hole #</th>
                                                    <th style="width: 120px;">Hole Name *</th>
                                                    <th style="width: 60px;">Par *</th>
                                                    <th style="width: 100px;">Stroke *</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php for ($i = 1; $i <= 9; $i++): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-primary" style="font-size: 12px; padding: 6px;"><?php echo $i; ?></span>
                                                            <input type="hidden" name="holes[<?php echo $i; ?>][number]" value="<?php echo $i; ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm" name="holes[<?php echo $i; ?>][name]" 
                                                                   value="<?php echo htmlspecialchars($old['holes'][$i]['name'] ?? ''); ?>"
                                                                   maxlength="24" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm" name="holes[<?php echo $i; ?>][par]" 
                                                                   value="<?php echo htmlspecialchars($old['holes'][$i]['par'] ?? ''); ?>"
                                                                   min="3" max="5" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm" name="holes[<?php echo $i; ?>][stroke]" 
                                                                   value="<?php echo htmlspecialchars($old['holes'][$i]['stroke'] ?? ''); ?>"
                                                                   min="1" max="18" required>
                                                        </td>
                                                    </tr>
                                                <?php endfor; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Back Nine Section -->
                                <div class="col-md-6">
                                    <h5 class="text-success mb-3">Back Nine (Holes 10-18)</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover table-sm">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th style="width: 70px;">Hole #</th>
                                                    <th style="width: 120px;">Hole Name *</th>
                                                    <th style="width: 60px;">Par *</th>
                                                    <th style="width: 100px;">Stroke *</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php for ($i = 10; $i <= 18; $i++): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-success" style="font-size: 12px; padding: 6px;"><?php echo $i; ?></span>
                                                            <input type="hidden" name="holes[<?php echo $i; ?>][number]" value="<?php echo $i; ?>">
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm" name="holes[<?php echo $i; ?>][name]" 
                                                                   value="<?php echo htmlspecialchars($old['holes'][$i]['name'] ?? ''); ?>"
                                                                   maxlength="24" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm" name="holes[<?php echo $i; ?>][par]" 
                                                                   value="<?php echo htmlspecialchars($old['holes'][$i]['par'] ?? ''); ?>"
                                                                   min="3" max="5" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm" name="holes[<?php echo $i; ?>][stroke]" 
                                                                   value="<?php echo htmlspecialchars($old['holes'][$i]['stroke'] ?? ''); ?>"
                                                                   min="1" max="18" required>
                                                        </td>
                                                    </tr>
                                                <?php endfor; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between mt-4">
                                <a href="/course-club" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Save All Holes
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
</body>
</html>
