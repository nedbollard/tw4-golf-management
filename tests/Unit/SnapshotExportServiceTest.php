<?php

namespace Tests\Unit;

use App\Core\Database;
use App\Services\SnapshotExportService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class SnapshotExportServiceTest extends TestCase
{
    private SnapshotExportService $service;

    protected function setUp(): void
    {
        /** @var Database|MockObject $db */
        $db = $this->createMock(Database::class);
        $this->service = new SnapshotExportService($db);
    }

    public function testRenderHandicapsIncludesTopNavigationButtons(): void
    {
        $ctx = [
            'name_club' => 'OVGC',
            'season_year' => '25_26',
            'number_round' => 8,
            'round_date' => '2026-06-10',
            'handicap_snapshot' => [],
        ];

        $html = $this->invokePrivateMethod('renderHandicaps', [$ctx]);

        $this->assertStringContainsString('Back to Reports', $html);
        $this->assertStringContainsString('href="/results?season=25_26&amp;round=008_Jun_10"', $html);
        $this->assertStringContainsString('Back to Main Menu', $html);
        $this->assertStringContainsString('href="/"', $html);
    }

    public function testRenderHandicapsSuppressesDefaultCardScoringReason(): void
    {
        $ctx = [
            'name_club' => 'OVGC',
            'season_year' => '25_26',
            'number_round' => 8,
            'round_date' => '2026-06-10',
            'handicap_snapshot' => [
                [
                    'player_identifier' => 'P1',
                    'alias' => 'Alias_Alice',
                    'current_handicap' => 8,
                    'handicap_previous' => 6,
                    'handicap_source' => 'card_scoring',
                    'reason' => 'finish_round_card_scoring',
                    'audit_season_year' => '25_26',
                    'audit_number_round' => 8,
                ],
            ],
        ];

        $html = $this->invokePrivateMethod('renderHandicaps', [$ctx]);

        $this->assertStringContainsString('<td>Alias_Alice</td>', $html);
        $this->assertStringContainsString('<td>+2</td><td>8</td><td>card_scoring</td><td></td>', $html);
        $this->assertStringContainsString('<td>card_scoring</td><td></td>', $html);
        $this->assertStringNotContainsString('finish_round_card_scoring</td>', $html);
    }

    public function testRenderHandicapsShowsFallbacksAndNonSuppressedReason(): void
    {
        $ctx = [
            'name_club' => 'OVGC',
            'season_year' => '25_26',
            'number_round' => 9,
            'round_date' => '2026-06-10',
            'handicap_snapshot' => [
                [
                    'player_identifier' => 'P2',
                    'alias' => 'BobbyBee',
                    'current_handicap' => 1,
                    'handicap_previous' => 4,
                    'handicap_source' => 'card_scoring',
                    'reason' => 'finish_round_card_scoring_backfill',
                    'audit_season_year' => '24_25',
                    'audit_number_round' => 7,
                ],
                [
                    'player_identifier' => 'P4',
                    'alias' => 'D4',
                    'current_handicap' => 5,
                    'handicap_previous' => null,
                    'handicap_source' => null,
                    'reason' => null,
                    'audit_season_year' => null,
                    'audit_number_round' => null,
                ],
            ],
        ];

        $html = $this->invokePrivateMethod('renderHandicaps', [$ctx]);

        $this->assertStringContainsString('finish_round_card_scoring_backfill', $html);
        $this->assertStringContainsString('<td>7 [24_25]</td>', $html);
        $this->assertStringContainsString('<td>n/a</td><td>n/a</td>', $html);
    }

    public function testRenderSmallBeerShowsTopThreeDistinctSeasonTotals(): void
    {
        $ctx = [
            'name_club' => 'OVGC',
            'season_year' => '25_26',
            'number_round' => 14,
            'round_date' => '2026-01-21',
            'small_beer_money' => [
                ['display_player' => 'Winnie', 'value_total' => 34],
                ['display_player' => 'PaulC', 'value_total' => 25],
                ['display_player' => 'ApiW', 'value_total' => 25],
                ['display_player' => 'MichealH', 'value_total' => 22],
                ['display_player' => 'Ignored', 'value_total' => 18],
            ],
            'small_beer_baggers' => [
                ['display_player' => 'ApiW', 'value_total' => 3],
                ['display_player' => 'Big Muz', 'value_total' => 2],
                ['display_player' => 'JonG', 'value_total' => 2],
                ['display_player' => 'JimG', 'value_total' => 2],
                ['display_player' => 'PremS', 'value_total' => 1],
                ['display_player' => 'Ignored', 'value_total' => 0],
            ],
            'small_beer_attendance' => [
                ['display_player' => 'MichealH', 'value_total' => 12],
                ['display_player' => 'Big Muz', 'value_total' => 10],
                ['display_player' => 'PatK', 'value_total' => 10],
                ['display_player' => 'PaulC', 'value_total' => 10],
                ['display_player' => 'ApiW', 'value_total' => 9],
                ['display_player' => 'ColinB', 'value_total' => 9],
                ['display_player' => 'MikeH', 'value_total' => 9],
                ['display_player' => 'Ignored', 'value_total' => 8],
            ],
        ];

        $html = $this->invokePrivateMethod('renderSmallBeer', [$ctx]);

        $this->assertStringContainsString('Money List', $html);
        $this->assertStringContainsString('Ball Baggers', $html);
        $this->assertStringContainsString('Best Attendance', $html);
        $this->assertStringContainsString('<td>Winnie</td><td>$34.00</td>', $html);
        $this->assertStringContainsString('<td>PaulC</td><td>$25.00</td>', $html);
        $this->assertStringContainsString('<td>ApiW</td><td>$25.00</td>', $html);
        $this->assertStringContainsString('<td>Big Muz</td><td>2</td>', $html);
        $this->assertStringContainsString('<td>JimG</td><td>2</td>', $html);
        $this->assertStringContainsString('<td>MichealH</td><td>12</td>', $html);
        $this->assertStringContainsString('<td>MikeH</td><td>9</td>', $html);
    }

    public function testLimitToTopDistinctValuesKeepsTiesAndDropsLowerValues(): void
    {
        $rows = [
            ['display_player' => 'Winnie', 'value_total' => 34],
            ['display_player' => 'PaulC', 'value_total' => 25],
            ['display_player' => 'ApiW', 'value_total' => 25],
            ['display_player' => 'MichealH', 'value_total' => 22],
            ['display_player' => 'Ignored', 'value_total' => 18],
        ];

        $method = new \ReflectionMethod(SnapshotExportService::class, 'limitToTopDistinctValues');
        $method->setAccessible(true);

        /** @var array<int, array<string, mixed>> $filtered */
        $filtered = $method->invoke($this->service, $rows, 'value_total', 3);

        $this->assertCount(4, $filtered);
        $this->assertSame([34, 25, 25, 22], array_map(static fn(array $row): int => (int) $row['value_total'], $filtered));
    }

    public function testRenderMovementsShowsBestFiveMovementBeforeTotalPoints(): void
    {
        $ctx = [
            'name_club' => 'OVGC',
            'season_year' => '25_26',
            'number_round' => 8,
            'round_date' => '2026-06-10',
            'name_course' => 'Whites',
            'movement_handicaps' => [],
            'movement_best_five' => [
                [
                    'display_player' => 'Alias_Alice',
                    'points_movement' => 3,
                    'points_total' => 41,
                    'points_best_1' => 12,
                    'points_best_2' => 10,
                    'points_best_3' => 8,
                    'points_best_4' => 6,
                    'points_best_5' => 5,
                ],
            ],
        ];

        $html = $this->invokePrivateMethod('renderMovements', [$ctx]);

        $this->assertStringContainsString('<th>Player</th><th>Movement</th><th>Total Points</th>', $html);
        $this->assertStringContainsString('<td>Alias_Alice</td><td>3</td><td>41</td>', $html);
    }

    public function testBuildRoundHandicapMovementsOrdersLargestDropsFirst(): void
    {
        /** @var Database|MockObject $db */
        $db = $this->createMock(Database::class);
        $db->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->stringContains('ORDER BY (ha.handicap_new - ha.handicap_previous) ASC'),
                ['25_26', 8]
            )
            ->willReturn([]);

        $service = new SnapshotExportService($db);

        $method = new \ReflectionMethod(SnapshotExportService::class, 'buildRoundHandicapMovements');
        $method->setAccessible(true);
        $result = $method->invoke($service, '25_26', 8);

        $this->assertSame([], $result);
    }

    public function testSnapshotDefinitionsIncludeRenumberedEclecticAndShiftedReports(): void
    {
        $definitions = SnapshotExportService::snapshotDefinitions();
        $filenames = array_map(static fn(array $row): string => (string) ($row['filename'] ?? ''), $definitions);

        $this->assertNotContains('35_Eclectic_Haggle.html', $filenames);
        $this->assertContains('33_Best_5_Haggle.html', $filenames);
        $this->assertContains('41_Eclectic_%COURSE_A%.html', $filenames);
        $this->assertContains('42_Eclectic_%COURSE_B%.html', $filenames);
        $this->assertContains('49_Eclectic_%COURSE_C%.html', $filenames);
        $this->assertContains('51_Small_Beer.html', $filenames);
        $this->assertContains('61_Handicaps.html', $filenames);
    }

    public function testRenderMovementsIncludesEclecticMovementSections(): void
    {
        $ctx = [
            'name_club' => 'OVGC',
            'season_year' => '25_26',
            'number_round' => 8,
            'round_date' => '2026-06-10',
            'name_course' => 'Whites',
            'eclectic_played_name' => 'Whites',
            'eclectic_combined_name' => 'Eclectic',
            'movement_handicaps' => [],
            'movement_best_five' => [],
            'eclectic_played_movement' => [
                ['display_player' => 'P1', 'score_movement' => 2, 'score_total' => 41],
            ],
            'eclectic_combined_movement' => [
                ['display_player' => 'P2', 'score_movement' => 1, 'score_total' => 39],
            ],
        ];

        $html = $this->invokePrivateMethod('renderMovements', [$ctx]);

        $this->assertStringContainsString('Eclectic Movements (Whites)', $html);
        $this->assertStringContainsString('Eclectic Movements (Eclectic)', $html);
        $this->assertStringContainsString('<td>P1</td><td>2</td><td>41</td>', $html);
        $this->assertStringContainsString('<td>P2</td><td>1</td><td>39</td>', $html);
    }

    private function invokePrivateMethod(string $methodName, array $args): string
    {
        $method = new \ReflectionMethod(SnapshotExportService::class, $methodName);
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->service, $args);
        $this->assertIsString($result);

        return $result;
    }
}
