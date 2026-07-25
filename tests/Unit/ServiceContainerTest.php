<?php

namespace Tests\Unit;

use App\Core\Database;
use App\Core\ServiceContainer;
use App\Services\AuthService;
use App\Services\FlashMessage;
use App\Services\RosterService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ServiceContainerTest extends TestCase
{
    public function testReturnsSharedServiceInstances(): void
    {
        $container = new ServiceContainer($this->createMock(Database::class));

        $this->assertSame($container->get(AuthService::class), $container->get(AuthService::class));
        $this->assertSame($container->get(FlashMessage::class), $container->get(FlashMessage::class));
    }

    public function testRecursivelyResolvesServiceDependencies(): void
    {
        $container = new ServiceContainer($this->createMock(Database::class));

        $this->assertInstanceOf(RosterService::class, $container->get(RosterService::class));
    }

    public function testRejectsClassesOutsideServiceNamespace(): void
    {
        $container = new ServiceContainer($this->createMock(Database::class));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('outside the allowed namespace');

        $container->get(\stdClass::class);
    }
}