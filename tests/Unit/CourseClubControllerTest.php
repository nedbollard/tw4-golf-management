<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use App\Controllers\CourseClubController;
use App\Core\Application;
use App\Core\Database;
use App\Services\CourseClubService;
use App\Services\ConfigService;
use App\Services\FlashMessage;
use App\Services\Logger;
use App\Services\AuthService;
use App\Models\CourseClub;

#[AllowMockObjectsWithoutExpectations]
class CourseClubControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = ['config_checked' => true, 'csrf_token' => 'test-token'];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_X_CSRF_TOKEN']);
        http_response_code(200);
        parent::tearDown();
    }

    public function testValidateCourseClubDataWithValidData(): void
    {
        $controller = $this->createControllerWithMockDependencies();

        $method = new \ReflectionMethod(CourseClubController::class, 'validateCourseClubData');
        $method->setAccessible(true);

        $validData = [
            'name_club' => 'Test Club',
            'number_hole' => 5,
            'name_hole' => 'Test Hole',
            'gender' => 'M',
            'par' => 4,
            'stroke' => 12
        ];

        $errors = $method->invoke($controller, $validData);

        $this->assertIsArray($errors);
        $this->assertEmpty($errors, 'Valid course club data should not produce validation errors');
    }

    public function testValidateCourseClubDataRejectsInvalidStroke(): void
    {
        $controller = $this->createControllerWithMockDependencies();

        $method = new \ReflectionMethod(CourseClubController::class, 'validateCourseClubData');
        $method->setAccessible(true);

        $invalidData = [
            'name_club' => 'Test Club',
            'number_hole' => 3,
            'name_hole' => 'Test Hole',
            'gender' => 'M',
            'par' => 4,
            'stroke' => 0
        ];

        $errors = $method->invoke($controller, $invalidData);

        $this->assertArrayHasKey('stroke', $errors);
        $this->assertSame('Stroke index must be between 1 and 18', $errors['stroke']);
    }

    public function testUpdateStoresPendingEditsInSession(): void
    {
        $courseClubServiceMock = $this->createMock(CourseClubService::class);
        $courseClubServiceMock->expects($this->once())
            ->method('getCourseClubById')
            ->with(1)
            ->willReturn(new CourseClub('Test Club', 9, 'Old Hole Name', 'M', 4, 10, 'admin', 1));
        $courseClubServiceMock->expects($this->once())
            ->method('holeNumberExists')
            ->with('Test Club', 9, 1, 'M')
            ->willReturn(false);

        $controller = $this->getMockBuilder(CourseClubController::class)
            ->setConstructorArgs($this->createControllerDependencies($courseClubServiceMock))
            ->onlyMethods(['redirect'])
            ->getMock();
        $this->initializeController($controller);

        $controller->expects($this->once())
            ->method('redirect')
            ->with('/course-club#Test Club-M');

        $_POST = [
            'csrf_token' => 'test-token',
            'name_club' => 'Test Club',
            'number_hole' => 9,
            'name_hole' => 'Updated Hole Name',
            'gender' => 'M',
            'par' => 5,
            'stroke' => 15
        ];

        $controller->update(1);

        $this->assertArrayHasKey('pendingEdits', $_SESSION);
        $this->assertArrayHasKey(1, $_SESSION['pendingEdits']);
        $this->assertSame([
            'id' => 1,
            'name_hole' => 'Updated Hole Name',
            'par' => 5,
            'stroke' => 15
        ], $_SESSION['pendingEdits'][1]);
        $this->assertSame(
            ['Edit saved as pending. Return to Course Holes to apply all edits.'],
            $_SESSION['_flash']['success']
        );
    }

    public function testBatchUpdateReturnsErrorForDuplicateStrokeIndexes(): void
    {
        $controller = $this->createControllerWithMockDependencies();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => 'test-token',
            'course_clubs' => [
                ['id' => 1, 'name_hole' => 'Hole 1', 'par' => 4, 'stroke' => 5],
                ['id' => 2, 'name_hole' => 'Hole 2', 'par' => 4, 'stroke' => 5]
            ]
        ];

        ob_start();
        $controller->batchUpdate();
        $output = ob_get_clean();

        $result = json_decode($output, true);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Duplicate stroke index', $result['message']);
    }

    public function testBatchUpdateReturnsErrorForMissingStrokeIndices(): void
    {
        $controller = $this->createControllerWithMockDependencies();

        $holes = [];
        for ($i = 1; $i <= 17; $i++) {
            $holes[] = ['id' => $i, 'name_hole' => "Hole {$i}", 'par' => 4, 'stroke' => $i];
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['csrf_token' => 'test-token', 'course_clubs' => $holes];

        ob_start();
        $controller->batchUpdate();
        $output = ob_get_clean();

        $result = json_decode($output, true);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing stroke indices', $result['message']);
    }

    public function testBatchUpdateReturnsErrorForInvalidStrokeValue(): void
    {
        $controller = $this->createControllerWithMockDependencies();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['csrf_token' => 'test-token', 'course_clubs' => [
            ['id' => 1, 'name_hole' => 'Hole 1', 'par' => 4, 'stroke' => 19]
        ]];

        ob_start();
        $controller->batchUpdate();
        $output = ob_get_clean();

        $result = json_decode($output, true);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Stroke index must be between 1 and 18', $result['message']);
    }

    private function createControllerWithMockDependencies(): CourseClubController
    {
        $dbMock = $this->createMock(Database::class);
        $appMock = $this->createMock(Application::class);
        $appMock->method('getDatabase')->willReturn($dbMock);
        $loggerMock = $this->createMock(Logger::class);
        $courseClubServiceMock = $this->createMock(CourseClubService::class);

        $controller = new CourseClubController($appMock, $loggerMock, $courseClubServiceMock);
        $this->initializeController($controller);
        return $controller;
    }

    private function createControllerDependencies(CourseClubService $courseClubService): array
    {
        $db = $this->createMock(Database::class);
        $app = $this->createMock(Application::class);
        $app->method('getDatabase')->willReturn($db);
        return [$app, $this->createMock(Logger::class), $courseClubService];
    }

    private function initializeController(CourseClubController $controller): void
    {
        $config = $this->createMock(ConfigService::class);
        $auth = $this->createMock(AuthService::class);
        $auth->method('requireRole');
        $controller->initializeServices($config, $auth, new FlashMessage(), $this->createMock(Logger::class));
    }
}
