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
 * Covers the fallback added for reports with no OCR-usable text (a raw
 * X-ray/sonography/MRI scan with no embedded text): instead of being stuck
 * "failed" forever, the report gets described visually via Gemini.
 */
class VisionAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private function makeImageReport(): Report
    {
        Storage::fake('local');

        // A blank image has nothing for Tesseract to read, so looksUsable()
        // will correctly return false and the job should fall through to
        // the vision path.
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
            'type' => 'xray',
            'file_path' => $path,
            'original_filename' => 'scan.png',
            'mime_type' => 'image/png',
            'ocr_status' => 'pending',
        ]);
    }

    public function test_vision_fallback_completes_the_report_when_gemini_succeeds(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'This appears to be a chest X-ray with no obvious abnormality.']]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 120, 'candidatesTokenCount' => 18],
            ]),
        ]);

        $report = $this->makeImageReport();

        ProcessReportOcrJob::dispatchSync($report);

        $report->refresh();

        $this->assertSame('completed', $report->ocr_status);
        $this->assertSame('vision', $report->analysis_method);
        $this->assertStringContainsString('chest X-ray', $report->ai_summary);

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), ':generateContent')
            && collect($request->data()['contents'][0]['parts'])->contains(fn ($part) => isset($part['inlineData'])));
    }

    public function test_vision_fallback_marks_the_report_failed_when_gemini_errors(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('server error', 500),
        ]);

        $report = $this->makeImageReport();

        ProcessReportOcrJob::dispatchSync($report);

        $report->refresh();

        $this->assertSame('failed', $report->ocr_status);
        $this->assertNull($report->analysis_method);
        $this->assertNull($report->ai_summary);
    }

    public function test_no_vision_attempt_when_no_ai_credential_is_configured(): void
    {
        config([
            'services.gemini.key' => null,
            'services.gemini.key_2' => null,
            'services.gemini.key_3' => null,
            'services.groq.key' => null,
        ]);

        Http::fake();

        $report = $this->makeImageReport();

        ProcessReportOcrJob::dispatchSync($report);

        $report->refresh();

        $this->assertSame('failed', $report->ocr_status);
        $this->assertNull($report->analysis_method);
        Http::assertNothingSent();
    }
}
