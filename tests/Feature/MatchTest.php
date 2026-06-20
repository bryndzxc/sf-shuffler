<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchTest extends TestCase
{
    use RefreshDatabase;

    // One game (two teams).
    private array $oneGame = [
        'teams' => [[1, 2, 3, 4, 5], [6, 7, 8, 9, 10]],
        'powers' => [10, 10],
    ];

    // Two games (four teams).
    private array $twoGames = [
        'teams' => [[1, 2], [3, 4], [5, 6], [7, 8]],
        'powers' => [5, 5, 4, 4],
    ];

    /** A started Bo3 series for the given game index. */
    private function series(int $game, array $maps = ['Train', 'Bunker', 'Plasma'], array $results = [null, null, null]): array
    {
        return [$game => ['bestOf' => 3, 'maps' => $maps, 'results' => $results]];
    }

    public function test_records_a_single_map_of_a_series(): void
    {
        $this->withSession(['shuffle' => $this->oneGame, 'series' => $this->series(0)])
            ->post('/matches', ['game' => 0, 'mapIndex' => 0, 'winner' => '0'])
            ->assertRedirect();

        $match = GameMatch::first();
        $this->assertSame([[1, 2, 3, 4, 5], [6, 7, 8, 9, 10]], $match->teams);
        $this->assertSame([10, 10], $match->powers);
        $this->assertSame(0, $match->winner_team);
        $this->assertSame('Train', $match->map);
        $this->assertSame(0, session('series')[0]['results'][0]);
    }

    public function test_records_the_second_game_of_a_shuffle(): void
    {
        $this->withSession(['shuffle' => $this->twoGames, 'series' => $this->series(1, ['KF', 'Train', 'Bunker'])])
            ->post('/matches', ['game' => 1, 'mapIndex' => 0, 'winner' => '1'])
            ->assertRedirect();

        $match = GameMatch::first();
        $this->assertSame([[5, 6], [7, 8]], $match->teams); // game 1 = teams 2 & 3
        $this->assertSame(1, $match->winner_team);
        $this->assertSame('KF', $match->map);
    }

    public function test_winner_must_be_0_or_1(): void
    {
        $this->withSession(['shuffle' => $this->oneGame, 'series' => $this->series(0)])
            ->post('/matches', ['game' => 0, 'mapIndex' => 0, 'winner' => '2'])
            ->assertSessionHasErrors('winner');

        $this->assertDatabaseCount('matches', 0);
    }

    public function test_draw_is_not_an_allowed_outcome(): void
    {
        $this->withSession(['shuffle' => $this->oneGame, 'series' => $this->series(0)])
            ->post('/matches', ['game' => 0, 'mapIndex' => 0, 'winner' => 'draw'])
            ->assertSessionHasErrors('winner');

        $this->assertDatabaseCount('matches', 0);
    }

    public function test_game_index_must_be_in_range(): void
    {
        $this->withSession(['shuffle' => $this->oneGame, 'series' => $this->series(1)]) // only game 0 exists
            ->post('/matches', ['game' => 1, 'mapIndex' => 0, 'winner' => '0'])
            ->assertSessionHasErrors('game');

        $this->assertDatabaseCount('matches', 0);
    }

    public function test_recording_needs_a_started_series(): void
    {
        $this->withSession(['shuffle' => $this->oneGame]) // no series
            ->post('/matches', ['game' => 0, 'mapIndex' => 0, 'winner' => '0'])
            ->assertRedirect();

        $this->assertDatabaseCount('matches', 0);
    }

    public function test_a_map_cannot_be_recorded_twice(): void
    {
        $this->withSession(['shuffle' => $this->oneGame, 'series' => $this->series(0, results: [0, null, null])])
            ->post('/matches', ['game' => 0, 'mapIndex' => 0, 'winner' => '1'])
            ->assertRedirect();

        $this->assertDatabaseCount('matches', 0); // map 0 already has a result
    }

    public function test_a_decided_series_blocks_further_maps(): void
    {
        // Team A already won maps 0 and 1 — the Bo3 is over.
        $this->withSession(['shuffle' => $this->oneGame, 'series' => $this->series(0, results: [0, 0, null])])
            ->post('/matches', ['game' => 0, 'mapIndex' => 2, 'winner' => '1'])
            ->assertRedirect();

        $this->assertDatabaseCount('matches', 0);
    }

    public function test_nothing_is_recorded_without_a_shuffle(): void
    {
        $this->post('/matches', ['game' => 0, 'mapIndex' => 0, 'winner' => '0'])->assertRedirect();

        $this->assertDatabaseCount('matches', 0);
    }

    public function test_history_page_renders_empty(): void
    {
        $this->get('/matches')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Matches/Index')
                ->has('matches.data', 0)
                ->where('matches.total', 0));
    }

    public function test_history_lists_matches_newest_first_with_names(): void
    {
        $alice = \App\Models\Player::create(['name' => 'Alice', 'tier' => 'B']);
        $bob = \App\Models\Player::create(['name' => 'Bob', 'tier' => 'B']);

        GameMatch::create([
            'teams' => [[$alice->id], [$bob->id]],
            'powers' => [2, 2], 'winner_team' => 0, 'played_at' => now()->subDay(),
        ]);
        GameMatch::create([
            'teams' => [[$bob->id], [$alice->id]],
            'powers' => [2, 2], 'winner_team' => null, 'played_at' => now(),
        ]);

        $this->get('/matches')
            ->assertInertia(fn ($page) => $page
                ->where('matches.total', 2)
                ->where('matches.data.0.winner_team', null)   // newest first (the draw)
                ->where('matches.data.0.teams.0.0', 'Bob')
                ->where('matches.data.1.winner_team', 0)
                ->where('matches.data.1.teams.0.0', 'Alice'));
    }

    public function test_history_shows_placeholder_for_removed_players(): void
    {
        GameMatch::create([
            'teams' => [[999], [998]],
            'powers' => [2, 2], 'winner_team' => 0, 'played_at' => now(),
        ]);

        $this->get('/matches')
            ->assertInertia(fn ($page) => $page->where('matches.data.0.teams.0.0', '(removed)'));
    }
}
