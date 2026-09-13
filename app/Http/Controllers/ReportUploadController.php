<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesActiveFamilyMember;
use App\Jobs\ProcessReportOcrJob;
use App\Models\Doctor;
use App\Models\FamilyMember;
use App\Models\Report;
use App\Rules\ReadableDocumentImage;
use App\Services\Ocr\OcrExtractor;
use App\Services\Ocr\OcrResolver;
use App\Services\Reports\ReportFieldDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
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
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,tiff,tif,bmp,gif,docx', 'max:10240', new ReadableDocumentImage],
            'type' => ['required', 'in:blood_test,prescription,xray,sonography,mri_ct,insurance,bill,ecg,dental,discharge_summary,pathology,eye_care,other'],
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

    /** Shown in the upload modal's OCR preview — enough to sanity-check the read without dumping the whole document. */
    private const TEXT_PREVIEW_CHARS = 600;

    /**
     * Runs OCR against a file the instant it's selected — before the user
     * has filled in anything — so the upload form can pre-fill type/date/
     * hospital/doctor instead of leaving them all blank, and show a preview
     * of what was actually read so a bad scan/wrong file is caught before
     * submitting. Uses the same PaddleOCR-first/Tesseract-fallback resolver
     * as the real background job (OcrResolver), so the preview reflects
     * what the report will actually be processed with. The result is
     * cached under the file's hash so the real POST /reports a few seconds
     * later reuses it instead of running OCR twice.
     */
    public function detect(Request $request, OcrExtractor $ocrExtractor, OcrResolver $ocrResolver, ReportFieldDetector $detector): JsonResponse
    {
        $validated = $request->validate([
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,tiff,tif,bmp,gif,docx', 'max:10240', new ReadableDocumentImage],
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
            $method = $cached['method'] ?? null;
        } else {
            try {
                $result = $ocrResolver->resolve($file->getRealPath(), $file->getMimeType());
            } catch (\Throwable) {
                $result = ['text' => '', 'looks_usable' => false, 'method' => null];
            }
            $text = $result['text'];
            $usable = $result['looks_usable'];
            $method = $result['method'];
            Cache::put($cacheKey, ['text' => $text, 'looks_usable' => $usable, 'method' => $method], now()->addMinutes(30));
        }

        if (! $usable) {
            return response()->json(['success' => true, 'detected' => [], 'text_preview' => '', 'method' => $method]);
        }

        $knownHospitals = Doctor::where('family_member_id', $familyMember->id)->pluck('hospital_or_clinic_name')
            ->merge(Report::where('family_member_id', $familyMember->id)->pluck('hospital_or_clinic_name'))
            ->filter()->unique()->values();

        $knownDoctors = Doctor::where('family_member_id', $familyMember->id)->pluck('name')
            ->merge(Report::where('family_member_id', $familyMember->id)->pluck('doctor_name'))
            ->filter()->unique()->values();

        $detected = $detector->detect($text, $file->getClientOriginalName(), $knownHospitals, $knownDoctors);

        return response()->json([
            'success' => true,
            'detected' => $detected,
            'text_preview' => Str::limit($text, self::TEXT_PREVIEW_CHARS, ''),
            'method' => $method,
        ]);
    }
}
