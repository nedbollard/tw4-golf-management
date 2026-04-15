<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Controllers\StaffController;
use App\Core\Application;
use App\Services\Logger;

class StaffControllerTest extends TestCase
{
    private StaffController $staffController;
    private Application|MockObject $appMock;
    private Logger|MockObject $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->appMock = $this->createMock(Application::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->staffController = new StaffController($this->appMock, $this->loggerMock);
    }

    public function testControllerInstantiatesWithDependencies(): void
    {
        $this->assertInstanceOf(StaffController::class, $this->staffController);
    }

    public function testControllerHasExpectedMethods(): void
    {
        $methods = ['index', 'add', 'edit', 'update', 'delete'];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists($this->staffController, $method),
                "StaffController should have method {$method}"
            );
        }
    }

    public function testConstructorRequiresApplicationAndLogger(): void
    {
        $reflection = new \ReflectionClass(StaffController::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $parameters = $constructor->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('app', $parameters[0]->getName());
        $this->assertEquals('logger', $parameters[1]->getName());
    }
}
