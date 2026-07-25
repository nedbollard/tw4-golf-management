<?php

namespace Tests\Unit;

use App\Controllers\AdminController;
use App\Core\Application;
use App\Core\Database;
use App\Services\AuthService;
use App\Services\ConfigService;
use App\Services\FlashMessage;
use App\Services\Logger;
use App\Services\RoundLockService;
use App\Services\RoundWorkflowService;
use App\Services\TeamHaggleSeriousService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AdminControllerBehaviorTest extends TestCase
{
    private Database|MockObject $database;
    private Logger|MockObject $logger;
    private RoundWorkflowService|MockObject $workflow;
    private RoundLockService|MockObject $roundLock;
    private TestableAdminController $controller;

    protected function setUp(): void
    {
        $_SESSION = ['config_checked' => true];

        $application = $this->createMock(Application::class);
        $this->database = $this->createMock(Database::class);
        $this->logger = $this->createMock(Logger::class);
        $this->workflow = $this->createMock(RoundWorkflowService::class);
        $this->roundLock = $this->createMock(RoundLockService::class);
        $teamHaggle = $this->createMock(TeamHaggleSeriousService::class);

        $application->method('getDatabase')->willReturn($this->database);

        $this->controller = new TestableAdminController(
            $application,
            $this->logger,
            $this->workflow,
            $this->roundLock,
            $teamHaggle
        );

        $config = $this->createMock(ConfigService::class);
        $auth = $this->createMock(AuthService::class);
        $auth->method('getUser')->willReturn([
            'user_id' => 17,
            'username' => 'admin_user',
        ]);
        $this->controller->initializeServices($config, $auth, new FlashMessage(), $this->logger);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testResetResultsToCardEntrySuccessLogsAndRedirects(): void
    {
        $this->workflow->expects($this->once())
            ->method('adminResetResultsToCardEntry')
            ->with('admin_user')
            ->willReturn([
                'round_id' => 7,
                'from_step' => 'results_presented',
                'to_step' => 'card_entry_open',
                'results_rows_cleared' => 5,
                'card_count' => 9,
            ]);
        $this->database->expects($this->once())
            ->method('fetchOne')
            ->willReturn([
                'workflow_step' => 'card_entry_open',
                'card_count' => 9,
                'locked_by_staff_id' => null,
                'lock_release_reason' => 'admin_forced',
            ]);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                Logger::LEVEL_WARNING,
                Logger::EVENT_SYSTEM,
                'Admin reset scoring state from results_presented to card_entry_open (state applied)',
                $this->callback(static fn(array $context): bool =>
                    $context['round_id'] === 7
                    && $context['admin_staff_id'] === 17
                    && $context['results_rows_cleared'] === 5
                    && $context['card_count'] === 9
                ),
                'admin_user'
            );

        $this->controller->resetResultsToCardEntry();

        $this->assertSame('admin', $this->controller->requiredRole);
        $this->assertSame('/admin/scoring-state', $this->controller->redirectedTo);
        $this->assertSame(
            ['Scoring state reset to card entry open. Live results were cleared.'],
            $_SESSION['_flash']['success']
        );
    }

    public function testResetResultsToCardEntryFailureSetsErrorAndRedirects(): void
    {
        $this->workflow->expects($this->once())
            ->method('adminResetResultsToCardEntry')
            ->willThrowException(new \RuntimeException('Reset is not allowed from card entry.'));
        $this->database->expects($this->never())->method('fetchOne');
        $this->logger->expects($this->never())->method('log');

        $this->controller->resetResultsToCardEntry();

        $this->assertSame('/admin/scoring-state', $this->controller->redirectedTo);
        $this->assertSame(['Reset is not allowed from card entry.'], $_SESSION['_flash']['error']);
    }

    public function testUnlockScoringProcessLogsAndRedirects(): void
    {
        $this->workflow->expects($this->once())
            ->method('getPermanentRound')
            ->willReturn(['round_id' => 9]);
        $this->database->expects($this->exactly(2))
            ->method('fetchOne')
            ->willReturnOnConsecutiveCalls(
                ['workflow_step' => 'card_entry_open', 'locked_by_staff_id' => 34, 'lock_release_reason' => null],
                ['workflow_step' => 'card_entry_open', 'locked_by_staff_id' => null, 'lock_release_reason' => 'admin_forced']
            );
        $this->roundLock->expects($this->once())
            ->method('forceReleaseLock')
            ->with(9, 17, 'admin_forced')
            ->willReturn(1);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                Logger::LEVEL_WARNING,
                Logger::EVENT_SYSTEM,
                'Admin forced release of scoring lock (state applied)',
                $this->callback(static fn(array $context): bool =>
                    $context['round_id'] === 9
                    && $context['admin_staff_id'] === 17
                    && $context['rows_updated'] === 1
                ),
                'admin_user'
            );

        $this->controller->unlockScoringProcess();

        $this->assertSame('/admin/scoring-state', $this->controller->redirectedTo);
        $this->assertSame(['Scoring lock released.'], $_SESSION['_flash']['success']);
    }

    public function testResetResultsToCardEntryWorkflowFailureDoesNotLogSuccess(): void
    {
        $this->workflow->expects($this->once())
            ->method('adminResetResultsToCardEntry')
            ->willThrowException(new \RuntimeException('Simulated delete failure'));
        $this->logger->expects($this->never())->method('log');

        $this->controller->resetResultsToCardEntry();

        $this->assertSame(['Simulated delete failure'], $_SESSION['_flash']['error']);
        $this->assertArrayNotHasKey('success', $_SESSION['_flash']);
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
