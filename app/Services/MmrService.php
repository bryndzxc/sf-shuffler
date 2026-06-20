<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Support\Collection;

/**
 * Derives each player's MMR — and their tier — from match history, never stored
 * on the player. Replays matches oldest → newest, like StatsService.
 *
 * Everyone starts at the same C-tier baseline (SEED) and climbs: Dota-style
 * fixed deltas — win +25, loss −15, draw +5, clamped at a floor of 100. **Tier
 * is derived from the resulting MMR** (tierForMmr), so players auto-level up (and
 * down) as their rating moves; there is no manual tier. The shuffle balances on
 * MMR directly.
 */
class MmrService
{
    /** Flat starting rating — everyone begins at the C-tier baseline. */
    public const SEED = 500;

    public const WIN_DELTA = 25;
    public const LOSS_DELTA = 15;
    public const DRAW_DELTA = 5;

    /** A loss can never drop a rating below this. */
    public const FLOOR = 100;

    /**
     * MMR floor for each tier, best → worst. A player's tier is the first one
     * whose floor their MMR meets. C is the baseline (anything below B).
     */
    public const TIER_FLOORS = ['S' => 1000, 'A' => 800, 'B' => 650, 'C' => 0];

    /** The tier a given MMR maps to. */
    public function tierForMmr(int $mmr): string
    {
        foreach (self::TIER_FLOORS as $tier => $floor) {
            if ($mmr >= $floor) {
                return $tier;
            }
        }

        return 'C';
    }

    /**
     * Current MMR, games played, and derived tier per ranked player.
     *
     * @param  Collection<int,Player>  $players  the roster to rate
     * @param  Collection<int,GameMatch>  $matches  ordered oldest → newest
     * @return array<int,array{mmr:int,games:int,tier:string}>
     */
    public function ratings(Collection $players, Collection $matches): array
    {
        // Seed every ranked player at the flat baseline; ids in matches but not
        // here (deleted players) are ignored. Fixed deltas are opponent-
        // independent, so a player's MMR needs only their own games.
        $mmr = [];
        $games = [];
        foreach ($players as $p) {
            $mmr[$p->id] = self::SEED;
            $games[$p->id] = 0;
        }

        foreach ($matches as $match) {
            foreach ($match->teams ?? [] as $teamIndex => $ids) {
                $delta = $this->delta($match->winner_team, $teamIndex);

                foreach ($ids as $id) {
                    if (! isset($mmr[$id])) {
                        continue;
                    }
                    $mmr[$id] = max(self::FLOOR, $mmr[$id] + $delta);
                    $games[$id]++;
                }
            }
        }

        $out = [];
        foreach ($players as $p) {
            $rating = (int) $mmr[$p->id];
            $out[$p->id] = [
                'mmr' => $rating,
                'games' => $games[$p->id],
                'tier' => $this->tierForMmr($rating),
            ];
        }

        return $out;
    }

    /**
     * Per-player shuffle balancing weight (the raw MMR) for the present roster.
     *
     * @param  Collection<int,Player>  $players
     * @param  Collection<int,GameMatch>  $matches
     * @return array<int,int>  player id → MMR
     */
    public function powerMap(Collection $players, Collection $matches): array
    {
        $ratings = $this->ratings($players, $matches);

        $map = [];
        foreach ($players as $p) {
            $map[$p->id] = $ratings[$p->id]['mmr'];
        }

        return $map;
    }

    /** Rating change for a team in a game: +25 win, −15 loss, +5 draw. */
    private function delta(?int $winner, int $teamIndex): int
    {
        if ($winner === null) {
            return self::DRAW_DELTA;
        }

        return $winner === $teamIndex ? self::WIN_DELTA : -self::LOSS_DELTA;
    }
}
