<?php

namespace Tests\Unit;

use App\Core\Database;
use App\Services\HandicapUpdateService;
use PHPUnit\Framework\TestCase;

class HandicapUpdateServiceTest extends TestCase
{
    public function testModernMethodAppliesThresholdsClampsAndAuditsRosterChanges(): void
    {
        $database = $this->createMock(Database::class);
        $cardRows = [
            ['card_row_id' => 1, 'handicap_applied' => 18, 'pts_adjusted' => 14],
            ['card_row_id' => 2, 'handicap_applied' => 20, 'pts_adjusted' => 22],
            ['card_row_id' => 3, 'handicap_applied' => 53, 'pts_adjusted' => 10],
            ['card_row_id' => 4, 'handicap_applied' => 2, 'pts_adjusted' => 24],
        ];
        $changedRows = [
            [
                'row_id_player' => 10,
                'handicap_previous' => 18,
                'handicap_new' => 20,
                'points_scored' => 13,
                'points_effective' => 14,
            ],
            [
                'row_id_player' => 11,
                'handicap_previous' => 20,
                'handicap_new' => 18,
                'points_scored' => 22,
                'points_effective' => 22,
            ],
        ];

        $database->expects($this->once())
            ->method('fetchOne')
            ->with($this->stringContains('config_name = ?'), ['handicap_method'])
            ->willReturn(['method' => 'modern']);
        $database->expects($this->exactly(2))
            ->method('fetchAll')
            ->willReturnOnConsecutiveCalls($cardRows, $changedRows);

        $expectedCardUpdates = [
            [20, 'admin', 1],
            [18, 'admin', 2],
            [54, 'admin', 3],
            [0, 'admin', 4],
        ];
        $database->expects($this->exactly(5))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params) use (&$expectedCardUpdates): \PDOStatement {
                if (str_contains($sql, 'UPDATE TW4_live.card SET handicap_updated')) {
                    $this->assertSame(array_shift($expectedCardUpdates), $params);
                } else {
                    $this->assertStringContainsString('UPDATE TW4_base.roster', $sql);
                    $this->assertSame(['admin'], $params);
                }
                return $this->createStub(\PDOStatement::class);
            });

        $expectedAudits = [
            [10, 18, 20, 13, 14],
            [11, 20, 18, 22, 22],
        ];
        $database->expects($this->exactly(2))
            ->method('insert')
            ->willReturnCallback(function (string $table, array $data) use (&$expectedAudits): int {
                $this->assertSame('TW4_base.handicap_audit', $table);
                [$playerId, $previous, $new, $scored, $effective] = array_shift($expectedAudits);
                $this->assertSame($playerId, $data['row_id_player']);
                $this->assertSame($previous, $data['handicap_previous']);
                $this->assertSame($new, $data['handicap_new']);
                $this->assertSame($scored, $data['points_scored']);
                $this->assertSame($effective, $data['points_effective']);
                $this->assertSame('card_scoring', $data['handicap_source']);
                $this->assertSame('25_26', $data['season_year']);
                $this->assertSame(12, $data['number_round']);
                $this->assertSame('finish_round_card_scoring', $data['reason']);
                $this->assertSame('admin', $data['updated_by']);
                return 1;
            });

        (new HandicapUpdateService($database))->applyForFinishedRound('admin', '25_26', 12);

        $this->assertSame([], $expectedCardUpdates);
        $this->assertSame([], $expectedAudits);
    }

    public function testLegacyMethodAppliesEveryBand(): void
    {
        $database = $this->createMock(Database::class);
        $pointsByCard = [8, 9, 13, 17, 22, 24, 26, 28];
        $cardRows = [];
        foreach ($pointsByCard as $index => $points) {
            $cardRows[] = [
                'card_row_id' => $index + 1,
                'handicap_applied' => 20,
                'pts_adjusted' => $points,
            ];
        }

        $database->expects($this->once())
            ->method('fetchOne')
            ->willReturn(['method' => 'legacy']);
        $database->expects($this->exactly(2))
            ->method('fetchAll')
            ->willReturnOnConsecutiveCalls($cardRows, []);

        $expectedHandicaps = [16, 22, 21, 20, 19, 18, 17, 16];
        $database->expects($this->exactly(9))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params) use (&$expectedHandicaps): \PDOStatement {
                if (str_contains($sql, 'UPDATE TW4_live.card SET handicap_updated')) {
                    $this->assertSame(array_shift($expectedHandicaps), $params[0]);
                } else {
                    $this->assertStringContainsString('UPDATE TW4_base.roster', $sql);
                }
                return $this->createStub(\PDOStatement::class);
            });
        $database->expects($this->never())->method('insert');

        (new HandicapUpdateService($database))->applyForFinishedRound('admin', '25_26', 12);

        $this->assertSame([], $expectedHandicaps);
    }
}
