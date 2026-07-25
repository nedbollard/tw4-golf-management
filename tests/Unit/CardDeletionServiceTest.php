<?php

namespace Tests\Unit;

use App\Core\Database;
use App\Services\CardDeletionService;
use PHPUnit\Framework\TestCase;

class CardDeletionServiceTest extends TestCase
{
    public function testDeleteNormalizesIdsRestoresPlayersAndCommits(): void
    {
        $database = $this->createMock(Database::class);
        $cards = [
            [
                'card_id' => 42,
                'row_id_player' => 10,
                'handicap_applied' => 18,
                'score' => 42,
                'points' => 19,
                'display_player' => 'JaneS',
                'player_identifier' => 'JaneS',
            ],
            [
                'card_id' => 43,
                'row_id_player' => 11,
                'handicap_applied' => 20,
                'score' => 45,
                'points' => 17,
                'display_player' => 'JohnD',
                'player_identifier' => 'JohnD',
            ],
        ];

        $database->expects($this->once())->method('beginTransaction');
        $database->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->callback(static fn(string $sql): bool => str_contains($sql, 'WHERE c.row_id IN (?,?)')),
                [42, 43]
            )
            ->willReturn($cards);
        $database->expects($this->exactly(2))
            ->method('delete')
            ->willReturnCallback(function (string $table, array $where): int {
                $this->assertSame('TW4_live.card', $table);
                $this->assertContains($where['row_id'], [42, 43]);
                return 1;
            });
        $database->expects($this->exactly(3))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params): \PDOStatement {
                if (str_contains($sql, 'UPDATE TW4_live.round')) {
                    $this->assertSame([3, 'admin', 5], $params);
                } else {
                    $this->assertStringContainsString('UPDATE TW4_base.roster', $sql);
                    $this->assertContains($params, [
                        [18, 'active', 'admin', 10],
                        [20, 'active', 'admin', 11],
                    ]);
                }
                return $this->createStub(\PDOStatement::class);
            });
        $database->expects($this->once())
            ->method('fetchOne')
            ->willReturn(['total' => 3]);
        $database->expects($this->once())->method('commit');
        $database->expects($this->never())->method('rollback');

        $result = (new CardDeletionService($database))->delete(5, ['42', 43, 42, 0, 'bad'], 'admin');

        $this->assertSame($cards, $result['deleted_cards']);
        $this->assertSame(3, $result['remaining_card_count']);
        $this->assertSame(['JaneS', 'JohnD'], array_column($result['deleted_players'], 'display_player'));
    }

    public function testMissingSelectedCardRollsBackWithoutDeleting(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->once())->method('beginTransaction');
        $database->expects($this->once())
            ->method('fetchAll')
            ->willReturn([['card_id' => 42, 'row_id_player' => 10]]);
        $database->expects($this->never())->method('delete');
        $database->expects($this->never())->method('commit');
        $database->expects($this->once())->method('rollback');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('One or more selected cards could not be found.');

        (new CardDeletionService($database))->delete(5, [42, 43], 'admin');
    }

    public function testEmptySelectionIsRejectedBeforeTransactionStarts(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->never())->method('beginTransaction');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Please select at least one card to delete.');

        (new CardDeletionService($database))->delete(5, [0, '', 'bad'], 'admin');
    }
}
