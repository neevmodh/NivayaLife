<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Railway (like most PaaS hosts) terminates HTTPS at its own edge
        // and forwards plain HTTP to the container, so without trusting its
        // X-Forwarded-Proto header every generated URL (including Vite's
        // asset tags) comes out as http:// on an https:// page — browsers
        // then block the mismatch as mixed content and nothing loads.
        // Trusting '*' is safe here: the container is only ever reached
        // through the platform's own proxy, never directly from the internet.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        $middleware->append(\App\Http\Middleware\PreventBackHistoryCaching::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
