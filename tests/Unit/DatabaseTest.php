<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Core\Database;

class DatabaseTest extends TestCase
{
    private Database $database;

    protected function setUp(): void
    {
        parent::setUp();
        // Use test database configuration
        $config = [
            'host' => 'db',
            'port' => 3306,
            'name' => 'tw4_test',
            'user' => 'root',
            'password' => 'secretpassword'
        ];
        $this->database = new Database($config);
    }

    public function testDatabaseConnection(): void
    {
        $this->assertInstanceOf(Database::class, $this->database);
        $pdo = $this->database->getConnection();
        $this->assertInstanceOf(\PDO::class, $pdo);
    }

    public function testInsert(): void
    {
        $data = [
            'username' => 'testuser_' . uniqid(),
            'password_hash' => '$2y$10$testhash',
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'admin',
            'is_active' => 1
        ];

        $id = $this->database->insert('staff', $data);
        
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    public function testFind(): void
    {
        // First insert a record
        $data = [
            'username' => 'testuser_find_' . uniqid(),
            'password_hash' => '$2y$10$testhash',
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'admin',
            'is_active' => 1
        ];

        $id = $this->database->insert('staff', $data);
        
        // Then find it
        $result = $this->database->find('staff', ['row_id' => $id]);
        
        $this->assertIsArray($result);
        $this->assertEquals($id, $result['row_id']);
        $this->assertEquals($data['username'], $result['username']);
        $this->assertEquals($data['first_name'], $result['first_name']);
    }

    public function testUpdate(): void
    {
        // First insert a record
        $data = [
            'username' => 'testuser_update_' . uniqid(),
            'password_hash' => '$2y$10$testhash',
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'admin',
            'is_active' => 1
        ];

        $id = $this->database->insert('staff', $data);
        
        // Update it
        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Name'
        ];

        $result = $this->database->update('staff', $updateData, ['row_id' => $id]);
        
        $this->assertGreaterThan(0, $result);
        
        // Verify the update
        $updated = $this->database->find('staff', ['row_id' => $id]);
        $this->assertEquals('Updated', $updated['first_name']);
        $this->assertEquals('Name', $updated['last_name']);
    }

    public function testDelete(): void
    {
        // First insert a record
        $data = [
            'username' => 'testuser_delete_' . uniqid(),
            'password_hash' => '$2y$10$testhash',
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'admin',
            'is_active' => 1
        ];

        $id = $this->database->insert('staff', $data);
        
        // Delete it
        $result = $this->database->delete('staff', ['row_id' => $id]);
        
        $this->assertGreaterThan(0, $result);
        
        // Verify deletion
        $deleted = $this->database->find('staff', ['row_id' => $id]);
        $this->assertEmpty($deleted);
    }

    public function testFindAll(): void
    {
        // Insert multiple records
        for ($i = 0; $i < 3; $i++) {
            $data = [
                'username' => 'testuser_all_' . uniqid() . '_' . $i,
                'password_hash' => '$2y$10$testhash',
                'first_name' => 'Test' . $i,
                'last_name' => 'User' . $i,
                'role' => 'admin',
                'is_active' => 1
            ];
            $this->database->insert('staff', $data);
        }

        $results = $this->database->findAll('staff');
        
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(3, count($results));
    }

    public function testCount(): void
    {
        // Insert a record
        $data = [
            'username' => 'testuser_count_' . uniqid(),
            'password_hash' => '$2y$10$testhash',
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'admin',
            'is_active' => 1
        ];

        $this->database->insert('staff', $data);
        
        $count = $this->database->count('staff');
        
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }
}
