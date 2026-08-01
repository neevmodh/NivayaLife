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
 * Covers the optional TorchXRayVision enrichment: when configured, its
 * findings should be stored and fed into the Gemini prompt as grounding
 * context for xray-type reports; when unconfigured or failing, the report
 * must still complete via the Gemini-only vision path (no regression from
 * before this enrichment existed).
 */
class XrayVisionTest extends TestCase
{
    use RefreshDatabase;

    private function makeXrayReport(): Report
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

        $path = 'reports/'.uniqid('xray_').'.png';
        Storage::disk('local')->put($path, $bytes);

        return Report::create([
            'family_member_id' => $familyMember->id,
            'uploaded_by_user_id' => $user->id,
            'type' => 'xray',
            'file_path' => $path,
            'original_filename' => 'xray.png',
            'mime_type' => 'image/png',
            'ocr_status' => 'pending',
        ]);
    }

    public function test_findings_are_stored_and_fed_into_the_gemini_prompt(): void
    {
        config([
            'services.gemini.key' => 'test-gemini-key',
            'services.xray_vision.url' => 'http://xray-vision.test',
            'services.xray_vision.token' => 'shared-secret',
        ]);

        Http::fake([
            'xray-vision.test/*' => Http::response([
                'findings' => [
                    ['pathology' => 'Effusion', 'probability' => 0.34],
                    ['pathology' => 'Cardiomegaly', 'probability' => 0.12],
                ],
            ]),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'This chest X-ray shows possible effusion.']]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 100, 'candidatesTokenCount' => 15],
            ]),
        ]);

        $report = $this->makeXrayReport();

        ProcessReportOcrJob::dispatchSync($report);

        $report->refresh();

        $this->assertSame('completed', $report->ocr_status);
        $this->assertSame('vision', $report->analysis_method);
        $this->assertNotNull($report->xray_findings);
        $this->assertSame('Effusion', $report->xray_findings[0]['pathology']);

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'xray-vision.test')
            && $request->hasHeader('X-Service-Token', 'shared-secret'));

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), ':generateContent')
            && str_contains($request->data()['contents'][0]['parts'][0]['text'], 'Effusion 34%'));
    }

    public function test_report_still_completes_via_gemini_when_xray_vision_service_is_unreachable(): void
    {
        config([
            'services.gemini.key' => 'test-gemini-key',
            'services.xray_vision.url' => 'http://xray-vision.test',
        ]);

        Http::fake([
            'xray-vision.test/*' => Http::response('service unavailable', 503),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'This appears to be a chest X-ray.']]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 80, 'candidatesTokenCount' => 10],
            ]),
        ]);

        $report = $this->makeXrayReport();

        ProcessReportOcrJob::dispatchSync($report);

        $report->refresh();

        $this->assertSame('completed', $report->ocr_status);
        $this->assertSame('vision', $report->analysis_method);
        $this->assertNull($report->xray_findings);
        $this->assertStringContainsString('chest X-ray', $report->ai_summary);
    }

    public function test_report_completes_via_gemini_when_xray_vision_is_not_configured(): void
    {
        config([
            'services.gemini.key' => 'test-gemini-key',
            'services.xray_vision.url' => null,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'This appears to be a chest X-ray.']]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 80, 'candidatesTokenCount' => 10],
            ]),
        ]);

        $report = $this->makeXrayReport();

        ProcessReportOcrJob::dispatchSync($report);

        $report->refresh();

        $this->assertSame('completed', $report->ocr_status);
        $this->assertNull($report->xray_findings);

        Http::assertNotSent(fn (HttpRequest $request) => str_contains($request->url(), 'xray-vision'));
    }
}
