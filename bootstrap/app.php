<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Port 8090 is firewalled to only accept connections from the NPM
        // reverse proxy, so trusting its X-Forwarded-* headers is safe.
        $middleware->trustProxies(at: '*');

        // Unauthenticated API requests must get a clean 401, not a redirect to
        // a 'login' route that doesn't exist (this app uses Filament's own
        // auth, not a named web login route) -- that mismatch was silently
        // turning every bad/missing token into a 500.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/admin/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
