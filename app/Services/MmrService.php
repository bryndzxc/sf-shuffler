<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Support\Collection;

/**
 * Derives each player's MMR from match history — never stored on the player,
 * always replayed from matches (oldest → newest) so it stays accurate and
 * DB-agnostic, exactly like StatsService.
 *
 * Dota-style fixed deltas: a win is +25, a loss −15, a draw +5, clamped at a
 * floor of 100 so a loss can never push a rating below it. Ratings are seeded
 * from the player's current tier (the manual anchor), so they're meaningful
 * from game one and S-tier starts well clear of the pack.
 *
 * The shuffle balances on a *blend* of that tier anchor and the live MMR
 * (see blendedPower): tier keeps a permanent pull, results provide the drift.
 */
class MmrService
{
    /** Starting rating per tier — S sits well above the rest so it has weight. */
    public const TIER_SEED = ['S' => 1000, 'A' => 800, 'B' => 650, 'C' => 500];

    /** Fallback seed for an id with no known tier (e.g. a deleted player). */
    public const NEUTRAL_SEED = 650;

    public const WIN_DELTA = 25;
    public const LOSS_DELTA = 15;
    public const DRAW_DELTA = 5;

    /** A loss can never drop a rating below this. */
    public const FLOOR = 100;

    /** Shuffle power = TIER_BLEND·tierSeed + MMR_BLEND·mmr (weights sum to 1). */
    public const TIER_BLEND = 0.4;
    public const MMR_BLEND = 0.6;

    /**
     * Current MMR + games played per ranked player, replayed from match history.
     *
     * @param  Collection<int,Player>  $players  the roster to rate
     * @param  Collection<int,GameMatch>  $matches  ordered oldest → newest
     * @return array<int,array{mmr:int,games:int}>
     */
    public function ratings(Collection $players, Collection $matches): array
    {
        // Seed every ranked player from their current tier; ids appearing in
        // matches but not here (deleted players) are simply ignored, as in
        // StatsService. Fixed deltas don't depend on the opponent's rating, so
        // a player's MMR needs only their own games to compute.
        $mmr = [];
        $games = [];
        foreach ($players as $p) {
            $mmr[$p->id] = self::TIER_SEED[$p->tier] ?? self::NEUTRAL_SEED;
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
            $out[$p->id] = ['mmr' => (int) $mmr[$p->id], 'games' => $games[$p->id]];
        }

        return $out;
    }

    /**
     * Per-player blended shuffle power for the present roster.
     *
     * @param  Collection<int,Player>  $players
     * @param  Collection<int,GameMatch>  $matches
     * @return array<int,float>  player id → blended power
     */
    public function powerMap(Collection $players, Collection $matches): array
    {
        $ratings = $this->ratings($players, $matches);

        $map = [];
        foreach ($players as $p) {
            $map[$p->id] = $this->blendedPower($p->tier, $ratings[$p->id]['mmr']);
        }

        return $map;
    }

    /** Blend the manual tier anchor with the earned MMR into a shuffle weight. */
    public function blendedPower(string $tier, int $mmr): float
    {
        $seed = self::TIER_SEED[$tier] ?? self::NEUTRAL_SEED;

        return self::TIER_BLEND * $seed + self::MMR_BLEND * $mmr;
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
