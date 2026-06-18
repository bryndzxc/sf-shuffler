<?php

namespace App\Http\Middleware;

use App\Models\Player;
use App\Models\TeamName;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            // Drives the "players ready" counter in the nav shell (present/total).
            'rosterCounts' => fn () => [
                'ready' => Player::where('present', true)->count(),
                'total' => Player::count(),
            ],
            // Custom team-slot names (null = use NATO default on the frontend).
            'teamNames' => fn () => TeamName::resolved(),
        ];
    }
}
