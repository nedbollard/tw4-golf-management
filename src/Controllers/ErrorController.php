<?php

namespace App\Controllers;

use App\Core\Application;

/**
 * Error Controller - Render user-facing error pages.
 */
class ErrorController extends BaseController
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    public function index(): void
    {
        $code = isset($_GET['code']) ? (int) $_GET['code'] : 500;
        $message = trim((string) ($_GET['message'] ?? 'Something went wrong.'));

        if ($message === '') {
            $message = 'Something went wrong.';
        }

        $this->render('errors/error', [
            'code' => $code,
            'message' => $message,
            'user' => $this->app->getDatabase()->getAuth()->getUser(),
        ]);
    }
}
