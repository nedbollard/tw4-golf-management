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
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Staff Management</h4>
                        <div>
                            <a href="/staff/add" class="btn btn-sm btn-success">+ Add Staff</a>
                            <a href="/admin/menu" class="btn btn-sm btn-outline-light">← Admin Menu</a>
                        </div>
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

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Staff ID</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staff as $member): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($member->getRowId() ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($member->getUsername()); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $member->isAdmin() ? 'danger' : 'primary'; ?>">
                                                    <?php echo htmlspecialchars($member->getRole()); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $member->isActive() ? 'success' : 'secondary'; ?>">
                                                    <?php echo $member->isActive() ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="/staff/edit/<?php echo $member->getRowId(); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                <?php if ($member->getUsername() !== ($_SESSION['username'] ?? '')): ?>
                                                    <a href="/staff/delete/<?php echo $member->getRowId(); ?>" 
                                                       class="btn btn-sm btn-outline-danger" 
                                                       onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($member->getUsername()); ?>? This will retain them for audit purposes.')">
                                                        Delete
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (empty($staff)): ?>
                            <div class="text-center py-4">
                                <p class="text-muted">No staff members found.</p>
                                <a href="/staff/add" class="btn btn-success">Add First Staff Member</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
