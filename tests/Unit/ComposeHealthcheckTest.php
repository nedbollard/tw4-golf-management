<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ComposeHealthcheckTest extends TestCase
{
    public function testDockerComposeWaitsForMysqlHealthBeforeStartingApp(): void
    {
        $compose = file_get_contents(__DIR__ . '/../../docker-compose-development.yml');

        $this->assertNotFalse($compose);
        $this->assertStringContainsString('healthcheck:', $compose);
        $this->assertStringContainsString('mysqladmin ping -h 127.0.0.1 -uroot -p$${MYSQL_ROOT_PASSWORD} --silent', $compose);
        $this->assertStringContainsString('condition: service_healthy', $compose);
    }

    public function testOracleEnvironmentsUseOneConfigurationWithoutDevelopmentFallback(): void
    {
        $root = __DIR__ . '/../..';
        $this->assertFileExists($root . '/docker-compose.oracle.yml');
        foreach (['bootstrap-systest.sh', 'db/db_import_oracle.sh',
            'reports_sync_prod.sh', 'reports_sync_systest.sh'] as $name) {
            $script = file_get_contents($root . '/scripts/' . $name);
            $this->assertStringContainsString('COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.oracle.yml}"', $script);
            $this->assertStringNotContainsString('LEGACY_COMPOSE', $script);
            $this->assertStringNotContainsString('FALLBACK_COMPOSE', $script);
        }
        foreach (['prod', 'systest'] as $environment) {
            $script = file_get_contents($root . '/scripts/health-check-' . $environment . '.sh');
            $this->assertStringContainsString('PREFERRED_COMPOSE_FILE="$REPO_ROOT/docker-compose.oracle.yml"', $script);
        }
    }

    public function testScriptsDoNotRelyOnAutomaticComposeFilenameDiscovery(): void
    {
        $directory = new \RecursiveDirectoryIterator(__DIR__ . '/../../scripts', \FilesystemIterator::SKIP_DOTS);
        foreach (new \RecursiveIteratorIterator($directory) as $file) {
            if ($file->getExtension() !== 'sh') {
                continue;
            }
            $script = file_get_contents($file->getPathname());
            $this->assertDoesNotMatchRegularExpression(
                '/docker compose\s+(?:up|down|exec|ps|logs|stop|start|build|run|restart)\b/',
                $script,
                $file->getPathname()
            );
        }
    }
}
