<?php

namespace Tests\Unit;

use App\Models\CourseClub;
use PHPUnit\Framework\TestCase;

class CourseClubTest extends TestCase
{
    public function testDatabaseRowIdMapsToDomainIdentity(): void
    {
        $courseClub = CourseClub::fromArray([
            'row_id' => 14,
            'name_club' => 'Test Club',
            'number_hole' => 3,
            'name_hole' => 'Third',
            'gender' => 'male',
            'par' => 4,
            'stroke' => 7,
            'updated_by' => 'admin',
        ]);

        $this->assertSame(14, $courseClub->getCourseClubId());
        $this->assertSame(14, $courseClub->toArray()['row_id']);
    }

    public function testCourseClubIdentityCanBeAssigned(): void
    {
        $courseClub = new CourseClub('Test Club', 3, 'Third', 'female', 4, 7, 'admin');

        $courseClub->setCourseClubId(15);

        $this->assertSame(15, $courseClub->getCourseClubId());
    }
}