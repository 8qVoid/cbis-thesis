<?php

namespace App\Http\Middleware;

use App\Models\Donor;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user instanceof Donor) {
            abort_unless($user->is_online_registered || $request->routeIs('logout'), 403, 'This account is inactive.');
        } elseif ($user instanceof User) {
            abort_unless($user->is_active || $request->routeIs('logout'), 403, 'This account is inactive.');
            if (($user->isBloodBankStaff() || $user->isEventFacilitator()) && ! $request->routeIs('logout')) {
                abort_unless($user->facility?->is_active, 403, 'This facility account is inactive.');
            }
        }

        return $next($request);
    }
}
