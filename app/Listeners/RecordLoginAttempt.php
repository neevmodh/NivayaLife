<?php

namespace App\Listeners;

use App\Models\LoginLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;

/**
 * Registered for both Login and Failed in AppServiceProvider — every login
 * path in the app (password form, Google OAuth, invitation accept,
 * registration complete) fires one of these two under the hood, so this one
 * listener covers all of them rather than needing a log call in each
 * controller.
 */
class RecordLoginAttempt
{
    public function handle(Login|Failed $event): void
    {
        LoginLog::create([
            'user_id' => $event->user?->id,
            'email' => $event->user?->email ?? ($event->credentials['email'] ?? null),
            'successful' => $event instanceof Login,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
