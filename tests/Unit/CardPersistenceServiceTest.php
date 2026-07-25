<?php

namespace Tests\Unit;

use App\Core\Database;
use App\Services\CardPersistenceService;
use PHPUnit\Framework\TestCase;

class CardPersistenceServiceTest extends TestCase
{
    public function testSaveNewCardCommitsCardHolesRoundCountAndRosterState(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->once())->method('beginTransaction');
        $database->expects($this->exactly(3))
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls(null, null, ['card_count' => 1]);
        $database->expects($this->exactly(2))
            ->method('insert')
            ->willReturnCallback(function (string $table, array $data): int {
                if ($table === 'TW4_live.card') {
                    $this->assertSame([
                        'row_id_player' => 10,
                        'handicap_applied' => 18,
                        'handicap_updated' => 18,
                        'score' => 42,
                        'points' => 19,
                        'updated_by' => 'scorer',
                    ], $data);
                    return 42;
                }

                $this->assertSame('TW4_live.card_by_hole', $table);
                $this->assertSame(42, $data['row_id_card']);
                $this->assertSame(1, $data['hole']);
                $this->assertSame(5, $data['score']);
                return 1;
            });
        $database->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params): \PDOStatement {
                if (str_contains($sql, 'UPDATE TW4_live.round')) {
                    $this->assertSame([1, 'scorer', 5], $params);
                } else {
                    $this->assertStringContainsString('UPDATE TW4_base.roster', $sql);
                    $this->assertSame(['scored', 'scorer', 10, 'active'], $params);
                }
                return $this->createStub(\PDOStatement::class);
            });
        $database->expects($this->once())->method('commit');
        $database->expects($this->never())->method('rollback');

        (new CardPersistenceService($database))->save(5, 10, $this->validEntryData(), 'scorer');
    }

    public function testValidationErrorsAreRejectedBeforeTransactionStarts(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->never())->method('beginTransaction');
        $database->expects($this->never())->method('commit');
        $database->expects($this->never())->method('rollback');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot save card with validation errors.');

        (new CardPersistenceService($database))->save(5, 10, ['errors' => ['bad score']], 'scorer');
    }

    public function testPersistenceFailureRollsBackAndRethrows(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->once())->method('beginTransaction');
        $database->expects($this->once())
            ->method('fetchOne')
            ->willThrowException(new \RuntimeException('database failed'));
        $database->expects($this->never())->method('commit');
        $database->expects($this->once())->method('rollback');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('database failed');

        (new CardPersistenceService($database))->save(5, 10, $this->validEntryData(), 'scorer');
    }

    private function validEntryData(): array
    {
        return [
            'errors' => [],
            'player' => ['handicap' => 18],
            'totals' => ['score' => 42, 'points' => 19],
            'holes' => [
                ['hole' => 1, 'score' => 5, 'shots' => 1, 'points' => 2],
            ],
        ];
    }
}
