<?php

namespace Tests\Feature\Reports;

use App\Jobs\ProcessReportOcrJob;
use App\Models\FamilyMember;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the PaddleOCR second-opinion fallback: when Tesseract's own read
 * comes back unusable, the clinical-nlp-service's /ocr endpoint gets a
 * chance to rescue the report before it's handed to the vision path.
 */
class PaddleOcrFallbackTest extends TestCase
{
    use RefreshDatabase;

    private function makeBlankImageReport(): Report
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

        $path = 'reports/'.uniqid('scan_').'.png';
        Storage::disk('local')->put($path, $bytes);

        return Report::create([
            'family_member_id' => $familyMember->id,
            'uploaded_by_user_id' => $user->id,
            'type' => 'blood_test',
            'file_path' => $path,
            'original_filename' => 'scan.png',
            'mime_type' => 'image/png',
            'ocr_status' => 'pending',
        ]);
    }

    public function test_paddleocr_rescues_a_tesseract_unusable_report(): void
    {
        config([
            'services.gemini.key' => 'test-gemini-key',
            'services.clinical_nlp.url' => 'http://clinical-nlp.test',
            'services.clinical_nlp.token' => 'shared-secret',
        ]);

        Http::fake([
            'clinical-nlp.test/ocr' => Http::response([
                'text' => 'Hemoglobin: 13.5 g/dL, WBC count normal, patient stable.',
                'looks_usable' => true,
            ]),
            'clinical-nlp.test/extract-entities' => Http::response(['entities' => []]),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Blood test shows normal hemoglobin.']]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 50, 'candidatesTokenCount' => 10],
            ]),
        ]);

        $report = $this->makeBlankImageReport();

        ProcessReportOcrJob::dispatchSync($report);

        $report->refresh();

        $this->assertSame('completed', $report->ocr_status);
        $this->assertSame('paddleocr', $report->analysis_method);
        $this->assertStringContainsString('Hemoglobin', $report->ocr_text);

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'clinical-nlp.test/ocr')
            && $request->hasHeader('X-Service-Token', 'shared-secret'));

        // GenerateShortSummaryJob runs the normal text-prompt path (not the
        // vision path) once PaddleOCR has rescued usable ocr_text.
        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'generativelanguage')
            && ! collect($request->data()['contents'][0]['parts'])->contains(fn ($part) => isset($part['inlineData'])));
    }

    public function test_falls_through_to_vision_when_paddleocr_also_finds_nothing_usable(): void
    {
        config([
            'services.gemini.key' => 'test-gemini-key',
            'services.clinical_nlp.url' => 'http://clinical-nlp.test',
        ]);

        Http::fake([
            'clinical-nlp.test/ocr' => Http::response(['text' => '', 'looks_usable' => false]),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'This appears to be a blank or unreadable image.']]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 40, 'candidatesTokenCount' => 8],
            ]),
        ]);

        $report = $this->makeBlankImageReport();

        ProcessReportOcrJob::dispatchSync($report);

        $report->refresh();

        $this->assertSame('completed', $report->ocr_status);
        $this->assertSame('vision', $report->analysis_method);
    }

    public function test_falls_through_to_vision_when_clinical_nlp_is_unconfigured(): void
    {
        config([
            'services.gemini.key' => 'test-gemini-key',
            'services.clinical_nlp.url' => null,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'This appears to be a blank or unreadable image.']]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 40, 'candidatesTokenCount' => 8],
            ]),
        ]);

        $report = $this->makeBlankImageReport();

        ProcessReportOcrJob::dispatchSync($report);

        $report->refresh();

        $this->assertSame('completed', $report->ocr_status);
        $this->assertSame('vision', $report->analysis_method);

        Http::assertNotSent(fn (HttpRequest $request) => str_contains($request->url(), 'clinical-nlp'));
    }
}
