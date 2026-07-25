<?php

namespace Tests\Unit;

use App\Core\Database;
use App\Services\HandicapReferenceService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class HandicapReferenceServiceTest extends TestCase
{
    private HandicapReferenceService $service;

    protected function setUp(): void
    {
        $this->service = new HandicapReferenceService($this->createMock(Database::class));
    }

    public function testCalculatesMensWhitePublishedBoundary(): void
    {
        $result = $this->service->calculate(10.2, false, $this->mensWhite());

        $this->assertSame(7, $result['published_handicap']);
        $this->assertSame('7', $result['published_display']);
        $this->assertSame(7, $result['tw4_handicap']);
    }

    public function testCalculatesPlusIndexAndCapsTw4AtScratch(): void
    {
        $result = $this->service->calculate(5.0, true, $this->mensWhite());

        $this->assertSame(-8, $result['published_handicap']);
        $this->assertSame('+8', $result['published_display']);
        $this->assertSame(0, $result['tw4_handicap']);
    }

    public function testCapsStandardIndexAtScratchWhenRatingAdjustmentIsNegative(): void
    {
        $result = $this->service->calculate(0.4, false, $this->mensWhite());

        $this->assertSame(-3, $result['published_handicap']);
        $this->assertSame('+3', $result['published_display']);
        $this->assertSame(0, $result['tw4_handicap']);
    }

    public function testCalculatesWomensYellowPublishedBoundaries(): void
    {
        $lower = $this->service->calculate(25.2, false, $this->womensYellow());
        $upper = $this->service->calculate(54.0, false, $this->womensYellow());

        $this->assertSame(24, $lower['tw4_handicap']);
        $this->assertSame(51, $upper['tw4_handicap']);
    }

    private function mensWhite(): array
    {
        return [
            'course_rating' => '62.9',
            'par' => 66,
            'slope' => 107,
            'handicap_allowance' => '100.00',
        ];
    }

    private function womensYellow(): array
    {
        return [
            'course_rating' => '65.2',
            'par' => 66,
            'slope' => 109,
            'handicap_allowance' => '100.00',
        ];
    }
}