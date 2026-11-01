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
        // The app sits behind a reverse proxy (Plesk's nginx) that terminates HTTPS. Without this Laravel thinks the
        // request is plain http and builds http:// URLs (mixed content in the admin). Set TRUSTED_PROXIES to the
        // proxy's address(es), comma separated; '*' trusts every proxy, which is only safe when the app can only be
        // reached through the proxy.
        $proxies = env('TRUSTED_PROXIES');

        if ($proxies) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : explode(',', $proxies));
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
