<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Player;
use Illuminate\Support\Collection;

/**
 * Derives the leaderboard from match history — win rate, games, and current
 * streak per player — never storing those on the player. Stats are computed in
 * one chronological pass over matches so a freshly recorded result is always
 * reflected, and so it stays DB-agnostic (no SQL-specific aggregation).
 */
class StatsService
{
    /**
     * @param  Collection<int,Player>  $players  the players to rank (the roster)
     * @param  Collection<int,GameMatch>  $matches  ordered oldest → newest
     * @return array<int,array{id:int,name:string,role:string,games:int,wins:int,win_rate:float,streak:int}>
     */
    public function leaderboard(Collection $players, Collection $matches): array
    {
        // Seed accumulators so only ranked players are tracked; ids appearing in
        // matches but not here (deleted/benched) are simply ignored.
        $stats = [];
        foreach ($players as $p) {
            $stats[$p->id] = ['games' => 0, 'wins' => 0, 'streak' => 0];
        }

        foreach ($matches as $match) {
            foreach ($match->teams ?? [] as $teamIndex => $ids) {
                $won = $match->winner_team === $teamIndex;
                $draw = $match->winner_team === null;

                foreach ($ids as $id) {
                    if (! isset($stats[$id])) {
                        continue;
                    }

                    $stats[$id]['games']++;

                    if ($won) {
                        $stats[$id]['wins']++;
                        $stats[$id]['streak'] = $stats[$id]['streak'] > 0 ? $stats[$id]['streak'] + 1 : 1;
                    } elseif (! $draw) {
                        // A loss (another team won); a draw leaves the streak untouched.
                        $stats[$id]['streak'] = $stats[$id]['streak'] < 0 ? $stats[$id]['streak'] - 1 : -1;
                    }
                }
            }
        }

        return $players
            ->map(function (Player $p) use ($stats) {
                $games = $stats[$p->id]['games'];
                $wins = $stats[$p->id]['wins'];

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'role' => $p->role,
                    'games' => $games,
                    'wins' => $wins,
                    'win_rate' => $games > 0 ? $wins / $games : 0.0,
                    'streak' => $stats[$p->id]['streak'],
                ];
            })
            ->sort(fn ($a, $b) => ($b['win_rate'] <=> $a['win_rate'])
                ?: ($b['games'] <=> $a['games'])
                ?: strcmp(strtolower($a['name']), strtolower($b['name'])))
            ->values()
            ->all();
    }
}
