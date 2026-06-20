<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A recorded shuffle outcome. Named GameMatch because `Match` is a reserved
 * keyword in PHP 8; the table is still `matches`.
 *
 * `teams` is an array of player-id arrays, `powers` the matching tier-weight
 * totals, and `winner_team` the winning team's index (null = draw).
 */
class GameMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'teams',
        'powers',
        'winner_team',
        'map',
        'played_at',
    ];

    protected $casts = [
        'teams' => 'array',
        'powers' => 'array',
        'winner_team' => 'integer',
        'played_at' => 'datetime',
    ];
}
