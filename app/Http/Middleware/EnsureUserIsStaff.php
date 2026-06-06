<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to staff/admin users with an active account. Customers
 * receive a 403. (The Filament panel additionally gates on canAccessPanel().)
 */
class EnsureUserIsStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user === null || ! $user->isStaff() || $user->status !== 'active', 403);

        return $next($request);
    }
}
