<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Staff;

class StaffTest extends TestCase
{
    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = new Staff(
            'testuser',
            '$2y$10$hashedpassword',
            'Test',
            'User',
            'admin',
            true,
            null
        );
    }

    public function testStaffCreation(): void
    {
        $this->assertInstanceOf(Staff::class, $this->staff);
        $this->assertEquals('testuser', $this->staff->getUsername());
        $this->assertEquals('Test', $this->staff->getFirstName());
        $this->assertEquals('User', $this->staff->getLastName());
        $this->assertEquals('admin', $this->staff->getRole());
        $this->assertTrue($this->staff->isActive());
    }

    public function testGettersAndSetters(): void
    {
        // Test username
        $this->staff->setUsername('newuser');
        $this->assertEquals('newuser', $this->staff->getUsername());

        // Test role
        $this->staff->setRole('scorer');
        $this->assertEquals('scorer', $this->staff->getRole());

        // Test first name
        $this->staff->setFirstName('John');
        $this->assertEquals('John', $this->staff->getFirstName());

        // Test last name
        $this->staff->setLastName('Doe');
        $this->assertEquals('Doe', $this->staff->getLastName());

        // Test password hash
        $this->staff->setPasswordHash('newhash');
        $this->assertEquals('newhash', $this->staff->getPasswordHash());
    }

    public function testIsActive(): void
    {
        $this->assertTrue($this->staff->isActive());
        
        $this->staff->deactivate();
        $this->assertFalse($this->staff->isActive());
        
        $this->staff->activate();
        $this->assertTrue($this->staff->isActive());
    }

    public function testToArray(): void
    {
        $array = $this->staff->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('testuser', $array['username']);
        $this->assertEquals('Test', $array['first_name']);
        $this->assertEquals('User', $array['last_name']);
        $this->assertEquals('admin', $array['role']);
        $this->assertEquals(1, $array['is_active']);
    }

    public function testFromArray(): void
    {
        $data = [
            'row_id' => 1,
            'username' => 'testuser2',
            'password_hash' => '$2y$10$hashedpassword2',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'role' => 'scorer',
            'is_active' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_ts' => '2024-01-01 00:00:00',
            'updated_by' => 'admin',
            'last_login' => '2024-01-01 00:00:00'
        ];

        $staff = Staff::fromArray($data);

        $this->assertInstanceOf(Staff::class, $staff);
        $this->assertEquals(1, $staff->getRowId());
        $this->assertEquals('testuser2', $staff->getUsername());
        $this->assertEquals('Jane', $staff->getFirstName());
        $this->assertEquals('Smith', $staff->getLastName());
        $this->assertEquals('scorer', $staff->getRole());
        $this->assertFalse($staff->isActive());
    }

    public function testHasRole(): void
    {
        $this->assertTrue($this->staff->hasRole('admin'));
        $this->assertFalse($this->staff->hasRole('scorer'));
        
        $this->staff->setRole('scorer');
        $this->assertTrue($this->staff->hasRole('scorer'));
        $this->assertFalse($this->staff->hasRole('admin'));
    }
}
