<?php

namespace Tests\Unit;

use App\Controllers\ScoreController;
use App\Core\Application;
use App\Services\AuthService;
use App\Services\Logger;
use App\Services\ResultsPresentationService;
use App\Services\RoundWorkflowService;
use App\Services\ScoreEntryService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ScoreControllerTest extends TestCase
{
    public function testSuccessfulCalculationEnablesSaveInRenderedView(): void
    {
        $data = $this->calculateCardAndCaptureView([]);

        $this->assertTrue($data['calculationComplete']);
        $this->assertSame([], $data['errors']);
    }

    public function testFailedCalculationDoesNotEnableSaveInRenderedView(): void
    {
        $data = $this->calculateCardAndCaptureView(['Hole 1: score is required.']);

        $this->assertArrayNotHasKey('calculationComplete', $data);
        $this->assertSame(['Hole 1: score is required.'], $data['errors']);
    }

    private function calculateCardAndCaptureView(array $errors): array
    {
        $entryService = $this->createMock(ScoreEntryService::class);
        $workflow = $this->createMock(RoundWorkflowService::class);
        $auth = $this->createStub(AuthService::class);
        $auth->method('getUser')->willReturn(['user_id' => 2, 'username' => 'scorer']);
        $workflow->expects($this->once())->method('getActiveRoundForScorerMenu')
            ->willReturn(['round_id' => 7, 'workflow_step' => 'card_entry_open']);
        $entryService->expects($this->once())->method('assertEntryLock')->with(7, 2)->willReturn(true);
        $entryService->expects($this->once())->method('buildEntryData')->with(7, 1)->willReturn([]);
        $entryService->expects($this->once())->method('calculateCard')->with([], [1 => '4'])
            ->willReturn(['errors' => $errors]);
        $entryService->expects($this->never())->method('saveCard');
        $controller = new class(
            $this->createStub(Application::class),
            $this->createStub(Logger::class),
            $entryService,
            $this->createStub(ResultsPresentationService::class),
            $workflow
        ) extends ScoreController {
            public array $renderedData = [];

            public function setAuth(AuthService $auth): void
            {
                $this->authService = $auth;
            }

            protected function requireRole(string $role): void {}

            protected function validateCsrf(): bool
            {
                return true;
            }

            protected function getPostData(): array
            {
                return ['action' => 'calculate', 'scores' => [1 => '4']];
            }

            protected function render(string $view, array $data = []): void
            {
                $this->renderedData = $data;
            }
        };
        $controller->setAuth($auth);
        $controller->storeCard(1);
        return $controller->renderedData;
    }

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