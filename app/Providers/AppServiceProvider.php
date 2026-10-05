<?php

namespace App\Providers;

use App\Mail\GoogleScriptTransport;
use App\Support\LoginIdentity;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Mail::extend('google_script', fn (array $config) => new GoogleScriptTransport(
            (string) ($config['endpoint'] ?? ''),
            (string) ($config['secret'] ?? ''),
        ));

        // Render terminates HTTPS before forwarding the request to Apache.
        // Generate secure links for stylesheets, scripts, routes, and uploads.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Event listeners are auto-discovered in Laravel 13.
        RateLimiter::for('login', function (Request $request): array {
            $identity = LoginIdentity::throttleKey($request->input('login'));

            return [
                Limit::perMinute(5)->by('identity:'.$identity.'|'.$request->ip()),
                Limit::perMinute(30)->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('donor-register', fn (Request $request): Limit => Limit::perMinutes(10, 3)->by((string) $request->ip()));
        RateLimiter::for('facility-apply', fn (Request $request): Limit => Limit::perMinutes(30, 2)->by((string) $request->ip()));
        RateLimiter::for('password-email', function (Request $request): Limit {
            $identifier = (string) $request->input('email', 'unknown');

            return Limit::perMinutes(10, 3)->by(strtolower($identifier).'|'.$request->ip());
        });
        RateLimiter::for('password-reset', function (Request $request): Limit {
            $identifier = (string) $request->input('email', 'unknown');
            $accountType = (string) $request->input('account_type', 'unknown');

            return Limit::perMinutes(10, 6)->by($accountType.'|'.strtolower($identifier).'|'.$request->ip());
        });

        RateLimiter::for('password-change', function (Request $request): Limit {
            $identifier = (string) optional($request->user())->getAuthIdentifier();

            return Limit::perMinutes(10, 6)->by(($identifier !== '' ? $identifier : 'guest').'|'.$request->ip());
        });
        RateLimiter::for('verification-email', function (Request $request): Limit {
            $identifier = (string) optional($request->user())->getAuthIdentifier();

            return Limit::perMinutes(10, 3)->by(($identifier !== '' ? $identifier : 'guest').'|'.$request->ip())
                ->response(function (Request $request, array $headers) {
                    $minutes = max(1, (int) ceil(((int) ($headers['Retry-After'] ?? 600)) / 60));
                    $message = "Too many resend requests. Please wait {$minutes} minute(s) before requesting another verification email. Your account is still saved.";

                    if ($request->expectsJson()) {
                        return response()->json(['message' => $message], 429, $headers);
                    }

                    return redirect()->route('verification.notice')->with('verification_warning', $message)->withHeaders($headers);
                });
        });
    }
}
