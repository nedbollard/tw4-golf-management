<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
    public function testBootstrap(): void
    {
        $this->assertTrue(true);
        $this->assertEquals(1, 1);
        
        // Test that we can load the application
        $this->assertTrue(class_exists('App\Core\Application'));
        $this->assertTrue(class_exists('App\Models\Staff'));
        $this->assertTrue(class_exists('App\Controllers\StaffController'));
        $this->assertTrue(class_exists('App\Services\AuthService'));
    }

    public function testAutoloading(): void
    {
        // Test that autoloading works correctly
        $this->assertTrue(class_exists('App\Core\Database'));
        $this->assertTrue(class_exists('App\Core\Router'));
        $this->assertTrue(class_exists('App\Controllers\BaseController'));
        $this->assertTrue(class_exists('App\Services\ConfigService'));
        $this->assertTrue(class_exists('App\Services\Logger'));
    }
}
