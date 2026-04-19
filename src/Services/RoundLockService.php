<?php

namespace App\Services;

use App\Core\Database;

class RoundLockService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function acquireLock(int $roundId, int $staffId, int $ttlMinutes = 120): bool
    {
        $sql = 'UPDATE TW4_live.round
                SET locked_by_staff_id = ?,
                    lock_acquired_at = NOW(),
                    lock_expires_at = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                    lock_released_at = NULL,
                    lock_release_reason = NULL
                WHERE row_id = ?
                  AND (
                        locked_by_staff_id IS NULL
                        OR lock_expires_at IS NULL
                        OR lock_expires_at < NOW()
                        OR locked_by_staff_id = ?
                  )';

        $stmt = $this->db->query($sql, [$staffId, $ttlMinutes, $roundId, $staffId]);
        return $stmt->rowCount() > 0;
    }

    public function assertLockHeld(int $roundId, int $staffId): bool
    {
        $row = $this->db->fetchOne(
            'SELECT row_id
             FROM TW4_live.round
             WHERE row_id = ?
               AND locked_by_staff_id = ?
               AND (lock_expires_at IS NULL OR lock_expires_at >= NOW())',
            [$roundId, $staffId]
        );

        return $row !== null;
    }

    public function releaseLock(int $roundId, int $staffId, string $reason = 'logout'): int
    {
        $stmt = $this->db->query(
            'UPDATE TW4_live.round
             SET locked_by_staff_id = NULL,
                 lock_released_at = NOW(),
                 lock_release_reason = ?,
                 lock_expires_at = NULL
             WHERE row_id = ?
               AND locked_by_staff_id = ?',
            [$reason, $roundId, $staffId]
        );

        return $stmt->rowCount();
    }

    public function releaseAnyLocksByStaff(int $staffId, string $reason = 'logout'): int
    {
        $stmt = $this->db->query(
            'UPDATE TW4_live.round
             SET locked_by_staff_id = NULL,
                 lock_released_at = NOW(),
                 lock_release_reason = ?,
                 lock_expires_at = NULL
             WHERE locked_by_staff_id = ?',
            [$reason, $staffId]
        );

        return $stmt->rowCount();
    }

    public function releaseExpiredLocks(): int
    {
        $stmt = $this->db->query(
            'UPDATE TW4_live.round
             SET locked_by_staff_id = NULL,
                 lock_released_at = NOW(),
                 lock_release_reason = ?,
                 lock_expires_at = NULL
             WHERE locked_by_staff_id IS NOT NULL
               AND lock_expires_at IS NOT NULL
               AND lock_expires_at < NOW()',
            ['session_expired']
        );

        return $stmt->rowCount();
    }

    public function forceReleaseLock(int $roundId, int $adminStaffId, string $reason = 'admin_forced'): int
    {
        $stmt = $this->db->query(
            'UPDATE TW4_live.round
             SET locked_by_staff_id = NULL,
                 lock_released_at = NOW(),
                 lock_release_reason = ?,
                 lock_expires_at = NULL
             WHERE row_id = ?',
            [$reason, $roundId]
        );

        return $stmt->rowCount();
    }

    public function getLockStatus(int $roundId, int $currentStaffId): ?array
    {
        $this->releaseExpiredLocks();

        $row = $this->db->fetchOne(
            'SELECT r.locked_by_staff_id, r.lock_acquired_at, r.lock_expires_at,
                    s.first_name, s.last_name, s.username
             FROM TW4_live.round r
             LEFT JOIN TW4_base.staff s ON s.row_id = r.locked_by_staff_id
             WHERE r.row_id = ?',
            [$roundId]
        );

        if (!$row || empty($row['locked_by_staff_id'])) {
            return null;
        }

        $holder = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
        if ($holder === '') {
            $holder = $row['username'] ?? 'unknown';
        }

        return [
            'holder_id' => (int) $row['locked_by_staff_id'],
            'holder_name' => $holder,
            'acquired_at' => $row['lock_acquired_at'] ?? null,
            'expires_at' => $row['lock_expires_at'] ?? null,
            'blocked' => (int) $row['locked_by_staff_id'] !== $currentStaffId,
        ];
    }
}
