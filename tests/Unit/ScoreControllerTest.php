<?php

namespace Tests\Unit;

use App\Controllers\ScoreController;
use App\Core\Application;
use App\Services\Logger;
use App\Services\ResultsPresentationService;
use App\Services\RoundWorkflowService;
use App\Services\ScoreEntryService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ScoreControllerTest extends TestCase
{
    public function testLeaderboardShowsRetainedCardsAfterRoundIsFinished(): void
    {
        $app = $this->createMock(Application::class);
        $logger = $this->createMock(Logger::class);
        $scoreEntryService = $this->createMock(ScoreEntryService::class);
        $resultsPresentationService = $this->createMock(ResultsPresentationService::class);
        $roundWorkflowService = $this->createMock(RoundWorkflowService::class);
        $round = [
            'round_id' => 7,
            'round_number' => 5,
            'workflow_step' => 'between_rounds',
        ];
        $resultsData = [
            'leaderboard' => [
                ['row_id_player' => 42, 'points' => 21],
            ],
        ];

        $roundWorkflowService->expects($this->once())
            ->method('getActiveRoundForScorerMenu')
            ->willReturn($round);
        $resultsPresentationService->expects($this->once())
            ->method('buildPresentationData')
            ->with(7)
            ->willReturn($resultsData);

        $controller = new class(
            $app,
            $logger,
            $scoreEntryService,
            $resultsPresentationService,
            $roundWorkflowService
        ) extends ScoreController {
            public string $renderedView = '';
            public array $renderedData = [];

            protected function render(string $view, array $data = []): void
            {
                $this->renderedView = $view;
                $this->renderedData = $data;
            }
        };

        $controller->leaderboard();

        $this->assertSame('scores/leaderboard', $controller->renderedView);
        $this->assertSame($resultsData, $controller->renderedData['resultsData']);
        $this->assertSame('Round 5 is finished - showing final scores.', $controller->renderedData['notice']);
        $this->assertTrue($controller->renderedData['showPublishedResultsNudge']);
    }
}