<?php

namespace App\Support;

use App\Models\FamilyInvitation;
use App\Models\FamilyMember;
use App\Models\Medication;
use App\Models\Report;
use App\Models\User;
use App\Models\Vaccination;
use Illuminate\Support\Collection;

/**
 * Builds the notification bell's contents out of state the app already has.
 *
 * Nothing here is stored — there is no notifications table and no read/unread
 * flags. Every item is derived on the fly from the same records the reminder
 * emails are built from, so the bell can never drift out of sync with what is
 * actually true, and dismissing something is simply a matter of doing it.
 */
class NotificationFeed
{
    /**
     * @return Collection<int, array{tone: string, title: string, meta: string, url: string, icon: string}>
     */
    public static function for(User $user): Collection
    {
        $memberIds = FamilyMember::query()
            ->where('primary_account_id', $user->id)
            ->orWhere('linked_user_id', $user->id)
            ->pluck('id');

        if ($memberIds->isEmpty()) {
            return collect();
        }

        return collect()
            ->concat(self::pendingInvitations($user))
            ->concat(self::overdueVaccinations($memberIds))
            ->concat(self::dosesDueToday($memberIds))
            ->concat(self::reportsStillReading($memberIds))
            // Most urgent first, then newest — the bell is only useful if the
            // thing that matters most is the thing you see without scrolling.
            ->sortBy(fn ($item) => ['urgent' => 0, 'warn' => 1, 'info' => 2][$item['tone']] ?? 3)
            ->values();
    }

    private static function pendingInvitations(User $user): Collection
    {
        return FamilyInvitation::query()
            ->where('invited_email', $user->email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with('primaryAccount:id,name')
            ->get()
            ->map(fn ($invite) => [
                'tone' => 'urgent',
                'title' => ($invite->primaryAccount->name ?? 'Someone').' invited you to their family',
                'meta' => 'Expires '.$invite->expires_at->diffForHumans(),
                'url' => route('invite.show', $invite->token),
                'icon' => 'M17 20h4v-2a4 4 0 0 0-3-3.87M13 3.13a4 4 0 0 1 0 7.75M3 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
            ]);
    }

    private static function overdueVaccinations(Collection $memberIds): Collection
    {
        return Vaccination::query()
            ->whereIn('family_member_id', $memberIds)
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<=', now()->addDays(7))
            ->with('familyMember:id,full_name')
            ->orderBy('next_due_date')
            ->limit(5)
            ->get()
            ->map(function ($vaccination) {
                $overdue = $vaccination->next_due_date->startOfDay()->isPast();

                return [
                    'tone' => $overdue ? 'urgent' : 'warn',
                    'title' => $vaccination->vaccine_name.($overdue ? ' is overdue' : ' is due soon'),
                    'meta' => ($vaccination->familyMember->full_name ?? 'Someone').' · '.$vaccination->next_due_date->format('M j'),
                    'url' => route('vaccinations.index').'?member='.$vaccination->family_member_id,
                    'icon' => 'M19 8 8 19l-5-5M14 3l7 7-3 3-7-7 3-3Z',
                ];
            });
    }

    private static function dosesDueToday(Collection $memberIds): Collection
    {
        $medications = Medication::query()
            ->whereIn('family_member_id', $memberIds)
            ->where('active', true)
            ->with(['medicationLogs' => fn ($q) => $q->whereDate('scheduled_at', today())])
            ->get();

        $outstanding = 0;
        foreach ($medications as $medication) {
            foreach ($medication->schedule_times ?? [] as $time) {
                $log = $medication->medicationLogs->first(fn ($l) => $l->scheduled_at->format('H:i') === $time);
                if (($log?->status ?? 'pending') === 'pending') {
                    $outstanding++;
                }
            }
        }

        if ($outstanding === 0) {
            return collect();
        }

        // Rolled into one line rather than one row per dose — six separate
        // "take your tablet" rows would bury everything else in the list.
        return collect([[
            'tone' => 'warn',
            'title' => $outstanding === 1 ? '1 dose still to take today' : "{$outstanding} doses still to take today",
            'meta' => 'Tap a time on the dashboard to mark it taken',
            'url' => route('dashboard').'#todays-medications',
            'icon' => 'M10.5 20.5 3.5 13.5a4.95 4.95 0 1 1 7-7l1 1 1-1a4.95 4.95 0 0 1 7 7l-7 7-1.5-1.5',
        ]]);
    }

    private static function reportsStillReading(Collection $memberIds): Collection
    {
        $count = Report::query()
            ->whereIn('family_member_id', $memberIds)
            ->whereIn('ocr_status', ['pending', 'processing'])
            ->count();

        if ($count === 0) {
            return collect();
        }

        return collect([[
            'tone' => 'info',
            'title' => $count === 1 ? 'A report is still being read' : "{$count} reports are still being read",
            'meta' => 'You will see the summary here once it is done',
            'url' => route('reports.index'),
            'icon' => 'M7 3h10a1 1 0 0 1 1 1v16l-3-2-3 2-3-2-3 2V4a1 1 0 0 1 1-1Z',
        ]]);
    }
}
