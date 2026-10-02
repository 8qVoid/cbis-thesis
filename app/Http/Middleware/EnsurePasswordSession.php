<?php

namespace App\Http\Middleware;

use App\Support\PasswordSessions;
use Closure;
use Illuminate\Auth\Recaller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordSession
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach (PasswordSessions::GUARDS as $guardName) {
            $guard = Auth::guard($guardName);
            $hadSession = $request->session()->has($guard->getName());
            $user = $guard->user();

            if (! $user) {
                $request->session()->forget(PasswordSessions::key($guardName));

                continue;
            }

            // A real session guard authenticates from a persisted login ID or
            // a remember cookie. Test actingAs() injects only an in-memory user.
            if (! $hadSession && ! $guard->viaRemember()) {
                continue;
            }

            $fingerprint = PasswordSessions::fingerprint($guardName, $user);
            if ($guard->viaRemember()) {
                $cookie = $request->cookies->get($guard->getRecallerName());
                $recaller = is_string($cookie) ? new Recaller($cookie) : null;
                if (! $recaller?->valid() || ! hash_equals($fingerprint, $recaller->hash())) {
                    return $this->invalidate($request);
                }
            }

            $stored = $request->session()->get(PasswordSessions::key($guardName));
            // Do not initialize a legacy session using the current password:
            // that would allow it to survive a password reset without a marker.
            if (! is_string($stored) || ! hash_equals($fingerprint, $stored)) {
                return $this->invalidate($request);
            }
        }

        return $next($request);
    }

    private function invalidate(Request $request): Response
    {
        foreach (PasswordSessions::GUARDS as $guard) {
            Auth::guard($guard)->logoutCurrentDevice();
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson()
            ? response()->json(['message' => 'Unauthenticated.'], 401)
            : redirect()->guest(route('login'));
    }
}
