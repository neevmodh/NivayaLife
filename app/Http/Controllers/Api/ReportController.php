<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ScopesToCaller;
use App\Http\Controllers\Controller;
use App\Models\Medication;
use App\Models\MedicationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ScopesToCaller;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $member = $this->resolveMember($user, $request->integer('member') ?: null);

        $search = trim((string) $request->query('q', ''));

        $reports = $member->reports()
            ->where('is_archived', false)
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('ai_summary', 'like', "%{$search}%")
                    ->orWhere('hospital_or_clinic_name', 'like', "%{$search}%")
                    ->orWhere('doctor_name', 'like', "%{$search}%")
                    ->orWhere('ocr_text', 'like', "%{$search}%");
            }))
            ->latest('uploaded_at')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $reports->map(fn ($report) => [
                'id' => $report->id,
                'type' => $report->type,
                'type_label' => $report->typeLabel(),
                'ocr_status' => $report->ocr_status,
                'report_date' => ($report->report_date ?? $report->uploaded_at)?->toDateString(),
                'hospital_or_clinic_name' => $report->hospital_or_clinic_name,
                'ai_summary' => $report->ai_summary,
            ])->values(),
        ]);
    }

    /**
     * Marks a dose taken or un-taken.
     *
     * Mirrors the web controller's behaviour exactly, including that
     * toggling is idempotent per (medication, scheduled time) — the app
     * marks optimistically, so a retried request must not double-record.
     */
    public function toggleDose(Request $request, Medication $medication): JsonResponse
    {
        abort_unless($medication->familyMember->canBeEditedBy($request->user()), 403);

        $validated = $request->validate([
            'time' => ['required', 'date_format:H:i'],
        ]);

        $scheduledAt = today()->setTimeFromTimeString($validated['time']);

        $log = MedicationLog::firstOrCreate(
            ['medication_id' => $medication->id, 'scheduled_at' => $scheduledAt],
            ['status' => 'pending']
        );

        $log->status === 'taken'
            ? $log->update(['status' => 'pending', 'taken_at' => null])
            : $log->update(['status' => 'taken', 'taken_at' => now()]);

        return response()->json(['success' => true, 'status' => $log->status]);
    }
}
