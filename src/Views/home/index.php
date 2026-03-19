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
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><?php echo htmlspecialchars($app_title); ?></h4>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">Welcome</h5>
                        <p class="text-center text-muted">Please select an option below:</p>
                        
                        <div class="d-grid gap-3">
                            <!-- Staff Login -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-lock fa-3x text-primary mb-3"></i>
                                    <h6 class="card-title">Staff Login</h6>
                                    <p class="card-text text-muted">For administrators and scorers</p>
                                    <a href="/login" class="btn btn-primary">Login</a>
                                </div>
                            </div>
                            
                            <!-- Recent Results -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-trophy fa-3x text-warning mb-3"></i>
                                    <h6 class="card-title">Recent Results</h6>
                                    <p class="card-text text-muted">View latest competition results</p>
                                    <a href="/results" class="btn btn-warning">View Results</a>
                                </div>
                            </div>
                            
                            <!-- Leaderboard -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-line fa-3x text-info mb-3"></i>
                                    <h6 class="card-title">Leaderboard</h6>
                                    <p class="card-text text-muted">Current season standings</p>
                                    <a href="/leaderboard" class="btn btn-info">View Leaderboard</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                TW4 Golf Management System &copy; <?php echo date('Y'); ?>
                            </small>
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
