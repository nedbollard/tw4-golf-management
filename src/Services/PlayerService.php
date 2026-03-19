<?php

namespace App\Services;

use App\Core\Database;

/**
 * Player Service - Handle all player-related operations
 */
class PlayerService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function createPlayer(array $data): int
    {
        // Generate member identifier if not provided
        if (empty($data['member_identifier'])) {
            $data['member_identifier'] = $this->generateMemberIdentifier(
                $data['first_name'],
                $data['last_name']
            );
        }

        // Validate data
        $errors = $this->validatePlayerData($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        return $this->db->insert('players', $data);
    }

    public function updatePlayer(int $playerId, array $data): bool
    {
        // If name changed, regenerate member identifier
        if (isset($data['first_name']) || isset($data['last_name'])) {
            $currentPlayer = $this->getPlayer($playerId);
            if ($currentPlayer) {
                $firstName = $data['first_name'] ?? $currentPlayer['first_name'];
                $lastName = $data['last_name'] ?? $currentPlayer['last_name'];
                
                // Only regenerate if names actually changed
                if ($firstName !== $currentPlayer['first_name'] || 
                    $lastName !== $currentPlayer['last_name']) {
                    $data['member_identifier'] = $this->generateMemberIdentifier(
                        $firstName,
                        $lastName,
                        $playerId // Exclude current player from uniqueness check
                    );
                }
            }
        }

        // Validate alias uniqueness if provided
        if (isset($data['alias']) && !empty($data['alias'])) {
            if (!$this->isAliasAvailable($data['alias'], $playerId)) {
                throw new \InvalidArgumentException("Alias '{$data['alias']}' is already taken");
            }
        }

        return $this->db->update('players', $data, ['player_id' => $playerId]) > 0;
    }

    public function getPlayer(int $playerId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM players WHERE player_id = ? AND status = "active"',
            [$playerId]
        );
    }

    public function getPlayerByIdentifier(string $identifier): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM players WHERE member_identifier = ? AND status = "active"',
            [$identifier]
        );
    }

    public function getPlayerByAlias(string $alias): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM players WHERE alias = ? AND status = "active"',
            [$alias]
        );
    }

    public function searchPlayers(string $query): array
    {
        $searchTerm = "%{$query}%";
        
        return $this->db->fetchAll(
            'SELECT * FROM players WHERE 
             (member_identifier LIKE ? OR alias LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
             AND status = "active" ORDER BY first_name, last_name',
            [$searchTerm, $searchTerm, $searchTerm, $searchTerm]
        );
    }

    public function getAllPlayers(): array
    {
        return $this->db->fetchAll(
            'SELECT row_id as player_id, ident_player as member_identifier, 
                    name_first as first_name, name_last as last_name, 
                    ident_public as alias, gender, handicap, status
             FROM player WHERE status = "A" ORDER BY name_first, name_last'
        );
    }

    public function getActivePlayers(): array
    {
        return $this->getAllPlayers(); // Same as getAllPlayers for clarity
    }

    public function getAllPlayersIncludingInactive(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM players ORDER BY first_name, last_name'
        );
    }

    public function deletePlayer(int $playerId): bool
    {
        return $this->db->update('players', ['status' => 'inactive'], ['player_id' => $playerId]) > 0;
    }

    public function activatePlayer(int $playerId): bool
    {
        return $this->db->update('players', ['status' => 'active'], ['player_id' => $playerId]) > 0;
    }

    public function getDisplayName(array $player): string
    {
        // Return alias if present, otherwise member identifier
        return !empty($player['alias']) ? $player['alias'] : $player['member_identifier'];
    }

    private function generateMemberIdentifier(string $firstName, string $lastName, ?int $excludePlayerId = null): string
    {
        // Base identifier: first name + first character of last name
        $baseIdentifier = ucfirst(strtolower(trim($firstName))) . 
                         strtoupper(substr(trim($lastName), 0, 1));
        
        // Check if base identifier is available
        if ($this->isMemberIdentifierAvailable($baseIdentifier, $excludePlayerId)) {
            return $baseIdentifier;
        }

        // If not available, append numbers
        $counter = 1;
        do {
            $identifier = $baseIdentifier . $counter;
            $counter++;
        } while (!$this->isMemberIdentifierAvailable($identifier, $excludePlayerId));

        return $identifier;
    }

    private function isMemberIdentifierAvailable(string $identifier, ?int $excludePlayerId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM players WHERE member_identifier = ? AND status = "active"';
        $params = [$identifier];
        
        if ($excludePlayerId !== null) {
            $sql .= ' AND player_id != ?';
            $params[] = $excludePlayerId;
        }
        
        $count = $this->db->fetchOne($sql, $params)['COUNT(*)'];
        return $count == 0;
    }

    private function isAliasAvailable(string $alias, ?int $excludePlayerId = null): bool
    {
        // Check against both aliases and member identifiers
        $sql = 'SELECT COUNT(*) FROM players WHERE 
                (alias = ? OR member_identifier = ?) AND status = "active"';
        $params = [$alias, $alias];
        
        if ($excludePlayerId !== null) {
            $sql .= ' AND player_id != ?';
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

        // Validate member identifier uniqueness
        if (!empty($data['member_identifier'])) {
            if (!$this->isMemberIdentifierAvailable($data['member_identifier'])) {
                $errors[] = 'Member identifier is already taken';
            }
        }

        // Validate alias uniqueness against both aliases and member identifiers
        if (!empty($data['alias'])) {
            if (!$this->isAliasAvailable($data['alias'])) {
                $errors[] = 'Alias is already taken (conflicts with existing member identifier or alias)';
            }
        }

        return $errors;
    }
}
