<?php

namespace Tests\Unit;

use App\Controllers\AdminController;
use App\Core\Application;
use App\Services\Logger;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AdminControllerTest extends TestCase
{
    private AdminController $controller;
    private Application|MockObject $appMock;
    private Logger|MockObject $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appMock = $this->createMock(Application::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->controller = new AdminController($this->appMock, $this->loggerMock);
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

    public function testConstructorRequiresApplicationAndLogger(): void
    {
        $reflection = new \ReflectionClass(AdminController::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $parameters = $constructor->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertSame('app', $parameters[0]->getName());
        $this->assertSame('logger', $parameters[1]->getName());
    }

    public function testResetResultsToCardEntryMethodHasNoRequiredParameters(): void
    {
        $reflection = new \ReflectionClass(AdminController::class);
        $method = $reflection->getMethod('resetResultsToCardEntry');

        $this->assertCount(0, $method->getParameters());
    }
}
