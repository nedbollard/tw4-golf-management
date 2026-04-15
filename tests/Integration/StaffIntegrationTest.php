<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Core\Database;
use App\Models\Staff;

class StaffIntegrationTest extends TestCase
{
    private Database $database;
    private array $createdStaff = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use test database
        $config = [
            'host' => 'db',
            'name' => 'tw4_test',
            'user' => 'root',
            'password' => 'secretpassword'
        ];
        $this->database = new Database($config);
        
        // Clean up any existing test data
        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanupTestData();
    }

    private function cleanupTestData(): void
    {
        foreach ($this->createdStaff as $id) {
            $this->database->delete('staff', ['row_id' => $id]);
        }
        $this->createdStaff = [];
    }

    public function testStaffCreationAndRetrieval(): void
    {
        // Create a staff member
        $staff = new Staff(
            'integration_test_' . uniqid(),
            '$2y$10$testhash',
            'Integration',
            'Test',
            'admin',
            true,
            null
        );

        // Save to database
        $id = $staff->save($this->database);
        $this->createdStaff[] = $id;

        // Verify it was saved
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        // Retrieve from database
        $retrievedStaff = Staff::findById($this->database, $id);
        
        $this->assertInstanceOf(Staff::class, $retrievedStaff);
        $this->assertEquals($id, $retrievedStaff->getRowId());
        $this->assertEquals($staff->getUsername(), $retrievedStaff->getUsername());
        $this->assertEquals($staff->getFirstName(), $retrievedStaff->getFirstName());
        $this->assertEquals($staff->getLastName(), $retrievedStaff->getLastName());
        $this->assertEquals($staff->getRole(), $retrievedStaff->getRole());
        $this->assertEquals($staff->isActive(), $retrievedStaff->isActive());
    }

    public function testStaffUpdate(): void
    {
        // Create a staff member
        $staff = new Staff(
            'update_test_' . uniqid(),
            '$2y$10$testhash',
            'Original',
            'Name',
            'admin',
            true,
            null
        );

        $id = $staff->save($this->database);
        $this->createdStaff[] = $id;

        // Update the staff member
        $staff->setFirstName('Updated');
        $staff->setLastName('Name');
        $staff->setRole('scorer');

        $result = $staff->save($this->database);
        $this->assertEquals($id, $result);

        // Verify the update
        $updatedStaff = Staff::findById($this->database, $id);
        $this->assertEquals('Updated', $updatedStaff->getFirstName());
        $this->assertEquals('Name', $updatedStaff->getLastName());
        $this->assertEquals('scorer', $updatedStaff->getRole());
    }

    public function testStaffActivation(): void
    {
        // Create an inactive staff member
        $staff = new Staff(
            'activation_test_' . uniqid(),
            '$2y$10$testhash',
            'Inactive',
            'User',
            'admin',
            false,
            null
        );

        $id = $staff->save($this->database);
        $this->createdStaff[] = $id;

        // Verify inactive
        $this->assertFalse($staff->isActive());

        // Activate
        $staff->activate();
        $staff->save($this->database);

        // Verify activated
        $activeStaff = Staff::findById($this->database, $id);
        $this->assertTrue($activeStaff->isActive());

        // Deactivate
        $activeStaff->deactivate();
        $activeStaff->save($this->database);

        // Verify deactivated
        $deactivatedStaff = Staff::findById($this->database, $id);
        $this->assertFalse($deactivatedStaff->isActive());
    }

    public function testFindAllStaff(): void
    {
        // Create multiple staff members
        $originalCount = count(Staff::findAll($this->database));

        for ($i = 0; $i < 3; $i++) {
            $staff = new Staff(
                'findall_test_' . uniqid() . '_' . $i,
                '$2y$10$testhash',
                'Test' . $i,
                'User' . $i,
                $i % 2 === 0 ? 'admin' : 'scorer',
                true,
                null
            );

            $id = $staff->save($this->database);
            $this->createdStaff[] = $id;
        }

        // Find all staff
        $allStaff = Staff::findAll($this->database);
        
        $this->assertIsArray($allStaff);
        $this->assertEquals($originalCount + 3, count($allStaff));

        // Verify all are Staff instances
        foreach ($allStaff as $staff) {
            $this->assertInstanceOf(Staff::class, $staff);
        }
    }

    public function testStaffNotFound(): void
    {
        $nonExistentId = 999999;
        $staff = Staff::findById($this->database, $nonExistentId);
        
        $this->assertNull($staff);
    }
}
