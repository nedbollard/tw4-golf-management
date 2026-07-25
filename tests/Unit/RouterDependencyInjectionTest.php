<?php

namespace Tests\Unit;

use App\Controllers\BaseController;
use App\Core\Application;
use App\Core\Database;
use App\Core\Router;
use App\Core\ServiceContainer;
use App\Services\AuthService;
use App\Services\ConfigService;
use App\Services\FlashMessage;
use App\Services\Logger;
use App\Services\ScoreEntryService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

class ContainerResolvedController extends BaseController
{
    public static ?Logger $resolvedLogger = null;
    public static ?ScoreEntryService $resolvedScoreEntryService = null;

    public function __construct(
        Application $app,
        Logger $logger,
        ScoreEntryService $scoreEntryService
    ) {
        parent::__construct($app);
        self::$resolvedLogger = $logger;
        self::$resolvedScoreEntryService = $scoreEntryService;
    }

    public function handle(): void
    {
        $this->requireAuth();
    }
}

#[AllowMockObjectsWithoutExpectations]
class RouterDependencyInjectionTest extends TestCase
{
    public function testDispatchResolvesControllerAndBaseServicesFromContainer(): void
    {
        $_SESSION = ['config_checked' => true];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/di-test';

        $database = $this->createMock(Database::class);
        $application = $this->createMock(Application::class);
        $application->method('getDatabase')->willReturn($database);

        $container = new ServiceContainer($database);
        $config = $this->createMock(ConfigService::class);
        $auth = $this->createMock(AuthService::class);
        $flash = $this->createMock(FlashMessage::class);
        $logger = $this->createMock(Logger::class);
        $scoreEntry = $this->createMock(ScoreEntryService::class);

        $container->set(ConfigService::class, $config);
        $container->set(AuthService::class, $auth);
        $container->set(FlashMessage::class, $flash);
        $container->set(Logger::class, $logger);
        $container->set(ScoreEntryService::class, $scoreEntry);

        $auth->expects($this->once())->method('updateActivity');
        $auth->expects($this->once())->method('requireLogin');

        $router = new Router($application, $container);
        $router->loadRoutes([
            'GET' => [[
                'method' => 'handle',
                'path' => '/di-test',
                'controller' => ContainerResolvedController::class,
            ]],
        ]);

        $router->dispatch();

        $this->assertSame($logger, ContainerResolvedController::$resolvedLogger);
        $this->assertSame($scoreEntry, ContainerResolvedController::$resolvedScoreEntryService);
    }
}