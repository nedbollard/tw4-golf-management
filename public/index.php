<?php

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load application
use App\Core\Application;

function isSecureRequest(): bool
{
    $https = $_SERVER['HTTPS'] ?? '';
    if (strtolower((string) $https) === 'on') {
        return true;
    }

    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['HTTP_X_FORWARDED_PROTOCOL'] ?? '';
    if (strtolower((string) $forwardedProto) === 'https') {
        return true;
    }

    return false;
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isSecureRequest(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.cookie_secure', isSecureRequest() ? '1' : '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Initialize and run application
$app = Application::getInstance();
$app->run();
