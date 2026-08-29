<?php

/**
 * Set (or rotate) the password of a staff account.
 *
 * Usage (inside the app container):
 *   php scripts/set-staff-password.php <username>
 *
 * The new password is read from stdin so it never appears in shell history
 * or the process list.
 */

declare(strict_types=1);

function env(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? $default;
    }
    return (string) $value;
}

$username = $argv[1] ?? '';
if ($username === '') {
    fwrite(STDERR, "Usage: php scripts/set-staff-password.php <username>\n");
    exit(1);
}

fwrite(STDERR, "New password for '{$username}': ");
$password = trim((string) fgets(STDIN));
if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    env('DB_HOST', 'localhost'),
    env('DB_PORT', '3306'),
    env('DB_NAME', 'TW4_base')
);

$pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASSWORD'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$statement = $pdo->prepare('UPDATE staff SET password_hash = ? WHERE username = ?');
$statement->execute([password_hash($password, PASSWORD_DEFAULT), $username]);

if ($statement->rowCount() === 0) {
    fwrite(STDERR, "No staff account matched '{$username}'.\n");
    exit(1);
}

fwrite(STDERR, "Password updated for '{$username}'.\n");
