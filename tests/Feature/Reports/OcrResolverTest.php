<?php

namespace Tests\Feature\Reports;

use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers the upload-time /reports/detect endpoint using the same
 * PaddleOCR-first/Tesseract-fallback OcrResolver as the real background
 * job — before this, detect() only ever used Tesseract, so the auto-filled
 * form could reflect a weaker read than what the report was actually
 * processed with.
 */
class OcrResolverTest extends TestCase
{
    use RefreshDatabase;

    private function actingUserWithFamilyMember(): array
    {
        $user = User::factory()->create();
        $familyMember = FamilyMember::create([
            'primary_account_id' => $user->id,
            'relation' => 'self',
            'full_name' => $user->name,
            'access_type' => 'dependent',
            'status' => 'active',
        ]);

        return [$user, $familyMember];
    }

    private function textImage(string $text = 'Hemoglobin 12.5 g/dL'): UploadedFile
    {
        $img = imagecreatetruecolor(600, 200);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        imagestring($img, 5, 10, 80, $text, imagecolorallocate($img, 0, 0, 0));
        ob_start();
        imagepng($img);
        $bytes = ob_get_clean();
        imagedestroy($img);

        $path = tempnam(sys_get_temp_dir(), 'detect_test_').'.png';
        file_put_contents($path, $bytes);

        return new UploadedFile($path, 'scan.png', 'image/png', null, true);
    }

    public function test_detect_uses_paddleocr_first_and_returns_a_preview(): void
    {
        [$user, $familyMember] = $this->actingUserWithFamilyMember();

        config([
            'services.clinical_nlp.url' => 'http://clinical-nlp.test',
            'services.clinical_nlp.token' => 'shared-secret',
        ]);

        Http::fake([
            'clinical-nlp.test/ocr' => Http::response([
                'text' => 'Complete Blood Count Report. Hemoglobin: 12.5 g/dL.',
                'looks_usable' => true,
            ]),
        ]);

        $response = $this->actingAs($user)->postJson(route('reports.detect'), [
            'family_member_id' => $familyMember->id,
            'file' => $this->textImage(),
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'method' => 'paddleocr']);
        $this->assertStringContainsString('Hemoglobin', $response->json('text_preview'));

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'clinical-nlp.test/ocr')
            && $request->hasHeader('X-Service-Token', 'shared-secret'));
    }

    public function test_detect_falls_back_to_tesseract_when_paddleocr_is_unconfigured(): void
    {
        [$user, $familyMember] = $this->actingUserWithFamilyMember();

        config(['services.clinical_nlp.url' => null]);

        $response = $this->actingAs($user)->postJson(route('reports.detect'), [
            'family_member_id' => $familyMember->id,
            'file' => $this->textImage('Blood Sugar 110'),
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        // Tesseract may or may not read GD-rendered test text cleanly, but
        // the response must at least reflect a real (non-PaddleOCR) attempt
        // rather than silently defaulting to PaddleOCR's shape.
        $this->assertNotSame('paddleocr', $response->json('method'));
    }
}
