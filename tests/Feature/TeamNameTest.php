<?php

namespace Tests\Feature;

use App\Models\TeamName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_names_default_to_null_when_unset(): void
    {
        // Shared prop exposes an array sized to MAX_TEAMS, all null by default.
        $this->get('/shuffle')
            ->assertInertia(fn ($page) => $page
                ->where('teamNames.0', null)
                ->where('teamNames.1', null));
    }

    public function test_can_rename_a_team_slot(): void
    {
        $this->patch('/team-names/0', ['name' => 'Wolves'])->assertRedirect();

        $this->assertDatabaseHas('team_names', ['slot' => 0, 'name' => 'Wolves']);

        $this->get('/shuffle')
            ->assertInertia(fn ($page) => $page->where('teamNames.0', 'Wolves'));
    }

    public function test_renaming_again_overwrites(): void
    {
        TeamName::create(['slot' => 1, 'name' => 'Sharks']);

        $this->patch('/team-names/1', ['name' => 'Hawks']);

        $this->assertDatabaseHas('team_names', ['slot' => 1, 'name' => 'Hawks']);
        $this->assertDatabaseCount('team_names', 1);
    }

    public function test_blank_name_resets_to_default(): void
    {
        TeamName::create(['slot' => 2, 'name' => 'Tigers']);

        $this->patch('/team-names/2', ['name' => '']);

        $this->assertDatabaseMissing('team_names', ['slot' => 2]);
    }

    public function test_name_is_length_validated(): void
    {
        $this->patch('/team-names/0', ['name' => str_repeat('x', 31)])
            ->assertSessionHasErrors('name');
    }

    public function test_slot_must_be_in_range(): void
    {
        $this->patch('/team-names/99', ['name' => 'Nope'])->assertNotFound();
    }
}
