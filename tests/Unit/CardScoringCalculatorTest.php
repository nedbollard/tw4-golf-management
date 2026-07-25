<?php

namespace Tests\Unit;

use App\Services\CardScoringCalculator;
use PHPUnit\Framework\TestCase;

class CardScoringCalculatorTest extends TestCase
{
    public function testCalculateAppliesHandicapStrokesAndAcceptsPickupScore(): void
    {
        $result = (new CardScoringCalculator())->calculate($this->entryData(), [1 => '5', 2 => 'X']);

        $this->assertSame([], $result['errors']);
        $this->assertSame(2, $result['holes'][0]['shots']);
        $this->assertSame(3, $result['holes'][0]['net']);
        $this->assertSame(3, $result['holes'][0]['points']);
        $this->assertSame(10, $result['holes'][1]['score']);
        $this->assertSame(1, $result['holes'][1]['shots']);
        $this->assertSame(15, $result['totals']['score']);
        $this->assertSame(3, $result['totals']['shots']);
        $this->assertSame(12, $result['totals']['net']);
        $this->assertSame(3, $result['totals']['points']);
    }

    public function testCalculateClearsTotalsWhenAnyScoreIsInvalid(): void
    {
        $result = (new CardScoringCalculator())->calculate($this->entryData(), [1 => '', 2 => '0']);

        $this->assertSame([
            'Hole 1: score is required.',
            'Hole 2: score must be 1-9 or X.',
        ], $result['errors']);
        $this->assertNull($result['totals']['score']);
        $this->assertNull($result['totals']['shots']);
        $this->assertNull($result['totals']['net']);
        $this->assertNull($result['totals']['points']);
    }

    private function entryData(): array
    {
        return [
            'player' => ['handicap' => 19],
            'holes' => [
                ['par' => 4, 'stroke' => 1],
                ['par' => 4, 'stroke' => 2],
            ],
            'totals' => [],
            'errors' => [],
        ];
    }
}