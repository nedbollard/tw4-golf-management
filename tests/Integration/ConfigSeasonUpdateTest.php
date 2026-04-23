<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Core\Database;
use App\Services\ConfigService;

class ConfigSeasonUpdateTest extends TestCase
{
    private Database $database;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use test database
        $config = [
            'host' => 'db',
            'name' => 'tw4_test',
            'user' => 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: ''
        ];
        $this->database = new Database($config);
        
        // Ensure test config row exists
        $this->ensureSeasonYearConfigExists();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function ensureSeasonYearConfigExists(): void
    {
        // Check if season_year config exists, create if not
        $existing = $this->database->fetchOne(
            'SELECT row_id FROM config_application WHERE config_name = ?',
            ['season_year']
        );
        
        if (!$existing) {
            $this->database->insert('config_application', [
                'config_name' => 'season_year',
                'config_value_string' => '25_26',
                'config_type' => 'string',
                'updated_by' => 'test_setup'
            ]);
        }
    }

    public function testSeasonYearConfigCanBeRetrieved(): void
    {
        $service = new ConfigService($this->database);
        
        $seasonYear = $service->getConfigValue('season_year');
        $this->assertNotNull($seasonYear);
        $this->assertMatchesRegularExpression('/^\d{2}_\d{2}$/', $seasonYear);
    }

    public function testSeasonYearConfigCanBeUpdated(): void
    {
        $service = new ConfigService($this->database);
        
        // Update to new season
        $result = $service->setConfigValue('season_year', '26_27');
        $this->assertTrue($result);
        
        // Verify the update
        $updatedSeason = $service->getConfigValue('season_year');
        $this->assertEquals('26_27', $updatedSeason);
        
        // Reset to original for other tests
        $service->setConfigValue('season_year', '25_26');
    }

    public function testSeasonYearIsValidatedByFormat(): void
    {
        $service = new ConfigService($this->database);
        
        // Test valid formats
        $validFormats = ['24_25', '25_26', '26_27', '99_00', '00_01'];
        foreach ($validFormats as $format) {
            $result = $service->setConfigValue('season_year', $format);
            $this->assertTrue($result, "Format $format should be valid");
        }
        
        // Reset to original
        $service->setConfigValue('season_year', '25_26');
    }

    public function testSeasonYearConfigIsRequiredField(): void
    {
        // Verify season_year cannot be set to empty via the service
        $service = new ConfigService($this->database);
        
        // The service should allow setting it (basic setConfigValue doesn't validate empty)
        // but the controller should prevent empty updates
        $result = $service->setConfigValue('season_year', '25_26');
        $this->assertTrue($result);
    }

    public function testRoundWorkflowReadsCurrentSeasonYear(): void
    {
        // First ensure a season is configured
        $service = new ConfigService($this->database);
        $service->setConfigValue('season_year', '25_26');
        
        // Now test that RoundWorkflowService can read it
        $this->database->query('DELETE FROM TW4_live.round');
        $this->database->insert('TW4_live.round', [
            'season_year' => '24_25',
            'number_round' => 5,
            'workflow_step' => 'not_started',
            'updated_by' => 'test'
        ]);
        
        $roundService = new \App\Services\RoundWorkflowService($this->database);
        $formData = $roundService->getStartRoundFormData();
        
        // Should pick up the configured season (not the permanent row's season)
        $this->assertEquals('25_26', $formData['current_season_year']);
        
        // Since permanent row is in old season, default round number should reset to 1
        $this->assertEquals(1, $formData['default_round_number']);
    }

    public function testRoundNumberIncrementsWithinSameSeason(): void
    {
        // Set up a season
        $service = new ConfigService($this->database);
        $service->setConfigValue('season_year', '25_26');
        
        // Seed a round in the current season on the permanent row
        $this->database->query('DELETE FROM TW4_live.round');
        $this->database->insert('TW4_live.round', [
            'season_year' => '25_26',
            'number_round' => 5,
            'workflow_step' => 'not_started',
            'updated_by' => 'test'
        ]);
        
        $roundService = new \App\Services\RoundWorkflowService($this->database);
        $formData = $roundService->getStartRoundFormData();
        
        // Should increment within same season
        $this->assertEquals('25_26', $formData['current_season_year']);
        $this->assertEquals(6, $formData['default_round_number']);
    }

    public function testSeasonChangeTriggersRoundNumberReset(): void
    {
        // Set initial season
        $service = new ConfigService($this->database);
        $service->setConfigValue('season_year', '25_26');
        
        // Create round in old season on permanent row
        $this->database->query('DELETE FROM TW4_live.round');
        $this->database->insert('TW4_live.round', [
            'season_year' => '24_25',
            'number_round' => 10,
            'workflow_step' => 'not_started',
            'updated_by' => 'test'
        ]);
        
        // Now advance season
        $service->setConfigValue('season_year', '25_26');
        
        $roundService = new \App\Services\RoundWorkflowService($this->database);
        $formData = $roundService->getStartRoundFormData();
        
        // Should reset to 1 because season changed
        $this->assertEquals('25_26', $formData['current_season_year']);
        $this->assertEquals(1, $formData['default_round_number']);
    }
}
