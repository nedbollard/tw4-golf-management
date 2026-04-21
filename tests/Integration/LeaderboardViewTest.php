<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class LeaderboardViewTest extends TestCase
{
    public function testLeaderboardViewExists(): void
    {
        $viewFile = __DIR__ . '/../../src/Views/scores/leaderboard.php';
        $this->assertFileExists($viewFile);
    }

    public function testLeaderboardViewShowsExpectedSectionsAndNoPrizeColumn(): void
    {
        $html = $this->renderLeaderboardView();

        $this->assertStringContainsString('Leaderboard', $html);
        $this->assertStringContainsString('Scoring Progress', $html);
        $this->assertStringNotContainsString('Ball Winners', $html);
        $this->assertStringNotContainsString('<th>Prize</th>', $html);
        $this->assertStringContainsString('setInterval(function () {', $html);
        $this->assertStringContainsString('window.location.reload();', $html);
        $this->assertStringContainsString('20000', $html);
    }

    public function testLeaderboardViewShowsPublishedResultsNudgeWhenRoundInactive(): void
    {
        $html = $this->renderLeaderboardView(true);

        $this->assertStringContainsString('No live round is active yet.', $html);
        $this->assertStringContainsString('View published results', $html);
        $this->assertStringContainsString('href="/results"', $html);
    }

    public function testLeaderboardViewDoesNotRenderCommiserationsPanel(): void
    {
        $html = $this->renderLeaderboardView();

        $this->assertStringNotContainsString('Commiserations', $html);
    }

    private function renderLeaderboardView(bool $showPublishedResultsNudge = false): string
    {
        $viewPath = __DIR__ . '/../../src/Views/scores/leaderboard.php';

        $app_title = 'TW4 Test';
        $round = [
            'round_number' => 9,
            'workflow_step' => 'card_entry_open',
        ];
        $notice = null;
        if ($showPublishedResultsNudge) {
            $notice = 'No live round is active yet.';
        }
        $resultsData = [
            'leaderboard' => [
                [
                    'position' => 1,
                    'display_name' => 'JoanD',
                    'player_identifier' => 'JoanD',
                    'points' => 26,
                    'score' => 44,
                    'handicap' => 38,
                    'countback1' => 3,
                    'countback3' => 10,
                    'countback6' => 16,
                    'coin_toss' => 65,
                    'countback_decision' => 'n/a',
                    'twos_count' => 1,
                ],
            ],
        ];

        ob_start();
        require $viewPath;
        return (string) ob_get_clean();
    }
}
