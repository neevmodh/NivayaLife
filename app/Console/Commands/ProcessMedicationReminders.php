<?php

namespace App\Console\Commands;

use App\Mail\MedicationDoseReminderMail;
use App\Models\MedicationLog;
use App\Services\Push\WebPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Two related jobs on the same pending dose logs, run together since both
 * only make sense periodically throughout the day: email a reminder for
 * doses coming due, and age out any pending dose nobody acted on into
 * 'missed' once its grace period has passed.
 */
class ProcessMedicationReminders extends Command
{
    protected $signature = 'app:process-medication-reminders';

    protected $description = 'Email reminders for doses coming due, and mark stale pending doses missed';

    private const REMINDER_WINDOW_MINUTES = 20;

    private const MISSED_GRACE_HOURS = 2;

    public function handle(WebPushService $webPush): int
    {
        $now = now();
        $sent = 0;

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

                        $webPush->sendToUser(
                            $user,
                            'Medication reminder',
                            "{$log->medication->medicine_name} ({$log->medication->dosage}) is due for {$log->medication->familyMember->full_name}.",
                            '/medications'
                        );
                    }

                    $log->update(['reminded_at' => now()]);
                    $sent++;
                }
            });

        $missed = MedicationLog::where('status', 'pending')
            ->where('scheduled_at', '<', $now->copy()->subHours(self::MISSED_GRACE_HOURS))
            ->update(['status' => 'missed']);

        $this->info("Sent {$sent} reminder(s), marked {$missed} dose(s) missed.");

        return self::SUCCESS;
    }
}
