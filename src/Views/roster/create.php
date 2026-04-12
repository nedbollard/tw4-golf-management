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
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Add New Roster Entry</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <strong>Please fix the following errors:</strong>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/roster/create">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($old['first_name'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($old['last_name'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="gender" class="form-label">Gender *</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($old['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($old['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="handicap" class="form-label">Handicap</label>
                                <input type="number" class="form-control" id="handicap" name="handicap" 
                                       value="<?php echo htmlspecialchars($old['handicap'] ?? '0'); ?>" min="0" max="54">
                                <div class="form-text">Golf handicap index (0-54). Leave as 0 if not known.</div>
                            </div>

                            <div class="mb-3">
                                <label for="alias" class="form-label">Alias/Nickname</label>
                                <input type="text" class="form-control" id="alias" name="alias" 
                                       value="<?php echo htmlspecialchars($old['alias'] ?? ''); ?>" 
                                       placeholder="Optional nickname for display">
                                <div class="form-text">This will be displayed instead of the player identifier if provided.</div>
                            </div>

                            <div class="mb-3">
                                <label for="player_identifier" class="form-label">Player Identifier</label>
                                <input type="text" class="form-control" id="player_identifier" name="player_identifier" 
                                       value="<?php echo htmlspecialchars($old['player_identifier'] ?? ''); ?>" 
                                       placeholder="Leave blank to auto-generate">
                                <div class="form-text">Unique identifier (e.g., JohnD). Will be auto-generated if left blank.</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="/roster" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-success">Add to Roster</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
