<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to users holding one of the given roles.
 * Usage: ->middleware('role:technical_admin,super_admin')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);

        // Super Admins always pass.
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        abort_unless($user->hasAnyRole($roles), 403);

        return $next($request);
    }
}
