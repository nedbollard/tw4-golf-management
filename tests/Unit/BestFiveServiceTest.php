<?php

namespace Tests\Unit;

use App\Core\Database;
use App\Services\BestFiveService;
use PHPUnit\Framework\TestCase;

class BestFiveServiceTest extends TestCase
{
    public function testRefreshMergesRanksAndPreservesUnchangedMovementMetadata(): void
    {
        $database = $this->createMock(Database::class);
        $holdingRows = [
            $this->holdingRow(101, [25, 20, 15, 10, 5], [1, 1, 2, 2, 3], 75, 2, 10),
            $this->holdingRow(102, [20, 19, 18, 17, 16], [1, 2, 3, 4, 5], 90, 5, 4),
        ];
        $database->expects($this->exactly(2))
            ->method('fetchAll')
            ->willReturnOnConsecutiveCalls($holdingRows, [
                ['row_id_player' => 101, 'points' => 28],
            ]);
        $database->expects($this->once())
            ->method('query')
            ->with('DELETE FROM TW4_live.best_five_scores');

        $inserted = [];
        $database->expects($this->exactly(2))
            ->method('insert')
            ->willReturnCallback(function (string $table, array $data) use (&$inserted): int {
                $this->assertSame('TW4_live.best_five_scores', $table);
                $inserted[$data['row_id_player']] = $data;
                return count($inserted);
            });

        (new BestFiveService($database))->refreshForFinish('25_26', 6, 'admin');

        $this->assertSame([28, 25, 20, 15, 10], $this->bestPoints($inserted[101]));
        $this->assertSame([6, 1, 1, 2, 2], $this->bestRounds($inserted[101]));
        $this->assertSame(98, $inserted[101]['points_total']);
        $this->assertSame(23, $inserted[101]['points_movement']);
        $this->assertSame(6, $inserted[101]['number_round_movement']);

        $this->assertSame([20, 19, 18, 17, 16], $this->bestPoints($inserted[102]));
        $this->assertSame(90, $inserted[102]['points_total']);
        $this->assertSame(4, $inserted[102]['points_movement']);
        $this->assertSame(5, $inserted[102]['number_round_movement']);
        $this->assertSame('admin', $inserted[102]['updated_by']);
    }

    public function testEqualScoresPreferEarlierRound(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->exactly(2))
            ->method('fetchAll')
            ->willReturnOnConsecutiveCalls(
                [$this->holdingRow(101, [25, 20, 15, 10, 5], [1, 2, 3, 4, 7], 75, 4, 3)],
                [['row_id_player' => 101, 'points' => 5]]
            );
        $database->method('query');
        $database->expects($this->once())
            ->method('insert')
            ->with(
                'TW4_live.best_five_scores',
                $this->callback(function (array $data): bool {
                    $this->assertSame(5, $data['points_best_5']);
                    $this->assertSame(6, $data['round_best_5']);
                    $this->assertSame(4, $data['number_round_movement']);
                    $this->assertSame(3, $data['points_movement']);
                    return true;
                })
            )
            ->willReturn(1);

        (new BestFiveService($database))->refreshForFinish('25_26', 6, 'admin');
    }

    private function holdingRow(
        int $playerId,
        array $points,
        array $rounds,
        int $total,
        int $movementRound,
        int $movementPoints
    ): array {
        $row = [
            'season_year' => '25_26',
            'row_id_player' => $playerId,
            'points_total' => $total,
            'number_round_movement' => $movementRound,
            'points_movement' => $movementPoints,
        ];
        for ($index = 1; $index <= 5; $index++) {
            $row['points_best_' . $index] = $points[$index - 1];
            $row['round_best_' . $index] = $rounds[$index - 1];
        }
        return $row;
    }

    private function bestPoints(array $row): array
    {
        return array_map(static fn(int $index): int => $row['points_best_' . $index], range(1, 5));
    }

    private function bestRounds(array $row): array
    {
        return array_map(static fn(int $index): int => $row['round_best_' . $index], range(1, 5));
    }
}
