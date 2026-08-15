<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ComposeHealthcheckTest extends TestCase
{
    public function testDockerComposeWaitsForMysqlHealthBeforeStartingApp(): void
    {
        $compose = file_get_contents(__DIR__ . '/../../docker-compose.yml');

        $this->assertNotFalse($compose);
        $this->assertStringContainsString('healthcheck:', $compose);
        $this->assertStringContainsString('mysqladmin ping -h 127.0.0.1 -uroot -p$${MYSQL_ROOT_PASSWORD} --silent', $compose);
        $this->assertStringContainsString('condition: service_healthy', $compose);
    }
}
