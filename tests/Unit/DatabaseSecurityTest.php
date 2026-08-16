<?php

namespace Tests\Unit;

use App\Core\Database;
use PHPUnit\Framework\TestCase;

class DatabaseSecurityTest extends TestCase
{
    public function testValidateOrderByRejectsUnsafeInjection(): void
    {
        $database = (new \ReflectionClass(Database::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(Database::class, 'validateOrderBy');
        $method->setAccessible(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid ORDER BY clause');

        $method->invoke($database, 'username DESC; DELETE FROM staff --');
    }

    public function testValidateOrderByAllowsSafeQualifiedOrdering(): void
    {
        $database = (new \ReflectionClass(Database::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(Database::class, 'validateOrderBy');
        $method->setAccessible(true);

        $this->assertSame('staff.username DESC, staff.last_name ASC', $method->invoke($database, 'staff.username DESC, staff.last_name ASC'));
    }
}
