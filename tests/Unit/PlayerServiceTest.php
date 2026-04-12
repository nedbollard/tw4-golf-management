<?php

namespace Tests\Unit;

use App\Services\PlayerService;
use App\Core\Database;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * PlayerService Unit Tests
 * Tests all player service functionality including database operations
 */
class PlayerServiceTest extends TestCase
{
    private PlayerService $playerService;
    private Database|MockObject $mockDatabase;

    protected function setUp(): void
    {
        $this->mockDatabase = $this->createMock(Database::class);
        $this->playerService = new PlayerService($this->mockDatabase);
    }

    public function testGetAllPlayers(): void
    {
        // Mock database fetchAll call
        $expectedPlayers = [
            [
                'player_id' => 1,
                'member_identifier' => 'JohnD',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'alias' => 'JD',
                'gender' => 'male',
                'handicap' => 12.5,
                'status' => 'A'
            ],
            [
                'player_id' => 2,
                'member_identifier' => 'JaneS',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'alias' => 'JS',
                'gender' => 'female',
                'handicap' => 8.2,
                'status' => 'A'
            ]
        ];

        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo('SELECT row_id as player_id, ident_player as member_identifier, 
                        name_first as first_name, name_last as last_name, 
                        ident_public as alias, gender, handicap, status
                 FROM player WHERE status = "A" ORDER BY name_first, name_last')
            )
            ->willReturn($expectedPlayers);

        $result = $this->playerService->getAllPlayers();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('JohnD', $result[0]['member_identifier']);
        $this->assertEquals('JaneS', $result[1]['member_identifier']);
    }

    public function testGetAllPlayersReturnsEmptyArray(): void
    {
        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo('SELECT row_id as player_id, ident_player as member_identifier, 
                        name_first as first_name, name_last as last_name, 
                        ident_public as alias, gender, handicap, status
                 FROM player WHERE status = "A" ORDER BY name_first, name_last')
            )
            ->willReturn([]);

        $result = $this->playerService->getAllPlayers();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetPlayer(): void
    {
        $expectedPlayer = [
            'player_id' => 1,
            'member_identifier' => 'JohnD',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'alias' => 'JD',
            'gender' => 'male',
            'handicap' => 12.5,
            'status' => 'active'
        ];

        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo('SELECT * FROM players WHERE player_id = ? AND status = "active"'),
                $this->equalTo([1])
            )
            ->willReturn($expectedPlayer);

        $result = $this->playerService->getPlayer(1);

        $this->assertIsArray($result);
        $this->assertEquals('JohnD', $result['member_identifier']);
        $this->assertEquals('John', $result['first_name']);
    }

    public function testGetPlayerNotFound(): void
    {
        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo('SELECT * FROM players WHERE player_id = ? AND status = "active"'),
                $this->equalTo([999])
            )
            ->willReturn(null);

        $result = $this->playerService->getPlayer(999);

        $this->assertNull($result);
    }

    public function testGetPlayerByIdentifier(): void
    {
        $expectedPlayer = [
            'player_id' => 1,
            'member_identifier' => 'JohnD',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'alias' => 'JD',
            'gender' => 'male',
            'handicap' => 12.5,
            'status' => 'active'
        ];

        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo('SELECT * FROM players WHERE member_identifier = ? AND status = "active"'),
                $this->equalTo(['JohnD'])
            )
            ->willReturn($expectedPlayer);

        $result = $this->playerService->getPlayerByIdentifier('JohnD');

        $this->assertIsArray($result);
        $this->assertEquals('JohnD', $result['member_identifier']);
    }

    public function testGetPlayerByAlias(): void
    {
        $expectedPlayer = [
            'player_id' => 1,
            'member_identifier' => 'JohnD',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'alias' => 'JD',
            'gender' => 'male',
            'handicap' => 12.5,
            'status' => 'active'
        ];

        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo('SELECT * FROM players WHERE alias = ? AND status = "active"'),
                $this->equalTo(['JD'])
            )
            ->willReturn($expectedPlayer);

        $result = $this->playerService->getPlayerByAlias('JD');

        $this->assertIsArray($result);
        $this->assertEquals('JD', $result['alias']);
    }

    public function testSearchPlayers(): void
    {
        $expectedPlayers = [
            [
                'player_id' => 1,
                'member_identifier' => 'JohnD',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'alias' => 'JD',
                'gender' => 'male',
                'handicap' => 12.5,
                'status' => 'active'
            ]
        ];

        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo('SELECT * FROM players WHERE 
                 (member_identifier LIKE ? OR alias LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
                 AND status = "active" ORDER BY first_name, last_name'),
                $this->equalTo(['%John%', '%John%', '%John%', '%John%'])
            )
            ->willReturn($expectedPlayers);

        $result = $this->playerService->searchPlayers('John');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('John', $result[0]['first_name']);
    }

    public function testSearchPlayersReturnsEmpty(): void
    {
        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo('SELECT * FROM players WHERE 
                 (member_identifier LIKE ? OR alias LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
                 AND status = "active" ORDER BY first_name, last_name'),
                $this->equalTo(['%Nonexistent%', '%Nonexistent%', '%Nonexistent%', '%Nonexistent%'])
            )
            ->willReturn([]);

        $result = $this->playerService->searchPlayers('Nonexistent');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testCreatePlayer(): void
    {
        $playerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'handicap' => 12.5,
            'alias' => 'JD'
        ];

        $this->mockDatabase
            ->expects($this->once())
            ->method('insert')
            ->with(
                $this->equalTo('players'),
                $this->callback(function($data) use ($playerData) {
                    return isset($data['member_identifier']) &&
                           $data['first_name'] === 'John' &&
                           $data['last_name'] === 'Doe' &&
                           $data['gender'] === 'male' &&
                           $data['handicap'] === 12.5 &&
                           $data['alias'] === 'JD';
                })
            )
            ->willReturn(1);

        $result = $this->playerService->createPlayer($playerData);

        $this->assertEquals(1, $result);
    }

    public function testCreatePlayerWithMissingData(): void
    {
        $invalidData = [
            'first_name' => '', // Missing last_name and gender
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Last name is required, Gender must be male or female');

        $this->playerService->createPlayer($invalidData);
    }

    public function testUpdatePlayer(): void
    {
        $playerId = 1;
        $updateData = [
            'first_name' => 'John Updated',
            'last_name' => 'Doe Updated',
            'handicap' => 15.0
        ];

        // Mock getPlayer call for checking current data
        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo('SELECT * FROM players WHERE player_id = ? AND status = "active"'),
                $this->equalTo([$playerId])
            )
            ->willReturn([
                'player_id' => $playerId,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'member_identifier' => 'JohnD'
            ]);

        // Mock update call
        $this->mockDatabase
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo('players'),
                $this->callback(function($data) use ($updateData) {
                    return isset($data['member_identifier']) &&
                           $data['first_name'] === 'John Updated' &&
                           $data['last_name'] === 'Doe Updated' &&
                           $data['handicap'] === 15.0;
                }),
                $this->equalTo(['player_id' => $playerId])
            )
            ->willReturn(1);

        $result = $this->playerService->updatePlayer($playerId, $updateData);

        $this->assertTrue($result);
    }

    public function testUpdatePlayerNotFound(): void
    {
        $playerId = 999;
        $updateData = ['first_name' => 'Updated'];

        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->equalTo('SELECT * FROM players WHERE player_id = ? AND status = "active"'),
                $this->equalTo([$playerId])
            )
            ->willReturn(null);

        $result = $this->playerService->updatePlayer($playerId, $updateData);

        $this->assertFalse($result);
    }

    public function testDeletePlayer(): void
    {
        $playerId = 1;

        $this->mockDatabase
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo('players'),
                $this->equalTo(['status' => 'inactive']),
                $this->equalTo(['player_id' => $playerId])
            )
            ->willReturn(1);

        $result = $this->playerService->deletePlayer($playerId);

        $this->assertTrue($result);
    }

    public function testActivatePlayer(): void
    {
        $playerId = 1;

        $this->mockDatabase
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->equalTo('players'),
                $this->equalTo(['status' => 'active']),
                $this->equalTo(['player_id' => $playerId])
            )
            ->willReturn(1);

        $result = $this->playerService->activatePlayer($playerId);

        $this->assertTrue($result);
    }

    public function testGetDisplayNameWithAlias(): void
    {
        $player = [
            'member_identifier' => 'JohnD',
            'alias' => 'JD'
        ];

        $result = $this->playerService->getDisplayName($player);

        $this->assertEquals('JD', $result);
    }

    public function testGetDisplayNameWithoutAlias(): void
    {
        $player = [
            'member_identifier' => 'JohnD',
            'alias' => ''
        ];

        $result = $this->playerService->getDisplayName($player);

        $this->assertEquals('JohnD', $result);
    }

    public function testGetDisplayNameWithNullAlias(): void
    {
        $player = [
            'member_identifier' => 'JohnD',
            'alias' => null
        ];

        $result = $this->playerService->getDisplayName($player);

        $this->assertEquals('JohnD', $result);
    }

    public function testGetActivePlayers(): void
    {
        $expectedPlayers = [
            [
                'player_id' => 1,
                'member_identifier' => 'JohnD',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'status' => 'A'
            ]
        ];

        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo('SELECT row_id as player_id, ident_player as member_identifier, 
                        name_first as first_name, name_last as last_name, 
                        ident_public as alias, gender, handicap, status
                 FROM player WHERE status = "A" ORDER BY name_first, name_last')
            )
            ->willReturn($expectedPlayers);

        $result = $this->playerService->getActivePlayers();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('JohnD', $result[0]['member_identifier']);
    }

    public function testGetAllPlayersIncludingInactive(): void
    {
        $expectedPlayers = [
            [
                'player_id' => 1,
                'member_identifier' => 'JohnD',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'status' => 'active'
            ],
            [
                'player_id' => 2,
                'member_identifier' => 'JaneS',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'status' => 'inactive'
            ]
        ];

        $this->mockDatabase
            ->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->equalTo('SELECT * FROM players ORDER BY first_name, last_name')
            )
            ->willReturn($expectedPlayers);

        $result = $this->playerService->getAllPlayersIncludingInactive();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('JohnD', $result[0]['member_identifier']);
        $this->assertEquals('JaneS', $result[1]['member_identifier']);
    }
}
