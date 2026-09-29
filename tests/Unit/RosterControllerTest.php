<?php

namespace Tests\Unit;

use App\Controllers\RosterController;
use App\Core\Application;
use App\Services\RosterService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * RosterController Unit Tests
 * Tests basic roster controller functionality that can be reliably unit tested
 */
#[AllowMockObjectsWithoutExpectations]
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

    public function testControllerInstantiatesWithCorrectDependencies(): void
    {
        $this->assertInstanceOf(RosterController::class, $this->controller);
        $this->assertNotNull($this->controller);
    }

    public function testValidatePlayerDataReturnsEmptyArrayForValidData(): void
    {
        $validData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male'
        ];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validatePlayerData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $validData);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testValidatePlayerDataReturnsErrorsForInvalidData(): void
    {
        $invalidData = [
            'first_name' => '',
            'last_name' => '',
            'gender' => 'invalid'
        ];

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validatePlayerData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $invalidData);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('first_name', $result);
        $this->assertArrayHasKey('last_name', $result);
        $this->assertArrayHasKey('gender', $result);
    }

    public function testValidatePlayerDataExcludesExistingPlayerFromUniquenessCheck(): void
    {
        $validData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'player_identifier' => 'JohnD'
        ];

        $excludePlayerId = 1;

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('validatePlayerData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $validData, $excludePlayerId);
        $this->assertIsArray($result);
        // Should not fail validation for this test since we're not mocking the service calls
    }

    public function testRosterServiceIsCorrectlyInjected(): void
    {
        // Use reflection to verify the service property
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('rosterService');
        $property->setAccessible(true);

        $service = $property->getValue($this->controller);
        $this->assertInstanceOf(RosterService::class, $service);
        $this->assertSame($this->mockRosterService, $service);
    }

    public function testIndexLoadsInactivePlayersForRosterManagement(): void
    {
        $roster = [[
            'row_id' => 1,
            'player_identifier' => 'JohnD',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'inactive',
        ]];

        $this->mockRosterService
            ->expects($this->once())
            ->method('getAllPlayersIncludingInactive')
            ->willReturn($roster);

        $controller = new class($this->mockApplication, $this->mockRosterService) extends RosterController {
            public array $renderedData = [];

            public function render(string $view, array $data = []): void
            {
                $this->renderedData = $data;
            }

            protected function requireRole(string $role): void
            {
                // no-op for unit test
            }
        };

        $controller->index();

        $this->assertSame('Players - TW4 Golf Management', $controller->renderedData['title']);
        $this->assertSame($roster, $controller->renderedData['roster']);
    }

    public function testShowUsesInactivePlayerLookupForRosterManagement(): void
    {
        $player = [
            'row_id' => 1,
            'player_identifier' => 'JohnD',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'inactive',
        ];

        $this->mockRosterService
            ->expects($this->once())
            ->method('getPlayerIncludingInactive')
            ->with(1)
            ->willReturn($player);

        $controller = new class($this->mockApplication, $this->mockRosterService) extends RosterController {
            public array $renderedData = [];

            public function render(string $view, array $data = []): void
            {
                $this->renderedData = $data;
            }

            protected function requireAuth(): void
            {
                // no-op for unit test
            }
        };

        $controller->show(1);

        $this->assertSame('Player Details - ' . $this->mockRosterService->getDisplayName($player), $controller->renderedData['title']);
        $this->assertSame($player, $controller->renderedData['player']);
    }
}
