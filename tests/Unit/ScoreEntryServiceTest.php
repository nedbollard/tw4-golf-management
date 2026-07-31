<?php

namespace Tests\Unit;

use App\Core\Database;
use App\Services\ScoreEntryService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ScoreEntryServiceTest extends TestCase
{
    public function testInitialEntryExcludesPlayersWithSavedScores(): void
    {
        /** @var Database|MockObject $db */
        $db = $this->createMock(Database::class);
        $players = [['row_id' => 12, 'card_id' => 34]];

        $db->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->stringContains('card_entry_reopened'),
                [7]
            )
            ->willReturn(['card_entry_reopened' => 0]);

        $db->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->callback(function (string $sql): bool {
                    $this->assertStringContainsString('c.row_id_player = r.row_id', $sql);
                    $this->assertStringContainsString('r.status = "active"', $sql);
                    $this->assertStringNotContainsString('r.status IN ("active", "scored")', $sql);
                    $this->assertStringNotContainsString('row_id_round', $sql);
                    $this->assertStringNotContainsString('c.round_id = ?', $sql);
                    return true;
                })
            )
            ->willReturn($players);

        $service = new ScoreEntryService($db);

        $this->assertSame($players, $service->getSelectablePlayers(7));
    }

    public function testEntryFollowingResetIncludesPlayersWithSavedScores(): void
    {
        /** @var Database|MockObject $db */
        $db = $this->createMock(Database::class);
        $players = [['row_id' => 12, 'card_id' => 34]];

        $db->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->stringContains('card_entry_reopened'),
                [7]
            )
            ->willReturn(['card_entry_reopened' => 1]);

        $db->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->callback(function (string $sql): bool {
                    $this->assertStringContainsString('c.row_id_player = r.row_id', $sql);
                    $this->assertStringContainsString('r.status IN ("active", "scored")', $sql);
                    return true;
                })
            )
            ->willReturn($players);

        $service = new ScoreEntryService($db);

        $this->assertSame($players, $service->getSelectablePlayers(7));
    }
}