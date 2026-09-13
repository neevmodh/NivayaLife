<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ScopesToCaller;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessReportOcrJob;
use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\Report;
use App\Rules\ReadableDocumentImage;
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
     * Full detail for one report — the list shape omits the fields that
     * make a detail screen actually worth opening (lab results, OCR text,
     * x-ray/entity findings). Authorized the same way the web report page
     * is (read access, not edit access) since this is a single-resource
     * lookup by id rather than a member-scoped list.
     */
    public function show(Request $request, Report $report): JsonResponse
    {
        abort_unless($report->familyMember->hasGrantedAccessTo($request->user()), 403);

        return response()->json([
            'id' => $report->id,
            'type' => $report->type,
            'type_label' => $report->typeLabel(),
            'ocr_status' => $report->ocr_status,
            'report_date' => ($report->report_date ?? $report->uploaded_at)?->toDateString(),
            'hospital_or_clinic_name' => $report->hospital_or_clinic_name,
            'doctor_name' => $report->doctor_name,
            'ai_summary' => $report->ai_summary,
            'ocr_text' => $report->ocr_text,
            'lab_results' => $report->lab_results,
            'xray_findings' => $report->xray_findings,
            'detected_entities' => $report->detected_entities,
            'original_filename' => $report->original_filename,
        ]);
    }

    /**
     * Mobile upload — same validation/hash-dedup/store logic as the web
     * ReportUploadController::store, minus the /reports/detect pre-fill
     * round-trip (that's UX polish, not needed for a working v1 upload).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $member = $this->resolveMember($user, $request->integer('family_member_id') ?: null);

        abort_unless($member->canBeEditedBy($user), 403);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,tiff,tif,bmp,gif,docx', 'max:10240', new ReadableDocumentImage],
            'type' => ['required', 'in:blood_test,prescription,xray,sonography,mri_ct,insurance,bill,ecg,dental,discharge_summary,pathology,eye_care,other'],
            'report_date' => ['required', 'date', 'before_or_equal:today'],
            'hospital_or_clinic_name' => ['nullable', 'string', 'max:255'],
            'doctor_name' => ['nullable', 'string', 'max:255'],
            'force' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('file');
        $hash = hash_file('sha256', $file->getRealPath());

        if (! ($validated['force'] ?? false)) {
            $duplicate = Report::where('family_member_id', $member->id)
                ->where('file_hash', $hash)
                ->first();

            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'duplicate' => true,
                    'message' => 'This exact file is already uploaded for '.$member->full_name.'.',
                    'existing_report_id' => $duplicate->id,
                ], 409);
            }
        }

        $path = $file->store("reports/{$member->id}", 'local');

        $report = Report::create([
            'family_member_id' => $member->id,
            'uploaded_by_user_id' => $user->id,
            'type' => $validated['type'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_hash' => $hash,
            'report_date' => $validated['report_date'],
            'hospital_or_clinic_name' => $validated['hospital_or_clinic_name'] ?? null,
            'doctor_name' => $validated['doctor_name'] ?? null,
            'uploaded_at' => now(),
            'ocr_status' => 'pending',
        ]);

        ProcessReportOcrJob::dispatch($report);

        return response()->json(['success' => true, 'report_id' => $report->id]);
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
