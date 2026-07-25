<?php

namespace Tests\Unit;

use App\Controllers\AdminController;
use App\Core\Application;
use App\Services\Logger;
use App\Services\RoundLockService;
use App\Services\RoundWorkflowService;
use App\Services\TeamHaggleSeriousService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AdminControllerTest extends TestCase
{
    private AdminController $controller;
    private Application|MockObject $appMock;
    private Logger|MockObject $loggerMock;
    private RoundWorkflowService|MockObject $roundWorkflowServiceMock;
    private RoundLockService|MockObject $roundLockServiceMock;
    private TeamHaggleSeriousService|MockObject $teamHaggleSeriousServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appMock = $this->createMock(Application::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->roundWorkflowServiceMock = $this->createMock(RoundWorkflowService::class);
        $this->roundLockServiceMock = $this->createMock(RoundLockService::class);
        $this->teamHaggleSeriousServiceMock = $this->createMock(TeamHaggleSeriousService::class);
        $this->controller = new AdminController(
            $this->appMock,
            $this->loggerMock,
            $this->roundWorkflowServiceMock,
            $this->roundLockServiceMock,
            $this->teamHaggleSeriousServiceMock
        );
    }

    public function testControllerInstantiatesWithDependencies(): void
    {
        $this->assertInstanceOf(AdminController::class, $this->controller);
    }

    public function testControllerHasExpectedMethods(): void
    {
        $methods = [
            'menu',
            'scoringState',
            'unlockScoringProcess',
            'resetResultsToCardEntry',
        ];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists($this->controller, $method),
                "AdminController should have method {$method}"
            );
        }
    }

    public function testConstructorRequiresAllDependencies(): void
    {
        $reflection = new \ReflectionClass(AdminController::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $parameters = $constructor->getParameters();
        $this->assertSame(
            ['app', 'logger', 'roundWorkflowService', 'roundLockService', 'teamHaggleSeriousService'],
            array_map(static fn(\ReflectionParameter $parameter): string => $parameter->getName(), $parameters)
        );
        $this->assertSame(5, $constructor->getNumberOfRequiredParameters());
    }

    public function testResetResultsToCardEntryMethodHasNoRequiredParameters(): void
    {
        $reflection = new \ReflectionClass(AdminController::class);
        $method = $reflection->getMethod('resetResultsToCardEntry');

        $this->assertCount(0, $method->getParameters());
    }
}
