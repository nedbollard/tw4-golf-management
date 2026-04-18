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
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">Admin Menu</h4>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">Administrator Options</h5>
                        <p class="text-center text-muted">System administration functions</p>
                        
                        <div class="d-grid gap-3">
                            <!-- Configure System -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-cog fa-3x text-danger mb-3"></i>
                                    <h6 class="card-title">Configure System</h6>
                                    <p class="card-text text-muted">Manage system settings and configuration</p>
                                    <a href="/config" class="btn btn-danger">Configure System</a>
                                </div>
                            </div>
                            
                            <!-- Staff Management -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                    <h6 class="card-title">Staff Management</h6>
                                    <p class="card-text text-muted">Add, update, and manage staff accounts</p>
                                    <a href="/staff" class="btn btn-primary">Manage Staff</a>
                                </div>
                            </div>
                            
                            <!-- Course Club Management -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-golf-ball fa-3x text-warning mb-3"></i>
                                    <h6 class="card-title">Course Club Management</h6>
                                    <p class="card-text text-muted">Manage course holes and club configuration</p>
                                    <a href="/course-club" class="btn btn-warning">Manage Course</a>
                                </div>
                            </div>

                            <!-- Course Played Management -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-route fa-3x text-info mb-3"></i>
                                    <h6 class="card-title">Course Played Management</h6>
                                    <p class="card-text text-muted">Create and maintain played 9-hole course definitions</p>
                                    <a href="/course-played" class="btn btn-info">Manage Course Played</a>
                                </div>
                            </div>
                            
                            <!-- View Logs -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-list-alt fa-3x text-success mb-3"></i>
                                    <h6 class="card-title">View Logs</h6>
                                    <p class="card-text text-muted">View system logs and events</p>
                                    <a href="/logs" class="btn btn-success">View Logs</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="/" class="btn btn-secondary">← Back to Main Menu</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
