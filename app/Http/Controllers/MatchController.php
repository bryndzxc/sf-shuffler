<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Player;
use App\Support\Series;
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
                'map' => $m->map,
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

    /** Record one map of a game's Best-of-N series. */
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
            'mapIndex' => ['required', 'integer', 'min:0'],
            'winner' => ['required', Rule::in(['0', '1'])], // winning team within the game (no draws)
        ]);

        $game = (int) $data['game'];
        $mapIndex = (int) $data['mapIndex'];

        $series = session('series', []);
        $s = $series[$game] ?? null;

        // A series must be started, the map must exist and be unplayed, and the
        // series must not already be decided.
        if (! $s
            || ! array_key_exists($mapIndex, $s['maps'])
            || $s['results'][$mapIndex] !== null
            || Series::decided($s['bestOf'], $s['results'])) {
            return back();
        }

        $a = 2 * $game;
        $b = 2 * $game + 1;

        GameMatch::create([
            'teams' => [$shuffle['teams'][$a], $shuffle['teams'][$b]],
            'powers' => [$shuffle['powers'][$a], $shuffle['powers'][$b]],
            'winner_team' => (int) $data['winner'],
            'map' => $s['maps'][$mapIndex],
            'played_at' => now(),
        ]);

        $s['results'][$mapIndex] = (int) $data['winner'];
        $series[$game] = $s;
        session(['series' => $series]);

        return back();
    }
}
