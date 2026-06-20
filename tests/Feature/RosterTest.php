<?php

namespace Tests\Feature;

use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_roster_page_renders(): void
    {
        Player::create(['name' => 'Ghost']);

        $this->get('/roster')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Roster/Index')
                ->has('players', 1)
                ->where('players.0.name', 'Ghost')
                ->where('players.0.tier', 'C')   // everyone starts at the C baseline
                ->where('players.0.mmr', 500));
    }

    public function test_home_redirects_to_roster(): void
    {
        $this->get('/')->assertRedirect('/roster');
    }

    public function test_can_add_a_player_with_defaults(): void
    {
        $this->post('/roster', ['name' => 'Viper'])
            ->assertRedirect();

        $this->assertDatabaseHas('players', [
            'name' => 'Viper',
            'role' => 'rifle', // defaults to rifle
            'present' => false,
        ]);
    }

    public function test_can_add_a_player_as_a_sniper(): void
    {
        $this->post('/roster', ['name' => 'Ghost', 'tier' => 'S', 'role' => 'sniper'])
            ->assertRedirect();

        $this->assertDatabaseHas('players', ['name' => 'Ghost', 'role' => 'sniper']);
    }

    public function test_can_update_a_role(): void
    {
        $player = Player::create(['name' => 'Wolf', 'tier' => 'B']);

        $this->patch("/roster/{$player->id}", ['role' => 'sniper']);

        $this->assertSame('sniper', $player->refresh()->role);
    }

    public function test_role_must_be_valid(): void
    {
        $this->post('/roster', ['name' => 'Bad', 'tier' => 'B', 'role' => 'medic'])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseCount('players', 0);
    }

    public function test_add_player_validates_name(): void
    {
        $this->post('/roster', ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('players', 0);
    }

    public function test_cannot_add_a_duplicate_callsign_case_insensitively(): void
    {
        Player::create(['name' => 'Ghost', 'tier' => 'S']);

        $this->post('/roster', ['name' => 'ghost', 'tier' => 'B'])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('players', 1);
    }

    public function test_renaming_to_an_existing_callsign_is_rejected(): void
    {
        Player::create(['name' => 'Ghost', 'tier' => 'S']);
        $wolf = Player::create(['name' => 'Wolf', 'tier' => 'B']);

        $this->patch("/roster/{$wolf->id}", ['name' => 'GHOST'])
            ->assertSessionHasErrors('name');

        $this->assertSame('Wolf', $wolf->refresh()->name);
    }

    public function test_a_player_can_keep_its_own_name_on_update(): void
    {
        $wolf = Player::create(['name' => 'Wolf', 'tier' => 'B']);

        // Same name, just toggling present — must not trip the unique check.
        $this->patch("/roster/{$wolf->id}", ['name' => 'Wolf', 'present' => true])
            ->assertSessionHasNoErrors();

        $this->assertTrue($wolf->refresh()->present);
    }

    public function test_can_toggle_present(): void
    {
        $player = Player::create(['name' => 'Wolf']);

        $this->patch("/roster/{$player->id}", ['present' => true]);

        $this->assertTrue($player->refresh()->present);
    }

    public function test_mark_all_present_and_clear(): void
    {
        Player::create(['name' => 'A', 'tier' => 'B']);
        Player::create(['name' => 'B', 'tier' => 'B']);

        $this->post('/roster/present/all');
        $this->assertSame(2, Player::where('present', true)->count());

        $this->post('/roster/present/clear');
        $this->assertSame(0, Player::where('present', true)->count());
    }

    private function seedPlayers(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Player::create(['name' => "Filler{$i}", 'tier' => 'B']);
        }
    }

    public function test_roster_is_capped_at_the_max(): void
    {
        $this->seedPlayers(Player::MAX_PLAYERS);

        $this->post('/roster', ['name' => 'Overflow', 'tier' => 'B'])
            ->assertSessionHasErrors('name');

        $this->assertSame(Player::MAX_PLAYERS, Player::count());
    }

    public function test_can_add_when_one_below_the_cap(): void
    {
        $this->seedPlayers(Player::MAX_PLAYERS - 1);

        $this->post('/roster', ['name' => 'LastSlot', 'tier' => 'B']);

        $this->assertSame(Player::MAX_PLAYERS, Player::count());
    }

    public function test_admin_can_delete_a_player(): void
    {
        $player = Player::create(['name' => 'Rookie']);

        $this->withSession(['is_admin' => true])
            ->delete("/roster/{$player->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    }
}
