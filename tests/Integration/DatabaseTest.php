<?php

namespace Tests\Integration;

use App\Core\Database;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private ?Database $database = null;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->database = new Database([
                'host' => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db',
                'port' => (int) ($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306),
                'name' => $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'tw4_test',
                'user' => $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '',
            ]);
        } catch (\RuntimeException $exception) {
            $this->markTestSkipped('Database host is not reachable in this environment: ' . $exception->getMessage());
        }

        $this->database->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->database?->getConnection()->inTransaction()) {
            $this->database->rollback();
        }

        parent::tearDown();
    }

    public function testDatabaseConnection(): void
    {
        $this->assertInstanceOf(\PDO::class, $this->database->getConnection());
    }

    public function testInsert(): void
    {
        $id = $this->database->insert('staff', $this->staffData('insert'));

        $this->assertGreaterThan(0, $id);
    }

    public function testFind(): void
    {
        $data = $this->staffData('find');
        $id = $this->database->insert('staff', $data);

        $result = $this->database->find('staff', ['row_id' => $id]);

        $this->assertSame($id, (int) $result['row_id']);
        $this->assertSame($data['username'], $result['username']);
        $this->assertSame($data['first_name'], $result['first_name']);
    }

    public function testUpdate(): void
    {
        $id = $this->database->insert('staff', $this->staffData('update'));

        $affected = $this->database->update('staff', [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ], ['row_id' => $id]);

        $this->assertSame(1, $affected);
        $updated = $this->database->find('staff', ['row_id' => $id]);
        $this->assertSame('Updated', $updated['first_name']);
        $this->assertSame('Name', $updated['last_name']);
    }

    public function testDelete(): void
    {
        $id = $this->database->insert('staff', $this->staffData('delete'));

        $this->assertSame(1, $this->database->delete('staff', ['row_id' => $id]));
        $this->assertNull($this->database->find('staff', ['row_id' => $id]));
    }

    public function testFindAll(): void
    {
        $ids = [];
        for ($index = 0; $index < 3; $index++) {
            $ids[] = $this->database->insert('staff', $this->staffData('all_' . $index));
        }

        $results = $this->database->findAll('staff');
        $foundIds = array_map('intval', array_column($results, 'row_id'));

        foreach ($ids as $id) {
            $this->assertContains($id, $foundIds);
        }
    }

    public function testCount(): void
    {
        $before = $this->database->count('staff');
        $this->database->insert('staff', $this->staffData('count'));

        $this->assertSame($before + 1, $this->database->count('staff'));
    }

    private function staffData(string $suffix): array
    {
        return [
            'username' => 'database_test_' . $suffix . '_' . bin2hex(random_bytes(4)),
            'password_hash' => '$2y$10$testhash',
            'first_name' => 'Test',
            'last_name' => 'User',
            'role' => 'admin',
            'is_active' => 1,
        ];
    }
}