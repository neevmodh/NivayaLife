<?php

namespace App\Http\Controllers;

use App\Services\Push\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        $request->user()->pushSubscriptions()->updateOrCreate(
            ['endpoint_hash' => hash('sha256', $validated['endpoint'])],
            [
                'endpoint' => $validated['endpoint'],
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
            ]
        );

        return response()->json(['status' => 'subscribed']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        $request->user()->pushSubscriptions()
            ->where('endpoint_hash', hash('sha256', $validated['endpoint']))
            ->delete();

        return response()->json(['status' => 'unsubscribed']);
    }

    public function test(Request $request, WebPushService $webPush): JsonResponse
    {
        if ($request->user()->pushSubscriptions()->doesntExist()) {
            return response()->json(['status' => 'no_subscription'], 422);
        }

        $webPush->sendToUser(
            $request->user(),
            'Test notification',
            'Push notifications are working on this device.',
            '/profile'
        );

        return response()->json(['status' => 'sent']);
    }
}
