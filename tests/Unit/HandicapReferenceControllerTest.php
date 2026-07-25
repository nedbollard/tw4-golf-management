<?php

namespace Tests\Unit;

use App\Controllers\HandicapReferenceController;
use App\Core\Application;
use App\Core\Database;
use App\Services\AuthService;
use App\Services\ConfigService;
use App\Services\FlashMessage;
use App\Services\HandicapReferenceService;
use App\Services\Logger;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class HandicapReferenceControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = ['config_checked' => true];
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_GET = [];
    }

    public function testPlusIndexIsCalculatedAndRenderedAtScratch(): void
    {
        $tee = [
            'row_id' => 1,
            'club_number' => 294,
            'gender' => 'M',
            'tee_name' => 'White',
            'course_rating' => '62.9',
            'par' => 66,
            'slope' => 107,
            'handicap_allowance' => '100.00',
            'effective_from' => '2025-12-02',
            'effective_to' => null,
        ];

        $service = $this->createMock(HandicapReferenceService::class);
        $service->expects($this->once())->method('getCurrentTees')->with(294)->willReturn([$tee]);
        $service->expects($this->once())->method('getTee')->with(1, 294)->willReturn($tee);
        $service->expects($this->once())
            ->method('calculate')
            ->with(5.0, true, $tee)
            ->willReturn([
                'published_handicap' => -8,
                'published_display' => '+8',
                'tw4_handicap' => 0,
            ]);

        $database = $this->createMock(Database::class);
        $app = $this->createMock(Application::class);
        $app->method('getDatabase')->willReturn($database);
        $controller = $this->getMockBuilder(HandicapReferenceController::class)
            ->setConstructorArgs([$app, $service])
            ->onlyMethods(['render'])
            ->getMock();

        $config = $this->createMock(ConfigService::class);
        $config->method('getConfigValue')->with('club_number', '294')->willReturn('294');
        $auth = $this->createMock(AuthService::class);
        $auth->expects($this->once())->method('requireRole')->with('scorer');
        $controller->initializeServices($config, $auth, new FlashMessage(), $this->createMock(Logger::class));

        $controller->expects($this->once())
            ->method('render')
            ->with('handicap/reference', $this->callback(static function (array $data): bool {
                return $data['clubNumber'] === 294
                    && $data['indexType'] === 'plus'
                    && $data['result']['tw4_handicap'] === 0;
            }));

        $_GET = [
            'gender' => 'M',
            'tee_id' => '1',
            'index_type' => 'plus',
            'handicap_index' => '5.0',
            'calculate' => '1',
        ];

        $controller->index();
    }
}