<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\Player;
use App\Services\MmrService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class MmrServiceTest extends TestCase
{
    private function player(int $id, string $tier): Player
    {
        $p = new Player(['name' => "P{$id}", 'tier' => $tier, 'role' => 'rifle']);
        $p->id = $id;

        return $p;
    }

    /** A 2-team game between teams of the given ids. */
    private function match(array $teamA, array $teamB, ?int $winner): GameMatch
    {
        return new GameMatch([
            'teams' => [$teamA, $teamB],
            'powers' => [0, 0],
            'winner_team' => $winner,
        ]);
    }

    public function test_unplayed_players_sit_at_their_tier_seed(): void
    {
        $players = collect([
            $this->player(1, 'S'),
            $this->player(2, 'A'),
            $this->player(3, 'B'),
            $this->player(4, 'C'),
        ]);

        $ratings = (new MmrService)->ratings($players, collect());

        $this->assertSame(1000, $ratings[1]['mmr']);
        $this->assertSame(800, $ratings[2]['mmr']);
        $this->assertSame(650, $ratings[3]['mmr']);
        $this->assertSame(500, $ratings[4]['mmr']);
        $this->assertSame(0, $ratings[1]['games']);
    }

    public function test_a_win_adds_25_and_a_loss_subtracts_15(): void
    {
        $players = collect([$this->player(1, 'B'), $this->player(2, 'B')]);
        $matches = collect([$this->match([1], [2], 0)]); // team 0 (player 1) wins

        $ratings = (new MmrService)->ratings($players, $matches);

        $this->assertSame(675, $ratings[1]['mmr']); // 650 + 25
        $this->assertSame(635, $ratings[2]['mmr']); // 650 − 15
        $this->assertSame(1, $ratings[1]['games']);
    }

    public function test_a_draw_adds_5_to_both_sides(): void
    {
        $players = collect([$this->player(1, 'B'), $this->player(2, 'B')]);
        $matches = collect([$this->match([1], [2], null)]); // draw

        $ratings = (new MmrService)->ratings($players, $matches);

        $this->assertSame(655, $ratings[1]['mmr']);
        $this->assertSame(655, $ratings[2]['mmr']);
    }

    public function test_mmr_never_drops_below_the_floor(): void
    {
        $players = collect([$this->player(1, 'C'), $this->player(2, 'S')]);
        // 40 straight losses for player 1 would reach 500 − 600 = −100 unclamped.
        $matches = collect(array_fill(0, 40, null))
            ->map(fn () => $this->match([1], [2], 1)); // team 1 wins every time

        $ratings = (new MmrService)->ratings($players, $matches);

        $this->assertSame(MmrService::FLOOR, $ratings[1]['mmr']);
        $this->assertSame(100, $ratings[1]['mmr']);
    }

    public function test_results_accumulate_across_games(): void
    {
        $players = collect([$this->player(1, 'B'), $this->player(2, 'B')]);
        $matches = collect([
            $this->match([1], [2], 0), // 1 wins: 675 / 635
            $this->match([1], [2], 0), // 1 wins: 700 / 620
            $this->match([1], [2], 1), // 2 wins: 685 / 645
        ]);

        $ratings = (new MmrService)->ratings($players, $matches);

        $this->assertSame(685, $ratings[1]['mmr']);
        $this->assertSame(645, $ratings[2]['mmr']);
        $this->assertSame(3, $ratings[1]['games']);
    }

    public function test_blended_power_anchors_on_tier_then_drifts_with_mmr(): void
    {
        $svc = new MmrService;

        // At seed (mmr == tier seed) power equals the seed exactly.
        $this->assertSame(650.0, $svc->blendedPower('B', 650));

        // A higher earned MMR pulls power up by MMR_BLEND of the gap.
        // 0.4*650 + 0.6*750 = 710.
        $this->assertSame(710.0, $svc->blendedPower('B', 750));
    }
}
