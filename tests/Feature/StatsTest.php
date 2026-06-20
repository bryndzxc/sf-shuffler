<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_with_a_leaderboard(): void
    {
        Player::create(['name' => 'Ghost', 'tier' => 'S']);

        $this->get('/stats')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Stats/Index')
                ->has('leaderboard', 1)
                ->where('matchCount', 0));
    }

    public function test_leaderboard_reflects_recorded_matches(): void
    {
        $alice = Player::create(['name' => 'Alice', 'tier' => 'B']);
        $bob = Player::create(['name' => 'Bob', 'tier' => 'B']);

        GameMatch::create([
            'teams' => [[$alice->id], [$bob->id]],
            'powers' => [2, 2], 'winner_team' => 0, 'played_at' => now(),
        ]);

        $this->get('/stats')
            ->assertInertia(fn ($page) => $page
                ->where('matchCount', 1)
                ->where('leaderboard.0.name', 'Alice')   // winner ranks first
                ->where('leaderboard.0.wins', 1)
                ->where('leaderboard.0.streak', 1)
                ->where('leaderboard.0.mmr', 525)        // C seed 500 + win 25
                ->where('leaderboard.0.tier', 'C')       // derived from MMR
                ->where('leaderboard.1.name', 'Bob')
                ->where('leaderboard.1.wins', 0)
                ->where('leaderboard.1.streak', -1)
                ->where('leaderboard.1.mmr', 485));      // C seed 500 − loss 15
    }

    public function test_stats_count_across_a_three_team_match(): void
    {
        $a = Player::create(['name' => 'A', 'tier' => 'B']);
        $b = Player::create(['name' => 'B', 'tier' => 'B']);
        $c = Player::create(['name' => 'C', 'tier' => 'B']);

        GameMatch::create([
            'teams' => [[$a->id], [$b->id], [$c->id]],
            'powers' => [2, 2, 2], 'winner_team' => 1, 'played_at' => now(),
        ]);

        $this->get('/stats')
            ->assertInertia(fn ($page) => $page
                ->where('matchCount', 1)
                ->where('leaderboard.0.name', 'B')  // team index 1 won
                ->where('leaderboard.0.wins', 1));
    }

    public function test_all_players_appear_on_the_leaderboard(): void
    {
        Player::create(['name' => 'One', 'tier' => 'B']);
        Player::create(['name' => 'Two', 'tier' => 'B']);

        $this->get('/stats')
            ->assertInertia(fn ($page) => $page->has('leaderboard', 2));
    }
}
