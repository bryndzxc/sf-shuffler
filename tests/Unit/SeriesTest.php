<?php

namespace Tests\Unit;

use App\Support\Maps;
use App\Support\Series;
use PHPUnit\Framework\TestCase;

class SeriesTest extends TestCase
{
    public function test_majority_is_first_to_two_for_bo3_and_three_for_bo5(): void
    {
        $this->assertSame(2, Series::majority(3));
        $this->assertSame(3, Series::majority(5));
    }

    public function test_wins_counts_each_side_ignoring_draws(): void
    {
        $this->assertSame([2, 1], Series::wins([0, 1, 0, 'draw']));
    }

    public function test_series_is_undecided_until_a_side_hits_the_majority(): void
    {
        $this->assertFalse(Series::decided(3, [0, 1, null]));
        $this->assertNull(Series::winner(3, [0, 1, null]));
    }

    public function test_series_is_decided_when_a_side_reaches_the_majority(): void
    {
        $this->assertTrue(Series::decided(3, [0, 0, null]));
        $this->assertSame(0, Series::winner(3, [0, 0, null]));

        $this->assertSame(1, Series::winner(5, [1, 0, 1, 1, null]));
    }

    public function test_roll_returns_distinct_maps_from_the_pool(): void
    {
        $maps = Maps::roll(5);

        $this->assertCount(5, $maps);
        $this->assertCount(5, array_unique($maps));
        foreach ($maps as $map) {
            $this->assertContains($map, Maps::ALL);
        }
    }

    public function test_roll_avoids_excluded_maps(): void
    {
        $exclude = ['Train', 'Bunker', 'Plasma'];
        $maps = Maps::roll(3, $exclude);

        $this->assertEmpty(array_intersect($maps, $exclude));
    }
}
