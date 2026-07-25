<?php

namespace App\Models;

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
    private ?\DateTime $dateFirstPlayed = null;

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

    /**
     * @throws \LogicException If a different identity has already been assigned.
     */
    public function assignPlayerId(int $playerId): void
    {
        if ($this->playerId !== null && $this->playerId !== $playerId) {
            throw new \LogicException('Roster identity has already been assigned.');
        }

        $this->playerId = $playerId;
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
    public function setPlayerIdentifier(string $playerIdentifier): void
    {
        $this->playerIdentifier = $playerIdentifier;
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
        return !empty($this->alias) ? $this->alias : $this->playerIdentifier;
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

    public function deactivate(): void
    {
        $this->status = 'inactive';
    }

    public function activate(): void
    {
        $this->status = 'active';
    }

    public static function fromArray(array $data): self
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

        if (!empty($data['date_first_played'])) {
            $roster->dateFirstPlayed = new \DateTime($data['date_first_played']);
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
