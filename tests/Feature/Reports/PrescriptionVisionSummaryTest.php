<?php

namespace Tests\Feature\Reports;

use App\Jobs\GenerateShortSummaryJob;
use App\Models\FamilyMember;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the vision-augmented summary path added for prescriptions:
 * a doctor's handwriting is the dominant failure mode there, and OCR text
 * alone often isn't enough — so the summary is generated from the actual
 * report image (plus OCR text as a cross-check) instead of OCR text alone.
 */
class PrescriptionVisionSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function makeReport(string $type, string $mimeType = 'image/png'): Report
    {
        Storage::fake('local');

        $blank = imagecreatetruecolor(200, 200);
        imagefill($blank, 0, 0, imagecolorallocate($blank, 255, 255, 255));
        ob_start();
        imagepng($blank);
        $bytes = ob_get_clean();
        imagedestroy($blank);

        $user = User::factory()->create();
        $familyMember = FamilyMember::create([
            'primary_account_id' => $user->id,
            'relation' => 'self',
            'full_name' => $user->name,
            'access_type' => 'linked',
            'status' => 'active',
        ]);

        $path = 'reports/'.uniqid('doc_').'.png';
        Storage::disk('local')->put($path, $bytes);

        return Report::create([
            'family_member_id' => $familyMember->id,
            'uploaded_by_user_id' => $user->id,
            'type' => $type,
            'file_path' => $path,
            'original_filename' => 'doc.png',
            'mime_type' => $mimeType,
            'ocr_status' => 'completed',
            'ocr_text' => 'Metformin 500mg twice daily',
        ]);
    }

    public function test_prescription_summary_uses_the_vision_path_with_image_and_text(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Prescription for Metformin, dosage handwritten but legible.']]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 80, 'candidatesTokenCount' => 14],
            ]),
        ]);

        $report = $this->makeReport('prescription');

        GenerateShortSummaryJob::dispatchSync($report);

        $report->refresh();

        $this->assertStringContainsString('handwritten', $report->ai_summary);

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), ':generateContent')
            && collect($request->data()['contents'][0]['parts'])->contains(fn ($part) => isset($part['inlineData'])));
    }

    public function test_non_prescription_summary_stays_text_only(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Blood test shows normal glucose levels.']]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 80, 'candidatesTokenCount' => 14],
            ]),
        ]);

        $report = $this->makeReport('blood_test');

        GenerateShortSummaryJob::dispatchSync($report);

        $report->refresh();

        $this->assertStringContainsString('Blood test', $report->ai_summary);

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), ':generateContent')
            && collect($request->data()['contents'][0]['parts'])->every(fn ($part) => ! isset($part['inlineData'])));
    }

    public function test_prescription_summary_falls_back_to_text_only_when_the_image_cannot_be_loaded(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Prescription for Metformin.']]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 60, 'candidatesTokenCount' => 12],
            ]),
        ]);

        $report = $this->makeReport('prescription', 'application/pdf');

        // The stored file is a PNG, not a real PDF — Imagick will fail to
        // parse it, exercising the fall-back-to-text-only branch.
        GenerateShortSummaryJob::dispatchSync($report);

        $report->refresh();

        $this->assertNotNull($report->ai_summary);
        $this->assertStringContainsString('Prescription for Metformin', $report->ai_summary);
    }
}
