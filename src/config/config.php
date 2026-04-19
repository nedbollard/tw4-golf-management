<?php

return [
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => $_ENV['DB_PORT'] ?? 3306,
        'name' => $_ENV['DB_NAME'] ?? 'TW4_base',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
    ],
    
    'app' => [
        'name' => 'TW4 Golf Management System',
        'debug' => $_ENV['DEBUG'] ?? false,
        'timezone' => 'Pacific/Auckland',
    ],
    
    'security' => [
        'session_lifetime' => 3600, // 1 hour
        'password_min_length' => 8,
        'max_login_attempts' => 5,
    ],
    
    'paths' => [
        'views' => __DIR__ . '/../Views',
        'logs' => __DIR__ . '/../logs',
        'uploads' => __DIR__ . '/../uploads',
    ]
];
