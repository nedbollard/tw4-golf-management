<?php

namespace Tests\Unit;

use App\Core\Database;
use App\Services\RoundWorkflowService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RoundWorkflowServiceTest extends TestCase
{
    public function testGetStartRoundFormDataResetsRoundNumberWhenSeasonChanges(): void
    {
        /** @var Database|MockObject $db */
        $db = $this->createMock(Database::class);

        $db->expects($this->exactly(3))
            ->method('fetchOne')
            ->willReturnCallback(static function (string $sql, array $params = []): ?array {
                if (str_contains($sql, 'FROM TW4_live.round') && str_contains($sql, 'ORDER BY row_id ASC')) {
                    return [
                        'round_id' => 7,
                        'season_year' => '24_25',
                        'round_number' => 18,
                        'workflow_step' => 'not_started',
                    ];
                }

                if (str_contains($sql, 'FROM TW4_base.config_application') && $params === ['season_year']) {
                    return ['config_value_string' => '25_26'];
                }

                if (str_contains($sql, 'FROM TW4_base.config_application') && $params === ['club_number']) {
                    return ['club_number' => 294];
                }

                return null;
            });

        $db->expects($this->once())
            ->method('fetchAll')
            ->with($this->stringContains('FROM TW4_base.course_played'))
            ->willReturn([
                ['row_id' => 3, 'name_course' => 'Whites', 'name_club' => 'TW4'],
                ['row_id' => 4, 'name_course' => 'Blues', 'name_club' => 'TW4'],
            ]);

        $service = new RoundWorkflowService($db);
        $formData = $service->getStartRoundFormData();

        $this->assertSame('25_26', $formData['current_season_year']);
        $this->assertSame(1, $formData['default_round_number']);
    }

    public function testGetStartRoundFormDataIncrementsRoundNumberWithinSeason(): void
    {
        /** @var Database|MockObject $db */
        $db = $this->createMock(Database::class);

        $db->expects($this->exactly(3))
            ->method('fetchOne')
            ->willReturnCallback(static function (string $sql, array $params = []): ?array {
                if (str_contains($sql, 'FROM TW4_live.round') && str_contains($sql, 'ORDER BY row_id ASC')) {
                    return [
                        'round_id' => 7,
                        'season_year' => '25_26',
                        'round_number' => 18,
                        'workflow_step' => 'not_started',
                    ];
                }

                if (str_contains($sql, 'FROM TW4_base.config_application') && $params === ['season_year']) {
                    return ['config_value_string' => '25_26'];
                }

                if (str_contains($sql, 'FROM TW4_base.config_application') && $params === ['club_number']) {
                    return ['club_number' => 294];
                }

                return null;
            });

        $db->expects($this->once())
            ->method('fetchAll')
            ->with($this->stringContains('FROM TW4_base.course_played'))
            ->willReturn([
                ['row_id' => 3, 'name_course' => 'Whites', 'name_club' => 'TW4'],
            ]);

        $service = new RoundWorkflowService($db);
        $formData = $service->getStartRoundFormData();

        $this->assertSame('25_26', $formData['current_season_year']);
        $this->assertSame(19, $formData['default_round_number']);
    }

    public function testAdminResetResultsToCardEntryClearsResultsAndUpdatesRound(): void
    {
        /** @var Database|MockObject $db */
        $db = $this->createMock(Database::class);

        $db->expects($this->once())
            ->method('beginTransaction');

        $db->expects($this->once())
            ->method('commit');

        $db->expects($this->never())
            ->method('rollback');

        $db->expects($this->exactly(3))
            ->method('fetchOne')
            ->willReturnCallback(static function (string $sql, array $params = []): ?array {
                if (str_contains($sql, 'FROM TW4_live.round') && str_contains($sql, 'ORDER BY row_id ASC')) {
                    return [
                        'round_id' => 7,
                        'round_number' => 42,
                        'workflow_step' => 'results_presented',
                    ];
                }

                if (str_contains($sql, 'COUNT(*) AS total FROM TW4_live.results')) {
                    return ['total' => 5];
                }

                if (str_contains($sql, 'FROM TW4_live.card')) {
                    return ['total' => 9];
                }

                return null;
            });

        $fakeStatement = $this->createMock(\PDOStatement::class);
        $fakeStatement->method('rowCount')->willReturn(1);

        $queryCalls = 0;
        $db->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params = []) use (&$queryCalls, $fakeStatement) {
                $queryCalls++;

                if ($queryCalls === 1) {
                    $this->assertStringContainsString('DELETE FROM TW4_live.results', $sql);
                    $this->assertSame([], $params);
                    return $fakeStatement;
                }

                $this->assertStringContainsString('UPDATE TW4_live.round', $sql);
                $this->assertStringContainsString("workflow_step = 'card_entry_open'", $sql);
                $this->assertSame([9, 'admin_user', 7], $params);

                return $fakeStatement;
            });

        $service = new RoundWorkflowService($db);
        $result = $service->adminResetResultsToCardEntry('admin_user');

        $this->assertSame(7, $result['round_id']);
        $this->assertSame('results_presented', $result['from_step']);
        $this->assertSame('card_entry_open', $result['to_step']);
        $this->assertSame(5, $result['results_rows_cleared']);
        $this->assertSame(9, $result['card_count']);
    }

    public function testAdminResetResultsToCardEntryRejectsWrongCurrentState(): void
    {
        /** @var Database|MockObject $db */
        $db = $this->createMock(Database::class);

        $db->expects($this->once())
            ->method('fetchOne')
            ->willReturnCallback(static function (string $sql, array $params = []): ?array {
                if (str_contains($sql, 'FROM TW4_live.round') && str_contains($sql, 'ORDER BY row_id ASC')) {
                    return [
                        'round_id' => 7,
                        'round_number' => 42,
                        'workflow_step' => 'card_entry_open',
                    ];
                }

                return null;
            });

        $db->expects($this->never())->method('beginTransaction');
        $db->expects($this->never())->method('query');

        $service = new RoundWorkflowService($db);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Reset is only allowed when workflow_step is results_presented.');

        $service->adminResetResultsToCardEntry('admin_user');
    }

    public function testAdminResetResultsToCardEntryRollsBackOnUpdateFailure(): void
    {
        /** @var Database|MockObject $db */
        $db = $this->createMock(Database::class);

        $db->expects($this->once())
            ->method('beginTransaction');

        $db->expects($this->never())
            ->method('commit');

        $db->expects($this->once())
            ->method('rollback');

        $db->expects($this->exactly(3))
            ->method('fetchOne')
            ->willReturnCallback(static function (string $sql, array $params = []): ?array {
                if (str_contains($sql, 'FROM TW4_live.round') && str_contains($sql, 'ORDER BY row_id ASC')) {
                    return [
                        'round_id' => 7,
                        'round_number' => 42,
                        'workflow_step' => 'results_presented',
                    ];
                }

                if (str_contains($sql, 'COUNT(*) AS total FROM TW4_live.results')) {
                    return ['total' => 5];
                }

                if (str_contains($sql, 'FROM TW4_live.card')) {
                    return ['total' => 9];
                }

                return null;
            });

        $fakeStatement = $this->createMock(\PDOStatement::class);
        $fakeStatement->method('rowCount')->willReturn(1);

        $queryCalls = 0;
        $db->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params = []) use (&$queryCalls, $fakeStatement) {
                $queryCalls++;

                if ($queryCalls === 1) {
                    $this->assertStringContainsString('DELETE FROM TW4_live.results', $sql);
                    return $fakeStatement;
                }

                throw new \RuntimeException('Simulated update failure');
            });

        $service = new RoundWorkflowService($db);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Simulated update failure');
        $service->adminResetResultsToCardEntry('admin_user');
    }

    public function testAdminResetResultsToCardEntryRejectsInvalidRoundId(): void
    {
        /** @var Database|MockObject $db */
        $db = $this->createMock(Database::class);

        $db->expects($this->once())
            ->method('fetchOne')
            ->willReturnCallback(static function (string $sql, array $params = []): ?array {
                if (str_contains($sql, 'FROM TW4_live.round') && str_contains($sql, 'ORDER BY row_id ASC')) {
                    return [
                        'round_id' => 0,
                        'round_number' => 42,
                        'workflow_step' => 'results_presented',
                    ];
                }

                return null;
            });

        $db->expects($this->never())->method('beginTransaction');
        $db->expects($this->never())->method('query');

        $service = new RoundWorkflowService($db);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid live round row.');

        $service->adminResetResultsToCardEntry('admin_user');
    }
}
