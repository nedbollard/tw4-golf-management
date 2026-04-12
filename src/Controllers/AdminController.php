<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;

/**
 * Admin Controller - Admin-only functions
 * 
 * reviewed? Yes
 */
class AdminController extends BaseController
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    public function menu(): void
    {
        $this->requireRole('admin');
        
        $this->render('admin/menu');
    }
}
