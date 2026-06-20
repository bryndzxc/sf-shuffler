<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards admin-only actions (currently: deleting players). Admin status is a
 * single shared password gate held in the session — see AdminController.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('is_admin')) {
            abort(403, 'Admin access required.');
        }

        return $next($request);
    }
}
