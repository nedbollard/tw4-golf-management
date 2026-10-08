<?php

namespace Tests\Unit;

use App\Core\Database;
use App\Models\Staff;
use App\Repositories\StaffRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class StaffRepositoryTest extends TestCase
{
    public function testFindByIdIncludesInactiveStaff(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT * FROM staff WHERE row_id = ?', [8])
            ->willReturn([
                'row_id' => 8,
                'username' => 'inactive',
                'password_hash' => 'hash',
                'first_name' => 'Inactive',
                'last_name' => 'User',
                'role' => 'scorer',
                'is_active' => 0,
            ]);

        $staff = (new StaffRepository($database))->findById(8);

        $this->assertInstanceOf(Staff::class, $staff);
        $this->assertFalse($staff->isActive());
    }

    public function testFindAllIncludesInactiveStaff(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->once())
            ->method('fetchAll')
            ->with('SELECT * FROM staff ORDER BY row_id')
            ->willReturn([
                [
                    'row_id' => 8,
                    'username' => 'inactive',
                    'password_hash' => 'hash',
                    'first_name' => 'Inactive',
                    'last_name' => 'User',
                    'role' => 'scorer',
                    'is_active' => 0,
                ],
            ]);

        $staff = (new StaffRepository($database))->findAll();

        $this->assertCount(1, $staff);
        $this->assertFalse($staff[0]->isActive());
    }

    public function testSaveAssignsIdentityToNewStaff(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->once())
            ->method('insert')
            ->with('staff', $this->callback(
                static fn(array $data): bool => $data['username'] === 'newuser' && $data['is_active'] === 1
            ))
            ->willReturn(17);

        $staff = new Staff('newuser', 'hash', 'New', 'User', 'scorer');
        $result = (new StaffRepository($database))->save($staff);

        $this->assertSame(17, $result);
        $this->assertSame(17, $staff->getStaffId());
    }

    public function testSavePersistsDeactivatedStateAndActor(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->once())
            ->method('update')
            ->with(
                'staff',
                $this->callback(static fn(array $data): bool => $data['is_active'] === 0 && $data['updated_by'] === 'admin'),
                ['row_id' => 9]
            )
            ->willReturn(1);

        $staff = new Staff('existing', 'hash', 'Existing', 'User', 'admin', true, 9);
        $staff->deactivate();

        $this->assertSame(9, (new StaffRepository($database))->save($staff, 'admin'));
        $this->assertFalse($staff->isActive());
    }

    public function testUpdateLastLoginUpdatesModelAndDatabase(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->once())
            ->method('update')
            ->with('staff', ['last_login' => '2026-07-25 12:30:00'], ['row_id' => 4])
            ->willReturn(1);

        $staff = new Staff('login', 'hash', 'Log', 'In', 'admin', true, 4);
        $when = new \DateTimeImmutable('2026-07-25 12:30:00');

        $this->assertTrue((new StaffRepository($database))->updateLastLogin($staff, $when));
        $this->assertSame('2026-07-25 12:30:00', $staff->getLastLogin()?->format('Y-m-d H:i:s'));
    }

    public function testUpdateLastLoginLeavesModelUnchangedWhenPersistenceFails(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->once())->method('update')->willReturn(0);

        $staff = new Staff('login', 'hash', 'Log', 'In', 'admin', true, 4);

        $this->assertFalse((new StaffRepository($database))->updateLastLogin(
            $staff,
            new \DateTimeImmutable('2026-07-25 12:30:00')
        ));
        $this->assertNull($staff->getLastLogin());
    }
}