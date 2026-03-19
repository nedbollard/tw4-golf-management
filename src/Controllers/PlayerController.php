<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\PlayerService;

/**
 * Player Controller - Handle all player-related operations
 */
class PlayerController extends BaseController
{
    private PlayerService $playerService;

    public function __construct(Application $app, PlayerService $playerService)
    {
        parent::__construct($app);
        $this->playerService = $playerService;
    }

    public function index(): void
    {
        $this->requireRole('scorer');
        
        try {
            $players = $this->playerService->getAllPlayers();
            
            $this->render('players/index', [
                'title' => 'Players - TW4 Golf Management',
                'players' => $players
            ]);
        } catch (\Exception $e) {
            // Show error if database fails
            echo '<!DOCTYPE html>
<html>
<head><title>Players - Error</title></head>
<body>
<h1>Players Page</h1>
<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
<a href="/dashboard">Back to Dashboard</a>
</body>
</html>';
        }
    }

    public function show(int $playerId): void
    {
        $this->requireAuth();
        
        $player = $this->playerService->getPlayer($playerId);
        
        if (!$player) {
            $this->redirect('/players');
            return;
        }
        
        $this->render('players/show', [
            'title' => 'Player Details - ' . $this->playerService->getDisplayName($player),
            'player' => $player
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        
        $this->render('players/create', [
            'title' => 'Add New Player - TW4 Golf Management',
            'errors' => $_SESSION['errors'] ?? [],
            'old' => $_SESSION['old'] ?? []
        ]);
        
        unset($_SESSION['errors'], $_SESSION['old']);
    }

    public function store(): void
    {
        $this->requireAuth();
        
        $data = $this->getPostData();
        
        // Validate required fields
        $errors = $this->validatePlayerData($data);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $data;
            $this->redirect('/players/create');
            return;
        }
        
        try {
            $playerId = $this->playerService->createPlayer($data);
            $_SESSION['success'] = 'Player created successfully!';
            $this->redirect('/players/' . $playerId);
        } catch (\InvalidArgumentException $e) {
            $_SESSION['errors'] = ['general' => $e->getMessage()];
            $_SESSION['old'] = $data;
            $this->redirect('/players/create');
        }
    }

    public function edit(int $playerId): void
    {
        $this->requireAuth();
        
        $player = $this->playerService->getPlayer($playerId);
        
        if (!$player) {
            $this->redirect('/players');
            return;
        }
        
        $this->render('players/edit', [
            'title' => 'Edit Player - ' . $this->playerService->getDisplayName($player),
            'player' => $player,
            'errors' => $_SESSION['errors'] ?? []
        ]);
        
        unset($_SESSION['errors']);
    }

    public function update(int $playerId): void
    {
        $this->requireAuth();
        
        $data = $this->getPostData();
        
        // Validate required fields
        $errors = $this->validatePlayerData($data, $playerId);
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $this->redirect('/players/' . $playerId . '/edit');
            return;
        }
        
        try {
            $success = $this->playerService->updatePlayer($playerId, $data);
            
            if ($success) {
                $_SESSION['success'] = 'Player updated successfully!';
            } else {
                $_SESSION['errors'] = ['general' => 'No changes made'];
            }
            
            $this->redirect('/players/' . $playerId);
        } catch (\InvalidArgumentException $e) {
            $_SESSION['errors'] = ['general' => $e->getMessage()];
            $this->redirect('/players/' . $playerId . '/edit');
        }
    }

    public function delete(int $playerId): void
    {
        $this->requireAuth();
        
        $player = $this->playerService->getPlayer($playerId);
        
        if (!$player) {
            $this->redirect('/players');
            return;
        }
        
        $this->render('players/delete', [
            'title' => 'Delete Player - ' . $this->playerService->getDisplayName($player),
            'player' => $player
        ]);
    }

    public function destroy(int $playerId): void
    {
        $this->requireAuth();
        
        $success = $this->playerService->deletePlayer($playerId);
        
        if ($success) {
            $_SESSION['success'] = 'Player deactivated successfully!';
        } else {
            $_SESSION['errors'] = ['general' => 'Failed to deactivate player'];
        }
        
        $this->redirect('/players');
    }

    public function search(): void
    {
        $this->requireAuth();
        
        $query = $_GET['q'] ?? '';
        $players = [];
        
        if (!empty($query)) {
            $players = $this->playerService->searchPlayers($query);
        }
        
        $this->render('players/search', [
            'title' => 'Search Players - TW4 Golf Management',
            'players' => $players,
            'query' => $query
        ]);
    }

    public function activate(int $playerId): void
    {
        $this->requireAuth();
        
        $success = $this->playerService->activatePlayer($playerId);
        
        if ($success) {
            $_SESSION['success'] = 'Player activated successfully!';
        } else {
            $_SESSION['errors'] = ['general' => 'Failed to activate player'];
        }
        
        $this->redirect('/players');
    }

    private function validatePlayerData(array $data, ?int $excludePlayerId = null): array
    {
        $errors = [];
        
        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required';
        }
        
        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required';
        }
        
        if (empty($data['gender']) || !in_array($data['gender'], ['male', 'female'])) {
            $errors['gender'] = 'Valid gender is required';
        }
        
        // Validate handicap
        if (isset($data['handicap']) && (!is_numeric($data['handicap']) || $data['handicap'] < 0)) {
            $errors['handicap'] = 'Handicap must be a positive number';
        }
        
        // Validate alias uniqueness if provided
        if (!empty($data['alias'])) {
            // This would need to be implemented in PlayerService
            // For now, basic validation
            if (strlen($data['alias']) > 50) {
                $errors['alias'] = 'Alias must be 50 characters or less';
            }
        }
        
        return $errors;
    }
}
