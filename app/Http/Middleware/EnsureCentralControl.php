<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCentralControl
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (! $user->isCentralAdmin()) {
            abort(403, 'Only the Philippine Red Cross central administrator can access this module.');
        }

        return $next($request);
    }
}
