<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ReportsSyncScriptsTest extends TestCase
{
    public function testSystemTestHealthCheckRequiresSystemTestComposeFile(): void
    {
        $script = file_get_contents(__DIR__ . '/../../scripts/health-check-systest.sh');

        $this->assertNotFalse($script);
        $this->assertStringContainsString('PREFERRED_COMPOSE_FILE="$REPO_ROOT/docker-compose.systest.yml"', $script);
        $this->assertStringContainsString('print_fail "Missing system-test compose file: $COMPOSE_FILE"', $script);
        $this->assertStringNotContainsString('LEGACY_COMPOSE_FILE', $script);
        $this->assertStringNotContainsString('COMPOSE_FILE="$LEGACY_COMPOSE_FILE"', $script);
    }

    public function testProdAndSystestScriptsFetchContainerReportsIntoHostCacheBeforeOracleSync(): void
    {
        $prod = file_get_contents(__DIR__ . '/../../scripts/reports_sync_prod.sh');
        $systest = file_get_contents(__DIR__ . '/../../scripts/reports_sync_systest.sh');

        $this->assertNotFalse($prod);
        $this->assertNotFalse($systest);

        foreach ([$prod, $systest] as $script) {
            $this->assertStringContainsString('LOCAL_REPORTS="${LOCAL_REPORTS:-$HOME/ReportsReadyForProd/reports}"', $script);
            $this->assertStringContainsString('mkdir -p "$LOCAL_REPORTS"', $script);
            $this->assertStringContainsString('cp -a', $script);
            $this->assertStringContainsString('LOCAL_REPORTS', $script);
        }
    }
}
