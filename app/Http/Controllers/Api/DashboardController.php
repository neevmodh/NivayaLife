<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ScopesToCaller;
use App\Http\Controllers\Controller;
use App\Models\FamilyMember;
use App\Models\Medication;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ScopesToCaller;

    /**
     * Everything the app's home screen needs, in one response.
     *
     * A phone on a slow connection should not make six requests to draw one
     * screen, so this is deliberately a composite rather than a set of REST
     * resources — the shape is dictated by the screen that consumes it.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $active = $this->resolveMember($user, $request->integer('member') ?: null);

        $members = FamilyMember::whereIn('id', $this->accessibleMemberIds($user))
            ->orderByRaw("relation = 'self' desc")
            ->orderBy('full_name')
            ->get();

        $latestBmi = $active->bmiLogs()->orderByDesc('recorded_date')->orderByDesc('id')->first();

        $medications = Medication::where('family_member_id', $active->id)
            ->where('active', true)
            ->with(['medicationLogs' => fn ($q) => $q->whereDate('scheduled_at', today())])
            ->get();

        return response()->json([
            'active' => $this->member($active),
            'family_members' => $members->map(fn ($m) => $this->member($m))->values(),
            'reports' => $active->reports()->latest('uploaded_at')->limit(10)->get()
                ->map(fn ($r) => $this->report($r))->values(),
            'medications' => $medications->map(fn ($m) => $this->medication($m))->values(),
            'vaccinations' => $active->vaccinations()
                ->whereNotNull('next_due_date')->orderBy('next_due_date')->limit(10)->get()
                ->map(fn ($v) => [
                    'id' => $v->id,
                    'vaccine_name' => $v->vaccine_name,
                    'dose_number' => (int) $v->dose_number,
                    'next_due_date' => $v->next_due_date?->toDateString(),
                ])->values(),
            'bmi' => $latestBmi?->bmi_value === null ? null : round((float) $latestBmi->bmi_value, 1),
            'bmi_category' => $latestBmi?->bmi_category,
        ]);
    }

    /** The member list, for a switcher that does not need the full record. */
    public function members(Request $request): JsonResponse
    {
        $members = FamilyMember::whereIn('id', $this->accessibleMemberIds($request->user()))
            ->orderByRaw("relation = 'self' desc")
            ->orderBy('full_name')
            ->get();

        return response()->json(['data' => $members->map(fn ($m) => $this->member($m))->values()]);
    }

    private function member(FamilyMember $member): array
    {
        return [
            'id' => $member->id,
            'full_name' => $member->full_name,
            'relation' => $member->relation,
            'photo_url' => $member->photo_path ? asset('storage/'.$member->photo_path) : null,
            'avatar_preset' => $member->avatar_preset,
            'blood_group' => $member->blood_group,
            'gender' => $member->gender,
            'age' => $member->age(),
            'unique_health_id' => $member->unique_health_id,
        ];
    }

    private function report(Report $report): array
    {
        return [
            'id' => $report->id,
            'type' => $report->type,
            'type_label' => $report->typeLabel(),
            'ocr_status' => $report->ocr_status,
            'report_date' => ($report->report_date ?? $report->uploaded_at)?->toDateString(),
            'hospital_or_clinic_name' => $report->hospital_or_clinic_name,
            'ai_summary' => $report->ai_summary,
        ];
    }

    private function medication(Medication $medication): array
    {
        // Which of today's doses are already taken, so the app can render every
        // chip's state without a second round trip.
        $taken = collect($medication->schedule_times ?? [])
            ->filter(function ($time) use ($medication) {
                $log = $medication->medicationLogs->first(fn ($l) => $l->scheduled_at->format('H:i') === $time);

                return ($log?->status ?? 'pending') === 'taken';
            })
            ->values();

        return [
            'id' => $medication->id,
            'medicine_name' => $medication->medicine_name,
            'dosage' => $medication->dosage,
            'schedule_times' => $medication->schedule_times ?? [],
            'taken_times' => $taken,
        ];
    }
}
