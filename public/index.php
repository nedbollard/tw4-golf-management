<?php

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load application
use App\Core\Application;

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize and run application
$app = Application::getInstance();
$app->run();
