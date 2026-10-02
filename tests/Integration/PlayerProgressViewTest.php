<?php

namespace Tests\Integration;

use App\Core\Database;
use App\Services\PlayerProgressService;
use PHPUnit\Framework\TestCase;

class PlayerProgressViewTest extends TestCase
{
    public function testChartLabelsSeasonsAndKeepsTrendLinesSeparate(): void
    {
        $html = $this->renderChart();
        $xpath = $this->xpath($html);

        $this->assertStringContainsString('Seasons shown</strong> 25/26, 26/27', $html);
        $this->assertStringContainsString('Rounds (all seasons)</strong> 2', $html);
        $this->assertStringContainsString('Total Points (all seasons)</strong> 38', $html);
        $this->assertStringContainsString('Latest Handicap</strong> 17', $html);
        $this->assertStringContainsString('Season 25/26 | R1 | 03/10/2025', $html);
        $this->assertStringContainsString('Season 26/27 | R1 | 02/10/2026', $html);
        $this->assertStringContainsString('Season 25/26 | R2 | 10/10/2025 | Missed round', $html);
        $this->assertStringNotContainsString('Real First', $html);
        $this->assertSame(2, $xpath->query('//text[@class="progress-season-label"]')->length);
        $this->assertSame(1, $xpath->query('//line[@class="progress-season-separator"]')->length);
        $this->assertSame(2, $xpath->query('//rect[@class="progress-bar"]')->length);
        $this->assertSame(2, $xpath->query('//polyline[@class="progress-handicap-trend-line"]')->length);
        $this->assertSame(2, $xpath->query('//text[@class="progress-round-label" and text()="R1"]')->length);

        $markers = $xpath->query('//circle[@class="progress-handicap-circle progress-handicap-start"]');
        $this->assertSame(2, $markers->length);
        $firstY = (int) $markers->item(0)->getAttribute('cy');
        $secondY = (int) $markers->item(1)->getAttribute('cy');
        // 12 and 18 are the independent season baselines on a 27-point, 280px axis.
        $this->assertSame(210, $firstY);
        $this->assertSame(147, $secondY);
    }

    public function testPreviousSeasonOnlyChartAndBlankAliasFallback(): void
    {
        $html = $this->renderChart(true, '');

        $this->assertStringContainsString('Player: Player7', $html);
        $this->assertStringContainsString('Season 25/26', $html);
        $this->assertStringNotContainsString('Season 26/27', $html);
        $this->assertStringContainsString('Total Points (all seasons)</strong> 20', $html);
    }

    public function testSelectorExplainsAllSeasonsAndPreservesPublicIdentity(): void
    {
        $playerOptions = [['row_id' => 7, 'alias' => '', 'player_identifier' => 'Player7', 'first_name' => 'Real First']];
        $selectedPlayerId = 7;
        $app_title = 'TW4 Test';
        $notice = null;
        ob_start();
        require __DIR__ . '/../../src/Views/player-progress/index.php';
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('All recorded seasons', $html);
        $this->assertStringContainsString('Unfinished live rounds are not included.', $html);
        $this->assertStringContainsString('Player7', $html);
        $this->assertStringNotContainsString('Real First', $html);
    }

    public function testLongHistoryHasScrollableNaturalWidth(): void
    {
        $progress = $this->progress();
        $template = $progress['rounds'][0];
        $progress['rounds'] = [];
        $progress['seasons'] = ['25_26'];
        for ($number = 1; $number <= 20; $number++) {
            $round = $template;
            $round['number_round'] = $number;
            $progress['rounds'][] = $round;
        }
        $xpath = $this->xpath($this->renderProgress($progress, 'Alias7'));
        $svg = $xpath->query('//svg')->item(0);

        $this->assertSame('1486', $svg->getAttribute('width'));
        $this->assertSame('0 0 1486 404', $svg->getAttribute('viewbox'));
    }

    private function renderChart(bool $previousOnly = false, string $alias = 'Alias7'): string
    {
        $progress = $this->progress();
        if ($previousOnly) {
            $progress['seasons'] = ['25_26'];
            $progress['rounds'] = array_slice($progress['rounds'], 0, 2);
        }
        return $this->renderProgress($progress, $alias);
    }

    private function renderProgress(array $progress, string $alias): string
    {
        $selectedPlayer = ['row_id' => 7, 'alias' => $alias, 'player_identifier' => 'Player7', 'first_name' => 'Real First', 'handicap' => 17];
        $selectedPlayerId = 7;
        $app_title = 'TW4 Test';
        $notice = null;
        ob_start();
        require __DIR__ . '/../../src/Views/player-progress/chart.php';
        return (string) ob_get_clean();
    }

    private function progress(): array
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('fetchOne')->willReturn(['row_id' => 7, 'status' => 'active']);
        $db->expects($this->once())->method('fetchAll')->willReturn([
            ['season_year' => '25_26', 'number_round' => 1, 'round_date' => '2025-10-03', 'points' => 20, 'handicap_applied' => 12, 'handicap_updated' => 14],
            ['season_year' => '25_26', 'number_round' => 2, 'round_date' => '2025-10-10', 'points' => null],
            ['season_year' => '26_27', 'number_round' => 1, 'round_date' => '2026-10-02', 'points' => 18, 'handicap_applied' => 18, 'handicap_updated' => 17],
        ]);
        return (new PlayerProgressService($db))->getPlayerProgress(7);
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        return new \DOMXPath($document);
    }
}
