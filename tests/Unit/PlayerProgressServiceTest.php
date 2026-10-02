<?php

namespace Tests\Unit;

use App\Core\Database;
use App\Services\PlayerProgressService;
use PHPUnit\Framework\TestCase;

class PlayerProgressServiceTest extends TestCase
{
    public function testEligibilityUsesActiveStatusAndHistoryFromAnySeason(): void
    {
        $db = $this->createMock(Database::class);
        $players = [['row_id' => 7, 'player_identifier' => 'Player7', 'alias' => null, 'handicap' => 12]];
        $db->expects($this->once())->method('fetchAll')
            ->with($this->callback(function (string $sql): bool {
                $this->assertStringContainsString('r.status = "active"', $sql);
                $this->assertStringContainsString('FROM TW4_history.card hc', $sql);
                $this->assertStringContainsString('INNER JOIN TW4_history.round hr', $sql);
                $this->assertStringContainsString('hc.points IS NOT NULL', $sql);
                $this->assertStringNotContainsString('season_year = ?', $sql);
                $this->assertStringNotContainsString('TW4_live', $sql);
                return true;
            }))
            ->willReturn($players);
        $db->expects($this->never())->method('fetchOne');

        $this->assertSame($players, (new PlayerProgressService($db))->getEligiblePlayersWithHistory());
    }

    public function testAllSeasonsPreserveMissedRoundsAndResetHandicapMovement(): void
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('fetchOne')
            ->with($this->stringContains('status = "active"'), [7])
            ->willReturn(['row_id' => 7, 'status' => 'active']);
        $db->expects($this->once())->method('fetchAll')
            ->with($this->callback(function (string $sql): bool {
                $this->assertStringContainsString('SELECT hr.season_year', $sql);
                $this->assertStringContainsString('hc.season_year = hr.season_year', $sql);
                $this->assertStringContainsString('ha2.season_year = hr.season_year COLLATE utf8mb4_0900_ai_ci', $sql);
                $this->assertStringContainsString('season_card.season_year = hr.season_year', $sql);
                $this->assertStringContainsString('season_card.points IS NOT NULL', $sql);
                $this->assertStringContainsString('ORDER BY hr.season_year ASC, hr.number_round ASC', $sql);
                $this->assertStringNotContainsString('TW4_live', $sql);
                return true;
            }), [7, 7, 7])
            ->willReturn([
                $this->round('25_26', 1, 20, 12, 14),
                $this->round('25_26', 2, null, null, null),
                $this->round('25_26', 3, 0, 14, 13),
                $this->round('26_27', 1, null, null, null),
                $this->round('26_27', 2, 18, 18, 17),
            ]);

        $progress = (new PlayerProgressService($db))->getPlayerProgress(7);

        $this->assertSame(['25_26', '26_27'], $progress['seasons']);
        $this->assertCount(5, $progress['rounds']);
        $this->assertSame([1, 2, 3, 1, 2], array_column($progress['rounds'], 'number_round'));
        $this->assertSame([true, false, true, false, true], array_column($progress['rounds'], 'played'));
        $this->assertSame([], $progress['rounds'][1]['handicap_markers']);
        $this->assertSame(15.0, $progress['rounds'][2]['handicap_markers'][0]['level']);
        $this->assertSame(PlayerProgressService::HANDICAP_BASELINE_LEVEL, $progress['rounds'][4]['handicap_markers'][0]['level']);
        $this->assertSame(12.0, $progress['rounds'][4]['handicap_markers'][1]['level']);
        $this->assertSame('26_27', $progress['rounds'][4]['season_year']);
        $this->assertSame(0, $progress['rounds'][2]['points']);
    }

    public function testPreviousSeasonOnlyHistoryIsReturned(): void
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('fetchOne')->willReturn(['row_id' => 7, 'status' => 'active']);
        $db->expects($this->once())->method('fetchAll')->willReturn([$this->round('25_26', 1, 20, 12, 12)]);

        $progress = (new PlayerProgressService($db))->getPlayerProgress(7);

        $this->assertSame(['25_26'], $progress['seasons']);
        $this->assertCount(1, $progress['rounds']);
        $this->assertCount(1, $progress['rounds'][0]['handicap_markers']);
    }

    public function testMissingOrInactivePlayerCannotLoadHistory(): void
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('fetchOne')
            ->with($this->stringContains('WHERE row_id = ? AND status = "active"'), [7])
            ->willReturn(null);
        $db->expects($this->never())->method('fetchAll');

        $this->assertSame(
            ['player' => null, 'seasons' => [], 'rounds' => []],
            (new PlayerProgressService($db))->getPlayerProgress(7)
        );
    }

    public function testActivePlayerWithoutHistoryHasNoSeasons(): void
    {
        $db = $this->createMock(Database::class);
        $db->expects($this->once())->method('fetchOne')->willReturn(['row_id' => 7, 'status' => 'active']);
        $db->expects($this->once())->method('fetchAll')->willReturn([]);

        $progress = (new PlayerProgressService($db))->getPlayerProgress(7);

        $this->assertSame([], $progress['seasons']);
        $this->assertSame([], $progress['rounds']);
    }

    private function round(string $season, int $number, ?int $points, ?int $applied, ?int $updated): array
    {
        return [
            'season_year' => $season,
            'number_round' => $number,
            'round_date' => '',
            'course_name' => 'Test Course',
            'score' => $points !== null ? 40 : null,
            'points' => $points,
            'handicap_applied' => $applied,
            'handicap_updated' => $updated,
            'points_scored' => $points,
            'points_effective' => $points,
        ];
    }
}
