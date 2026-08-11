<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsStaff;
use App\Http\Middleware\ResolveRegion;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'staff' => EnsureUserIsStaff::class,
            'role' => EnsureUserHasRole::class,
            // Sets the regional storefront (currency, tax, hreflang) from the
            // route prefix — see routes/web.php and config/regions.php.
            'region' => ResolveRegion::class,
        ]);

        // The Stripe webhook is authenticated by signature, never by CSRF token.
        $middleware->validateCsrfTokens(except: [
            'webhooks/stripe',
        ]);

        // The region cookie is written by the client (the storefront selector
        // and the geolocation banner), so it never carries Laravel's encryption
        // envelope — encrypting it would make every client-set value fail to
        // decrypt and silently vanish. It holds a region key, never anything
        // sensitive, and is validated against config/regions.php on read.
        //
        // Named literally because config is not yet loaded at this point in the
        // bootstrap; it must stay in step with config('regions.cookie').
        $middleware->encryptCookies(except: [
            'pw_region',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Render JSON errors for API-style and AJAX requests (the domain
        // search, cart and other XHR endpoints all expect JSON).
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
