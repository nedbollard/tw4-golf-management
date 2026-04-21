<?php

namespace Tests\Unit;

use App\Controllers\AdminController;
use App\Core\Application;
use App\Core\Database;
use App\Services\Logger;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AdminControllerBehaviorTest extends TestCase
{
    public function testResetResultsToCardEntrySuccessLogsAndRedirects(): void
    {
        $_SESSION = ['config_checked' => true];

        /** @var Application|MockObject $app */
        $app = $this->createMock(Application::class);
        /** @var Database|MockObject $db */
        $db = $this->createMock(Database::class);
        /** @var Logger|MockObject $logger */
        $logger = $this->createMock(Logger::class);

        $app->method('getDatabase')->willReturn($db);
        $db->method('getAuth')->willReturn($this->createAuthStub([
            'user_id' => 17,
            'username' => 'admin_user',
        ]));

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

        $db->expects($this->once())->method('beginTransaction');
        $db->expects($this->once())->method('commit');
        $db->expects($this->never())->method('rollback');

        $fakeStatement = $this->createMock(\PDOStatement::class);
        $fakeStatement->method('rowCount')->willReturn(1);

        $db->expects($this->exactly(2))
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params = []) use ($fakeStatement) {
                if (str_contains($sql, 'DELETE FROM TW4_live.results')) {
                    return $fakeStatement;
                }

                $this->assertStringContainsString('UPDATE TW4_live.round', $sql);
                $this->assertSame([9, 'admin_user', 7], $params);
                return $fakeStatement;
            });

        $logger->expects($this->once())
            ->method('log')
            ->with(
                Logger::LEVEL_WARNING,
                Logger::EVENT_SYSTEM,
                'Admin reset scoring state from results_presented to card_entry_open',
                $this->callback(static function (array $context): bool {
                    return ($context['round_id'] ?? 0) === 7
                        && ($context['admin_staff_id'] ?? 0) === 17
                        && ($context['results_rows_cleared'] ?? 0) === 5
                        && ($context['card_count'] ?? 0) === 9;
                }),
                'admin_user'
            );

        $controller = new TestableAdminController($app, $logger);
        $controller->resetResultsToCardEntry();

        $this->assertSame('admin', $controller->requiredRole);
        $this->assertSame('/admin/scoring-state', $controller->redirectedTo);
        $this->assertSame('Scoring state reset to card entry open. Live results were cleared.', $_SESSION['success']);
    }

    public function testResetResultsToCardEntryFailureSetsErrorAndRedirects(): void
    {
        $_SESSION = ['config_checked' => true];

        /** @var Application|MockObject $app */
        $app = $this->createMock(Application::class);
        /** @var Database|MockObject $db */
        $db = $this->createMock(Database::class);
        /** @var Logger|MockObject $logger */
        $logger = $this->createMock(Logger::class);

        $app->method('getDatabase')->willReturn($db);
        $db->method('getAuth')->willReturn($this->createAuthStub([
            'user_id' => 17,
            'username' => 'admin_user',
        ]));

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
        $logger->expects($this->never())->method('log');

        $controller = new TestableAdminController($app, $logger);
        $controller->resetResultsToCardEntry();

        $this->assertSame('admin', $controller->requiredRole);
        $this->assertSame('/admin/scoring-state', $controller->redirectedTo);
        $this->assertSame('Reset is only allowed when workflow_step is results_presented.', $_SESSION['errors'][0]);
    }

    public function testUnlockScoringProcessLogsAndRedirects(): void
    {
        $_SESSION = ['config_checked' => true];

        /** @var Application|MockObject $app */
        $app = $this->createMock(Application::class);
        /** @var Database|MockObject $db */
        $db = $this->createMock(Database::class);
        /** @var Logger|MockObject $logger */
        $logger = $this->createMock(Logger::class);

        $app->method('getDatabase')->willReturn($db);
        $db->method('getAuth')->willReturn($this->createAuthStub([
            'user_id' => 21,
            'username' => 'admin_unlock',
        ]));

        $db->expects($this->once())
            ->method('fetchOne')
            ->willReturnCallback(static function (string $sql, array $params = []): ?array {
                if (str_contains($sql, 'FROM TW4_live.round') && str_contains($sql, 'ORDER BY row_id ASC')) {
                    return [
                        'round_id' => 9,
                        'round_number' => 43,
                        'workflow_step' => 'card_entry_open',
                    ];
                }

                return null;
            });

        $fakeStatement = $this->createMock(\PDOStatement::class);
        $fakeStatement->method('rowCount')->willReturn(1);

        $db->expects($this->once())
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params = []) use ($fakeStatement) {
                $this->assertStringContainsString('UPDATE TW4_live.round', $sql);
                $this->assertSame(['admin_forced', 9], $params);
                return $fakeStatement;
            });

        $logger->expects($this->once())
            ->method('log')
            ->with(
                Logger::LEVEL_WARNING,
                Logger::EVENT_SYSTEM,
                'Admin forced release of scoring lock',
                $this->callback(static function (array $context): bool {
                    return ($context['round_id'] ?? 0) === 9
                        && ($context['admin_staff_id'] ?? 0) === 21
                        && ($context['rows_updated'] ?? 0) === 1;
                }),
                'admin_unlock'
            );

        $controller = new TestableAdminController($app, $logger);
        $controller->unlockScoringProcess();

        $this->assertSame('admin', $controller->requiredRole);
        $this->assertSame('/admin/scoring-state', $controller->redirectedTo);
        $this->assertSame('Scoring lock released.', $_SESSION['success']);
    }

    public function testResetResultsToCardEntryDeleteFailureRollsBackSetsErrorAndRedirects(): void
    {
        $_SESSION = ['config_checked' => true];

        /** @var Application|MockObject $app */
        $app = $this->createMock(Application::class);
        /** @var Database|MockObject $db */
        $db = $this->createMock(Database::class);
        /** @var Logger|MockObject $logger */
        $logger = $this->createMock(Logger::class);

        $app->method('getDatabase')->willReturn($db);
        $db->method('getAuth')->willReturn($this->createAuthStub([
            'user_id' => 17,
            'username' => 'admin_user',
        ]));

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

        $db->expects($this->once())->method('beginTransaction');
        $db->expects($this->never())->method('commit');
        $db->expects($this->once())->method('rollback');

        $db->expects($this->once())
            ->method('query')
            ->willReturnCallback(static function (string $sql, array $params = []) {
                if (str_contains($sql, 'DELETE FROM TW4_live.results')) {
                    throw new \RuntimeException('Simulated delete failure');
                }

                return null;
            });

        $logger->expects($this->never())->method('log');

        $controller = new TestableAdminController($app, $logger);
        $controller->resetResultsToCardEntry();

        $this->assertSame('admin', $controller->requiredRole);
        $this->assertSame('/admin/scoring-state', $controller->redirectedTo);
        $this->assertSame('Simulated delete failure', $_SESSION['errors'][0]);
        $this->assertArrayNotHasKey('success', $_SESSION);
    }

    private function createAuthStub(array $user): object
    {
        return new class($user) {
            private array $user;

            public function __construct(array $user)
            {
                $this->user = $user;
            }

            public function getUser(): array
            {
                return $this->user;
            }

            public function requireRole(string $role): void
            {
            }
        };
    }
}

class TestableAdminController extends AdminController
{
    public ?string $requiredRole = null;
    public ?string $redirectedTo = null;

    protected function requireRole(string $role): void
    {
        $this->requiredRole = $role;
    }

    protected function redirect(string $url, int $statusCode = 302): void
    {
        $this->redirectedTo = $url;
    }
}
