<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Without Cache-Control: no-store, a browser's back/forward cache (bfcache)
 * can restore a full snapshot of an authenticated page — dashboard, reports,
 * profile — after logout or account deletion, with no request ever reaching
 * the server to reveal the session is gone. The plain 'no-cache' header PHP
 * sends by default for session-backed pages stops normal HTTP caching but
 * does not reliably stop bfcache. Applied globally rather than only to
 * auth-gated routes: a health-records app has no page where showing stale
 * content instead of a fresh server round-trip is the right tradeoff.
 */
class PreventBackHistoryCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $response;
    }
}
