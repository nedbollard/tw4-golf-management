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
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Roster Entry Details</h5>
                        <div>
                            <a href="/roster/<?php echo $player['row_id']; ?>/edit" class="btn btn-sm btn-warning me-2">Edit</a>
                            <a href="/roster" class="btn btn-sm btn-outline-light">Back to Roster</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Basic Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Player ID:</strong></td>
                                        <td><?php echo htmlspecialchars($player['player_identifier']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Name:</strong></td>
                                        <td><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></td>
                                    </tr>
                                    <?php if (!empty($player['alias'])): ?>
                                    <tr>
                                        <td><strong>Alias:</strong></td>
                                        <td><?php echo htmlspecialchars($player['alias']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td><strong>Gender:</strong></td>
                                        <td><?php echo ucfirst(htmlspecialchars($player['gender'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <span class="badge bg-<?php echo $player['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($player['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Golf Information</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Handicap:</strong></td>
                                        <td><?php echo htmlspecialchars($player['handicap']); ?></td>
                                    </tr>
                                    <?php if (!empty($player['first_play_date'])): ?>
                                    <tr>
                                        <td><strong>First Play Date:</strong></td>
                                        <td><?php echo htmlspecialchars($player['first_play_date']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td><strong>Created:</strong></td>
                                        <td><?php echo htmlspecialchars($player['created_at'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Last Updated:</strong></td>
                                        <td><?php echo htmlspecialchars($player['updated_at'] ?? 'N/A'); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <a href="/roster" class="btn btn-secondary">Back to Roster</a>
                            <a href="/roster/<?php echo $player['row_id']; ?>/edit" class="btn btn-warning">Edit Entry</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
