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
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Scorer Menu</h4>
                        <div>
                            <a href="/switch/admin" class="btn btn-sm btn-danger me-2">Switch to Admin</a>
                            <a href="/logout" class="btn btn-sm btn-secondary">Logout</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">Scorer Options</h5>
                        <p class="text-center text-muted">Competition scoring functions</p>
                        
                        <div class="d-grid gap-3">
                            <!-- View Roster -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-3x text-success mb-3"></i>
                                    <h6 class="card-title">View Roster</h6>
                                    <p class="card-text text-muted">View roster details and statistics</p>
                                    <a href="/roster" class="btn btn-success">View Roster</a>
                                </div>
                            </div>
                            
                            <!-- Start Round -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-play-circle fa-3x text-primary mb-3"></i>
                                    <h6 class="card-title">Start Round</h6>
                                    <p class="card-text text-muted">Begin a new competition round</p>
                                    <a href="/round/start" class="btn btn-primary">Start Round</a>
                                </div>
                            </div>
                            
                            <!-- Enter Scores -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-edit fa-3x text-warning mb-3"></i>
                                    <h6 class="card-title">Enter Scores</h6>
                                    <p class="card-text text-muted">Record player scores</p>
                                    <a href="/score/enter" class="btn btn-warning">Enter Scores</a>
                                </div>
                            </div>
                            
                            <!-- Common Options -->
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-trophy fa-3x text-warning mb-3"></i>
                                    <h6 class="card-title">Recent Results</h6>
                                    <p class="card-text text-muted">View latest competition results</p>
                                    <a href="/results" class="btn btn-warning">View Results</a>
                                </div>
                            </div>
                            
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
                            <a href="/logout" class="btn btn-secondary">← Back to Main Menu</a>
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
