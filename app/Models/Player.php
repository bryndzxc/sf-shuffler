<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    /** Tier weights used by the shuffle engine and power totals. */
    public const TIER_WEIGHTS = ['S' => 4, 'A' => 3, 'B' => 2, 'C' => 1];

    /** Tier order for cycling and sorting (best to worst). */
    public const TIERS = ['S', 'A', 'B', 'C'];

    /** Combat roles. Snipers seed teams one-per-side on shuffle; rest are rifles. */
    public const ROLES = ['rifle', 'sniper'];

    /** Hard cap on the roster (50 players = up to 10 teams / 5 games). */
    public const MAX_PLAYERS = 50;

    protected $fillable = [
        'name',
        'tier',
        'role',
        'present',
    ];

    protected $casts = [
        'present' => 'boolean',
    ];

    /** This player's tier weight (S=4 … C=1). */
    public function getWeightAttribute(): int
    {
        return self::TIER_WEIGHTS[$this->tier] ?? 0;
    }

    public function isSniper(): bool
    {
        return $this->role === 'sniper';
    }
}
