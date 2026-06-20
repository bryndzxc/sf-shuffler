<?php

namespace App\Console\Commands;

use App\Models\GameMatch;
use App\Models\Player;
use App\Services\MmrService;
use Illuminate\Console\Command;

/**
 * Read-only report of every player's recomputed MMR + derived tier + games.
 * Useful after the tier→MMR rebaseline to sanity-check production: ratings are
 * replayed live from match history, so nothing here writes to the database.
 */
class RosterRatings extends Command
{
    protected $signature = 'roster:ratings';

    protected $description = 'Show each player\'s replayed MMR, derived tier, and games (read-only)';

    public function handle(MmrService $mmr): int
    {
        $players = Player::all();
        $matches = GameMatch::orderBy('played_at')->orderBy('id')->get();
        $ratings = $mmr->ratings($players, $matches);

        $rows = $players
            ->map(fn (Player $p) => [
                'name' => $p->name,
                'tier' => $ratings[$p->id]['tier'],
                'mmr' => $ratings[$p->id]['mmr'],
                'games' => $ratings[$p->id]['games'],
            ])
            ->sortByDesc('mmr')
            ->values()
            ->all();

        $this->table(['Callsign', 'Tier', 'MMR', 'Games'], $rows);
        $this->info($players->count().' players · '.$matches->count().' matches replayed · seed '.MmrService::SEED);

        return self::SUCCESS;
    }
}
