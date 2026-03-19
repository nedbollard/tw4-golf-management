<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">Edit Staff Member</h4>
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
                                <strong>Errors:</strong><br>
                                <?php 
                                $errorMessages = is_array($errors) ? $errors : [$errors];
                                foreach ($errorMessages as $error): 
                                ?>
                                    <?php echo htmlspecialchars($error); ?><br>
                                <?php endforeach; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/staff/update/<?php echo $staff->getRowId(); ?>">
                            
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($_SESSION['old']['first_name'] ?? $staff->getFirstName()); ?>">
                                <small class="form-text text-muted">First name of staff member</small>
                                <?php if (isset($errors['first_name'])): ?>
                                    <div class="text-danger small"><?php echo htmlspecialchars($errors['first_name']); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($_SESSION['old']['last_name'] ?? $staff->getLastName()); ?>">
                                <small class="form-text text-muted">Last name of staff member</small>
                                <?php if (isset($errors['last_name'])): ?>
                                    <div class="text-danger small"><?php echo htmlspecialchars($errors['last_name']); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_SESSION['old']['username'] ?? $staff->getUsername()); ?>" required>
                                <small class="form-text text-muted">Display name for staff member</small>
                                <?php if (isset($errors['username'])): ?>
                                    <div class="text-danger small"><?php echo htmlspecialchars($errors['username']); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="admin" <?php echo ($staff->getRole() === 'admin' || ($_SESSION['old']['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                        <option value="scorer" <?php echo ($staff->getRole() === 'scorer' || ($_SESSION['old']['role'] ?? '') === 'scorer') ? 'selected' : ''; ?>>Scorer</option>
                                    </select>
                                    <?php if (isset($errors['role'])): ?>
                                        <div class="text-danger small"><?php echo htmlspecialchars($errors['role']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    &nbsp;
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Leave blank to keep current password">
                                <small class="form-text text-muted">Leave blank to keep current password</small>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="text-danger small"><?php echo htmlspecialchars($errors['password']); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="/staff" class="btn btn-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-danger">Update Staff Member</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
