<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    /** Combat roles. Snipers seed teams one-per-side on shuffle; rest are rifles. */
    public const ROLES = ['rifle', 'sniper'];

    /** Hard cap on the roster (50 players = up to 10 teams / 5 games). */
    public const MAX_PLAYERS = 50;

    protected $fillable = [
        'name',
        'role',
        'present',
    ];

    protected $casts = [
        'present' => 'boolean',
    ];

    public function isSniper(): bool
    {
        return $this->role === 'sniper';
    }
}
