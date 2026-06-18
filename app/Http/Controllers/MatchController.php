<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MatchController extends Controller
{
    /** Recorded match history, newest first, with player ids resolved to names. */
    public function index()
    {
        $names = Player::pluck('name', 'id');

        $matches = GameMatch::orderByDesc('played_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (GameMatch $m) => [
                'id' => $m->id,
                'played_at' => $m->played_at?->toIso8601String(),
                'teams' => collect($m->teams)->map(
                    fn (array $ids) => collect($ids)
                        ->map(fn ($id) => $names[$id] ?? '(removed)')
                        ->all()
                )->all(),
                'powers' => $m->powers,
                'winner_team' => $m->winner_team,
            ]);

        return Inertia::render('Matches/Index', [
            'matches' => $matches,
        ]);
    }

    /** Record one game (a pair of teams) of the current shuffle. */
    public function store(Request $request)
    {
        $shuffle = session('shuffle');

        // Nothing to record without a live shuffle (e.g. a stale double-submit).
        if (! $shuffle || empty($shuffle['teams'])) {
            return back();
        }

        $gameCount = intdiv(count($shuffle['teams']), 2);

        $data = $request->validate([
            'game' => ['required', 'integer', 'min:0', 'max:'.max(0, $gameCount - 1)],
            'winner' => ['required', Rule::in(['0', '1', 'draw'])], // winner within the game's two teams
        ]);

        $game = (int) $data['game'];
        $a = 2 * $game;
        $b = 2 * $game + 1;

        GameMatch::create([
            'teams' => [$shuffle['teams'][$a], $shuffle['teams'][$b]],
            'powers' => [$shuffle['powers'][$a], $shuffle['powers'][$b]],
            'winner_team' => $data['winner'] === 'draw' ? null : (int) $data['winner'],
            'played_at' => now(),
        ]);

        $recorded = session('recordedGames', []);
        $recorded[] = $game;
        session(['recordedGames' => array_values(array_unique($recorded))]);

        return back();
    }
}
