<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * View Template Integration Tests
 * Tests view templates for URL consistency and correct routing
 */
class ViewTemplateTest extends TestCase
{
    public function testRosterIndexViewExists(): void
    {
        $viewFile = __DIR__ . '/../../src/Views/roster/index.php';
        $this->assertFileExists($viewFile, 'Roster index view should exist');
    }

    public function testRosterIndexViewUsesCorrectCreateUrl(): void
    {
        $viewContent = file_get_contents(__DIR__ . '/../../src/Views/roster/index.php');
        
        // Should NOT contain old /players/create URL
        $this->assertStringNotContainsString('/players/create', $viewContent, 
            'Roster index view should not contain old /players/create URL');
        
        // Should contain new /roster/create URL
        $this->assertStringContainsString('/roster/create', $viewContent, 
            'Roster index view should contain /roster/create URL');
    }

    public function testRosterIndexViewUsesCorrectViewUrls(): void
    {
        $viewContent = file_get_contents(__DIR__ . '/../../src/Views/roster/index.php');
        
        // Should NOT contain old /players/ URLs
        $this->assertStringNotContainsString('/players/', $viewContent, 
            'Roster index view should not contain old /players/ URLs');
        
        // Should contain new /roster/ URLs
        $this->assertStringContainsString('/roster/', $viewContent, 
            'Roster index view should contain /roster/ URLs');
    }

    public function testRosterIndexViewUsesCorrectDatabaseField(): void
    {
        $viewContent = file_get_contents(__DIR__ . '/../../src/Views/roster/index.php');
        
        // Should NOT contain old player_id field
        $this->assertStringNotContainsString('player[\'player_id\']', $viewContent, 
            'Roster index view should not contain old player_id field access');
        
        // Should contain new row_id field
        $this->assertStringContainsString('row_id', $viewContent, 
            'Roster index view should contain new row_id field');
    }

    public function testRosterCreateViewExists(): void
    {
        $viewFile = __DIR__ . '/../../src/Views/roster/create.php';
        $this->assertFileExists($viewFile, 'Roster create view should exist');
    }

    public function testRosterCreateViewUsesCorrectFormAction(): void
    {
        $viewContent = file_get_contents(__DIR__ . '/../../src/Views/roster/create.php');
        
        // Should POST to /roster/create
        $this->assertStringContainsString('action="/roster/create"', $viewContent, 
            'Roster create view should POST to /roster/create');
    }

    public function testRosterShowViewExists(): void
    {
        $viewFile = __DIR__ . '/../../src/Views/roster/show.php';
        $this->assertFileExists($viewFile, 'Roster show view should exist');
    }

    public function testRosterShowViewUsesCorrectUrls(): void
    {
        $viewContent = file_get_contents(__DIR__ . '/../../src/Views/roster/show.php');
        
        // Should NOT contain old /players/ URLs
        $this->assertStringNotContainsString('/players/', $viewContent, 
            'Roster show view should not contain old /players/ URLs');
        
        // Should contain new /roster/ URLs
        $this->assertStringContainsString('/roster/', $viewContent, 
            'Roster show view should contain /roster/ URLs');
    }

    public function testRosterEditViewExists(): void
    {
        $viewFile = __DIR__ . '/../../src/Views/roster/edit.php';
        $this->assertFileExists($viewFile, 'Roster edit view should exist');
    }

    public function testRosterEditViewUsesCorrectFormAction(): void
    {
        $viewContent = file_get_contents(__DIR__ . '/../../src/Views/roster/edit.php');
        
        // Should POST to /roster/{id}/update
        $this->assertStringContainsString('action="/roster/', $viewContent, 
            'Roster edit view should POST to /roster/{id}/update');
        
        // Should NOT contain old /players/ URLs
        $this->assertStringNotContainsString('/players/', $viewContent, 
            'Roster edit view should not contain old /players/ URLs');
    }

    public function testScorerMenuUsesCorrectRosterUrl(): void
    {
        $viewFile = __DIR__ . '/../../src/Views/scorer/menu.php';
        $this->assertFileExists($viewFile, 'Scorer menu view should exist');
        
        $viewContent = file_get_contents($viewFile);
        
        // Should NOT contain old /players/ URLs
        $this->assertStringNotContainsString('/players/', $viewContent, 
            'Scorer menu should not contain old /players/ URLs');
        
        // Should contain new /roster/ URLs
        $this->assertStringContainsString('/roster', $viewContent, 
            'Scorer menu should contain /roster URL');
    }

    public function testAllRosterViewsExist(): void
    {
        $rosterViews = [
            'index.php',
            'create.php', 
            'show.php',
            'edit.php'
        ];
        
        foreach ($rosterViews as $view) {
            $viewFile = __DIR__ . "/../../src/Views/roster/{$view}";
            $this->assertFileExists($viewFile, "Roster view {$view} should exist");
        }
    }

    public function testRosterViewsHaveConsistentTerminology(): void
    {
        $rosterViews = [
            'index.php',
            'create.php', 
            'show.php',
            'edit.php'
        ];
        
        foreach ($rosterViews as $view) {
            $viewContent = file_get_contents(__DIR__ . "/../../src/Views/roster/{$view}");
            
            // Should NOT contain old "players" terminology in URLs
            $this->assertStringNotContainsString('/players/', $viewContent, 
                "Roster view {$view} should not contain old /players/ URLs");
        }
    }

    public function testRosterViewsUseCorrectDatabaseFields(): void
    {
        $rosterViews = [
            'index.php',
            'show.php',
            'edit.php'
        ];
        
        foreach ($rosterViews as $view) {
            $viewContent = file_get_contents(__DIR__ . "/../../src/Views/roster/{$view}");
            
            // Should use row_id instead of old player_id field reference
            $this->assertDoesNotMatchRegularExpression('/\bplayer_id\b/', $viewContent, 
                "Roster view {$view} should not contain old player_id field");
        }
    }

    public function testRosterViewsHaveCorrectPageTitles(): void
    {
        $viewContent = file_get_contents(__DIR__ . '/../../src/Views/roster/index.php');
        
        // Should not contain "Players" in title (should be "Roster")
        $this->assertStringNotContainsString('Players - TW4 Golf Management', $viewContent, 
            'Roster index should not contain "Players" in title');
        
        // Should contain roster-related content
        $this->assertStringContainsString('Players', $viewContent, 
            'Roster index should still contain "Players" for user understanding');
    }

    public function testNoOldPlayerRoutesExistInViews(): void
    {
        $viewDir = __DIR__ . '/../../src/Views';
        
        // Recursively search all view files for old /players/ URLs
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($viewDir));
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                
                // Should not contain old player URLs
                $this->assertStringNotContainsString('/players/', $content, 
                    "View file {$file->getFilename()} should not contain old /players/ URLs");
            }
        }
    }
}
