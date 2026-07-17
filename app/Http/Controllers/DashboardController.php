<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\FamilyMember;
use App\Models\Medication;
use App\Models\Report;
use App\Models\Vaccination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $user->ensureLinkedFamilyMember();

        $familyMembers = FamilyMember::where('primary_account_id', $user->id)
            ->orWhere('linked_user_id', $user->id)
            ->orderByRaw("relation = 'self' desc")
            ->orderBy('full_name')
            ->get();

        $activeId = session('active_family_member_id');
        $active = $familyMembers->firstWhere('id', $activeId)
            ?? $familyMembers->firstWhere('relation', 'self')
            ?? $familyMembers->first();

        session(['active_family_member_id' => $active->id]);

        $bmiLogs = $active->bmiLogs()->orderByDesc('recorded_date')->orderByDesc('id')->take(2)->get();
        $latestBmi = $bmiLogs->first();
        $previousBmi = $bmiLogs->skip(1)->first();

        $trend = null;
        if ($latestBmi && $previousBmi) {
            $trend = match (true) {
                $latestBmi->bmi_value > $previousBmi->bmi_value => 'up',
                $latestBmi->bmi_value < $previousBmi->bmi_value => 'down',
                default => 'stable',
            };
        }

        $recentReports = $active->reports()->latest('uploaded_at')->take(5)->get();

        $activeMedications = Medication::where('family_member_id', $active->id)
            ->where('active', true)
            ->with(['medicationLogs' => fn ($q) => $q->whereDate('scheduled_at', today())])
            ->get();

        $doseStatuses = [];
        foreach ($activeMedications as $medication) {
            foreach ($medication->schedule_times ?? [] as $time) {
                $log = $medication->medicationLogs->first(fn ($l) => $l->scheduled_at->format('H:i') === $time);
                $doseStatuses["{$medication->id}:{$time}"] = $log?->status ?? 'pending';
            }
        }

        $upcomingVaccinations = $active->vaccinations()
            ->whereNotNull('next_due_date')
            ->orderBy('next_due_date')
            ->take(5)
            ->get();

        $onboarding = null;
        if (! $user->onboarding_dismissed_at) {
            $steps = [
                'family' => $familyMembers->count() > 1,
                'report' => Report::whereIn('family_member_id', $familyMembers->pluck('id'))->exists(),
                'assistant' => ChatMessage::where('asked_by_user_id', $user->id)->exists(),
            ];

            if (in_array(false, $steps, true)) {
                $onboarding = $steps;
            }
        }

        return view('dashboard', [
            'active' => $active,
            'familyMembers' => $familyMembers,
            'latestBmi' => $latestBmi,
            'trend' => $trend,
            'recentReports' => $recentReports,
            'activeMedications' => $activeMedications,
            'doseStatuses' => $doseStatuses,
            'upcomingVaccinations' => $upcomingVaccinations,
            'onboarding' => $onboarding,
        ]);
    }

    public function dismissOnboarding(Request $request): JsonResponse
    {
        $request->user()->update(['onboarding_dismissed_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function switch(Request $request, FamilyMember $familyMember): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $familyMember->primary_account_id === $user->id || $familyMember->linked_user_id === $user->id,
            403
        );

        session(['active_family_member_id' => $familyMember->id]);

        return redirect()->route('dashboard');
    }
}
