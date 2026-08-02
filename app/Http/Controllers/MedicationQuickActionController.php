<?php

namespace App\Http\Controllers;

use App\Models\MedicationLog;
use Illuminate\Http\JsonResponse;

/**
 * Backs the "Mark as taken" / "Snooze" buttons on a medication push
 * notification. Reached directly from the service worker (no page, no
 * session guaranteed on that device) via a signed URL generated at send
 * time — the signature itself is the authorization, the same trust model
 * as an email unsubscribe link, scoped to exactly one dose log.
 */
class MedicationQuickActionController extends Controller
{
    public function handle(MedicationLog $log, string $action): JsonResponse
    {
        abort_unless(in_array($action, ['taken', 'snooze'], true), 404);

        if ($log->status === 'pending') {
            if ($action === 'taken') {
                $log->update(['status' => 'taken', 'taken_at' => now()]);
            } else {
                $log->update([
                    'scheduled_at' => now()->addMinutes(10),
                    'reminded_at' => null,
                    'escalation_sent_at' => null,
                ]);
            }
        }

        return response()->json(['status' => $log->fresh()->status]);
    }
}
