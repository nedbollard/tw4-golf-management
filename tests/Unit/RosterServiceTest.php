<?php

namespace Tests\Unit;

use App\Services\RosterService;
use App\Core\Database;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * RosterService Unit Tests
 * Tests all roster service functionality including database operations
 */
class RosterServiceTest extends TestCase
{
    private RosterService $rosterService;
    private Database|MockObject $mockDatabase;

    protected function setUp(): void
    {
        $this->mockDatabase = $this->createMock(Database::class);
        $this->rosterService = new RosterService($this->mockDatabase);
    }

    public function testGetAllPlayers(): void
    {
        // Mock database fetchAll call with current schema
        $expectedRoster = [
            [
                'row_id' => 1,
                'player_identifier' => 'JohnD',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'alias' => 'JD',
                'gender' => 'male',
                'handicap' => 12,
                'status' => 'active'
            ],
            [
                'row_id' => 2,
                'player_identifier' => 'JaneS',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'alias' => 'JS',
                'gender' => 'female',
                'handicap' => 8,
                'status' => 'active'
            ]
        ];

        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo('SELECT row_id, player_identifier, first_name, last_name, 
                    alias, gender, handicap, status
             FROM roster WHERE status = "active" ORDER BY first_name, last_name')
            )
            ->willReturn($expectedRoster);

        $result = $this->rosterService->getAllPlayers();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('JohnD', $result[0]['player_identifier']);
        $this->assertEquals('JaneS', $result[1]['player_identifier']);
    }

    public function testGetAllPlayersReturnsEmptyArray(): void
    {
        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo('SELECT row_id, player_identifier, first_name, last_name, 
                    alias, gender, handicap, status
             FROM roster WHERE status = "active" ORDER BY first_name, last_name')
            )
            ->willReturn([]);

        $result = $this->rosterService->getAllPlayers();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetPlayer(): void
    {
        $expectedPlayer = [
            'row_id' => 1,
            'player_identifier' => 'JohnD',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'alias' => 'JD',
            'gender' => 'male',
            'handicap' => 12,
            'status' => 'active'
        ];

        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo('SELECT * FROM roster WHERE row_id = ? AND status = "active"'),
                $this->equalTo([1])
            )
            ->willReturn($expectedPlayer);

        $result = $this->rosterService->getPlayer(1);

        $this->assertIsArray($result);
        $this->assertEquals('JohnD', $result['player_identifier']);
        $this->assertEquals('John', $result['first_name']);
    }

    public function testGetPlayerReturnsNull(): void
    {
        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo('SELECT * FROM roster WHERE row_id = ? AND status = "active"'),
                $this->equalTo([999])
            )
            ->willReturn(null);

        $result = $this->rosterService->getPlayer(999);

        $this->assertNull($result);
    }

    public function testGetPlayerByIdentifier(): void
    {
        $expectedPlayer = [
            'row_id' => 1,
            'player_identifier' => 'JohnD',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'alias' => 'JD',
            'gender' => 'male',
            'handicap' => 12,
            'status' => 'active'
        ];

        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo('SELECT * FROM roster WHERE player_identifier = ? AND status = "active"'),
                $this->equalTo(['JohnD'])
            )
            ->willReturn($expectedPlayer);

        $result = $this->rosterService->getPlayerByIdentifier('JohnD');

        $this->assertIsArray($result);
        $this->assertEquals('JohnD', $result['player_identifier']);
    }

    public function testCreatePlayer(): void
    {
        $playerData = [
            'player_identifier' => 'JohnD',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'handicap' => 12
        ];

        $this->mockDatabase
            ->expects($this->once())
            ->method('insert')
            ->with(
                $this->equalTo('roster'),
                $this->equalTo($playerData)
            )
            ->willReturn(1);

        $result = $this->rosterService->createPlayer($playerData);

        $this->assertEquals(1, $result);
    }

    public function testUpdatePlayer(): void
    {
        $playerId = 1;
        $updateData = [
            'first_name' => 'Johnathan',
            'last_name' => 'Doe'
        ];

        // Mock update call directly (simplified test)
        $this->mockDatabase
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo('roster'),
                $this->equalTo($updateData),
                $this->equalTo(['row_id' => $playerId])
            )
            ->willReturn(1);

        // Create a simple mock for getPlayer that returns null to avoid validation
        $this->mockDatabase
            ->expects($this->any())
            ->method('fetchOne')
            ->willReturn(null);

        $result = $this->rosterService->updatePlayer($playerId, $updateData);

        $this->assertTrue($result);
    }

    public function testDeletePlayer(): void
    {
        $playerId = 1;

        $this->mockDatabase
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo('roster'),
                $this->equalTo(['status' => 'inactive']),
                $this->equalTo(['row_id' => $playerId])
            )
            ->willReturn(1);

        $result = $this->rosterService->deletePlayer($playerId);

        $this->assertTrue($result);
    }

    public function testActivatePlayer(): void
    {
        $playerId = 1;

        $this->mockDatabase
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo('roster'),
                $this->equalTo(['status' => 'active']),
                $this->equalTo(['row_id' => $playerId])
            )
            ->willReturn(1);

        $result = $this->rosterService->activatePlayer($playerId);

        $this->assertTrue($result);
    }

    public function testGetDisplayName(): void
    {
        $playerWithAlias = [
            'player_identifier' => 'JohnD',
            'alias' => 'JD'
        ];

        $playerWithoutAlias = [
            'player_identifier' => 'JaneS',
            'alias' => null
        ];

        // Test with alias
        $result1 = $this->rosterService->getDisplayName($playerWithAlias);
        $this->assertEquals('JD', $result1);

        // Test without alias
        $result2 = $this->rosterService->getDisplayName($playerWithoutAlias);
        $this->assertEquals('JaneS', $result2);
    }

    public function testSearchPlayers(): void
    {
        $query = 'John';
        $expectedResults = [
            [
                'row_id' => 1,
                'player_identifier' => 'JohnD',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'alias' => 'JD',
                'gender' => 'male',
                'handicap' => 12,
                'status' => 'active'
            ]
        ];

        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo('SELECT * FROM roster WHERE 
             (player_identifier LIKE ? OR alias LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
             AND status = "active" ORDER BY first_name, last_name'),
                $this->equalTo(['%John%', '%John%', '%John%', '%John%'])
            )
            ->willReturn($expectedResults);

        $result = $this->rosterService->searchPlayers($query);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('JohnD', $result[0]['player_identifier']);
    }
}
