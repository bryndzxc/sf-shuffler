<?php

namespace App\Http\Controllers;

use App\Models\TeamName;
use App\Services\ShuffleService;
use Illuminate\Http\Request;

class TeamNameController extends Controller
{
    /** Rename a team slot, or reset it to the NATO default when blank. */
    public function update(Request $request, int $slot)
    {
        abort_unless($slot >= 0 && $slot < ShuffleService::MAX_TEAMS, 404);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:30'],
        ]);

        $name = trim($data['name'] ?? '');

        if ($name === '') {
            TeamName::where('slot', $slot)->delete(); // back to default
        } else {
            TeamName::updateOrCreate(['slot' => $slot], ['name' => $name]);
        }

        return back();
    }
}
