<?php

namespace Tests\Feature\Reports;

use App\Jobs\GenerateShortSummaryJob;
use App\Models\FamilyMember;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers the scispaCy/medspaCy entity-detection enrichment: after the
 * automatic short summary is generated, the clinical-nlp-service's
 * /extract-entities endpoint (when configured) should populate
 * Report::detected_entities — and any failure there must never break the
 * summary itself, which is the actual point of the job.
 */
class ClinicalNlpEntitiesTest extends TestCase
{
    use RefreshDatabase;

    private function makeReportWithOcrText(): Report
    {
        $user = User::factory()->create();
        $familyMember = FamilyMember::create([
            'primary_account_id' => $user->id,
            'relation' => 'self',
            'full_name' => $user->name,
            'access_type' => 'linked',
            'status' => 'active',
        ]);

        return Report::create([
            'family_member_id' => $familyMember->id,
            'uploaded_by_user_id' => $user->id,
            'type' => 'prescription',
            'file_path' => 'reports/rx.pdf',
            'original_filename' => 'rx.pdf',
            'mime_type' => 'application/pdf',
            'ocr_status' => 'completed',
            'ocr_text' => 'Patient prescribed Metformin 500mg twice daily for Type 2 Diabetes. No history of hypertension.',
        ]);
    }

    public function test_detected_entities_are_stored_after_the_summary_is_generated(): void
    {
        config([
            'services.gemini.key' => 'test-gemini-key',
            'services.clinical_nlp.url' => 'http://clinical-nlp.test',
            'services.clinical_nlp.token' => 'shared-secret',
        ]);

        Http::fake([
            'clinical-nlp.test/extract-entities' => Http::response([
                'entities' => [
                    ['text' => 'Metformin', 'label' => 'CHEMICAL', 'negated' => false],
                    ['text' => 'Type 2 Diabetes', 'label' => 'DISEASE', 'negated' => false],
                    ['text' => 'hypertension', 'label' => 'DISEASE', 'negated' => true],
                ],
            ]),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Prescription for Metformin to manage Type 2 Diabetes.']]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 60, 'candidatesTokenCount' => 12],
            ]),
        ]);

        $report = $this->makeReportWithOcrText();

        GenerateShortSummaryJob::dispatchSync($report);

        $report->refresh();

        $this->assertNotNull($report->ai_summary);
        $this->assertNotNull($report->detected_entities);
        $this->assertSame('Metformin', $report->detected_entities[0]['text']);
        $this->assertTrue($report->detected_entities[2]['negated']);

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'clinical-nlp.test/extract-entities')
            && $request->hasHeader('X-Service-Token', 'shared-secret'));
    }

    public function test_summary_still_completes_when_entity_extraction_fails(): void
    {
        config([
            'services.gemini.key' => 'test-gemini-key',
            'services.clinical_nlp.url' => 'http://clinical-nlp.test',
        ]);

        Http::fake([
            'clinical-nlp.test/extract-entities' => Http::response('server error', 500),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Prescription for Metformin.']]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 60, 'candidatesTokenCount' => 12],
            ]),
        ]);

        $report = $this->makeReportWithOcrText();

        GenerateShortSummaryJob::dispatchSync($report);

        $report->refresh();

        $this->assertNotNull($report->ai_summary);
        $this->assertNull($report->detected_entities);
    }

    public function test_summary_still_completes_when_clinical_nlp_is_unconfigured(): void
    {
        config([
            'services.gemini.key' => 'test-gemini-key',
            'services.clinical_nlp.url' => null,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Prescription for Metformin.']]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 60, 'candidatesTokenCount' => 12],
            ]),
        ]);

        $report = $this->makeReportWithOcrText();

        GenerateShortSummaryJob::dispatchSync($report);

        $report->refresh();

        $this->assertNotNull($report->ai_summary);
        $this->assertNull($report->detected_entities);

        Http::assertNotSent(fn (HttpRequest $request) => str_contains($request->url(), 'clinical-nlp'));
    }
}
