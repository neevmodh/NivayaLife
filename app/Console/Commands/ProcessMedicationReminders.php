<?php

namespace App\Console\Commands;

use App\Mail\MedicationDoseReminderMail;
use App\Models\MedicationLog;
use App\Models\User;
use App\Services\Push\WebPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Three related jobs on the same pending dose logs, run together since all
 * three only make sense periodically throughout the day: email + push a
 * reminder for doses coming due, push a single escalation nudge for one
 * still ignored partway through its grace period, and age out any pending
 * dose nobody acted on into 'missed' once that grace period has passed.
 */
class ProcessMedicationReminders extends Command
{
    protected $signature = 'app:process-medication-reminders';

    protected $description = 'Email/push reminders for doses coming due, escalate ignored ones, and mark stale pending doses missed';

    private const REMINDER_WINDOW_MINUTES = 20;

    private const ESCALATION_AFTER_MINUTES = 30;

    private const MISSED_GRACE_HOURS = 2;

    public function handle(WebPushService $webPush): int
    {
        $now = now();
        $sent = 0;
        $escalated = 0;

        MedicationLog::where('status', 'pending')
            ->whereNull('reminded_at')
            ->whereBetween('scheduled_at', [$now->copy()->subMinutes(self::REMINDER_WINDOW_MINUTES), $now])
            ->whereHas('medication', fn ($q) => $q->where('reminder_enabled', true))
            ->with('medication.familyMember')
            ->chunkById(100, function ($logs) use (&$sent, $webPush) {
                foreach ($logs as $log) {
                    $recipients = $log->medication->familyMember->notifiableUsers();

                    if ($recipients->isEmpty()) {
                        continue;
                    }

                    foreach ($recipients as $user) {
                        Mail::to($user->email)->send(new MedicationDoseReminderMail($log));
                        $this->pushDoseAlarm($webPush, $user, $log, 'Medication reminder');
                    }

                    $log->update(['reminded_at' => now()]);
                    $sent++;
                }
            });

        // A single follow-up push (no repeat email — that would get spammy)
        // for a dose that was reminded about but still hasn't been marked
        // taken, sent partway through the grace period rather than only
        // finding out it was missed after the fact.
        MedicationLog::where('status', 'pending')
            ->whereNotNull('reminded_at')
            ->whereNull('escalation_sent_at')
            ->where('reminded_at', '<=', $now->copy()->subMinutes(self::ESCALATION_AFTER_MINUTES))
            ->with('medication.familyMember')
            ->chunkById(100, function ($logs) use (&$escalated, $webPush) {
                foreach ($logs as $log) {
                    foreach ($log->medication->familyMember->notifiableUsers() as $user) {
                        $this->pushDoseAlarm($webPush, $user, $log, 'Still pending');
                    }

                    $log->update(['escalation_sent_at' => now()]);
                    $escalated++;
                }
            });

        $missed = MedicationLog::where('status', 'pending')
            ->where('scheduled_at', '<', $now->copy()->subHours(self::MISSED_GRACE_HOURS))
            ->update(['status' => 'missed']);

        $this->info("Sent {$sent} reminder(s), {$escalated} escalation(s), marked {$missed} dose(s) missed.");

        return self::SUCCESS;
    }

    /**
     * Alarm-style push: stays on screen until dismissed (requireInteraction),
     * vibrates, and carries "Mark as taken" / "Snooze 10 min" buttons that
     * the service worker resolves against a signed, no-login-required URL —
     * the notification itself is the only "device" those buttons run on.
     */
    private function pushDoseAlarm(WebPushService $webPush, User $user, MedicationLog $log, string $title): void
    {
        $webPush->sendToUser(
            $user,
            $title,
            "{$log->medication->medicine_name} ({$log->medication->dosage}) is due for {$log->medication->familyMember->full_name}.",
            '/medications',
            [
                'tag' => "medication-log-{$log->id}",
                'requireInteraction' => true,
                'vibrate' => [200, 100, 200],
                'actions' => [
                    ['action' => 'taken', 'title' => 'Mark as taken'],
                    ['action' => 'snooze', 'title' => 'Snooze 10 min'],
                ],
                'actionUrls' => [
                    'taken' => URL::temporarySignedRoute('medications.quick-action', now()->addHours(6), ['log' => $log->id, 'action' => 'taken']),
                    'snooze' => URL::temporarySignedRoute('medications.quick-action', now()->addHours(6), ['log' => $log->id, 'action' => 'snooze']),
                ],
            ]
        );
    }
}
