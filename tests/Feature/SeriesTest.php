<?php

namespace Tests\Feature;

use App\Support\Maps;
use Tests\TestCase;

class SeriesTest extends TestCase
{
    private array $oneGame = [
        'teams' => [[1, 2, 3, 4, 5], [6, 7, 8, 9, 10]],
        'powers' => [10, 10],
    ];

    public function test_starting_a_series_rolls_maps_and_seeds_empty_results(): void
    {
        $this->withSession(['shuffle' => $this->oneGame])
            ->post('/series/start', ['game' => 0, 'bestOf' => 3])
            ->assertRedirect();

        $series = session('series')[0];
        $this->assertSame(3, $series['bestOf']);
        $this->assertCount(3, $series['maps']);
        $this->assertSame([null, null, null], $series['results']);
        foreach ($series['maps'] as $map) {
            $this->assertContains($map, Maps::ALL);
        }
    }

    public function test_best_of_must_be_3_or_5(): void
    {
        $this->withSession(['shuffle' => $this->oneGame])
            ->post('/series/start', ['game' => 0, 'bestOf' => 7])
            ->assertSessionHasErrors('bestOf');

        $this->assertNull(session('series'));
    }

    public function test_rerolling_replaces_one_unplayed_map(): void
    {
        $start = ['bestOf' => 3, 'maps' => ['Train', 'Bunker', 'Plasma'], 'results' => [null, null, null]];

        $this->withSession(['shuffle' => $this->oneGame, 'series' => [0 => $start]])
            ->post('/series/reroll', ['game' => 0, 'mapIndex' => 1])
            ->assertRedirect();

        $maps = session('series')[0]['maps'];
        $this->assertNotSame('Bunker', $maps[1]);          // re-rolled
        $this->assertSame(['Train', 'Plasma'], [$maps[0], $maps[2]]); // others untouched
    }

    public function test_a_recorded_map_cannot_be_rerolled(): void
    {
        $start = ['bestOf' => 3, 'maps' => ['Train', 'Bunker', 'Plasma'], 'results' => [0, null, null]];

        $this->withSession(['shuffle' => $this->oneGame, 'series' => [0 => $start]])
            ->post('/series/reroll', ['game' => 0, 'mapIndex' => 0])
            ->assertRedirect();

        $this->assertSame('Train', session('series')[0]['maps'][0]); // unchanged
    }

    public function test_resetting_clears_a_games_series(): void
    {
        $start = ['bestOf' => 3, 'maps' => ['Train', 'Bunker', 'Plasma'], 'results' => [0, null, null]];

        $this->withSession(['shuffle' => $this->oneGame, 'series' => [0 => $start]])
            ->post('/series/reset', ['game' => 0])
            ->assertRedirect();

        $this->assertArrayNotHasKey(0, session('series'));
    }
}
