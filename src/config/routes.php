<?php

return [
    // Public routes
    'GET' => [
        '/' => [
            'path' => '/',
            'controller' => 'App\\Controllers\\HomeController',
            'method' => 'index'
        ],
        '/login' => [
            'path' => '/login',
            'controller' => 'App\\Controllers\\AuthController',
            'method' => 'showLogin'
        ],
        '/logout' => [
            'path' => '/logout',
            'controller' => 'App\\Controllers\\AuthController',
            'method' => 'logout'
        ],
        '/register' => [
            'path' => '/register',
            'controller' => 'App\\Controllers\\AuthController',
            'method' => 'showRegister'
        ],
        '/roster' => [
            'path' => '/roster',
            'controller' => 'App\Controllers\RosterController',
            'method' => 'index'
        ],
        '/course-club' => [
            'path' => '/course-club',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'index'
        ],
        '/course-club/create' => [
            'path' => '/course-club/create',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'create'
        ],
        '/course-club/add-course' => [
            'path' => '/course-club/add-course',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'addCourse'
        ],
        '/course-club/bulk-create' => [
            'path' => '/course-club/bulk-create',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'bulkCreate'
        ],
        '/course-played' => [
            'path' => '/course-played',
            'controller' => 'App\Controllers\CoursePlayedController',
            'method' => 'index'
        ],
        '/course-played/create' => [
            'path' => '/course-played/create',
            'controller' => 'App\Controllers\CoursePlayedController',
            'method' => 'create'
        ],
        '/course-played/{id}/edit' => [
            'path' => '/course-played/{id}/edit',
            'controller' => 'App\Controllers\CoursePlayedController',
            'method' => 'edit'
        ],
        '/course-club/{club}-{gender}' => [
            'path' => '/course-club/{club}-{gender}',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'index'
        ],
        '/roster/create' => [
            'path' => '/roster/create',
            'controller' => 'App\Controllers\RosterController',
            'method' => 'create'
        ],
        '/roster/{id}' => [
            'path' => '/roster/{id}',
            'controller' => 'App\Controllers\RosterController',
            'method' => 'show'
        ],
        '/roster/{id}/edit' => [
            'path' => '/roster/{id}/edit',
            'controller' => 'App\Controllers\RosterController',
            'method' => 'edit'
        ],
        '/admin/menu' => [
            'path' => '/admin/menu',
            'controller' => 'App\\Controllers\\AdminController',
            'method' => 'menu'
        ],
        '/scorer/menu' => [
            'path' => '/scorer/menu',
            'controller' => 'App\\Controllers\\ScorerController',
            'method' => 'menu'
        ],
        '/switch/admin' => [
            'path' => '/switch/admin',
            'controller' => 'App\\Controllers\\RoleSwitchController',
            'method' => 'switchToAdmin'
        ],
        '/switch/scorer' => [
            'path' => '/switch/scorer',
            'controller' => 'App\\Controllers\\RoleSwitchController',
            'method' => 'switchToScorer'
        ],
        '/config' => [
            'path' => '/config',
            'controller' => 'App\\Controllers\\ConfigController',
            'method' => 'index'
        ],
        '/config/delete' => [
            'path' => '/config/delete',
            'controller' => 'App\\Controllers\\ConfigController',
            'method' => 'delete'
        ],
        '/staff' => [
            'path' => '/staff',
            'controller' => 'App\\Controllers\\StaffController',
            'method' => 'index'
        ],
        '/staff/add' => [
            'path' => '/staff/add',
            'controller' => 'App\\Controllers\\StaffController',
            'method' => 'add'
        ],
        '/staff/edit/{id}' => [
            'path' => '/staff/edit/{id}',
            'controller' => 'App\\Controllers\\StaffController',
            'method' => 'edit'
        ],
        '/staff/update/{id}' => [
            'path' => '/staff/update/{id}',
            'controller' => 'App\\Controllers\\StaffController',
            'method' => 'update'
        ],
        '/staff/delete/{id}' => [
            'path' => '/staff/delete/{id}',
            'controller' => 'App\\Controllers\\StaffController',
            'method' => 'delete'
        ],
        '/course-club/store' => [
            'path' => '/course-club/store',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'store'
        ],
        '/course-club/{id}/edit' => [
            'path' => '/course-club/{id}/edit',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'edit'
        ],
        '/course-club/{id}/update' => [
            'path' => '/course-club/{id}/update',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'update'
        ],
        '/course-club/{id}/delete' => [
            'path' => '/course-club/{id}/delete',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'delete'
        ],
        '/course-club/batch-update' => [
            'path' => '/course-club/batch-update',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'batchUpdate'
        ],
        '/logs' => [
            'path' => '/logs',
            'controller' => 'App\\Controllers\\LogController',
            'method' => 'index'
        ],
        '/logs/export' => [
            'path' => '/logs/export',
            'controller' => 'App\\Controllers\\LogController',
            'method' => 'export'
        ],
        '/results' => [
            'path' => '/results',
            'controller' => 'App\\Controllers\\HomeController',
            'method' => 'underConstruction'
        ],
        '/leaderboard' => [
            'path' => '/leaderboard',
            'controller' => 'App\\Controllers\\HomeController',
            'method' => 'underConstruction'
        ],
        '/rounds' => [
            'path' => '/rounds',
            'controller' => 'App\\Controllers\\RoundController',
            'method' => 'index'
        ],
        '/rounds/start' => [
            'path' => '/rounds/start',
            'controller' => 'App\\Controllers\\RoundController',
            'method' => 'start'
        ],
        '/rounds/finish' => [
            'path' => '/rounds/finish',
            'controller' => 'App\\Controllers\\RoundController',
            'method' => 'finish'
        ],
        '/rounds/{id}/unlock' => [
            'path' => '/rounds/{id}/unlock',
            'controller' => 'App\\Controllers\\RoundController',
            'method' => 'forceUnlock'
        ],
        '/scores' => [
            'path' => '/scores',
            'controller' => 'App\\Controllers\\ScoreController',
            'method' => 'index'
        ],
        '/scores/enter' => [
            'path' => '/scores/enter',
            'controller' => 'App\\Controllers\\ScoreController',
            'method' => 'enter'
        ],
        '/scores/enter/{id}' => [
            'path' => '/scores/enter/{id}',
            'controller' => 'App\\Controllers\\ScoreController',
            'method' => 'enterCard'
        ],
        '/scores/present-results' => [
            'path' => '/scores/present-results',
            'controller' => 'App\\Controllers\\ScoreController',
            'method' => 'presentResults'
        ],
    ],
    
    'POST' => [
        '/login' => [
            'path' => '/login',
            'controller' => 'App\\Controllers\\AuthController',
            'method' => 'login'
        ],
        '/config' => [
            'path' => '/config',
            'controller' => 'App\\Controllers\\ConfigController',
            'method' => 'save'
        ],
        '/staff/add' => [
            'path' => '/staff/add',
            'controller' => 'App\\Controllers\\StaffController',
            'method' => 'add'
        ],
        '/staff/update/{id}' => [
            'path' => '/staff/update/{id}',
            'controller' => 'App\\Controllers\\StaffController',
            'method' => 'update'
        ],
        '/staff/delete/{id}' => [
            'path' => '/staff/delete/{id}',
            'controller' => 'App\\Controllers\\StaffController',
            'method' => 'delete'
        ],
        '/roster/create' => [
            'path' => '/roster/create',
            'controller' => 'App\Controllers\RosterController',
            'method' => 'store'
        ],
        '/logout' => [
            'path' => '/logout',
            'controller' => 'App\\Controllers\\AuthController',
            'method' => 'logout'
        ],
        '/roster/{id}/update' => [
            'path' => '/roster/{id}/update',
            'controller' => 'App\Controllers\RosterController',
            'method' => 'update'
        ],
        '/roster/{id}/delete' => [
            'path' => '/roster/{id}/delete',
            'controller' => 'App\Controllers\RosterController',
            'method' => 'delete'
        ],
        '/rounds' => [
            'path' => '/rounds',
            'controller' => 'App\\Controllers\\RoundController',
            'method' => 'store'
        ],
        '/scores' => [
            'path' => '/scores',
            'controller' => 'App\\Controllers\\ScoreController',
            'method' => 'store'
        ],
        '/scores/enter/{id}' => [
            'path' => '/scores/enter/{id}',
            'controller' => 'App\\Controllers\\ScoreController',
            'method' => 'storeCard'
        ],
        '/scores/present-results' => [
            'path' => '/scores/present-results',
            'controller' => 'App\\Controllers\\ScoreController',
            'method' => 'finalizeResults'
        ],
        '/course-club/store' => [
            'path' => '/course-club/store',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'store'
        ],
        '/course-club/store-course' => [
            'path' => '/course-club/store-course',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'storeCourse'
        ],
        '/course-club/bulk-store' => [
            'path' => '/course-club/bulk-store',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'bulkStore'
        ],
        '/course-played/store' => [
            'path' => '/course-played/store',
            'controller' => 'App\Controllers\CoursePlayedController',
            'method' => 'store'
        ],
        '/course-played/{id}/update' => [
            'path' => '/course-played/{id}/update',
            'controller' => 'App\Controllers\CoursePlayedController',
            'method' => 'update'
        ],
        '/course-played/{id}/delete' => [
            'path' => '/course-played/{id}/delete',
            'controller' => 'App\Controllers\CoursePlayedController',
            'method' => 'delete'
        ],
        '/course-club/{id}/update' => [
            'path' => '/course-club/{id}/update',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'update'
        ],
        '/course-club/{id}/delete' => [
            'path' => '/course-club/{id}/delete',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'delete'
        ],
        '/course-club/batch-update' => [
            'path' => '/course-club/batch-update',
            'controller' => 'App\Controllers\CourseClubController',
            'method' => 'batchUpdate'
        ],
    ],
];
