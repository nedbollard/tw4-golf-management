<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class ResultsRecordedViewTest extends TestCase
{
    public function testResultsRecordedViewExists(): void
    {
        $viewFile = __DIR__ . '/../../src/Views/scores/results-recorded.php';
        $this->assertFileExists($viewFile);
    }

    public function testResultsRecordedViewShowsCoreSections(): void
    {
        $html = $this->renderResultsRecordedView([]);

        $this->assertStringContainsString('Results Recorded - Round', $html);
        $this->assertStringContainsString('Results</h3>', $html);
        $this->assertStringContainsString('Ball Winners</h3>', $html);
        $this->assertStringContainsString('$6.00', $html);
        $this->assertStringContainsString('Back', $html);
        $this->assertStringContainsString('Home', $html);
    }

    public function testResultsRecordedViewShowsCommiserationsWhenProvided(): void
    {
        $commiserations = [
            [
                'display_name' => 'Player A',
                'points' => 18,
                'countback_decision' => 'last 3',
            ],
            [
                'display_name' => 'Player B',
                'points' => 18,
                'countback_decision' => 'coin toss',
            ],
        ];

        $html = $this->renderResultsRecordedView($commiserations);

        $this->assertStringContainsString('Commiserations', $html);
        $this->assertStringContainsString('Player A - 18 points (last 3)', $html);
        $this->assertStringContainsString('Player B - 18 points (coin toss)', $html);
    }

    private function renderResultsRecordedView(array $commiserations): string
    {
        $viewPath = __DIR__ . '/../../src/Views/scores/results-recorded.php';

        $app_title = 'TW4 Test';
        $round = ['round_number' => 17];
        $success = 'Results stored for this live round.';
        $recordedData = [
            'podium' => [
                [
                    'position' => 1,
                    'prize' => 6,
                    'display_name' => 'JoanD',
                    'points' => 26,
                    'score' => 44,
                    'handicap' => 38,
                    'countback1' => 3,
                    'countback3' => 10,
                    'countback6' => 16,
                    'coin_toss' => 65,
                    'countback_decision' => 'n/a',
                ],
            ],
            'ball_winners' => [
                ['type' => 'twos', 'who' => 'Big Muz', 'count' => 2],
                ['type' => 'C_P', 'who' => 'PatK', 'count' => 1],
            ],
            'commiserations' => $commiserations,
        ];

        ob_start();
        require $viewPath;
        return (string) ob_get_clean();
    }
}
