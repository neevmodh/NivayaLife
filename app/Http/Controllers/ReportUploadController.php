<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesActiveFamilyMember;
use App\Jobs\ProcessReportOcrJob;
use App\Models\Doctor;
use App\Models\FamilyMember;
use App\Models\Report;
use App\Services\Ocr\OcrExtractor;
use App\Services\Reports\ReportFieldDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ReportUploadController extends Controller
{
    use ResolvesActiveFamilyMember;

    public function create(Request $request): View
    {
        $user = $request->user();
        $active = $this->resolveActiveFamilyMember($request, $user);

        $doctorNames = Doctor::where('family_member_id', $active->id)
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();

        return view('reports.upload', [
            'active' => $active,
            'doctorNames' => $doctorNames,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'type' => ['required', 'in:blood_test,prescription,xray,mri_ct,insurance,bill,ecg,other'],
            'report_date' => ['required', 'date', 'before_or_equal:today'],
            'hospital_or_clinic_name' => ['nullable', 'string', 'max:255'],
            'doctor_name' => ['nullable', 'string', 'max:255'],
            'force' => ['nullable', 'boolean'],
        ]);

        $familyMember = FamilyMember::findOrFail($validated['family_member_id']);
        abort_unless($familyMember->canBeEditedBy($request->user()), 403);

        $file = $request->file('file');
        $hash = hash_file('sha256', $file->getRealPath());

        if (! ($validated['force'] ?? false)) {
            $duplicate = Report::where('family_member_id', $familyMember->id)
                ->where('file_hash', $hash)
                ->first();

            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'duplicate' => true,
                    'message' => 'This exact file is already uploaded for '.$familyMember->full_name.'.',
                    'existing_report_url' => route('reports.show', $duplicate),
                ], 409);
            }
        }

        $path = $file->store("reports/{$familyMember->id}", 'local');

        $report = Report::create([
            'family_member_id' => $familyMember->id,
            'uploaded_by_user_id' => $request->user()->id,
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

        return response()->json([
            'success' => true,
            'report_id' => $report->id,
            'redirect' => route('reports.show', $report),
        ]);
    }

    /**
     * Runs OCR against a file the instant it's selected — before the user
     * has filled in anything — so the upload form can pre-fill type/date/
     * hospital/doctor instead of leaving them all blank. The extracted text
     * is cached under the file's hash so the real POST /reports a few
     * seconds later reuses it instead of running Tesseract twice.
     */
    public function detect(Request $request, OcrExtractor $ocrExtractor, ReportFieldDetector $detector): JsonResponse
    {
        $validated = $request->validate([
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $familyMember = FamilyMember::findOrFail($validated['family_member_id']);
        abort_unless($familyMember->canBeEditedBy($request->user()), 403);

        $file = $request->file('file');
        $hash = hash_file('sha256', $file->getRealPath());
        $cacheKey = OcrExtractor::cacheKey($hash);

        $cached = Cache::get($cacheKey);

        if ($cached) {
            $text = $cached['text'];
            $usable = $cached['looks_usable'];
        } else {
            try {
                $text = $ocrExtractor->extract($file->getRealPath(), $file->getMimeType());
            } catch (\Throwable) {
                $text = '';
            }
            $usable = $ocrExtractor->looksUsable($text);
            Cache::put($cacheKey, ['text' => $text, 'looks_usable' => $usable], now()->addMinutes(30));
        }

        if (! $usable) {
            return response()->json(['success' => true, 'detected' => []]);
        }

        $knownHospitals = Doctor::where('family_member_id', $familyMember->id)->pluck('hospital_or_clinic_name')
            ->merge(Report::where('family_member_id', $familyMember->id)->pluck('hospital_or_clinic_name'))
            ->filter()->unique()->values();

        $knownDoctors = Doctor::where('family_member_id', $familyMember->id)->pluck('name')
            ->merge(Report::where('family_member_id', $familyMember->id)->pluck('doctor_name'))
            ->filter()->unique()->values();

        $detected = $detector->detect($text, $file->getClientOriginalName(), $knownHospitals, $knownDoctors);

        return response()->json(['success' => true, 'detected' => $detected]);
    }
}
