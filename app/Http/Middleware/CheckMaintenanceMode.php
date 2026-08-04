<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global (not the route-scoped 'admin' alias) so it also covers logged-out
 * visitors. Always lets through: admins (so the toggle-flipper isn't locked
 * out themselves), the /up health check (Railway's healthcheckPath — blocking
 * it would make Railway think the deploy itself failed), the GitHub webhook
 * (an external service, not a browser visitor), and auth routes — an admin
 * who isn't currently logged in still needs to reach /login to prove they're
 * an admin in the first place.
 */
class CheckMaintenanceMode
{
    private const BYPASS_ROUTE_PATTERNS = [
        'admin.*', 'webhooks.github', 'login', 'logout', 'register*',
        'auth.google.*', 'password.*', 'verification.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! SiteSetting::current()->maintenance_mode) {
            return $next($request);
        }

        if ($request->user()?->is_admin) {
            return $next($request);
        }

        if ($request->is('up') || Str::is(self::BYPASS_ROUTE_PATTERNS, (string) $request->route()?->getName())) {
            return $next($request);
        }

        return response()->view('maintenance', [
            'message' => SiteSetting::current()->maintenance_message,
        ], 503);
    }
}
