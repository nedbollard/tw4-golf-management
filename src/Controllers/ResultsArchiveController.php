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
        $selectedSeason = trim((string) ($_GET['season'] ?? ''));
        $selectedRound = trim((string) ($_GET['round'] ?? ''));

        $this->render('results/archive', [
            'title' => 'Results Archive - TW4 Golf Management',
            'archiveTree' => $tree,
            'selectedSeason' => $selectedSeason,
            'selectedRound' => $selectedRound,
            'success' => $_SESSION['success'] ?? null,
            'errors' => $_SESSION['errors'] ?? [],
        ]);

        unset($_SESSION['success'], $_SESSION['errors']);
    }
}
