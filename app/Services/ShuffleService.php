<?php

namespace App\Services;

use App\Models\Player;
use Illuminate\Support\Collection;

/**
 * Forms balanced 5-man teams (1 sniper + 4 rifles) from the present roster.
 *
 * The team count is derived, not chosen: as many full teams as the roster
 * allows — limited by snipers (one each) and rifles (four each). Snipers and a
 * random subset of rifles are placed, then rifles are balanced across teams by
 * tier power via a batch of randomized greedy splits (the prototype's engine,
 * adapted). Players who don't fit a full team become reserves. The controller
 * pairs the teams into 2-team games.
 */
class ShuffleService
{
    /** How many randomized splits to try per shuffle. */
    public const ATTEMPTS = 60;

    public const SNIPERS_PER_TEAM = 1;
    public const RIFLES_PER_TEAM = 4;
    public const TEAM_SIZE = self::SNIPERS_PER_TEAM + self::RIFLES_PER_TEAM;

    /** Cap on teams formed (10 teams = 5 games = 50 players). */
    public const MAX_TEAMS = 10;

    /** Score penalty for reproducing the previous shuffle exactly. */
    private const REPEAT_PENALTY = 1000;

    /**
     * @param  Collection<int,Player>  $players  candidates (caller filters to present)
     * @param  array<int,array<int>>|null  $lastTeams  team id-arrays from the previous shuffle
     * @param  array<int,float>|null  $ratings  player id → balancing weight; falls
     *                                          back to the tier weight when absent
     *                                          (the blended MMR power in practice)
     * @return array{teams: array<int,array<int>>, powers: array<int,int>, reserves: array<int>}
     */
    public function shuffle(Collection $players, ?array $lastTeams = null, ?array $ratings = null): array
    {
        $snipers = [];
        $rifles = [];
        foreach ($players as $p) {
            $entry = ['id' => $p->id, 'weight' => $ratings[$p->id] ?? $p->weight];
            $p->isSniper() ? $snipers[] = $entry : $rifles[] = $entry;
        }

        $teamCount = min(
            count($snipers),
            intdiv(count($rifles), self::RIFLES_PER_TEAM),
            self::MAX_TEAMS,
        );

        if ($teamCount < 1) {
            // Not enough for one full team — everyone waits in reserve.
            return [
                'teams' => [],
                'powers' => [],
                'reserves' => array_column(array_merge($snipers, $rifles), 'id'),
            ];
        }

        $lastKey = $lastTeams !== null ? $this->keyFromIdLists($lastTeams) : null;

        $best = null;
        $bestScore = INF;

        for ($i = 0; $i < self::ATTEMPTS; $i++) {
            $split = $this->buildTeams($snipers, $rifles, $teamCount);
            $score = $this->score($split, $lastKey);

            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $split;
            }
        }

        return [
            'teams' => array_map(fn ($team) => array_column($team, 'id'), $best['teams']),
            'powers' => array_map(fn ($p) => (int) round($p), $best['powers']),
            'reserves' => array_column($best['reserves'], 'id'),
        ];
    }

    /**
     * One attempt: seed a sniper per team, then greedily fill four rifles each.
     *
     * @param  array<array{id:int,weight:int}>  $snipers
     * @param  array<array{id:int,weight:int}>  $rifles
     */
    private function buildTeams(array $snipers, array $rifles, int $teamCount): array
    {
        $s = $snipers;
        $r = $rifles;
        shuffle($s);
        shuffle($r);

        $teamSnipers = array_slice($s, 0, $teamCount);
        $reserveSnipers = array_slice($s, $teamCount);

        $needRifles = $teamCount * self::RIFLES_PER_TEAM;
        $playing = array_slice($r, 0, $needRifles);
        $reserveRifles = array_slice($r, $needRifles);

        $teams = array_fill(0, $teamCount, []);
        $powers = array_fill(0, $teamCount, 0);

        foreach ($teamSnipers as $i => $sniper) {
            $teams[$i][] = $sniper;
            $powers[$i] += $sniper['weight'];
        }

        // Strongest rifles first (random tiebreak); each drops onto the weakest
        // team that still has an open slot.
        usort($playing, fn ($x, $y) => $y['weight'] <=> $x['weight'] ?: random_int(-1, 1));
        foreach ($playing as $rifle) {
            $t = $this->weakestOpenTeam($powers, $teams);
            $teams[$t][] = $rifle;
            $powers[$t] += $rifle['weight'];
        }

        return [
            'teams' => $teams,
            'powers' => $powers,
            'reserves' => array_merge($reserveSnipers, $reserveRifles),
        ];
    }

    /** Index of the lowest-power team with a free slot (tie → fewest players). */
    private function weakestOpenTeam(array $powers, array $teams): int
    {
        $best = -1;

        for ($i = 0; $i < count($teams); $i++) {
            if (count($teams[$i]) >= self::TEAM_SIZE) {
                continue;
            }
            if ($best === -1
                || $powers[$i] < $powers[$best]
                || ($powers[$i] === $powers[$best] && count($teams[$i]) < count($teams[$best]))) {
                $best = $i;
            }
        }

        return $best;
    }

    private function score(array $split, ?string $lastKey): float
    {
        $powers = $split['powers'];
        $score = max($powers) - min($powers); // team sizes are fixed, so power spread only

        if ($lastKey !== null) {
            $idLists = array_map(fn ($team) => array_column($team, 'id'), $split['teams']);
            if ($this->keyFromIdLists($idLists) === $lastKey) {
                $score += self::REPEAT_PENALTY;
            }
        }

        return $score;
    }

    /** Order-independent fingerprint of a set of teams (by player ids). */
    private function keyFromIdLists(array $idLists): string
    {
        $sigs = array_map(function ($ids) {
            sort($ids);

            return implode('.', $ids);
        }, $idLists);

        sort($sigs);

        return implode('|', $sigs);
    }
}
