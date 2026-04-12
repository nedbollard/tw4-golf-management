<?php

namespace Tests\Unit;

use App\Controllers\PlayerController;
use App\Core\Application;
use App\Services\PlayerService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * PlayerController Unit Tests
 * Tests all player controller functionality including routing and rendering
 */
class PlayerControllerTest extends TestCase
{
    private PlayerController $controller;
    private Application|MockObject $mockApplication;
    private PlayerService|MockObject $mockPlayerService;

    protected function setUp(): void
    {
        $this->mockApplication = $this->createMock(Application::class);
        $this->mockPlayerService = $this->createMock(PlayerService::class);
        
        $this->controller = new PlayerController($this->mockApplication, $this->mockPlayerService);
    }

    public function testGetAllPlayersCallsCorrectServiceMethod(): void
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
                'status' => 'A'
            ]
        ];

        $this->mockPlayerService
            ->expects($this->once())
            ->method('getAllPlayers')
            ->willReturn($expectedPlayers);

        // This test verifies the service method is called
        // The actual rendering would be tested in integration tests
        $this->mockApplication
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('players/index'),
                $this->callback(function($data) use ($expectedPlayers) {
                    return isset($data['title']) && 
                           isset($data['players']) &&
                           $data['players'] === $expectedPlayers;
                })
            );

        // Mock the requireRole method to avoid authentication
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('requireRole');
        $method->setAccessible(true);
        $method->invoke($this->controller, 'scorer');

        $this->controller->index();
    }

    public function testGetAllPlayersHandlesDatabaseError(): void
    {
        $errorMessage = "Table 'player' doesn't exist";
        
        $this->mockPlayerService
            ->expects($this->once())
            ->method('getAllPlayers')
            ->willThrowException(new \Exception($errorMessage));

        // Mock the requireRole method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('requireRole');
        $method->setAccessible(true);
        $method->invoke($this->controller, 'scorer');

        // Capture output to verify error handling
        ob_start();
        $this->controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('Error:', $output);
        $this->assertStringContainsString($errorMessage, $output);
        $this->assertStringContainsString('Back to Dashboard', $output);
    }

    public function testGetPlayer(): void
    {
        $playerId = 1;
        $expectedPlayer = [
            'player_id' => 1,
            'member_identifier' => 'JohnD',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'alias' => 'JD',
            'gender' => 'male',
            'handicap' => 12.5,
            'status' => 'A'
        ];

        $this->mockPlayerService
            ->expects($this->once())
            ->method('getPlayer')
            ->with($this->equalTo($playerId))
            ->willReturn($expectedPlayer);

        $this->mockPlayerService
            ->expects($this->once())
            ->method('getDisplayName')
            ->with($this->equalTo($expectedPlayer))
            ->willReturn('JD');

        $this->mockApplication
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('players/show'),
                $this->callback(function($data) use ($expectedPlayer) {
                    return isset($data['title']) &&
                           isset($data['player']) &&
                           $data['player'] === $expectedPlayer;
                })
            );

        // Mock requireAuth method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('requireAuth');
        $method->setAccessible(true);
        $method->invoke($this->controller);

        $this->controller->show($playerId);
    }

    public function testGetPlayerNotFound(): void
    {
        $playerId = 999;

        $this->mockPlayerService
            ->expects($this->once())
            ->method('getPlayer')
            ->with($this->equalTo($playerId))
            ->willReturn(null);

        $this->mockApplication
            ->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo('/players'));

        // Mock requireAuth method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('requireAuth');
        $method->setAccessible(true);
        $method->invoke($this->controller);

        $this->controller->show($playerId);
    }

    public function testSearchPlayers(): void
    {
        $query = 'John';
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

        $this->mockPlayerService
            ->expects($this->once())
            ->method('searchPlayers')
            ->with($this->equalTo($query))
            ->willReturn($expectedPlayers);

        $this->mockApplication
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('players/search'),
                $this->callback(function($data) use ($expectedPlayers, $query) {
                    return isset($data['title']) &&
                           isset($data['players']) &&
                           isset($data['query']) &&
                           $data['players'] === $expectedPlayers &&
                           $data['query'] === $query;
                })
            );

        // Mock requireAuth method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('requireAuth');
        $method->setAccessible(true);
        $method->invoke($this->controller);

        // Mock $_GET
        $_GET['q'] = $query;
        $this->controller->search();
        unset($_GET['q']);
    }

    public function testSearchPlayersWithEmptyQuery(): void
    {
        $this->mockPlayerService
            ->expects($this->never())
            ->method('searchPlayers');

        $this->mockApplication
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('players/search'),
                $this->callback(function($data) {
                    return isset($data['title']) &&
                           isset($data['players']) &&
                           isset($data['query']) &&
                           empty($data['players']) &&
                           $data['query'] === '';
                })
            );

        // Mock requireAuth method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('requireAuth');
        $method->setAccessible(true);
        $method->invoke($this->controller);

        // Don't set $_GET['q'] to test empty query
        $this->controller->search();
    }

    public function testCreatePlayer(): void
    {
        $this->mockApplication
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('players/create'),
                $this->callback(function($data) {
                    return isset($data['title']) &&
                           isset($data['errors']) &&
                           isset($data['old']);
                })
            );

        // Mock requireAuth method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('requireAuth');
        $method->setAccessible(true);
        $method->invoke($this->controller);

        $this->controller->create();
    }

    public function testStorePlayerWithValidData(): void
    {
        $validData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'handicap' => 12.5,
            'alias' => 'JD'
        ];

        $this->mockPlayerService
            ->expects($this->once())
            ->method('createPlayer')
            ->with($this->equalTo($validData))
            ->willReturn(1);

        $this->mockApplication
            ->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo('/players/1'));

        // Mock requireAuth and getPostData methods
        $reflection = new \ReflectionClass($this->controller);
        
        $requireAuthMethod = $reflection->getMethod('requireAuth');
        $requireAuthMethod->setAccessible(true);
        $requireAuthMethod->invoke($this->controller);

        $getPostDataMethod = $reflection->getMethod('getPostData');
        $getPostDataMethod->setAccessible(true);
        $getPostDataMethod->willReturn($validData);

        $this->controller->store();
    }

    public function testStorePlayerWithInvalidData(): void
    {
        $invalidData = [
            'first_name' => '', // Missing required fields
        ];

        $this->mockPlayerService
            ->expects($this->never())
            ->method('createPlayer');

        $this->mockApplication
            ->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo('/players/create'));

        // Mock methods
        $reflection = new \ReflectionClass($this->controller);
        
        $requireAuthMethod = $reflection->getMethod('requireAuth');
        $requireAuthMethod->setAccessible(true);
        $requireAuthMethod->invoke($this->controller);

        $getPostDataMethod = $reflection->getMethod('getPostData');
        $getPostDataMethod->setAccessible(true);
        $getPostDataMethod->willReturn($invalidData);

        $this->controller->store();
    }

    public function testDeletePlayer(): void
    {
        $playerId = 1;
        $expectedPlayer = [
            'player_id' => 1,
            'member_identifier' => 'JohnD',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'alias' => 'JD',
            'gender' => 'male',
            'handicap' => 12.5,
            'status' => 'A'
        ];

        $this->mockPlayerService
            ->expects($this->once())
            ->method('getPlayer')
            ->with($this->equalTo($playerId))
            ->willReturn($expectedPlayer);

        $this->mockPlayerService
            ->expects($this->once())
            ->method('getDisplayName')
            ->with($this->equalTo($expectedPlayer))
            ->willReturn('JD');

        $this->mockApplication
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('players/delete'),
                $this->callback(function($data) use ($expectedPlayer) {
                    return isset($data['title']) &&
                           isset($data['player']) &&
                           $data['player'] === $expectedPlayer;
                })
            );

        // Mock requireAuth method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('requireAuth');
        $method->setAccessible(true);
        $method->invoke($this->controller);

        $this->controller->delete($playerId);
    }

    public function testDestroyPlayer(): void
    {
        $playerId = 1;

        $this->mockPlayerService
            ->expects($this->once())
            ->method('deletePlayer')
            ->with($this->equalTo($playerId))
            ->willReturn(true);

        $this->mockApplication
            ->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo('/players'));

        // Mock requireAuth method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('requireAuth');
        $method->setAccessible(true);
        $method->invoke($this->controller);

        $this->controller->destroy($playerId);
    }

    public function testActivatePlayer(): void
    {
        $playerId = 1;

        $this->mockPlayerService
            ->expects($this->once())
            ->method('activatePlayer')
            ->with($this->equalTo($playerId))
            ->willReturn(true);

        $this->mockApplication
            ->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo('/players'));

        // Mock requireAuth method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('requireAuth');
        $method->setAccessible(true);
        $method->invoke($this->controller);

        $this->controller->activate($playerId);
    }
}
