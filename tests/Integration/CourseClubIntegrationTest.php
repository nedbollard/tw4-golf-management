<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use App\Core\Application;
use App\Core\Database;
use App\Core\Router;

#[AllowMockObjectsWithoutExpectations]
class CourseClubIntegrationTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $_SERVER['CLI_REDIRECT_URL'] = null;
        $_SERVER['CLI_REDIRECT_STATUS'] = null;
        $this->app = Application::getInstance();
    }

    public function testCourseClubRoutesAreRegistered(): void
    {
        $routes = $this->app->getRouter()->getRoutes();
        $getRoutes = $routes['GET'];
        $postRoutes = $routes['POST'];

        $this->assertArrayHasKey('/course-club', $getRoutes, 'Should have GET /course-club route');
        $this->assertArrayHasKey('/course-club/{club}-{gender}', $getRoutes, 'Should have GET /course-club/{club}-{gender} route');
        $this->assertArrayHasKey('/course-club/create', $getRoutes, 'Should have GET /course-club/create route');
        $this->assertArrayHasKey('/course-club/{id}/edit', $getRoutes, 'Should have GET /course-club/{id}/edit route');

        $this->assertArrayHasKey('/course-club/store', $postRoutes, 'Should have POST /course-club/store route');
        $this->assertArrayHasKey('/course-club/{id}/update', $postRoutes, 'Should have POST /course-club/{id}/update route');
        $this->assertArrayHasKey('/course-club/batch-update', $postRoutes, 'Should have POST /course-club/batch-update route');
    }

    public function testCourseClubStatsRouteHasBeenRemoved(): void
    {
        $routes = $this->app->getRouter()->getRoutes();
        $getRoutes = $routes['GET'];
        $this->assertArrayNotHasKey('/course-club/stats', $getRoutes, 'Stale /course-club/stats route should be removed');
    }

    public function testCourseClubControllerAndMethodsExist(): void
    {
        $this->assertTrue(class_exists('App\\Controllers\\CourseClubController'));

        $expectedMethods = [
            'index',
            'create',
            'store',
            'edit',
            'update',
            'delete',
            'batchUpdate'
        ];

        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                method_exists('App\\Controllers\\CourseClubController', $method),
                "CourseClubController should have method {$method}"
            );
        }
    }

    public function testCourseClubIndexRouteDispatchesSuccessfullyForAdmin(): void
    {
        $_SESSION = [];
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['user_role'] = 'admin';

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/course-club';

        $dbMock = $this->createMock(Database::class);
        $dbMock->method('fetchOne')->willReturn(['config_value_string' => 'ready']);
        $dbMock->method('fetchAll')->willReturnCallback(function ($sql) {
            if (stripos($sql, 'SELECT config_name') !== false) {
                return [
                    ['config_name' => 'club_name', 'config_value_string' => 'Test Club'],
                    ['config_name' => 'competition_name', 'config_value_string' => 'Weekly Competition']
                ];
            }

            return [];
        });

        $authServiceMock = $this->createMock(\App\Services\AuthService::class);
        $authServiceMock->method('getUser')->willReturn(['username' => 'admin']);
        $dbMock->method('getAuth')->willReturn($authServiceMock);

        $stmtAll = $this->createMock(\PDOStatement::class);
        $stmtAll->method('fetchAll')->willReturn([
            [
                'row_id' => 1,
                'name_club' => 'Test Club',
                'number_hole' => 1,
                'name_hole' => 'Hole 1',
                'gender' => 'M',
                'par' => 4,
                'stroke' => 1,
                'updated_by' => 'admin'
            ]
        ]);

        $stmtUnique = $this->createMock(\PDOStatement::class);
        $stmtUnique->method('fetchAll')->willReturn([
            ['name_club' => 'Test Club']
        ]);

        $dbMock->method('query')->willReturnCallback(function ($sql, $params = []) use ($stmtAll, $stmtUnique) {
            if (stripos($sql, 'SELECT DISTINCT name_club') !== false) {
                return $stmtUnique;
            }

            return $stmtAll;
        });

        $appMock = $this->createMock(Application::class);
        $appMock->method('getDatabase')->willReturn($dbMock);
        $appMock->method('getConfig')->willReturn([
            'paths' => [
                'views' => __DIR__ . '/../../src/Views'
            ]
        ]);

        $router = new Router($appMock);
        $router->loadRoutes(require __DIR__ . '/../../src/config/routes.php');

        ob_start();
        $router->dispatch();
        $output = ob_get_clean();

        $this->assertStringContainsString('Test Club', $output);
    }

    public function testCourseClubBatchUpdateRouteDispatchReturnsCountErrorForIncompleteHoleList(): void
    {
        $_SESSION = [];
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['user_role'] = 'admin';

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/course-club/batch-update';
        $_POST = [
            'course_clubs' => [
                ['id' => 1, 'name_hole' => 'Hole 1', 'par' => 4, 'stroke' => 1],
                ['id' => 2, 'name_hole' => 'Hole 2', 'par' => 4, 'stroke' => 2]
            ]
        ];

        $dbMock = $this->createMock(Database::class);
        $dbMock->method('fetchOne')->willReturn(['config_value_string' => 'ready']);
        $dbMock->method('fetchAll')->willReturn([
            ['config_name' => 'club_name', 'config_value_string' => 'Test Club'],
            ['config_name' => 'competition_name', 'config_value_string' => 'Weekly Competition']
        ]);

        $authServiceMock = $this->createMock(\App\Services\AuthService::class);
        $authServiceMock->method('getUser')->willReturn(['username' => 'admin']);
        $dbMock->method('getAuth')->willReturn($authServiceMock);

        $appMock = $this->createMock(Application::class);
        $appMock->method('getDatabase')->willReturn($dbMock);
        $appMock->method('getConfig')->willReturn([
            'paths' => [
                'views' => __DIR__ . '/../../src/Views'
            ]
        ]);

        $router = new Router($appMock);
        $router->loadRoutes(require __DIR__ . '/../../src/config/routes.php');

        ob_start();
        $router->dispatch();
        $output = ob_get_clean();

        $result = json_decode($output, true);
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing stroke indices', $result['message']);
    }
}
