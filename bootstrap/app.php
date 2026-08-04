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

        // Appended to the 'web' group specifically (not the true global
        // stack via append()) because it needs $request->route() to resolve
        // route names for its bypass list — the global stack runs before
        // routing, where the route is never yet resolved.
        $middleware->web(append: [\App\Http\Middleware\CheckMaintenanceMode::class]);

        // GitHub signs the webhook payload itself (see GithubWebhookController);
        // it can't carry a Laravel session token, so it's exempt from CSRF.
        $middleware->validateCsrfTokens(except: [
            'webhooks/github',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
