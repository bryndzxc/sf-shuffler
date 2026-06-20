<?php

namespace App\Support;

/**
 * The map pool a Best-of-N series rolls from. Single source of truth on the
 * backend; the frontend only ever displays the names the server rolled.
 */
class Maps
{
    public const ALL = [
        'Shanghai',
        'Venezia',
        'Plasma',
        'Dessert Camp',
        'KF',
        'Train',
        'Bunker',
        'Crossroad',
        'Nerve Gas',
        'Satellite',
    ];

    /**
     * Roll $count distinct random maps, avoiding any in $exclude when possible.
     *
     * @return array<int,string>
     */
    public static function roll(int $count, array $exclude = []): array
    {
        $pool = array_values(array_diff(self::ALL, $exclude));

        // If we've excluded so many there aren't enough left, fall back to the
        // full pool (shouldn't happen with 10 maps and a Bo5 of 5).
        if (count($pool) < $count) {
            $pool = self::ALL;
        }

        shuffle($pool);

        return array_slice($pool, 0, $count);
    }
}
