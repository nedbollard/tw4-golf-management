<?php

namespace App\Controllers;

use App\Core\Application;
use App\Services\ResultsArchiveService;

class ResultsArchiveController extends BaseController
{
    private ResultsArchiveService $archiveService;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->archiveService = new ResultsArchiveService($this->app->getDatabase());
    }

    public function index(): void
    {
        $tree = $this->archiveService->getArchiveTree();

        $this->render('results/archive', [
            'title' => 'Results Archive - TW4 Golf Management',
            'archiveTree' => $tree,
            'success' => $_SESSION['success'] ?? null,
            'errors' => $_SESSION['errors'] ?? [],
        ]);

        unset($_SESSION['success'], $_SESSION['errors']);
    }
}
