<?php

namespace App\Models;

use App\Core\Database;

/**
 * Roster Model - Represents a player in the golf roster
 */
class Roster
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
        ?int $rosterId = null
    ) {
        $this->playerId = $rosterId;
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

    private ?\DateTime $dateFirstPlayed;

    public function getDateFirstPlayed(): ?\DateTime
    {
        return $this->dateFirstPlayed;
    }

    public function setDateFirstPlayed(?\DateTime $dateFirstPlayed): void
    {
        $this->dateFirstPlayed = $dateFirstPlayed;
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
        return in_array($this->status, ['active', 'scored'], true);
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
            // Insert new roster entry
            $this->playerId = $db->insert('roster', $data);
            return $this->playerId;
        } else {
            // Update existing player
            $db->update('roster', $data, ['row_id' => $this->playerId]);
            return $this->playerId;
        }
    }

    public function delete(Database $db): bool
    {
        if ($this->playerId === null) {
            return false;
        }

        $this->status = 'inactive';
        return $db->update('roster', ['status' => 'inactive'], ['row_id' => $this->playerId]) > 0;
    }

    public function activate(Database $db): bool
    {
        if ($this->playerId === null) {
            return false;
        }

        $this->status = 'active';
        return $db->update('roster', ['status' => 'active'], ['row_id' => $this->playerId]) > 0;
    }

    // Static methods for data access
    public static function findById(Database $db, int $rosterId): ?self
    {
        $data = $db->fetchOne(
            'SELECT * FROM roster WHERE row_id = ? AND status IN (?, ?)',
            [$rosterId, 'active', 'scored']
        );

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function findByIdentifier(Database $db, string $identifier): ?self
    {
        $data = $db->fetchOne(
            'SELECT * FROM roster WHERE player_identifier = ? AND status IN (?, ?)',
            [$identifier, 'active', 'scored']
        );

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function findByAlias(Database $db, string $alias): ?self
    {
        $data = $db->fetchOne(
            'SELECT * FROM roster WHERE alias = ? AND status IN (?, ?)',
            [$alias, 'active', 'scored']
        );

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function findAll(Database $db): array
    {
        $data = $db->fetchAll(
            'SELECT * FROM roster WHERE status IN (?, ?) ORDER BY first_name, last_name',
            ['active', 'scored']
        );

        return array_map([self::class, 'fromArray'], $data);
    }

    public static function search(Database $db, string $query): array
    {
        $searchTerm = "%{$query}%";
        
        $data = $db->fetchAll(
            'SELECT * FROM roster WHERE 
             (player_identifier LIKE ? OR alias LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
             AND status IN (?, ?) ORDER BY first_name, last_name',
            [$searchTerm, $searchTerm, $searchTerm, $searchTerm, 'active', 'scored']
        );

        return array_map([self::class, 'fromArray'], $data);
    }

    private static function fromArray(array $data): self
    {
        $roster = new self(
            $data['player_identifier'],
            $data['first_name'],
            $data['last_name'],
            $data['gender'],
            $data['status'],
            $data['handicap'],
            $data['alias'] ?? null,
            $data['row_id'] ?? null
        );

        if (isset($data['created_at'])) {
            $roster->createdAt = new \DateTime($data['created_at']);
        }

        if (isset($data['updated_at'])) {
            $roster->updatedAt = new \DateTime($data['updated_at']);
        }

        return $roster;
    }

    public function toArray(): array
    {
        return [
            'row_id' => $this->playerId,
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
            'date_first_played' => $this->dateFirstPlayed?->format('Y-m-d')
        ];
    }
}
