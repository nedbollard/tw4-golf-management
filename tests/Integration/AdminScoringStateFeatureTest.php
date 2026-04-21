<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class AdminScoringStateFeatureTest extends TestCase
{
    public function testAdminScoringStateRoutesAreConfigured(): void
    {
        $routes = require __DIR__ . '/../../src/config/routes.php';

        $getRoutes = $routes['GET'];
        $postRoutes = $routes['POST'];

        $this->assertArrayHasKey('/admin/scoring-state', $getRoutes);
        $this->assertEquals('App\\Controllers\\AdminController', $getRoutes['/admin/scoring-state']['controller']);
        $this->assertEquals('scoringState', $getRoutes['/admin/scoring-state']['method']);

        $this->assertArrayHasKey('/admin/scoring-state/unlock', $postRoutes);
        $this->assertEquals('App\\Controllers\\AdminController', $postRoutes['/admin/scoring-state/unlock']['controller']);
        $this->assertEquals('unlockScoringProcess', $postRoutes['/admin/scoring-state/unlock']['method']);

        $this->assertArrayHasKey('/admin/scoring-state/reset-results', $postRoutes);
        $this->assertEquals('App\\Controllers\\AdminController', $postRoutes['/admin/scoring-state/reset-results']['controller']);
        $this->assertEquals('resetResultsToCardEntry', $postRoutes['/admin/scoring-state/reset-results']['method']);
    }

    public function testScoringStateViewDisablesResetButtonWhenNotResultsPresented(): void
    {
        $html = $this->renderScoringStateView('card_entry_open');

        $this->assertStringContainsString('action="/admin/scoring-state/reset-results"', $html);
        $this->assertStringContainsString('disabled aria-disabled="true"', $html);
        $this->assertStringContainsString('enabled only when workflow step is results_presented', $html);
    }

    public function testScoringStateViewEnablesResetButtonWhenResultsPresented(): void
    {
        $html = $this->renderScoringStateView('results_presented');

        $this->assertStringContainsString('action="/admin/scoring-state/reset-results"', $html);

        $this->assertDoesNotMatchRegularExpression(
            '/<button\s+[^>]*class="btn btn-danger"[^>]*disabled\s+aria-disabled="true"/s',
            $html
        );
    }

    public function testScoringStateViewContainsAllThreeRequestedItems(): void
    {
        $html = $this->renderScoringStateView('results_presented');

        $this->assertStringContainsString('Item 1: Unlock Scoring Process', $html);
        $this->assertStringContainsString('Item 2: Reset Results Complete to Cards Entry Open', $html);
        $this->assertStringContainsString('Item 3: Future Scoring-State Actions (Stub)', $html);
    }

    public function testScoringStateViewContainsExpectedActionEndpoints(): void
    {
        $html = $this->renderScoringStateView('results_presented');

        $this->assertStringContainsString('action="/admin/scoring-state/unlock"', $html);
        $this->assertStringContainsString('action="/admin/scoring-state/reset-results"', $html);
    }

    public function testAdminMenuContainsScoringStateNavigationCard(): void
    {
        $html = $this->renderAdminMenuView();

        $this->assertStringContainsString('Scoring State Management', $html);
        $this->assertStringContainsString('href="/admin/scoring-state"', $html);
        $this->assertStringContainsString('Admin-only scoring process controls', $html);
    }

    private function renderScoringStateView(string $workflowStep): string
    {
        $viewPath = __DIR__ . '/../../src/Views/admin/scoring-state.php';
        $this->assertFileExists($viewPath);

        $app_title = 'TW4 Test';
        $round = [
            'workflow_step' => $workflowStep,
            'round_number' => 12,
            'round_date' => '2026-04-21',
        ];
        $errors = [];
        $success = null;

        ob_start();
        require $viewPath;
        return (string) ob_get_clean();
    }

    private function renderAdminMenuView(): string
    {
        $viewPath = __DIR__ . '/../../src/Views/admin/menu.php';
        $this->assertFileExists($viewPath);

        $app_title = 'TW4 Test';

        ob_start();
        require $viewPath;
        return (string) ob_get_clean();
    }
}
