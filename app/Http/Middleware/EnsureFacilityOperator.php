<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFacilityOperator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->isCentralAdmin()) {
            return $next($request);
        }

        if ($user->facility_id === null) {
            abort(403, 'Facility operational actions require an assigned facility account.');
        }

        return $next($request);
    }
}
