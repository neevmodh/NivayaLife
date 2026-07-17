<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesActiveFamilyMember;
use App\Jobs\ProcessReportOcrJob;
use App\Models\Doctor;
use App\Models\FamilyMember;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
}
