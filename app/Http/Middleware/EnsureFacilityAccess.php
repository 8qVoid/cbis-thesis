<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFacilityAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (! $user->isCentralAdmin() && ! $user->facility_id) {
            abort(403, 'User is not assigned to any facility.');
        }

        return $next($request);
    }
}
