<?php

namespace Tests\Unit;

use App\Core\Database;
use App\Models\Roster;
use App\Repositories\RosterRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RosterRepositoryTest extends TestCase
{
    public function testSaveAssignsIdentityToNewRosterEntry(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->once())
            ->method('insert')
            ->with('roster', $this->callback(
                static fn(array $data): bool => $data['player_identifier'] === 'JaneS' && $data['status'] === 'active'
            ))
            ->willReturn(23);

        $roster = new Roster('JaneS', 'Jane', 'Smith', 'female');
        $result = (new RosterRepository($database))->save($roster);

        $this->assertSame(23, $result);
        $this->assertSame(23, $roster->getPlayerId());
    }

    public function testDeactivatePersistsAndChangesDomainState(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->once())
            ->method('update')
            ->with('roster', ['status' => 'inactive'], ['row_id' => 5])
            ->willReturn(1);

        $roster = new Roster('JohnD', 'John', 'Doe', 'male', 'active', 12, null, 5);

        $this->assertTrue((new RosterRepository($database))->deactivate($roster));
        $this->assertSame('inactive', $roster->getStatus());
        $this->assertFalse($roster->isActive());
    }

    public function testDeactivateLeavesDomainStateUnchangedWhenPersistenceFails(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->once())
            ->method('update')
            ->with('roster', ['status' => 'inactive'], ['row_id' => 5])
            ->willReturn(0);

        $roster = new Roster('JohnD', 'John', 'Doe', 'male', 'active', 12, null, 5);

        $this->assertFalse((new RosterRepository($database))->deactivate($roster));
        $this->assertSame('active', $roster->getStatus());
        $this->assertTrue($roster->isActive());
    }

    public function testActivateLeavesDomainStateUnchangedWhenPersistenceFails(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->once())
            ->method('update')
            ->with('roster', ['status' => 'active'], ['row_id' => 5])
            ->willReturn(0);

        $roster = new Roster('JohnD', 'John', 'Doe', 'male', 'inactive', 12, null, 5);

        $this->assertFalse((new RosterRepository($database))->activate($roster));
        $this->assertSame('inactive', $roster->getStatus());
        $this->assertFalse($roster->isActive());
    }

    public function testRosterIdentityCannotBeReassigned(): void
    {
        $roster = new Roster('JohnD', 'John', 'Doe', 'male', 'active', 12, null, 5);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Roster identity has already been assigned.');

        $roster->assignPlayerId(6);
    }

    public function testFindByIdHydratesRoster(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT * FROM roster WHERE row_id = ? AND status IN (?, ?)', [8, 'active', 'scored'])
            ->willReturn([
                'row_id' => 8,
                'player_identifier' => 'AlexB',
                'first_name' => 'Alex',
                'last_name' => 'Brown',
                'gender' => 'male',
                'status' => 'scored',
                'handicap' => 7,
                'date_first_played' => '2026-06-10',
            ]);

        $roster = (new RosterRepository($database))->findById(8);

        $this->assertInstanceOf(Roster::class, $roster);
        $this->assertSame('AlexB', $roster->getPlayerIdentifier());
        $this->assertTrue($roster->isActive());
        $this->assertSame('2026-06-10', $roster->getDateFirstPlayed()?->format('Y-m-d'));
    }
}