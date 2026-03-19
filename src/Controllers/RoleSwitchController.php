<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;

/**
 * Role Switch Controller - Handle role switching for staff
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
        
        // Update session role to admin
        $_SESSION['role'] = 'admin';
        
        $this->redirect('/admin/menu');
    }

    public function switchToScorer(): void
    {
        $this->requireAuth();
        
        // Update session role to scorer
        $_SESSION['role'] = 'scorer';
        
        $this->redirect('/scorer/menu');
    }
}
