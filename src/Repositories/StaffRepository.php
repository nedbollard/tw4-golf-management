<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\Staff;

class StaffRepository
{
    public function __construct(private Database $db)
    {
    }

    public function findById(int $staffId): ?Staff
    {
        $data = $this->db->fetchOne(
            'SELECT * FROM staff WHERE row_id = ? AND is_active = TRUE',
            [$staffId]
        );

        return $data ? Staff::fromArray($data) : null;
    }

    public function findByUsername(string $username): ?Staff
    {
        $data = $this->db->fetchOne(
            'SELECT * FROM staff WHERE username = ? AND is_active = TRUE',
            [$username]
        );

        return $data ? Staff::fromArray($data) : null;
    }

    public function findAll(): array
    {
        $data = $this->db->fetchAll('SELECT * FROM staff WHERE is_active = TRUE ORDER BY row_id');
        return array_map([Staff::class, 'fromArray'], $data);
    }

    public function findByRole(string $role): array
    {
        $data = $this->db->fetchAll(
            'SELECT * FROM staff WHERE role = ? AND is_active = TRUE ORDER BY username',
            [$role]
        );

        return array_map([Staff::class, 'fromArray'], $data);
    }

    public function save(Staff $staff, string $updatedBy = 'system'): int
    {
        $data = [
            'username' => $staff->getUsername(),
            'password_hash' => $staff->getPasswordHash(),
            'first_name' => $staff->getFirstName(),
            'last_name' => $staff->getLastName(),
            'role' => $staff->getRole(),
            'is_active' => $staff->isActive() ? 1 : 0,
        ];

        if ($staff->getStaffId() === null) {
            $staff->assignStaffId($this->db->insert('staff', $data));
        } else {
            $data['updated_by'] = $updatedBy;
            $this->db->update('staff', $data, ['row_id' => $staff->getStaffId()]);
        }

        return (int) $staff->getStaffId();
    }

    public function updateLastLogin(Staff $staff, ?\DateTimeInterface $lastLogin = null): bool
    {
        if ($staff->getStaffId() === null) {
            return false;
        }

        $timestamp = $lastLogin ?? new \DateTimeImmutable();
        if ($this->db->update(
            'staff',
            ['last_login' => $timestamp->format('Y-m-d H:i:s')],
            ['row_id' => $staff->getStaffId()]
        ) < 1) {
            return false;
        }

        $staff->setLastLogin(\DateTime::createFromInterface($timestamp));
        return true;
    }
}