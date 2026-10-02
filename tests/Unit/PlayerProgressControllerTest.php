<?php

namespace Tests\Unit;

use App\Controllers\PlayerProgressController;
use App\Core\Application;
use App\Services\PlayerProgressService;
use App\Services\RosterService;
use PHPUnit\Framework\TestCase;

class PlayerProgressControllerTest extends TestCase
{
    public function testSelectorLoadsPlayersWithoutCurrentSeasonContext(): void
    {
        $service = $this->createMock(PlayerProgressService::class);
        $players = [['row_id' => 7, 'alias' => 'Alias7']];
        $service->expects($this->once())->method('getEligiblePlayersWithHistory')->with()->willReturn($players);
        $controller = $this->controller($service);

        $controller->index();

        $this->assertSame($players, $controller->renderedData['playerOptions']);
        $this->assertSame(7, $controller->renderedData['selectedPlayerId']);
        $this->assertNull($controller->renderedData['notice']);
    }

    public function testChartLoadsPreviousSeasonOnlyHistory(): void
    {
        $service = $this->createMock(PlayerProgressService::class);
        $players = [['row_id' => 7, 'alias' => 'Alias7']];
        $progress = ['player' => $players[0], 'seasons' => ['25_26'], 'rounds' => [['season_year' => '25_26', 'number_round' => 1]]];
        $service->expects($this->once())->method('getEligiblePlayersWithHistory')->willReturn($players);
        $service->expects($this->once())->method('getPlayerProgress')->with(7)->willReturn($progress);
        $controller = $this->controller($service);
        $previousGet = $_GET;
        $_GET['player_id'] = '999';
        try {
            $controller->chart();
        } finally {
            $_GET = $previousGet;
        }

        $this->assertSame(7, $controller->renderedData['selectedPlayerId']);
        $this->assertSame($progress, $controller->renderedData['progress']);
        $this->assertNull($controller->renderedData['notice']);
    }

    private function controller(PlayerProgressService $service): PlayerProgressController
    {
        return new class($this->createStub(Application::class), $this->createStub(RosterService::class), $service) extends PlayerProgressController {
            public array $renderedData = [];

            protected function render(string $view, array $data = []): void
            {
                $this->renderedData = $data;
            }
        };
    }
}
