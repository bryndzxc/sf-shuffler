<?php

namespace Tests\Unit;

use App\Models\GameMatch;
use App\Models\Player;
use App\Services\MmrService;
use PHPUnit\Framework\TestCase;

class MmrServiceTest extends TestCase
{
    private function player(int $id): Player
    {
        $p = new Player(['name' => "P{$id}", 'role' => 'rifle']);
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

    public function test_everyone_starts_at_the_flat_c_seed(): void
    {
        $players = collect([$this->player(1), $this->player(2), $this->player(3)]);

        $ratings = (new MmrService)->ratings($players, collect());

        foreach ([1, 2, 3] as $id) {
            $this->assertSame(MmrService::SEED, $ratings[$id]['mmr']);
            $this->assertSame(500, $ratings[$id]['mmr']);
            $this->assertSame('C', $ratings[$id]['tier']);
            $this->assertSame(0, $ratings[$id]['games']);
        }
    }

    public function test_tier_is_derived_from_mmr_thresholds(): void
    {
        $svc = new MmrService;

        $this->assertSame('C', $svc->tierForMmr(500));
        $this->assertSame('C', $svc->tierForMmr(649));
        $this->assertSame('B', $svc->tierForMmr(650));
        $this->assertSame('B', $svc->tierForMmr(799));
        $this->assertSame('A', $svc->tierForMmr(800));
        $this->assertSame('A', $svc->tierForMmr(999));
        $this->assertSame('S', $svc->tierForMmr(1000));
        $this->assertSame('S', $svc->tierForMmr(1500));
    }

    public function test_a_win_adds_25_and_a_loss_subtracts_15(): void
    {
        $players = collect([$this->player(1), $this->player(2)]);
        $matches = collect([$this->match([1], [2], 0)]); // team 0 (player 1) wins

        $ratings = (new MmrService)->ratings($players, $matches);

        $this->assertSame(525, $ratings[1]['mmr']); // 500 + 25
        $this->assertSame(485, $ratings[2]['mmr']); // 500 − 15
        $this->assertSame(1, $ratings[1]['games']);
    }

    public function test_a_draw_adds_5_to_both_sides(): void
    {
        $players = collect([$this->player(1), $this->player(2)]);
        $matches = collect([$this->match([1], [2], null)]); // draw

        $ratings = (new MmrService)->ratings($players, $matches);

        $this->assertSame(505, $ratings[1]['mmr']);
        $this->assertSame(505, $ratings[2]['mmr']);
    }

    public function test_mmr_never_drops_below_the_floor(): void
    {
        $players = collect([$this->player(1), $this->player(2)]);
        // 40 straight losses for player 1 would reach 500 − 600 = −100 unclamped.
        $matches = collect(array_fill(0, 40, null))
            ->map(fn () => $this->match([1], [2], 1)); // team 1 wins every time

        $ratings = (new MmrService)->ratings($players, $matches);

        $this->assertSame(MmrService::FLOOR, $ratings[1]['mmr']);
        $this->assertSame(100, $ratings[1]['mmr']);
    }

    public function test_results_accumulate_and_can_level_up_a_tier(): void
    {
        $players = collect([$this->player(1), $this->player(2)]);
        // Player 1 wins 6 straight: 500 + 6·25 = 650 → crosses into tier B.
        $matches = collect(array_fill(0, 6, null))
            ->map(fn () => $this->match([1], [2], 0));

        $ratings = (new MmrService)->ratings($players, $matches);

        $this->assertSame(650, $ratings[1]['mmr']);
        $this->assertSame('B', $ratings[1]['tier']);
        $this->assertSame(6, $ratings[1]['games']);
    }

    public function test_power_map_returns_raw_mmr(): void
    {
        $players = collect([$this->player(1), $this->player(2)]);
        $matches = collect([$this->match([1], [2], 0)]);

        $map = (new MmrService)->powerMap($players, $matches);

        $this->assertSame(525, $map[1]);
        $this->assertSame(485, $map[2]);
    }
}
