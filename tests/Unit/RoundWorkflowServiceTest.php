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
    public function testFinishRoundRetainsLiveCardsAndResetsWorkflow(): void
    {
        $_SESSION['username'] = 'scorer_user';

        /** @var Database|MockObject $db */
        $db = $this->createMock(Database::class);

        $db->expects($this->exactly(4))
            ->method('fetchOne')
            ->willReturnCallback(static function (string $sql, array $params = []): ?array {
                if (str_contains($sql, 'FROM TW4_live.round') && str_contains($sql, 'locked_by_staff_id = ?')) {
                    return ['row_id' => 7];
                }

                if (str_contains($sql, 'SELECT workflow_step FROM TW4_live.round WHERE row_id = ?')) {
                    return ['workflow_step' => 'results_presented'];
                }

                if (str_contains($sql, 'SELECT row_id, season_year, number_round') && str_contains($sql, 'FROM TW4_live.round')) {
                    return [
                        'row_id' => 7,
                        'season_year' => '25_26',
                        'number_round' => 12,
                    ];
                }

                if (str_contains($sql, 'FROM TW4_base.config_application') && $params === ['handicap_method']) {
                    return ['method' => 'modern'];
                }

                return null;
            });

        $db->expects($this->atLeast(2))
            ->method('fetchAll')
            ->willReturnCallback(static function (string $sql, array $params = []): array {
                if (str_contains($sql, 'FROM TW4_live.card c') && str_contains($sql, 'card_by_hole')) {
                    return [];
                }

                if (str_contains($sql, 'FROM TW4_base.roster r') && str_contains($sql, 'INNER JOIN TW4_live.card c')) {
                    return [];
                }

                if (str_contains($sql, 'FROM TW4_holding.best_five_scores')) {
                    return [];
                }

                if (str_contains($sql, 'SELECT row_id_player, points') && str_contains($sql, 'FROM TW4_live.card')) {
                    return [];
                }

                return [];
            });

        $db->expects($this->once())
            ->method('beginTransaction');

        $db->expects($this->once())
            ->method('commit');

        $db->expects($this->never())
            ->method('rollback');

        $fakeStatement = $this->createMock(\PDOStatement::class);
        $fakeStatement->method('rowCount')->willReturn(1);

        $executedSql = [];
        $db->expects($this->atLeast(11))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params = []) use (&$executedSql, $fakeStatement) {
                $executedSql[] = ['sql' => $sql, 'params' => $params];

                $this->assertStringNotContainsString('DELETE FROM TW4_live.card', $sql);
                $this->assertStringNotContainsString('DELETE FROM TW4_live.results', $sql);

                return $fakeStatement;
            });

        $service = new RoundWorkflowService($db);
        $this->assertTrue($service->finishRound(7, 11));

        $this->assertTrue($this->containsSql($executedSql, 'DELETE FROM TW4_history.card_by_hole'));
        $this->assertTrue($this->containsSql($executedSql, 'DELETE FROM TW4_history.results'));
        $this->assertTrue($this->containsSql($executedSql, 'DELETE FROM TW4_history.card'));
        $this->assertTrue($this->containsSql($executedSql, 'DELETE FROM TW4_history.round'));
        $this->assertTrue($this->containsSql($executedSql, 'INSERT INTO TW4_history.round'));
        $this->assertTrue($this->containsSql($executedSql, 'INSERT INTO TW4_history.card'));
        $this->assertTrue($this->containsSql($executedSql, 'INSERT INTO TW4_history.card_by_hole'));
        $this->assertTrue($this->containsSql($executedSql, 'INSERT INTO TW4_history.results'));
        $this->assertTrue($this->containsSql($executedSql, 'INSERT INTO TW4_history.best_five_scores'));
        $this->assertTrue($this->containsSql($executedSql, 'DELETE FROM TW4_live.best_five_scores'));
        $this->assertTrue($this->containsSql($executedSql, 'CREATE TABLE IF NOT EXISTS TW4_live.best_five_scores'));
        $this->assertTrue($this->containsSql($executedSql, 'UPDATE TW4_base.roster'));
        $this->assertTrue($this->containsSql($executedSql, 'UPDATE TW4_live.round'));
        $this->assertTrue($this->containsSql($executedSql, 'INNER JOIN TW4_live.card'));

        $this->assertTrue($this->containsSqlAndParams($executedSql, 'UPDATE TW4_base.roster', ['scorer_user']));
        $this->assertTrue($this->containsSqlAndParams($executedSql, 'UPDATE TW4_live.round', ['scorer_user', 7]));
    }

    private function containsSql(array $executedSql, string $needle): bool
    {
        foreach ($executedSql as $item) {
            if (str_contains($item['sql'], $needle)) {
                return true;
            }
        }

        return false;
    }

    private function containsSqlAndParams(array $executedSql, string $needle, array $params): bool
    {
        foreach ($executedSql as $item) {
            if (str_contains($item['sql'], $needle) && $item['params'] === $params) {
                return true;
            }
        }

        return false;
    }

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
                        'season_year' => '25_26',
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

        $executedSql = [];
        $db->expects($this->atLeast(2))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params = []) use (&$executedSql, $fakeStatement) {
                $executedSql[] = ['sql' => $sql, 'params' => $params];
                return $fakeStatement;
            });

        $service = new RoundWorkflowService($db);
        $result = $service->adminResetResultsToCardEntry('admin_user');

        $this->assertSame(7, $result['round_id']);
        $this->assertSame('results_presented', $result['from_step']);
        $this->assertSame('card_entry_open', $result['to_step']);
        $this->assertSame(5, $result['results_rows_cleared']);
        $this->assertSame(9, $result['card_count']);
        $this->assertTrue($this->containsSql($executedSql, 'DELETE FROM TW4_live.results'));
        $this->assertTrue($this->containsSql($executedSql, 'UPDATE TW4_live.round'));
        $this->assertTrue($this->containsSql($executedSql, 'DELETE FROM TW4_live.best_five_scores'));
        $this->assertTrue($this->containsSqlAndParams($executedSql, 'DELETE FROM TW4_history.best_five_scores', ['25_26', 42]));
    }

    public function testAdminResetResultsToCardEntryAllowsNotStartedState(): void
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
                        'season_year' => '25_26',
                        'round_number' => 42,
                        'workflow_step' => 'not_started',
                    ];
                }

                if (str_contains($sql, 'COUNT(*) AS total FROM TW4_live.results')) {
                    return ['total' => 0];
                }

                if (str_contains($sql, 'FROM TW4_live.card')) {
                    return ['total' => 0];
                }

                if (str_contains($sql, 'SELECT season_year, number_round')) {
                    return [
                        'season_year' => '25_26',
                        'number_round' => 42,
                    ];
                }

                return null;
            });

        $fakeStatement = $this->createMock(\PDOStatement::class);
        $fakeStatement->method('rowCount')->willReturn(1);

        $db->expects($this->atLeast(2))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params = []) use ($fakeStatement) {
                return $fakeStatement;
            });

        $service = new RoundWorkflowService($db);
        $result = $service->adminResetResultsToCardEntry('admin_user');

        $this->assertSame('not_started', $result['from_step']);
        $this->assertSame('card_entry_open', $result['to_step']);
        $this->assertSame(0, $result['results_rows_cleared']);
        $this->assertSame(0, $result['card_count']);
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
        $this->expectExceptionMessage('Reset is only allowed when workflow_step is not_started or results_presented.');

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
                        'season_year' => '25_26',
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
        $db->expects($this->atLeast(2))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params = []) use (&$queryCalls, $fakeStatement) {
                $queryCalls++;

                if ($queryCalls === 1) {
                    $this->assertStringContainsString('DELETE FROM TW4_live.results', $sql);
                    return $fakeStatement;
                }

                if (str_contains($sql, 'UPDATE TW4_live.round')) {
                    throw new \RuntimeException('Simulated update failure');
                }

                return $fakeStatement;
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
                        'season_year' => '25_26',
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
