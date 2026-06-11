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
        $this->assertStringContainsString('href="/results"', $html);
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

    private function invokePrivateMethod(string $methodName, array $args): string
    {
        $method = new \ReflectionMethod(SnapshotExportService::class, $methodName);
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->service, $args);
        $this->assertIsString($result);

        return $result;
    }
}
