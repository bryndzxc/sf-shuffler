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

    public function test_records_a_single_game(): void
    {
        $this->withSession(['shuffle' => $this->oneGame])
            ->post('/matches', ['game' => 0, 'winner' => '0'])
            ->assertRedirect();

        $match = GameMatch::first();
        $this->assertSame([[1, 2, 3, 4, 5], [6, 7, 8, 9, 10]], $match->teams);
        $this->assertSame([10, 10], $match->powers);
        $this->assertSame(0, $match->winner_team);
        $this->assertSame([0], session('recordedGames'));
    }

    public function test_records_the_second_game_of_a_shuffle(): void
    {
        $this->withSession(['shuffle' => $this->twoGames])
            ->post('/matches', ['game' => 1, 'winner' => '1'])
            ->assertRedirect();

        $match = GameMatch::first();
        $this->assertSame([[5, 6], [7, 8]], $match->teams); // game 1 = teams 2 & 3
        $this->assertSame(1, $match->winner_team);
    }

    public function test_a_draw_is_stored_as_null(): void
    {
        $this->withSession(['shuffle' => $this->oneGame])
            ->post('/matches', ['game' => 0, 'winner' => 'draw'])
            ->assertRedirect();

        $this->assertNull(GameMatch::first()->winner_team);
    }

    public function test_winner_must_be_0_1_or_draw(): void
    {
        $this->withSession(['shuffle' => $this->oneGame])
            ->post('/matches', ['game' => 0, 'winner' => '2'])
            ->assertSessionHasErrors('winner');

        $this->assertDatabaseCount('matches', 0);
    }

    public function test_game_index_must_be_in_range(): void
    {
        $this->withSession(['shuffle' => $this->oneGame]) // only game 0 exists
            ->post('/matches', ['game' => 1, 'winner' => '0'])
            ->assertSessionHasErrors('game');

        $this->assertDatabaseCount('matches', 0);
    }

    public function test_nothing_is_recorded_without_a_shuffle(): void
    {
        $this->post('/matches', ['game' => 0, 'winner' => '0'])->assertRedirect();

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
