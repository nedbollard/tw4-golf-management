<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SchemaCollationTest extends TestCase
{
    private const TARGET_COLLATION = 'utf8mb4_0900_ai_ci';

    public function testCanonicalBaselinesDeclareTargetDatabaseCollation(): void
    {
        $baselineFiles = [
            'TW4_base_schema.sql',
            'TW4_live_schema.sql',
            'TW4_history_schema.sql',
            'TW4_holding_schema.sql',
        ];

        foreach ($baselineFiles as $file) {
            $schema = file_get_contents(__DIR__ . '/../../database/baseline/' . $file);

            $this->assertNotFalse($schema, $file);
            $this->assertStringContainsString(
                'DEFAULT CHARACTER SET utf8mb4 COLLATE ' . self::TARGET_COLLATION,
                $schema,
                $file . ' must declare the canonical database default'
            );
            $this->assertSame(
                0,
                preg_match('/COLLATE\s+utf8mb4_(?!0900_ai_ci\b|bin\b)[a-z0-9_]+/i', $schema),
                $file . ' contains a noncanonical explicit text collation'
            );
        }
    }

    public function testBaselineSeedFilesDoNotCreateDatabaseDefaults(): void
    {
        $seedFiles = glob(__DIR__ . '/../../database/baseline/*seed*.sql');

        $this->assertNotFalse($seedFiles);

        foreach ($seedFiles as $file) {
            $seed = file_get_contents($file);

            $this->assertNotFalse($seed, $file);
            $this->assertSame(
                0,
                preg_match('/\bCREATE\s+(?:DATABASE|SCHEMA)\b/i', $seed),
                basename($file) . ' must not define database defaults'
            );
            $this->assertSame(
                0,
                preg_match('/COLLATE\s+utf8mb4_(?!0900_ai_ci\b|bin\b)[a-z0-9_]+/i', $seed),
                basename($file) . ' contains a noncanonical explicit text collation'
            );
        }
    }

    public function testMigrationsUseOnlyCanonicalOrIntentionalBinaryUtf8mb4Collations(): void
    {
        $migrationFiles = glob(__DIR__ . '/../../src/migrations/*.sql');

        $this->assertNotFalse($migrationFiles);
        $this->assertNotEmpty($migrationFiles);

        foreach ($migrationFiles as $file) {
            $migration = file_get_contents($file);

            $this->assertNotFalse($migration, $file);
            $this->assertSame(
                0,
                preg_match('/COLLATE\s+utf8mb4_(?!0900_ai_ci\b|bin\b)[a-z0-9_]+/i', $migration),
                basename($file) . ' contains a noncanonical explicit text collation'
            );
        }

        $handicapAuditMigration = file_get_contents(
            __DIR__ . '/../../src/migrations/032_handicap_audit_store_points_at_source.sql'
        );

        $this->assertNotFalse($handicapAuditMigration);
        $this->assertStringContainsString(
            'pts.season_year COLLATE ' . self::TARGET_COLLATION . ' = ha.season_year',
            $handicapAuditMigration
        );
    }

    public function testRuntimeServicesUseCanonicalCollationsAndTableDdl(): void
    {
        $serviceFiles = glob(__DIR__ . '/../../src/Services/*.php');

        $this->assertNotFalse($serviceFiles);
        $this->assertNotEmpty($serviceFiles);

        foreach ($serviceFiles as $file) {
            $service = file_get_contents($file);

            $this->assertNotFalse($service, $file);
            $this->assertStringNotContainsString(
                'utf8mb4_general_ci',
                $service,
                basename($file) . ' contains a legacy collation'
            );
        }

        foreach (['BestFiveService.php' => 'best_five_scores', 'EclecticService.php' => 'eclectic_scores'] as $file => $table) {
            $service = file_get_contents(__DIR__ . '/../../src/Services/' . $file);

            $this->assertNotFalse($service, $file);
            $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS %s.' . $table, $service);
            $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS TW4_history.' . $table, $service);
            $this->assertSame(
                2,
                substr_count(
                    $service,
                    'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=' . self::TARGET_COLLATION
                ),
                $file . ' must use the target collation for both table DDL definitions'
            );
        }
    }

    public function testSystemTestBootstrapCreatesDatabasesWithCanonicalDefaults(): void
    {
        $bootstrap = file_get_contents(__DIR__ . '/../../scripts/bootstrap-systest.sh');

        $this->assertNotFalse($bootstrap);

        foreach (['TW4_base', 'TW4_live', 'TW4_history', 'TW4_holding'] as $database) {
            $this->assertStringContainsString(
                'CREATE DATABASE ' . $database . ' CHARACTER SET utf8mb4 COLLATE ' . self::TARGET_COLLATION,
                $bootstrap
            );
        }

        $this->assertStringContainsString(
            ') DEFAULT CHARSET=utf8mb4 COLLATE=' . self::TARGET_COLLATION . ';',
            $bootstrap
        );
    }
}
