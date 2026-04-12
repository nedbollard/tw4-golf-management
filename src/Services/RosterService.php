<?php

namespace App\Services;

use App\Core\Database;
use App\Utility\NameHelper;

/**
 * Roster Service - Handle all roster-related operations
 */
class RosterService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function createPlayer(array $data): int
    {
        // Capitalize names properly
        if (isset($data['first_name'])) {
            $data['first_name'] = NameHelper::capitalizeName($data['first_name']);
        }
        if (isset($data['last_name'])) {
            $data['last_name'] = NameHelper::capitalizeName($data['last_name']);
        }
        
        // Generate player identifier if not provided
        if (empty($data['player_identifier'])) {
            $data['player_identifier'] = $this->generatePlayerIdentifier(
                $data['first_name'],
                $data['last_name']
            );
        }

        // Validate data
        $errors = $this->validatePlayerData($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        // Add updated_by field with logged-in staff username
        $auth = $this->db->getAuth();
        if ($auth->isLoggedIn()) {
            $currentUser = $auth->getUser();
            $data['updated_by'] = $currentUser['username'] ?? null;
        }
        
        return $this->db->insert('roster', $data);
    }

    public function updatePlayer(int $playerId, array $data): bool
    {
        // Capitalize names properly
        if (isset($data['first_name'])) {
            $data['first_name'] = NameHelper::capitalizeName($data['first_name']);
        }
        if (isset($data['last_name'])) {
            $data['last_name'] = NameHelper::capitalizeName($data['last_name']);
        }
        
        // If name changed, regenerate player identifier
        if (isset($data['first_name']) || isset($data['last_name'])) {
            $currentPlayer = $this->getPlayer($playerId);
            if ($currentPlayer) {
                $firstName = $data['first_name'] ?? $currentPlayer['first_name'];
                $lastName = $data['last_name'] ?? $currentPlayer['last_name'];
                
                // Only regenerate if names actually changed
                if ($firstName !== $currentPlayer['first_name'] || 
                    $lastName !== $currentPlayer['last_name']) {
                    $data['player_identifier'] = $this->generatePlayerIdentifier(
                        $firstName,
                        $lastName,
                        $playerId // Exclude current player from uniqueness check
                    );
                }
            }
        }

        // Validate player_identifier uniqueness if provided and date_first_played is null
        if (isset($data['player_identifier']) && !empty($data['player_identifier'])) {
            $currentPlayer = $this->getPlayer($playerId);
            if ($currentPlayer && empty($currentPlayer['date_first_played'])) {
                // Only allow player_identifier change if date_first_played is null
                if (!$this->isPlayerIdentifierAvailable($data['player_identifier'], $playerId)) {
                    throw new \InvalidArgumentException("Player identifier '{$data['player_identifier']}' is already taken or conflicts with an existing alias");
                }
            }
        }

        // Validate alias uniqueness if provided
        if (isset($data['alias']) && !empty($data['alias'])) {
            if (!$this->isAliasAvailable($data['alias'], $playerId)) {
                throw new \InvalidArgumentException("Alias '{$data['alias']}' is already taken");
            }
        }

        // Add updated_by field with logged-in staff username
        $auth = $this->db->getAuth();
        if ($auth->isLoggedIn()) {
            $currentUser = $auth->getUser();
            $data['updated_by'] = $currentUser['username'] ?? null;
        }
        
        return $this->db->update('roster', $data, ['row_id' => $playerId]) > 0;
    }

    public function getPlayer(int $playerId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM roster WHERE row_id = ? AND status = "active"',
            [$playerId]
        );
    }

    public function getPlayerByIdentifier(string $identifier): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM roster WHERE player_identifier = ? AND status = "active"',
            [$identifier]
        );
    }

    public function getPlayerByAlias(string $alias): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM roster WHERE alias = ? AND status = "active"',
            [$alias]
        );
    }

    public function searchPlayers(string $query): array
    {
        $searchTerm = "%{$query}%";
        
        return $this->db->fetchAll(
            'SELECT * FROM roster WHERE 
             (player_identifier LIKE ? OR alias LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
             AND status = "active" ORDER BY first_name, last_name',
            [$searchTerm, $searchTerm, $searchTerm, $searchTerm]
        );
    }

    public function getAllPlayers(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM roster WHERE status = "active" ORDER BY first_name, last_name'
        );
    }

    public function getActivePlayers(): array
    {
        return $this->getAllPlayers(); // Same as getAllPlayers for clarity
    }

    public function getAllPlayersIncludingInactive(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM roster ORDER BY first_name, last_name'
        );
    }

    public function deletePlayer(int $playerId): bool
    {
        return $this->db->update('roster', ['status' => 'inactive'], ['row_id' => $playerId]) > 0;
    }

    public function activatePlayer(int $playerId): bool
    {
        return $this->db->update('roster', ['status' => 'active'], ['row_id' => $playerId]) > 0;
    }

    public function getDisplayName(array $player): string
    {
        // Return alias if present, otherwise player identifier
        return !empty($player['alias']) ? $player['alias'] : $player['player_identifier'];
    }

    private function generatePlayerIdentifier(string $firstName, string $lastName, ?int $excludePlayerId = null): string
    {
        // Base identifier: first name + first character of last name
        $baseIdentifier = ucfirst(strtolower(trim($firstName))) . 
                         strtoupper(substr(trim($lastName), 0, 1));
        
        // Check if base identifier is available
        if ($this->isPlayerIdentifierAvailable($baseIdentifier, $excludePlayerId)) {
            return $baseIdentifier;
        }

        // If not available, append numbers
        $counter = 1;
        do {
            $identifier = $baseIdentifier . $counter;
            $counter++;
        } while (!$this->isPlayerIdentifierAvailable($identifier, $excludePlayerId));

        return $identifier;
    }

    private function isPlayerIdentifierAvailable(string $identifier, ?int $excludePlayerId = null): bool
    {
        // Check if identifier conflicts with existing player identifiers
        $sql = 'SELECT COUNT(*) FROM roster WHERE player_identifier = ? AND status = "active"';
        $params = [$identifier];
        
        if ($excludePlayerId !== null) {
            $sql .= ' AND row_id != ?';
            $params[] = $excludePlayerId;
        }
        
        $count = $this->db->fetchOne($sql, $params)['COUNT(*)'];
        if ($count > 0) {
            return false;
        }
        
        // Check if identifier conflicts with existing aliases
        $sql = 'SELECT COUNT(*) FROM roster WHERE alias = ? AND status = "active"';
        $params = [$identifier];
        
        if ($excludePlayerId !== null) {
            $sql .= ' AND row_id != ?';
            $params[] = $excludePlayerId;
        }
        
        $count = $this->db->fetchOne($sql, $params)['COUNT(*)'];
        return $count == 0;
    }

    private function isAliasAvailable(string $alias, ?int $excludePlayerId = null): bool
    {
        // Check against both aliases and player identifiers
        $sql = 'SELECT COUNT(*) FROM roster WHERE 
                (alias = ? OR player_identifier = ?) AND status = "active"';
        $params = [$alias, $alias];
        
        if ($excludePlayerId !== null) {
            $sql .= ' AND row_id != ?';
            $params[] = $excludePlayerId;
        }
        
        $count = $this->db->fetchOne($sql, $params)['COUNT(*)'];
        return $count == 0;
    }

    private function validatePlayerData(array $data): array
    {
        $errors = [];

        if (empty($data['first_name'])) {
            $errors[] = 'First name is required';
        }

        if (empty($data['last_name'])) {
            $errors[] = 'Last name is required';
        }

        if (!in_array($data['gender'] ?? '', ['male', 'female'])) {
            $errors[] = 'Gender must be male or female';
        }

        // Validate player identifier uniqueness
        if (!empty($data['player_identifier'])) {
            if (!$this->isPlayerIdentifierAvailable($data['player_identifier'])) {
                $errors[] = 'Player identifier is already taken';
            }
        }

        // Validate alias uniqueness against both aliases and player identifiers
        if (!empty($data['alias'])) {
            if (!$this->isAliasAvailable($data['alias'])) {
                $errors[] = 'Alias is already taken (conflicts with existing player identifier or alias)';
            }
        }

        return $errors;
    }
}
