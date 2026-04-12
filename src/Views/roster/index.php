<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Players</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Player List</h6>
                            <a href="/roster/create" class="btn btn-primary">Add New Player</a>
                        </div>
                        
                        <?php if (empty($roster)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                No players found. <a href="/roster/create">Add your first player</a>.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Player ID</th>
                                            <th>Name</th>
                                            <th>Alias</th>
                                            <th>Gender</th>
                                            <th>Handicap</th>
                                            <th>Status</th>
                                            <th>Updated By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($roster as $player): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($player['player_identifier']); ?></td>
                                                <td><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></td>
                                                <td>
                                                    <?php if (!empty($player['alias'])): ?>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($player['alias']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $player['gender'] === 'male' ? 'primary' : 'pink'; ?>">
                                                        <?php echo ucfirst($player['gender']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($player['handicap']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $player['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($player['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($player['updated_by'])): ?>
                                                        <?php echo htmlspecialchars($player['updated_by']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="/roster/<?php echo $player['row_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                        <a href="/roster/<?php echo $player['row_id']; ?>/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
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
