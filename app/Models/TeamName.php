<?php

namespace App\Models;

use App\Services\ShuffleService;
use Illuminate\Database\Eloquent\Model;

/**
 * A custom name for a team slot (0-based). Slots without a row use the NATO
 * default on the frontend. Display-only — the shuffle/match logic never reads it.
 */
class TeamName extends Model
{
    protected $primaryKey = 'slot';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['slot', 'name'];

    /**
     * Custom names indexed by slot for all team slots — null where unset.
     *
     * @return array<int,?string>
     */
    public static function resolved(): array
    {
        $custom = static::pluck('name', 'slot');

        return collect(range(0, ShuffleService::MAX_TEAMS - 1))
            ->map(fn ($i) => $custom->get($i))
            ->all();
    }
}
