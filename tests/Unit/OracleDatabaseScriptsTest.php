<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OracleDatabaseScriptsTest extends TestCase
{
    private string $directory;
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
        $this->directory = sys_get_temp_dir() . '/tw4-oracle-script-' . bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        $mock = <<<'BASH'
#!/usr/bin/env bash
printf '%s %s\n' "$(basename "$0")" "$*" >> "$TW4_MOCK_LOG"
BASH;
        foreach (['ssh', 'scp', 'docker'] as $name) {
            file_put_contents($this->directory . '/' . $name, $mock . "\n");
            chmod($this->directory . '/' . $name, 0700);
        }
        $dump = gzencode('-- fixture only');
        file_put_contents($this->directory . '/fixture.sql.gz', $dump);
        file_put_contents($this->directory . '/fixture.sql.gz.sha256', hash('sha256', $dump) . "  fixture.sql.gz\n");
    }

    protected function tearDown(): void
    {
        foreach (['ssh', 'scp', 'docker', 'fixture.sql.gz', 'fixture.sql.gz.sha256', 'calls.log'] as $name) {
            $path = $this->directory . '/' . $name;
            if (file_exists($path)) {
                unlink($path);
            }
        }
        rmdir($this->directory);
    }

    public function testUploadRequiresExplicitKnownEnvironmentBeforeConnecting(): void
    {
        foreach ([[], ['both'], ['unknown']] as $arguments) {
            [$status, $output] = $this->runScript('db_push_to_oracle.sh', $arguments);
            $this->assertSame(1, $status);
            $this->assertStringContainsString('{prod|systest}', $output);
            $this->assertFileDoesNotExist($this->directory . '/calls.log');
        }
    }

    public function testUploadsRouteToDistinctHostsAndPrintCorrectSharedImporter(): void
    {
        foreach (['prod' => '~/TW4', 'systest' => '~/tw4-golf-management'] as $target => $checkout) {
            [$status, $output] = $this->runScript('db_push_to_oracle.sh', [$target, $this->directory . '/fixture.sql.gz']);
            $this->assertSame(0, $status, $output);
            $calls = file_get_contents($this->directory . '/calls.log');
            $this->assertStringContainsString('ssh mock-' . $target . ' mkdir -p /tmp/tw4-import', $calls);
            $this->assertStringContainsString('mock-' . $target . ':/tmp/tw4-import/', $calls);
            $this->assertStringNotContainsString('docker ', $calls);
            $this->assertStringContainsString(
                'cd ' . $checkout . ' && RESTORE_TARGET=' . $target . ' ./scripts/db/db_import_oracle.sh',
                $output
            );
            $this->assertStringContainsString('no database was changed', $output);
            unlink($this->directory . '/calls.log');
        }
    }

    public function testChecksumFailurePreventsUpload(): void
    {
        file_put_contents($this->directory . '/fixture.sql.gz.sha256', str_repeat('0', 64) . "\n");
        [$status, $output] = $this->runScript('db_push_to_oracle.sh', ['prod', $this->directory . '/fixture.sql.gz']);

        $this->assertSame(1, $status);
        $this->assertStringContainsString('Checksum mismatch', $output);
        $this->assertFileDoesNotExist($this->directory . '/calls.log');
    }

    public function testImporterValidatesBackupLabelBeforeTouchingDatabase(): void
    {
        [$status, $output] = $this->runScript(
            'db_import_oracle.sh',
            [$this->directory . '/fixture.sql.gz'],
            ['RESTORE_TARGET' => '../invalid']
        );
        $this->assertSame(1, $status);
        $this->assertStringContainsString('Invalid RESTORE_TARGET label', $output);
        $this->assertFileDoesNotExist($this->directory . '/calls.log');
    }

    public function testPullStillHasProductionOnlySourceAndExplicitLowerTargets(): void
    {
        $script = file_get_contents($this->root . '/scripts/db/db_pull_from_oracle.sh');
        $this->assertStringContainsString('Replaces the selected databases with a fresh production snapshot.', $script);
        $this->assertStringContainsString('development|systest|both) TARGET="$1"', $script);
        $this->assertStringContainsString('ssh "$PROD_SSH"', $script);
        $this->assertStringContainsString('RESTORE_TARGET=systest ./scripts/db/db_import_oracle.sh', $script);
        $this->assertStringContainsString(
            'COMPOSE_FILE=docker-compose-development.yml RESTORE_TARGET=development "${SCRIPT_DIR}/db_import_oracle.sh"',
            $script
        );
        [$status] = $this->runScript('db_pull_from_oracle.sh', ['prod', '--yes']);
        $this->assertSame(1, $status);
        $this->assertFileDoesNotExist($this->directory . '/calls.log');
    }

    private function runScript(string $name, array $arguments, array $overrides = []): array
    {
        $environment = array_merge(getenv(), [
            'PATH' => $this->directory . ':' . getenv('PATH'),
            'TW4_MOCK_LOG' => $this->directory . '/calls.log',
            'TW4_PROD_SSH' => 'mock-prod',
            'TW4_SYSTEST_SSH' => 'mock-systest',
        ], $overrides);
        $process = proc_open(
            array_merge(['bash', $this->root . '/scripts/db/' . $name], $arguments),
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]],
            $pipes,
            $this->root,
            $environment
        );
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        return [proc_close($process), $output];
    }
}
