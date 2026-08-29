<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;

/**
 * Role Switch Controller - Handle role switching for staff
 *
 * Switching may only ever reduce privilege relative to the role stored in the
 * database. The session role is never a valid basis for granting a switch.
 */
class RoleSwitchController extends BaseController
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    public function switchToAdmin(): void
    {
        $this->requireAuth();
        $this->ensureServicesInitialized();

        if ($this->authService->getStoredRole() !== 'admin') {
            $this->denySwitch();
            return;
        }

        // Normalize role key and keep legacy compatibility.
        $_SESSION['user_role'] = 'admin';
        $_SESSION['role'] = 'admin';

        $this->redirect('/admin/menu');
    }

    public function switchToScorer(): void
    {
        $this->requireAuth();
        $this->ensureServicesInitialized();

        $storedRole = $this->authService->getStoredRole();
        if ($storedRole !== 'admin' && $storedRole !== 'scorer') {
            $this->denySwitch();
            return;
        }

        // Normalize role key and keep legacy compatibility.
        $_SESSION['user_role'] = 'scorer';
        $_SESSION['role'] = 'scorer';

        $this->redirect('/scorer/menu');
    }

    private function denySwitch(): void
    {
        $query = http_build_query([
            'code' => 403,
            'message' => 'Access denied. Your account is not permitted to switch to that role.',
        ]);
        $this->redirect('/error?' . $query);
    }
}
