<?php

use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureCentralControl;
use App\Http\Middleware\EnsureFacilityAccess;
use App\Http\Middleware\EnsureFacilityOperator;
use App\Http\Middleware\EnsurePasswordSession;
use App\Http\Middleware\EnsurePublicAccountEmailVerified;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render terminates TLS at its proxy. Trust its forwarded HTTPS details so
        // Laravel validates signed verification URLs against the public URL.
        if (env('APP_ENV') === 'production') {
            $middleware->trustProxies(at: '*');
        }

        $middleware->web(append: [EnsurePasswordSession::class, EnsureActiveAccount::class]);
        $middleware->prependToPriorityList(AuthenticatesRequests::class, EnsurePasswordSession::class);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'facility.access' => EnsureFacilityAccess::class,
            'central.control' => EnsureCentralControl::class,
            'facility.operator' => EnsureFacilityOperator::class,
            'public.verified' => EnsurePublicAccountEmailVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
