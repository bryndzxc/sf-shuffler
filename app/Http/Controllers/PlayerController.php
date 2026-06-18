<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Player;
use App\Services\MmrService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PlayerController extends Controller
{
    /** Roster page: every player, ordered by tier then name. */
    public function index(MmrService $mmr)
    {
        $tierRank = array_flip(Player::TIERS); // S => 0, A => 1, …

        $players = Player::all();
        $ratings = $mmr->ratings($players, GameMatch::orderBy('played_at')->orderBy('id')->get());

        $players = $players
            ->sortBy(fn (Player $p) => sprintf(
                '%d-%s',
                $tierRank[$p->tier] ?? 9,
                strtolower($p->name),
            ))
            ->values()
            ->map(fn (Player $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'tier' => $p->tier,
                'role' => $p->role,
                'present' => $p->present,
                'weight' => $p->weight,
                'mmr' => $ratings[$p->id]['mmr'],
            ]);

        return Inertia::render('Roster/Index', [
            'players' => $players,
            'tiers' => Player::TIERS,
            'roles' => Player::ROLES,
            'tierWeights' => Player::TIER_WEIGHTS,
            'maxRoster' => Player::MAX_PLAYERS,
        ]);
    }

    /** Add a player to the clan. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', $this->uniqueNameRule()],
            'tier' => ['required', Rule::in(Player::TIERS)],
            'role' => ['sometimes', Rule::in(Player::ROLES)],
        ]);

        if (Player::count() >= Player::MAX_PLAYERS) {
            return back()->withErrors([
                'name' => 'Roster is full ('.Player::MAX_PLAYERS.' max) — remove someone first.',
            ]);
        }

        Player::create($data + ['role' => 'rifle', 'present' => false]);

        return back();
    }

    /** Edit name/tier/role or toggle present — all fields optional. */
    public function update(Request $request, Player $player)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:50', $this->uniqueNameRule($player->id)],
            'tier' => ['sometimes', 'required', Rule::in(Player::TIERS)],
            'role' => ['sometimes', 'required', Rule::in(Player::ROLES)],
            'present' => ['sometimes', 'boolean'],
        ]);

        $player->update($data);

        return back();
    }

    /**
     * Reject a callsign already on the roster, case-insensitively. Done in PHP
     * (not a DB `unique`) so it behaves the same on case-sensitive SQLite tests
     * and case-insensitive MySQL. Pass the editing player's id to exclude self.
     */
    private function uniqueNameRule(?int $ignoreId = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($ignoreId) {
            $taken = Player::when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->pluck('name')
                ->contains(fn (string $name) => mb_strtolower($name) === mb_strtolower(trim((string) $value)));

            if ($taken) {
                $fail("Callsign \"{$value}\" is already on the roster.");
            }
        };
    }

    /** Permanently delete a player. */
    public function destroy(Player $player)
    {
        $player->delete();

        return back();
    }

    /** Mark every player present for tonight's session. */
    public function markAllPresent()
    {
        Player::query()->update(['present' => true]);

        return back();
    }

    /** Clear attendance (start of a fresh session). */
    public function clearPresent()
    {
        Player::where('present', true)->update(['present' => false]);

        return back();
    }
}
