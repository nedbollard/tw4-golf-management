<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ReportsSyncScriptsTest extends TestCase
{
    public function testSystemTestHealthCheckRequiresOracleComposeFile(): void
    {
        $script = file_get_contents(__DIR__ . '/../../scripts/health-check-systest.sh');

        $this->assertNotFalse($script);
        $this->assertStringContainsString('PREFERRED_COMPOSE_FILE="$REPO_ROOT/docker-compose.oracle.yml"', $script);
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
            $this->assertStringContainsString('LOCAL_COMPOSE_FILE="${LOCAL_COMPOSE_FILE:-docker-compose-development.yml}"', $script);
            $this->assertStringContainsString('PROJECT="$1"; COMPOSE="$2"; DEST="$3"; MIRROR="$4"', $script);
            $this->assertStringContainsString('PROJECT="$1"; COMPOSE="$2"; DEST="$3"; SAMPLE_REL="${4-}"; LOCAL_HASH="${5-}"', $script);
            $this->assertStringContainsString('LOCAL_REPORTS="${LOCAL_REPORTS:-$HOME/ReportsReadyForProd/reports}"', $script);
            $this->assertStringContainsString('mkdir -p "$LOCAL_REPORTS"', $script);
            $this->assertStringContainsString('cp -a', $script);
            $this->assertStringContainsString('LOCAL_REPORTS', $script);
        }
    }
}
