<?php

namespace App\Controllers;

use App\Core\Application;
use App\Services\PlayerProgressService;
use App\Services\RosterService;

class PlayerProgressController extends BaseController
{
    private RosterService $rosterService;
    private PlayerProgressService $playerProgressService;

    public function __construct(
        Application $app,
        RosterService $rosterService,
        PlayerProgressService $playerProgressService
    )
    {
        parent::__construct($app);
        $this->rosterService = $rosterService;
        $this->playerProgressService = $playerProgressService;
    }

    public function index(): void
    {
        $playerOptions = $this->playerProgressService->getEligiblePlayersWithHistory();

        $selectedPlayerId = (int) ($_GET['player_id'] ?? 0);
        if ($selectedPlayerId < 1 && !empty($playerOptions)) {
            $selectedPlayerId = (int) ($playerOptions[0]['row_id'] ?? 0);
        }

        $notice = null;
        if (empty($playerOptions)) {
            $notice = 'No active players with recorded history are available.';
        }

        $this->render('player-progress/index', [
            'title' => 'Player Progress - TW4 Golf Management',
            'playerOptions' => $playerOptions,
            'selectedPlayerId' => $selectedPlayerId,
            'notice' => $notice,
        ]);
    }

    public function chart(): void
    {
        $playerOptions = $this->playerProgressService->getEligiblePlayersWithHistory();

        if (empty($playerOptions)) {
            $this->flash->error('No active players with recorded history are available.');
            $this->redirect('/player-progress');
            return;
        }

        $selectedPlayerId = (int) ($_GET['player_id'] ?? 0);
        if ($selectedPlayerId < 1 && !empty($playerOptions)) {
            $selectedPlayerId = (int) ($playerOptions[0]['row_id'] ?? 0);
        }

        $selectedPlayer = null;
        foreach ($playerOptions as $option) {
            if ((int) ($option['row_id'] ?? 0) === $selectedPlayerId) {
                $selectedPlayer = $option;
                break;
            }
        }

        if (!$selectedPlayer) {
            $selectedPlayer = $playerOptions[0];
            $selectedPlayerId = (int) ($selectedPlayer['row_id'] ?? 0);
        }

        $notice = null;
        $progress = $this->playerProgressService->getPlayerProgress($selectedPlayerId);
        if (empty($progress['rounds'])) {
            $notice = 'No recorded round history is available for this player.';
        }

        $this->render('player-progress/chart', [
            'title' => 'Player Progress - TW4 Golf Management',
            'playerOptions' => $playerOptions,
            'selectedPlayer' => $selectedPlayer,
            'selectedPlayerId' => $selectedPlayerId,
            'progress' => $progress,
            'notice' => $notice,
        ]);
    }
}
