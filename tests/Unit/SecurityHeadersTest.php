<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function testCspDoesNotAllowUnsafeInlineScript(): void
    {
        $controller = new class extends \App\Controllers\BaseController {
            public function __construct()
            {
            }

            public function testBuildCspPolicy(): string
            {
                return $this->buildCspPolicy();
            }
        };

        $policy = $controller->testBuildCspPolicy();

        $this->assertNotSame('', $policy);
        $this->assertStringNotContainsString("'unsafe-inline'", $policy, 'Inline script execution is not permitted.');
        $this->assertMatchesRegularExpression("/script-src .*'nonce-[^']+'/i", $policy);
    }

    public function testCspDoesNotAllowUnsafeInlineStyles(): void
    {
        $controller = new class extends \App\Controllers\BaseController {
            public function __construct()
            {
            }

            public function testBuildCspPolicy(): string
            {
                return $this->buildCspPolicy();
            }
        };

        $policy = $controller->testBuildCspPolicy();

        $this->assertNotSame('', $policy);
        $this->assertMatchesRegularExpression("/style-src .*'nonce-[^']+'/i", $policy);
        $this->assertStringNotContainsString("style-src .*'unsafe-inline'", $policy, 'Inline CSS is not permitted.');
    }
}
