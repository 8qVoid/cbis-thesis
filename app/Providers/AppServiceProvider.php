<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Event listeners are auto-discovered in Laravel 13.
        RateLimiter::for('login', function (Request $request): Limit {
            $identifier = (string) $request->input('login', 'unknown');

            return Limit::perMinute(5)->by($identifier.'|'.$request->ip());
        });

        RateLimiter::for('donor-register', fn (Request $request): Limit => Limit::perMinutes(10, 3)->by((string) $request->ip()));
        RateLimiter::for('facility-apply', fn (Request $request): Limit => Limit::perMinutes(30, 2)->by((string) $request->ip()));

        RateLimiter::for('password-change', function (Request $request): Limit {
            $identifier = (string) optional($request->user())->getAuthIdentifier();

            return Limit::perMinutes(10, 6)->by(($identifier !== '' ? $identifier : 'guest').'|'.$request->ip());
        });
    }
}
