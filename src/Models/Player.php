<?php

namespace App\Models;

use App\Core\Database;

/**
 * Player Model - Represents a player in the system
 */
class Player
{
    private ?int $playerId = null;
    private string $playerIdentifier;
    private string $firstName;
    private string $lastName;
    private ?string $alias = null;
    private string $gender;
    private int $handicap;
    private string $status;
    private ?\DateTime $createdAt = null;
    private ?\DateTime $updatedAt = null;

    public function __construct(
        string $playerIdentifier,
        string $firstName,
        string $lastName,
        string $gender,
        string $status = 'active',
        int $handicap = 0,
        ?string $alias = null,
        ?int $playerId = null
    ) {
        $this->playerId = $playerId;
        $this->playerIdentifier = $playerIdentifier;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->alias = $alias;
        $this->gender = $gender;
        $this->handicap = $handicap;
        $this->status = $status;
    }

    // Getters
    public function getPlayerId(): ?int
    {
        return $this->playerId;
    }

    public function getPlayerIdentifier(): string
    {
        return $this->playerIdentifier;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function getHandicap(): int
    {
        return $this->handicap;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    // Setters
    public function setMemberIdentifier(string $memberIdentifier): void
    {
        $this->memberIdentifier = $memberIdentifier;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function setAlias(?string $alias): void
    {
        $this->alias = $alias;
    }

    public function setGender(string $gender): void
    {
        $this->gender = $gender;
    }

    public function setHandicap(int $handicap): void
    {
        $this->handicap = $handicap;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    // Business logic methods
    public function getDisplayName(): string
    {
        return !empty($this->alias) ? $this->alias : $this->memberIdentifier;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isMale(): bool
    {
        return $this->gender === 'male';
    }

    public function isFemale(): bool
    {
        return $this->gender === 'female';
    }

    // Database methods
    public function save(Database $db): int
    {
        $data = [
            'player_identifier' => $this->playerIdentifier,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'alias' => $this->alias,
            'gender' => $this->gender,
            'handicap' => $this->handicap,
            'status' => $this->status
        ];

        if ($this->playerId === null) {
            // Insert new player
            $this->playerId = $db->insert('players', $data);
            return $this->playerId;
        } else {
            // Update existing player
            $db->update('players', $data, ['player_id' => $this->playerId]);
            return $this->playerId;
        }
    }

    public function delete(Database $db): bool
    {
        if ($this->playerId === null) {
            return false;
        }

        $this->status = 'inactive';
        return $db->update('players', ['status' => 'inactive'], ['player_id' => $this->playerId]) > 0;
    }

    public function activate(Database $db): bool
    {
        if ($this->playerId === null) {
            return false;
        }

        $this->status = 'active';
        return $db->update('players', ['status' => 'active'], ['player_id' => $this->playerId]) > 0;
    }

    // Static methods for data access
    public static function findById(Database $db, int $playerId): ?self
    {
        $data = $db->fetchOne(
            'SELECT * FROM players WHERE player_id = ? AND status = "active"',
            [$playerId]
        );

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function findByIdentifier(Database $db, string $identifier): ?self
    {
        $data = $db->fetchOne(
            'SELECT * FROM players WHERE player_identifier = ? AND status = "active"",
            [$identifier]
        );

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function findByAlias(Database $db, string $alias): ?self
    {
        $data = $db->fetchOne(
            'SELECT * FROM players WHERE alias = ? AND status = "active"',
            [$alias]
        );

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function findAll(Database $db): array
    {
        $data = $db->fetchAll(
            'SELECT * FROM players WHERE status = "active" ORDER BY first_name, last_name'
        );

        return array_map([self::class, 'fromArray'], $data);
    }

    public static function search(Database $db, string $query): array
    {
        $searchTerm = "%{$query}%";
        
        $data = $db->fetchAll(
            'SELECT * FROM players WHERE 
             (player_identifier LIKE ? OR alias LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
             AND status = "active" ORDER BY first_name, last_name',
            [$searchTerm, $searchTerm, $searchTerm, $searchTerm]
        );

        return array_map([self::class, 'fromArray'], $data);
    }

    private static function fromArray(array $data): self
    {
        $player = new self(
            $data['player_identifier'],
            $data['first_name'],
            $data['last_name'],
            $data['gender'],
            $data['status'],
            $data['handicap'],
            $data['alias'] ?? null,
            $data['player_id'] ?? null
        );

        if (isset($data['created_at'])) {
            $player->createdAt = new \DateTime($data['created_at']);
        }

        if (isset($data['updated_at'])) {
            $player->updatedAt = new \DateTime($data['updated_at']);
        }

        return $player;
    }

    public function toArray(): array
    {
        return [
            'player_id' => $this->playerId,
            'player_identifier' => $this->playerIdentifier,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'alias' => $this->alias,
            'gender' => $this->gender,
            'handicap' => $this->handicap,
            'status' => $this->status,
            'display_name' => $this->getDisplayName(),
            'full_name' => $this->getFullName(),
            'is_active' => $this->isActive(),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'first_play_date' => $this->firstPlayDate?->format('Y-m-d')
        ];
    }
}
