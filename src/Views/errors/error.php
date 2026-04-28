<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?php echo (int) ($code ?? 500); ?> - <?php echo htmlspecialchars($app_title ?? 'TW4'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php
$resolvedCode = (int) ($code ?? 500);
$resolvedMessage = (string) ($message ?? 'Something went wrong.');
$role = (string) ($user['user_role'] ?? '');
$roleMenu = '/';

if ($role === 'admin') {
    $roleMenu = '/admin/menu';
} elseif ($role === 'scorer') {
    $roleMenu = '/scorer/menu';
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Error <?php echo $resolvedCode; ?></h5>
                </div>
                <div class="card-body">
                    <p class="mb-3"><?php echo htmlspecialchars($resolvedMessage); ?></p>

                    <?php if ($resolvedCode === 403): ?>
                        <div class="alert alert-warning mb-3" role="alert">
                            You are signed in, but your account does not have permission for that page.
                        </div>
                    <?php endif; ?>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?php echo htmlspecialchars($roleMenu); ?>" class="btn btn-primary">Go to My Menu</a>
                        <a href="/" class="btn btn-outline-secondary">Main Menu</a>
                        <a href="/logout" class="btn btn-outline-dark">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
