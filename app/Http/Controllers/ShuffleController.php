<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Player;
use App\Services\MmrService;
use App\Services\ShuffleService;
use App\Support\Series;
use Inertia\Inertia;

class ShuffleController extends Controller
{
    public function __construct(private ShuffleService $shuffler, private MmrService $mmr) {}

    /** Show the current teams/games, regenerating if the roster changed. */
    public function index()
    {
        $present = $this->presentPlayers();

        if ($present->isEmpty()) {
            session()->forget(['shuffle', 'shuffleSig', 'series']);

            return $this->render($present, null);
        }

        $sig = $this->signature($present);

        if (! session('shuffle') || session('shuffleSig') !== $sig) {
            $this->store($this->shuffler->shuffle($present, $this->lastTeams(), $this->powerMap($present)), $sig);
        }

        return $this->render($present, session('shuffle'));
    }

    /** Re-shuffle, biased away from the previous teams. */
    public function run()
    {
        $present = $this->presentPlayers();

        if ($present->isEmpty()) {
            session()->forget(['shuffle', 'shuffleSig', 'series']);
        } else {
            $this->store($this->shuffler->shuffle($present, $this->lastTeams(), $this->powerMap($present)), $this->signature($present));
        }

        return redirect()->route('shuffle.index');
    }

    /** MMR-based balancing weight per present player. */
    private function powerMap($present): array
    {
        return $this->mmr->powerMap($present, GameMatch::orderBy('played_at')->orderBy('id')->get());
    }

    /** Shape a game's stored series for the frontend (null = not started). */
    private function seriesPayload(?array $s): ?array
    {
        if ($s === null) {
            return null;
        }

        return [
            'bestOf' => $s['bestOf'],
            'maps' => $s['maps'],
            'results' => $s['results'],
            'wins' => Series::wins($s['results']),
            'needed' => Series::majority($s['bestOf']),
            'winner' => Series::winner($s['bestOf'], $s['results']),
        ];
    }

    /** Previous teams (id arrays) from the session, for re-shuffle variety. */
    private function lastTeams(): ?array
    {
        $teams = session('shuffle.teams');

        return is_array($teams) ? $teams : null;
    }

    private function store(array $shuffle, string $sig): void
    {
        session([
            'shuffle' => $shuffle,
            'shuffleSig' => $sig,
            'series' => [], // fresh shuffle → no series started yet
        ]);
    }

    private function render($present, ?array $shuffle)
    {
        $powerMap = $present->isEmpty() ? [] : $this->powerMap($present);
        $hydrated = $this->hydrate($shuffle, $present, $powerMap);
        $teams = $hydrated['teams'] ?? [];
        $powers = $hydrated['powers'] ?? [];
        $series = session('series', []);

        $games = [];
        for ($gi = 0; $gi < intdiv(count($teams), 2); $gi++) {
            $a = 2 * $gi;
            $b = 2 * $gi + 1;
            $games[] = [
                'index' => $gi,
                'teamIndices' => [$a, $b],
                'teams' => [$teams[$a], $teams[$b]],
                'powers' => [$powers[$a], $powers[$b]],
                'series' => $this->seriesPayload($series[$gi] ?? null),
            ];
        }

        $bye = null;
        if (count($teams) % 2 === 1) {
            $last = count($teams) - 1;
            $bye = ['teamIndex' => $last, 'players' => $teams[$last], 'power' => $powers[$last]];
        }

        return Inertia::render('Shuffle/Index', [
            'games' => $games,
            'bye' => $bye,
            'reserves' => $hydrated['reserves'] ?? [],
            'presentCount' => $present->count(),
            'snipersReady' => $present->where('role', 'sniper')->count(),
            'riflesReady' => $present->where('role', 'rifle')->count(),
        ]);
    }

    /** @return \Illuminate\Support\Collection<int,Player> */
    private function presentPlayers()
    {
        return Player::where('present', true)->get();
    }

    /** Fingerprint of who's present and their roles — drives re-shuffle on change. */
    private function signature($present): string
    {
        return $present->sortBy('id')
            ->map(fn (Player $p) => $p->id.':'.$p->role)
            ->implode(',');
    }

    /**
     * Map stored id arrays to display data, recomputing power from the current
     * blended tier+MMR weights so it tracks live roster/tier changes.
     *
     * @param  array<int,float>  $powerMap  player id → blended balancing weight
     * @return array{teams: array, powers: array, reserves: array}|null
     */
    private function hydrate(?array $shuffle, $present, array $powerMap): ?array
    {
        if ($shuffle === null) {
            return null;
        }

        $byId = $present->keyBy('id');
        $map = fn (array $ids) => collect($ids)
            ->map(fn ($id) => $byId->get($id))
            ->filter()
            ->map(fn (Player $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'tier' => $this->mmr->tierForMmr((int) ($powerMap[$p->id] ?? MmrService::SEED)),
                'role' => $p->role,
                'weight' => (int) round($powerMap[$p->id] ?? MmrService::SEED),
            ])
            ->values();

        $teams = array_map($map, $shuffle['teams']);

        return [
            'teams' => $teams,
            'powers' => array_map(fn ($team) => $team->sum('weight'), $teams),
            'reserves' => $map($shuffle['reserves'] ?? []),
        ];
    }
}
