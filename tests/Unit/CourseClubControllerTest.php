<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\CourseClubController;
use App\Core\Application;
use App\Core\Database;
use App\Services\Logger;
use App\Services\AuthService;

class CourseClubControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $_POST = [];
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
        $dbMock = $this->createMock(Database::class);
        $authMock = $this->createMock(AuthService::class);
        $authMock->expects($this->once())
            ->method('requireRole')
            ->with('admin');

        $dbMock->method('getAuth')->willReturn($authMock);
        $dbMock->method('fetchOne')->willReturn(['config_value_string' => 'ready']);
        $dbMock->method('fetchAll')->willReturn([]);

        $rowData = [
            'row_id' => 1,
            'name_club' => 'Test Club',
            'number_hole' => 9,
            'name_hole' => 'Old Hole Name',
            'gender' => 'M',
            'par' => 4,
            'stroke' => 10,
            'updated_by' => 'admin'
        ];

        $stmtForGetById = $this->createMock(\PDOStatement::class);
        $stmtForGetById->method('fetch')->willReturn($rowData);

        $stmtForHoleExists = $this->createMock(\PDOStatement::class);
        $stmtForHoleExists->method('fetch')->willReturn(['count' => 0]);

        $dbMock->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function ($sql, $params = []) use ($stmtForGetById, $stmtForHoleExists) {
                if (stripos($sql, 'SELECT * FROM course_club WHERE row_id = ?') !== false) {
                    return $stmtForGetById;
                }

                if (stripos($sql, 'SELECT COUNT(*) as count FROM course_club') !== false) {
                    return $stmtForHoleExists;
                }

                return $stmtForGetById;
            });

        $appMock = $this->createMock(Application::class);
        $appMock->method('getDatabase')->willReturn($dbMock);

        $loggerMock = $this->createMock(Logger::class);

        $controller = $this->getMockBuilder(CourseClubController::class)
            ->setConstructorArgs([$appMock, $loggerMock])
            ->onlyMethods(['redirect'])
            ->getMock();

        $controller->expects($this->once())
            ->method('redirect')
            ->with('/course-club#Test Club-M');

        $_POST = [
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
        $this->assertSame('Edit saved as pending. Return to Course Holes to apply all edits.', $_SESSION['success']);
    }

    public function testBatchUpdateReturnsErrorForDuplicateStrokeIndexes(): void
    {
        $dbMock = $this->createMock(Database::class);
        $authMock = $this->createMock(AuthService::class);
        $authMock->expects($this->once())
            ->method('requireRole')
            ->with('admin');

        $dbMock->method('getAuth')->willReturn($authMock);
        $dbMock->method('fetchOne')->willReturn(['config_value_string' => 'ready']);
        $dbMock->method('fetchAll')->willReturn([]);

        $appMock = $this->createMock(Application::class);
        $appMock->method('getDatabase')->willReturn($dbMock);

        $loggerMock = $this->createMock(Logger::class);

        $controller = new CourseClubController($appMock, $loggerMock);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
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
        $dbMock = $this->createMock(Database::class);
        $authMock = $this->createMock(AuthService::class);
        $authMock->expects($this->once())
            ->method('requireRole')
            ->with('admin');

        $dbMock->method('getAuth')->willReturn($authMock);
        $dbMock->method('fetchOne')->willReturn(['config_value_string' => 'ready']);
        $dbMock->method('fetchAll')->willReturn([]);

        $appMock = $this->createMock(Application::class);
        $appMock->method('getDatabase')->willReturn($dbMock);

        $loggerMock = $this->createMock(Logger::class);

        $controller = new CourseClubController($appMock, $loggerMock);

        $holes = [];
        for ($i = 1; $i <= 17; $i++) {
            $holes[] = ['id' => $i, 'name_hole' => "Hole {$i}", 'par' => 4, 'stroke' => $i];
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['course_clubs' => $holes];

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
        $dbMock = $this->createMock(Database::class);
        $authMock = $this->createMock(AuthService::class);
        $authMock->expects($this->once())
            ->method('requireRole')
            ->with('admin');

        $dbMock->method('getAuth')->willReturn($authMock);
        $dbMock->method('fetchOne')->willReturn(['config_value_string' => 'ready']);
        $dbMock->method('fetchAll')->willReturn([]);

        $appMock = $this->createMock(Application::class);
        $appMock->method('getDatabase')->willReturn($dbMock);

        $loggerMock = $this->createMock(Logger::class);

        $controller = new CourseClubController($appMock, $loggerMock);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['course_clubs' => [
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
        $dbMock->method('getAuth')->willReturn($this->createMock(AuthService::class));
        $dbMock->method('fetchOne')->willReturn(['config_value_string' => 'ready']);
        $dbMock->method('fetchAll')->willReturn([]);

        $appMock = $this->createMock(Application::class);
        $appMock->method('getDatabase')->willReturn($dbMock);

        $loggerMock = $this->createMock(Logger::class);

        return new CourseClubController($appMock, $loggerMock);
    }
}
