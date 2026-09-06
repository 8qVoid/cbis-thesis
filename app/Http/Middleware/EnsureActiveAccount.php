<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            abort_unless($user->is_active || $request->routeIs('logout'), 403, 'This account is inactive.');
            if (($user->isBloodBankStaff() || $user->isEventFacilitator()) && ! $request->routeIs('logout')) {
                abort_unless($user->facility?->is_active, 403, 'This facility account is inactive.');
            }
        }

        return $next($request);
    }
}
