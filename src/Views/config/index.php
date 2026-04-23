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
                        <h4 class="mb-0">System Configuration</h4>
                        <a href="/admin/menu" class="btn btn-sm btn-outline-light">← Admin Menu</a>
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

                        <!-- Configuration Form -->
                        <form method="POST" action="/config">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Configuration Name</th>
                                            <th>Type</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($configs as $config): ?>
                                            <?php if ($config['config_name'] === 'config_status') continue; // Hide config_status ?>
                                            <tr>
                                                <td>
                                                    <input type="hidden" name="name_<?php echo $config['row_id']; ?>" value="<?php echo htmlspecialchars($config['config_name']); ?>">
                                                    <strong><?php echo htmlspecialchars($config['config_name']); ?></strong>
                                                    <?php if (in_array($config['config_name'], ['club_name', 'competition_name', 'season_year'])): ?>
                                                        <span class="badge bg-warning ms-2">Critical</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <input type="hidden" name="type_<?php echo $config['row_id']; ?>" value="<?php echo htmlspecialchars($config['config_type']); ?>">
                                                    <span class="badge bg-<?php echo $config['config_type'] === 'int' ? 'primary' : 'info'; ?>">
                                                        <?php echo htmlspecialchars($config['config_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($config['config_name'] === 'handicap_method'): ?>
                                                        <select class="form-select" name="config_<?php echo $config['row_id']; ?>">
                                                            <option value="L" <?php echo $config['config_value_string'] === 'L' ? 'selected' : ''; ?>>L - Legacy</option>
                                                            <option value="M" <?php echo $config['config_value_string'] === 'M' ? 'selected' : ''; ?>>M - Modern</option>
                                                        </select>
                                                    <?php elseif ($config['config_name'] === 'team_haggle_state'): ?>
                                                        <select class="form-select" name="config_<?php echo $config['row_id']; ?>">
                                                            <option value="F" <?php echo $config['config_value_string'] === 'F' ? 'selected' : ''; ?>>F - Floating</option>
                                                            <option value="L" <?php echo $config['config_value_string'] === 'L' ? 'selected' : ''; ?>>L - Locked</option>
                                                        </select>
                                                    <?php else: ?>
                                                        <input type="<?php echo $config['config_type'] === 'int' ? 'number' : 'text'; ?>" 
                                                               class="form-control" 
                                                               name="config_<?php echo $config['row_id']; ?>" 
                                                               value="<?php echo htmlspecialchars($config['config_value_string']); ?>"
                                                               <?php echo $config['config_name'] === 'season_year' ? 'pattern="\\d{2}_\\d{2}" maxlength="5"' : ''; ?>
                                                               <?php echo $config['config_type'] === 'int' ? 'step="1"' : ''; ?>>
                                                        <?php if ($config['config_name'] === 'season_year'): ?>
                                                            <div class="form-text">Use format NN_NN, for example 25_26.</div>
                                                        <?php endif; ?>
                                                        <?php if (isset($errors["config_{$config['row_id']}"])): ?>
                                                            <div class="text-danger small"><?php echo htmlspecialchars($errors["config_{$config['row_id']}"]); ?></div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="/admin/menu" class="btn btn-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-danger">Save Configuration</button>
                            </div>
                        </form>
                        
                        <div class="mt-4">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Configuration Notes:</h6>
                                <ul class="mb-0">
                                    <li><strong>String values</strong> can contain any text</li>
                                    <li><strong>Integer values</strong> must be whole numbers (e.g., 1, 42, 100)</li>
                                    <li><strong>Critical configurations</strong> (marked with yellow badge) are essential system settings</li>
                                    <li><strong>Season year</strong> controls round numbering and should use the format NN_NN, for example 25_26</li>
                                    <li><strong>Config Status</strong> controls whether the system shows "Configuration required" or normal operation</li>
                                    <li>All changes are logged for audit purposes with user attribution</li>
                                    <li><strong>Programmer Note:</strong> New configuration items must be added/removed by programmers via database migrations</li>
                                </ul>
                            </div>
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
