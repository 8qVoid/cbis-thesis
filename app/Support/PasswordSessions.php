<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;

class PasswordSessions
{
    public const GUARDS = ['web', 'donor'];

    public static function key(string $guard): string
    {
        return 'password_fingerprint_'.$guard;
    }

    public static function fingerprint(string $guard, Authenticatable $user): string
    {
        return Auth::guard($guard)->hashPasswordForCookie($user->getAuthPassword());
    }

    public static function remember(Session $session, string $guard, Authenticatable $user): void
    {
        $session->put(self::key($guard), self::fingerprint($guard, $user));
    }
}
