<?php

namespace App\Http\Controllers;

use App\Support\Maps;
use App\Support\Series;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Manages the per-game Best-of-N series on the Deploy page. Series state lives
 * in the session (per device, alongside the active shuffle): for each game
 * index, the chosen bestOf, the rolled maps, and a result per map. Reset on
 * (re)shuffle by ShuffleController.
 */
class SeriesController extends Controller
{
    /** Choose Bo3/Bo5 for a game and roll its maps. */
    public function start(Request $request)
    {
        $game = $this->validatedGame($request);
        if ($game === null) {
            return back();
        }

        $data = $request->validate([
            'bestOf' => ['required', 'integer', Rule::in([3, 5])],
        ]);

        $series = session('series', []);
        $series[$game] = [
            'bestOf' => (int) $data['bestOf'],
            'maps' => Maps::roll((int) $data['bestOf']),
            'results' => array_fill(0, (int) $data['bestOf'], null),
        ];
        session(['series' => $series]);

        return back();
    }

    /** Re-roll a single not-yet-played map in a game's series. */
    public function reroll(Request $request)
    {
        $game = $this->validatedGame($request);
        $series = session('series', []);
        if ($game === null || ! isset($series[$game])) {
            return back();
        }

        $s = $series[$game];
        $mapIndex = (int) $request->input('mapIndex');

        // Can't re-roll a map that's already been recorded or once the series
        // is decided.
        if (! array_key_exists($mapIndex, $s['maps'])
            || $s['results'][$mapIndex] !== null
            || Series::decided($s['bestOf'], $s['results'])) {
            return back();
        }

        $s['maps'][$mapIndex] = Maps::roll(1, $s['maps'])[0];
        $series[$game] = $s;
        session(['series' => $series]);

        return back();
    }

    /** Clear a game's series so the format can be re-picked. */
    public function reset(Request $request)
    {
        $game = $this->validatedGame($request);
        $series = session('series', []);
        if ($game !== null) {
            unset($series[$game]);
            session(['series' => $series]);
        }

        return back();
    }

    /** Validate the game index against the live shuffle; null if out of range. */
    private function validatedGame(Request $request): ?int
    {
        $shuffle = session('shuffle');
        if (! $shuffle || empty($shuffle['teams'])) {
            return null;
        }

        $gameCount = intdiv(count($shuffle['teams']), 2);
        $game = (int) $request->input('game', -1);

        return ($game >= 0 && $game < $gameCount) ? $game : null;
    }
}
