<?php

namespace Tests\Feature;

use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.admin_password' => 'secret']);
    }

    public function test_public_cannot_delete_a_player(): void
    {
        $player = Player::create(['name' => 'Ghost']);

        $this->delete("/roster/{$player->id}")->assertForbidden();

        $this->assertDatabaseHas('players', ['id' => $player->id]);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->post('/admin/login', ['password' => 'nope'])
            ->assertSessionHasErrors('password');

        $this->assertFalse(session()->get('is_admin', false));
    }

    public function test_correct_password_unlocks_delete(): void
    {
        $this->post('/admin/login', ['password' => 'secret'])->assertRedirect();

        $player = Player::create(['name' => 'Ghost']);

        $this->delete("/roster/{$player->id}")->assertRedirect();

        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    }

    public function test_admin_with_session_flag_can_delete(): void
    {
        $player = Player::create(['name' => 'Ghost']);

        $this->withSession(['is_admin' => true])
            ->delete("/roster/{$player->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    }

    public function test_logout_drops_admin(): void
    {
        $this->withSession(['is_admin' => true])
            ->post('/admin/logout')
            ->assertRedirect();

        $player = Player::create(['name' => 'Ghost']);
        $this->delete("/roster/{$player->id}")->assertForbidden();
    }

    public function test_is_admin_is_shared_to_the_frontend(): void
    {
        $this->withSession(['is_admin' => true])
            ->get('/roster')
            ->assertInertia(fn ($page) => $page->where('isAdmin', true));
    }
}
