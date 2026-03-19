<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;

/**
 * Scorer Controller - Scorer-only functions
 */
class ScorerController extends BaseController
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    public function menu(): void
    {
        $this->requireRole('scorer');
        
        $this->render('scorer/menu');
    }
}
