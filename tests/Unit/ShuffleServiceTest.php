<?php

namespace Tests\Unit;

use App\Models\Player;
use App\Services\ShuffleService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class ShuffleServiceTest extends TestCase
{
    /** Build a roster of N snipers + M rifles, all tier B unless overridden. */
    private function roster(int $snipers, int $rifles, string $tier = 'B'): Collection
    {
        $players = collect();
        $id = 1;
        for ($i = 0; $i < $snipers; $i++) {
            $players->push($this->player($id++, $tier, 'sniper'));
        }
        for ($i = 0; $i < $rifles; $i++) {
            $players->push($this->player($id++, $tier, 'rifle'));
        }

        return $players;
    }

    private function player(int $id, string $tier, string $role): Player
    {
        $p = new Player(['name' => "P{$id}", 'tier' => $tier, 'role' => $role]);
        $p->id = $id;

        return $p;
    }

    public function test_twenty_players_four_snipers_make_four_teams_of_five(): void
    {
        $result = (new ShuffleService)->shuffle($this->roster(4, 16));

        $this->assertCount(4, $result['teams']);
        foreach ($result['teams'] as $team) {
            $this->assertCount(5, $team);
        }
        $this->assertCount(0, $result['reserves']);
    }

    public function test_ten_players_two_snipers_make_two_teams(): void
    {
        $result = (new ShuffleService)->shuffle($this->roster(2, 8));

        $this->assertCount(2, $result['teams']);
        $this->assertCount(0, $result['reserves']);
    }

    public function test_each_team_has_exactly_one_sniper(): void
    {
        $players = $this->roster(4, 16);
        $sniperIds = $players->where('role', 'sniper')->pluck('id')->all();

        $result = (new ShuffleService)->shuffle($players);

        foreach ($result['teams'] as $team) {
            $this->assertCount(1, array_intersect($team, $sniperIds), 'team needs exactly one sniper');
        }
    }

    public function test_leftover_players_go_to_reserves(): void
    {
        // 3 snipers + 14 rifles → limited by rifles: floor(14/4) = 3 teams (15 used),
        // leaving 0 snipers spare and 2 rifles in reserve.
        $result = (new ShuffleService)->shuffle($this->roster(3, 14));

        $this->assertCount(3, $result['teams']);
        $this->assertCount(2, $result['reserves']);
    }

    public function test_extra_snipers_go_to_reserves(): void
    {
        // 5 snipers + 16 rifles → 4 teams (limited by rifles); 1 sniper reserved.
        $players = $this->roster(5, 16);
        $sniperIds = $players->where('role', 'sniper')->pluck('id')->all();

        $result = (new ShuffleService)->shuffle($players);

        $this->assertCount(4, $result['teams']);
        $this->assertCount(1, $result['reserves']);
        $this->assertContains($result['reserves'][0], $sniperIds);
    }

    public function test_no_full_team_possible_returns_no_teams(): void
    {
        // No snipers at all → can't anchor a team.
        $result = (new ShuffleService)->shuffle($this->roster(0, 8));
        $this->assertCount(0, $result['teams']);
        $this->assertCount(8, $result['reserves']);

        // A sniper but too few rifles.
        $result = (new ShuffleService)->shuffle($this->roster(1, 3));
        $this->assertCount(0, $result['teams']);
        $this->assertCount(4, $result['reserves']);
    }

    public function test_every_assigned_player_appears_once(): void
    {
        $result = (new ShuffleService)->shuffle($this->roster(4, 16));

        $all = array_merge(array_merge(...$result['teams']), $result['reserves']);
        sort($all);
        $this->assertSame(range(1, 20), $all);
    }

    public function test_ratings_map_overrides_tier_weight_for_balancing(): void
    {
        // All tier B (weight 2), but a ratings map says player 1 is a heavyweight.
        // Balanced teams should split that weight away from the rest.
        $players = $this->roster(2, 8);
        $ratings = [1 => 1000, 2 => 500]; // snipers; rest fall back to tier weight

        $result = (new ShuffleService)->shuffle($players, null, $ratings);

        // The two snipers (ids 1 and 2) anchor different teams, so the heavy and
        // light snipers land apart — power spread reflects the ratings, not a
        // flat tier weight of 2 each.
        $this->assertCount(2, $result['teams']);
        $sniperTeam = collect($result['teams'])->first(fn ($t) => in_array(1, $t, true));
        $this->assertNotContains(2, $sniperTeam, 'heavy and light snipers seed different teams');
    }

    public function test_teams_are_balanced_by_power(): void
    {
        // Mixed tiers; balanced 5-man teams should be close in power.
        $players = collect();
        $id = 1;
        foreach (['S', 'S'] as $t) {
            $players->push($this->player($id++, $t, 'sniper'));
        }
        foreach (['S', 'A', 'A', 'B', 'B', 'B', 'C', 'C'] as $t) {
            $players->push($this->player($id++, $t, 'rifle'));
        }

        $result = (new ShuffleService)->shuffle($players);

        $this->assertCount(2, $result['teams']);
        $this->assertLessThanOrEqual(2, abs($result['powers'][0] - $result['powers'][1]));
    }
}
