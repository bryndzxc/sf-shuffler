<?php

namespace App\Support;

/**
 * Pure helpers for a Best-of-N series between two teams. A series is just a
 * `bestOf` count, the rolled `maps`, and a `results` array (one entry per map:
 * 0 = team A won, 1 = team B won, 'draw', or null = not yet played).
 */
class Series
{
    /** Map wins per side: [teamA wins, teamB wins] (draws count for neither). */
    public static function wins(array $results): array
    {
        $wins = [0, 0];
        foreach ($results as $r) {
            if ($r === 0 || $r === '0') {
                $wins[0]++;
            } elseif ($r === 1 || $r === '1') {
                $wins[1]++;
            }
        }

        return $wins;
    }

    /** Map wins needed to take the series (2 for Bo3, 3 for Bo5). */
    public static function majority(int $bestOf): int
    {
        return intdiv($bestOf, 2) + 1;
    }

    public static function decided(int $bestOf, array $results): bool
    {
        return self::winner($bestOf, $results) !== null;
    }

    /** Series winner (team index) once a side hits the majority, else null. */
    public static function winner(int $bestOf, array $results): ?int
    {
        [$a, $b] = self::wins($results);
        $needed = self::majority($bestOf);

        if ($a >= $needed) {
            return 0;
        }
        if ($b >= $needed) {
            return 1;
        }

        return null;
    }
}
