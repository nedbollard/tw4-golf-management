<?php

namespace App\Models;

use App\Core\Database;

/**
 * Staff Model - Represents staff members (admins and scorers)
 */
class Staff
{
    private ?int $rowId = null;
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
        ?int $rowId = null
    ) {
        $this->rowId = $rowId;
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->role = $role;
        $this->isActive = $isActive;
    }

    // Getters
    public function getRowId(): ?int
    {
        return $this->rowId;
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
    public function setStaffLoginId(string $staffLoginId): void
    {
        $this->staffLoginId = $staffLoginId;
    }

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

    public function updateLastLogin(Database $db): bool
    {
        if ($this->rowId === null) {
            return false;
        }

        $this->lastLogin = new \DateTime();
        return $db->update(
            'staff',
            ['last_login' => $this->lastLogin->format('Y-m-d H:i:s')],
            ['row_id' => $this->rowId]
        ) > 0;
    }

    // Database methods
    public function save(Database $db): int
    {
        $data = [
            'username' => $this->username,
            'password_hash' => $this->passwordHash,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'role' => $this->role,
            'is_active' => $this->isActive ? 1 : 0
        ];

        if ($this->rowId === null) {
            // Insert new staff
            $this->rowId = $db->insert('staff', $data);
        } else {
            // Update existing staff
            $data['updated_by'] = $_SESSION['username'] ?? 'system';
            
            $db->update('staff', $data, ['row_id' => $this->rowId]);
        }
        
        return $this->rowId;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    // Static methods for data access
    public static function findById(Database $db, int $rowId): ?self
    {
        $data = $db->fetchOne(
            'SELECT * FROM staff WHERE row_id = ? AND is_active = TRUE',
            [$rowId]
        );

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function findByUsername(Database $db, string $username): ?self
    {
        $data = $db->fetchOne(
            'SELECT * FROM staff WHERE username = ? AND is_active = TRUE',
            [$username]
        );

        if (!$data) {
            return null;
        }

        return self::fromArray($data);
    }

    public static function findAll(Database $db): array
    {
        $data = $db->fetchAll(
            'SELECT * FROM staff WHERE is_active = TRUE ORDER BY row_id',
        );

        return array_map([self::class, 'fromArray'], $data);
    }

    public static function findByRole(Database $db, string $role): array
    {
        $data = $db->fetchAll(
            'SELECT * FROM staff WHERE role = ? AND is_active = TRUE ORDER BY username',
            [$role]
        );

        return array_map([self::class, 'fromArray'], $data);
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

        return $staff;
    }

    public function toArray(): array
    {
        return [
            'row_id' => $this->rowId,
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
