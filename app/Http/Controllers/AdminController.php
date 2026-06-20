<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Hidden single-password admin gate (not linked in the nav). A correct password
 * sets an `is_admin` session flag, which unlocks admin-only actions (deleting
 * players) and is shared to the frontend so admin-only controls can show.
 */
class AdminController extends Controller
{
    /** The login page at /admin/login. */
    public function show()
    {
        return Inertia::render('Admin/Login', [
            'isAdmin' => (bool) session('is_admin'),
        ]);
    }

    /** Check the password against ADMIN_PASSWORD and flag the session. */
    public function login(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);

        $expected = config('app.admin_password');

        if (! $expected || ! hash_equals($expected, $request->input('password'))) {
            return back()->withErrors(['password' => 'Incorrect admin password.']);
        }

        $request->session()->put('is_admin', true);

        return redirect()->route('roster.index');
    }

    /** Drop admin privileges. */
    public function logout(Request $request)
    {
        $request->session()->forget('is_admin');

        return back();
    }
}
