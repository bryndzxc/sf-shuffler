<?php

namespace Tests\Feature;

use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShuffleTest extends TestCase
{
    use RefreshDatabase;

    /** Seed $snipers snipers + $rifles rifles, all present. */
    private function presentRoster(int $snipers, int $rifles): void
    {
        $n = 1;
        for ($i = 0; $i < $snipers; $i++) {
            Player::create(['name' => "S{$n}", 'tier' => 'B', 'role' => 'sniper', 'present' => true]);
            $n++;
        }
        for ($i = 0; $i < $rifles; $i++) {
            Player::create(['name' => "R{$n}", 'tier' => 'B', 'role' => 'rifle', 'present' => true]);
            $n++;
        }
    }

    public function test_page_renders_empty_when_nobody_present(): void
    {
        Player::create(['name' => 'AbsentGuy', 'tier' => 'A', 'present' => false]);

        $this->get('/shuffle')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Shuffle/Index')
                ->has('games', 0)
                ->where('presentCount', 0));
    }

    public function test_ten_players_form_one_game(): void
    {
        $this->presentRoster(2, 8);

        $this->get('/shuffle')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('presentCount', 10)
                ->has('games', 1)
                ->has('games.0.teams', 2));
    }

    public function test_twenty_players_form_two_games(): void
    {
        $this->presentRoster(4, 16);

        $this->get('/shuffle')
            ->assertInertia(fn ($page) => $page
                ->where('presentCount', 20)
                ->has('games', 2));
    }

    public function test_not_enough_for_a_team_shows_no_games(): void
    {
        $this->presentRoster(0, 8); // no snipers

        $this->get('/shuffle')
            ->assertInertia(fn ($page) => $page
                ->has('games', 0)
                ->where('snipersReady', 0)
                ->where('riflesReady', 8)
                ->has('reserves', 8));
    }

    public function test_leftovers_become_reserves(): void
    {
        $this->presentRoster(2, 9); // 2 teams use 8 rifles, 1 rifle reserved

        $this->get('/shuffle')
            ->assertInertia(fn ($page) => $page
                ->has('games', 1)
                ->has('reserves', 1));
    }

    public function test_only_present_players_are_included(): void
    {
        $this->presentRoster(2, 8);
        Player::create(['name' => 'Absent', 'tier' => 'S', 'role' => 'sniper', 'present' => false]);

        $this->get('/shuffle')
            ->assertInertia(fn ($page) => $page->where('presentCount', 10));
    }

    public function test_run_reshuffles_and_redirects(): void
    {
        $this->presentRoster(2, 8);

        $this->post('/shuffle')->assertRedirect(route('shuffle.index'));
        $this->assertNotNull(session('shuffle'));
        $this->assertSame([], session('recordedGames'));
    }
}
