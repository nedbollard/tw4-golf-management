<?php

namespace Tests\Unit;

use App\Controllers\RosterController;
use App\Core\Application;
use App\Services\RosterService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * RosterController Unit Tests
 * Tests all roster controller functionality including routing and rendering
 */
class RosterControllerTest extends TestCase
{
    private RosterController $controller;
    private Application|MockObject $mockApplication;
    private RosterService|MockObject $mockRosterService;

    protected function setUp(): void
    {
        $this->mockApplication = $this->createMock(Application::class);
        $this->mockRosterService = $this->createMock(RosterService::class);
        
        $this->controller = new RosterController($this->mockApplication, $this->mockRosterService);
    }

    public function testIndexCallsCorrectServiceMethod(): void
    {
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
            ]
        ];

        $this->mockRosterService
            ->expects($this->once())
            ->method('getAllPlayers')
            ->willReturn($expectedRoster);

        // Mock the render method to avoid actual template rendering
        $this->mockApplication
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('roster/index'),
                $this->callback(function ($data) {
                    return isset($data['title']) && isset($data['roster']);
                })
            );

        $this->controller->index();
    }

    public function testShowCallsCorrectServiceMethod(): void
    {
        $playerId = 1;
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

        $this->mockRosterService
            ->expects($this->once())
            ->method('getPlayer')
            ->with($this->equalTo($playerId))
            ->willReturn($expectedPlayer);

        $this->mockRosterService
            ->expects($this->once())
            ->method('getDisplayName')
            ->with($this->equalTo($expectedPlayer))
            ->willReturn('JD');

        // Mock the render method
        $this->mockApplication
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('roster/show'),
                $this->callback(function ($data) {
                    return isset($data['title']) && isset($data['player']);
                })
            );

        $this->controller->show($playerId);
    }

    public function testShowRedirectsWhenPlayerNotFound(): void
    {
        $playerId = 999;

        $this->mockRosterService
            ->expects($this->once())
            ->method('getPlayer')
            ->with($this->equalTo($playerId))
            ->willReturn(null);

        // Mock redirect method
        $this->mockApplication
            ->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo('/roster'));

        $this->controller->show($playerId);
    }

    public function testCreateRendersCorrectView(): void
    {
        // Mock the render method
        $this->mockApplication
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('roster/create'),
                $this->callback(function ($data) {
                    return isset($data['title']) && 
                           isset($data['errors']) && 
                           isset($data['old']);
                })
            );

        $this->controller->create();
    }

    public function testStoreCallsServiceMethodWithValidData(): void
    {
        $playerData = [
            'player_identifier' => 'JohnD',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'handicap' => 12
        ];

        $newPlayerId = 1;

        // Mock getPostData method
        $controller = $this->getMockBuilder(RosterController::class)
            ->setConstructorArgs([$this->mockApplication, $this->mockRosterService])
            ->onlyMethods(['getPostData', 'validatePlayerData'])
            ->getMock();

        $controller
            ->expects($this->once())
            ->method('getPostData')
            ->willReturn($playerData);

        $controller
            ->expects($this->once())
            ->method('validatePlayerData')
            ->willReturn([]);

        $this->mockRosterService
            ->expects($this->once())
            ->method('createPlayer')
            ->with($this->equalTo($playerData))
            ->willReturn($newPlayerId);

        // Mock redirect
        $this->mockApplication
            ->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo("/roster/{$newPlayerId}"));

        $controller->store();
    }

    public function testStoreRedirectsToCreateWithErrors(): void
    {
        $playerData = [
            'first_name' => '', // Invalid data
            'last_name' => 'Doe',
            'gender' => 'male'
        ];

        $errors = ['first_name' => 'First name is required'];

        // Mock getPostData method
        $controller = $this->getMockBuilder(RosterController::class)
            ->setConstructorArgs([$this->mockApplication, $this->mockRosterService])
            ->onlyMethods(['getPostData', 'validatePlayerData'])
            ->getMock();

        $controller
            ->expects($this->once())
            ->method('getPostData')
            ->willReturn($playerData);

        $controller
            ->expects($this->once())
            ->method('validatePlayerData')
            ->willReturn($errors);

        // Mock redirect
        $this->mockApplication
            ->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo('/roster/create'));

        $controller->store();
    }

    public function testEditRendersCorrectView(): void
    {
        $playerId = 1;
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

        $this->mockRosterService
            ->expects($this->once())
            ->method('getPlayer')
            ->with($this->equalTo($playerId))
            ->willReturn($expectedPlayer);

        $this->mockRosterService
            ->expects($this->once())
            ->method('getDisplayName')
            ->willReturn('JD');

        // Mock the render method
        $this->mockApplication
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('roster/edit'),
                $this->callback(function ($data) {
                    return isset($data['title']) && 
                           isset($data['player']) && 
                           isset($data['errors']);
                })
            );

        $this->controller->edit($playerId);
    }

    public function testUpdateCallsServiceMethod(): void
    {
        $playerId = 1;
        $updateData = [
            'first_name' => 'Johnathan',
            'last_name' => 'Doe'
        ];

        // Mock methods
        $controller = $this->getMockBuilder(RosterController::class)
            ->setConstructorArgs([$this->mockApplication, $this->mockRosterService])
            ->onlyMethods(['getPostData', 'validatePlayerData'])
            ->getMock();

        $controller
            ->expects($this->once())
            ->method('getPostData')
            ->willReturn($updateData);

        $controller
            ->expects($this->once())
            ->method('validatePlayerData')
            ->willReturn([]);

        $this->mockRosterService
            ->expects($this->once())
            ->method('updatePlayer')
            ->with($this->equalTo($playerId), $this->equalTo($updateData))
            ->willReturn(true);

        // Mock redirect
        $this->mockApplication
            ->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo("/roster/{$playerId}"));

        $controller->update($playerId);
    }

    public function testDeleteCallsServiceMethod(): void
    {
        $playerId = 1;
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

        $this->mockRosterService
            ->expects($this->once())
            ->method('getPlayer')
            ->with($this->equalTo($playerId))
            ->willReturn($expectedPlayer);

        $this->mockRosterService
            ->expects($this->once())
            ->method('getDisplayName')
            ->willReturn('JD');

        // Mock the render method
        $this->mockApplication
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('roster/delete'),
                $this->callback(function ($data) {
                    return isset($data['title']) && isset($data['player']);
                })
            );

        $this->controller->delete($playerId);
    }

    public function testDestroyCallsServiceMethod(): void
    {
        $playerId = 1;

        $this->mockRosterService
            ->expects($this->once())
            ->method('deletePlayer')
            ->with($this->equalTo($playerId))
            ->willReturn(true);

        // Mock redirect
        $this->mockApplication
            ->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo('/roster'));

        $this->controller->destroy($playerId);
    }

    public function testSearchCallsServiceMethod(): void
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

        // Mock $_GET superglobal
        $_GET['q'] = $query;

        $this->mockRosterService
            ->expects($this->once())
            ->method('searchPlayers')
            ->with($this->equalTo($query))
            ->willReturn($expectedResults);

        // Mock the render method
        $this->mockApplication
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->equalTo('roster/search'),
                $this->callback(function ($data) {
                    return isset($data['title']) && 
                           isset($data['roster']) && 
                           isset($data['query']);
                })
            );

        $this->controller->search();
        
        // Clean up
        unset($_GET['q']);
    }

    public function testActivateCallsServiceMethod(): void
    {
        $playerId = 1;

        $this->mockRosterService
            ->expects($this->once())
            ->method('activatePlayer')
            ->with($this->equalTo($playerId))
            ->willReturn(true);

        // Mock redirect
        $this->mockApplication
            ->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo('/roster'));

        $this->controller->activate($playerId);
    }
}
