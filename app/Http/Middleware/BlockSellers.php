<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * For admin/franchise write actions (courier settings, rate cards) that have no
 * dedicated permission: any logged-in role except a seller ("user") may pass.
 */
class BlockSellers
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['status' => 'error', 'msg' => 'Unauthenticated.'], 401)
                : redirect()->guest(route('login'));
        }

        if ($user->role === 'user') {
            abort(403);
        }

        return $next($request);
    }
}
