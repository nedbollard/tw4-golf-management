<?php

namespace App\Models;

/**
 * Staff Model - Represents staff members (admins and scorers)
 */
class Staff
{
    private ?int $staffId = null;
    private string $username;
    private string $passwordHash;
    private string $firstName;
    private string $lastName;
    private string $role;
    private bool $isActive;
    private ?\DateTime $createdAt = null;
    private ?\DateTime $updatedAt = null;
    private ?string $updatedBy = null;
    private ?\DateTime $lastLogin = null;

    public function __construct(
        string $username,
        string $passwordHash,
        string $firstName,
        string $lastName,
        string $role,
        bool $isActive = true,
        ?int $staffId = null
    ) {
        $this->staffId = $staffId;
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->role = $role;
        $this->isActive = $isActive;
    }

    // Getters
    public function getStaffId(): ?int
    {
        return $this->staffId;
    }

    /**
     * @throws \LogicException If a different identity has already been assigned.
     */
    public function assignStaffId(int $staffId): void
    {
        if ($this->staffId !== null && $this->staffId !== $staffId) {
            throw new \LogicException('Staff identity has already been assigned.');
        }

        $this->staffId = $staffId;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    // Setters
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function setLastLogin(?\DateTime $lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }

    // Business logic methods
    public function getFullName(): string
    {
        // If first and last names are not set, return username as display name
        if (empty(trim($this->firstName)) && empty(trim($this->lastName))) {
            return $this->username;
        }
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isScorer(): bool
    {
        return $this->role === 'scorer';
    }

    public function canManagePlayers(): bool
    {
        return $this->isActive && ($this->isAdmin() || $this->isScorer());
    }

    public function canManageSystem(): bool
    {
        return $this->isActive && $this->isAdmin();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public static function fromArray(array $data): self
    {
        $staff = new self(
            $data['username'],
            $data['password_hash'],
            $data['first_name'] ?? '',
            $data['last_name'] ?? '',
            $data['role'],
            (bool)$data['is_active'],
            $data['row_id'] ?? null
        );

        if (isset($data['created_at'])) {
            $staff->createdAt = new \DateTime($data['created_at']);
        }

        if (isset($data['updated_ts'])) {
            $staff->updatedAt = new \DateTime($data['updated_ts']);
        }

        if (isset($data['updated_by'])) {
            $staff->updatedBy = $data['updated_by'];
        }

        if (!empty($data['last_login'])) {
            $staff->lastLogin = new \DateTime($data['last_login']);
        }

        return $staff;
    }

    public function toArray(): array
    {
        return [
            'row_id' => $this->staffId,
            'username' => $this->username,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'role' => $this->role,
            'is_active' => $this->isActive ? 1 : 0,
            'full_name' => $this->getFullName(),
            'is_admin' => $this->isAdmin(),
            'is_scorer' => $this->isScorer(),
            'can_manage_players' => $this->canManagePlayers(),
            'can_manage_system' => $this->canManageSystem(),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_ts' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'updated_by' => $this->updatedBy
        ];
    }
}
