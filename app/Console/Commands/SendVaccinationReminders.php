<?php

namespace App\Console\Commands;

use App\Mail\VaccinationDueReminderMail;
use App\Models\Vaccination;
use App\Services\Push\WebPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendVaccinationReminders extends Command
{
    protected $signature = 'app:send-vaccination-reminders';

    protected $description = 'Email a reminder for vaccinations due within a week, or already overdue, at most once per day';

    private const LOOKAHEAD_DAYS = 7;

    public function handle(WebPushService $webPush): int
    {
        $today = today();
        $sent = 0;

        Vaccination::whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<=', $today->copy()->addDays(self::LOOKAHEAD_DAYS))
            ->where(function ($q) use ($today) {
                $q->whereNull('last_reminded_at')->orWhereDate('last_reminded_at', '<', $today);
            })
            ->with('familyMember')
            ->chunkById(100, function ($vaccinations) use (&$sent, $webPush) {
                foreach ($vaccinations as $vaccination) {
                    $recipients = $vaccination->familyMember->notifiableUsers();

                    if ($recipients->isEmpty()) {
                        continue;
                    }

                    $overdue = $vaccination->next_due_date->isPast();

                    foreach ($recipients as $user) {
                        Mail::to($user->email)->send(new VaccinationDueReminderMail($vaccination));

                        $webPush->sendToUser(
                            $user,
                            $overdue ? 'Vaccination overdue' : 'Vaccination due soon',
                            "{$vaccination->vaccine_name} for {$vaccination->familyMember->full_name} — due {$vaccination->next_due_date->format('M j, Y')}.",
                            '/vaccinations'
                        );
                    }

                    $vaccination->update(['last_reminded_at' => now()]);
                    $sent++;
                }
            });

        $this->info("Sent {$sent} vaccination reminder(s).");

        return self::SUCCESS;
    }
}
