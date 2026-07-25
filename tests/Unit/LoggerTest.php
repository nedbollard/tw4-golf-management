<?php

namespace Tests\Unit;

use App\Core\Database;
use App\Services\Logger;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testGetFilterOptionsLoadsSupportedColumns(): void
    {
        $database = $this->createMock(Database::class);
        $database->expects($this->exactly(3))
            ->method('fetchAll')
            ->willReturnCallback(static function (string $sql): array {
                return match (true) {
                    str_contains($sql, 'DISTINCT level') => [['level' => 'INFO']],
                    str_contains($sql, 'DISTINCT event_type') => [['event_type' => 'LOGIN']],
                    str_contains($sql, 'DISTINCT username') => [['username' => 'admin']],
                    default => throw new \RuntimeException('Unexpected filter query'),
                };
            });

        $logger = new Logger($database);

        $this->assertSame([
            'levels' => ['INFO'],
            'event_types' => ['LOGIN'],
            'usernames' => ['admin'],
        ], $logger->getFilterOptions());
    }
}