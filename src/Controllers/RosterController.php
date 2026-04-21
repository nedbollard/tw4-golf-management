<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Services\RosterService;

/**
 * Roster Controller - Handle all roster-related operations
 */
class RosterController extends BaseController
{
    private RosterService $rosterService;

    public function __construct(Application $app, RosterService $rosterService)
    {
        parent::__construct($app);
        $this->rosterService = $rosterService;
    }

    public function index(): void
    {
        $this->requireRole('scorer');
        
        try {
            $roster = $this->rosterService->getAllPlayers();
            
            $this->render('roster/index', [
                'title' => 'Players - TW4 Golf Management',
                'roster' => $roster
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
        
        $player = $this->rosterService->getPlayer($playerId);
        
        if (!$player) {
            $this->redirect('/roster');
            return;
        }
        
        $this->render('roster/show', [
            'title' => 'Player Details - ' . $this->rosterService->getDisplayName($player),
            'player' => $player
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        
        $this->render('roster/create', [
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
            $this->redirect('/roster/create');
            return;
        }
        
        try {
            $playerId = $this->rosterService->createPlayer($data);
            $_SESSION['success'] = 'Player created successfully!';
            $this->redirect('/roster/' . $playerId);
        } catch (\InvalidArgumentException $e) {
            $_SESSION['errors'] = ['general' => $e->getMessage()];
            $_SESSION['old'] = $data;
            $this->redirect('/roster/create');
            return;
        } catch (\Throwable $e) {
            $_SESSION['errors'] = ['general' => 'Unable to create player right now.'];
            $_SESSION['old'] = $data;
            $this->redirect('/roster/create');
        }
    }

    public function edit(int $playerId): void
    {
        $this->requireAuth();
        
        $player = $this->rosterService->getPlayer($playerId);
        
        if (!$player) {
            $this->redirect('/roster');
            return;
        }
        
        $this->render('roster/edit', [
            'title' => 'Edit Player - ' . $this->rosterService->getDisplayName($player),
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
            $this->redirect('/roster/' . $playerId . '/edit');
            return;
        }
        
        try {
            $success = $this->rosterService->updatePlayer($playerId, $data);
            
            if ($success) {
                $_SESSION['success'] = 'Player updated successfully!';
            } else {
                $_SESSION['errors'] = ['general' => 'No changes made'];
            }
            
            $this->redirect('/roster/' . $playerId);
        } catch (\InvalidArgumentException $e) {
            $_SESSION['errors'] = ['general' => $e->getMessage()];
            $this->redirect('/roster/' . $playerId . '/edit');
            return;
        } catch (\Throwable $e) {
            $_SESSION['errors'] = ['general' => 'Unable to update player right now.'];
            $this->redirect('/roster/' . $playerId . '/edit');
        }
    }

    public function delete(int $playerId): void
    {
        $this->requireAuth();
        
        $player = $this->rosterService->getPlayer($playerId);
        
        if (!$player) {
            $this->redirect('/roster');
            return;
        }
        
        $this->render('roster/delete', [
            'title' => 'Delete Player - ' . $this->rosterService->getDisplayName($player),
            'player' => $player
        ]);
    }

    public function destroy(int $playerId): void
    {
        $this->requireAuth();
        
        $success = $this->rosterService->deletePlayer($playerId);
        
        if ($success) {
            $_SESSION['success'] = 'Player deactivated successfully!';
        } else {
            $_SESSION['errors'] = ['general' => 'Failed to deactivate player'];
        }
        
        $this->redirect('/roster');
    }

    public function search(): void
    {
        $this->requireAuth();
        
        $query = $_GET['q'] ?? '';
        $roster = [];
        
        if (!empty($query)) {
            $roster = $this->rosterService->searchPlayers($query);
        }
        
        $this->render('roster/search', [
            'title' => 'Search Players - TW4 Golf Management',
            'roster' => $roster,
            'query' => $query
        ]);
    }

    public function activate(int $playerId): void
    {
        $this->requireAuth();
        
        $success = $this->rosterService->activatePlayer($playerId);
        
        if ($success) {
            $_SESSION['success'] = 'Player activated successfully!';
        } else {
            $_SESSION['errors'] = ['general' => 'Failed to activate player'];
        }
        
        $this->redirect('/roster');
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
