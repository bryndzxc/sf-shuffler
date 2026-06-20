<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Player;
use App\Services\MmrService;
use App\Services\StatsService;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function index(StatsService $stats, MmrService $mmr)
    {
        $players = Player::all();
        $matches = GameMatch::orderBy('played_at')->orderBy('id')->get();

        $ratings = $mmr->ratings($players, $matches);
        $leaderboard = array_map(
            fn (array $row) => $row + [
                'mmr' => $ratings[$row['id']]['mmr'],
                'tier' => $ratings[$row['id']]['tier'], // derived from MMR
            ],
            $stats->leaderboard($players, $matches),
        );

        return Inertia::render('Stats/Index', [
            'leaderboard' => $leaderboard,
            'matchCount' => $matches->count(),
        ]);
    }
}
