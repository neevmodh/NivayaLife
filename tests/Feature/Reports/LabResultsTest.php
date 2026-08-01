<?php

namespace Tests\Feature\Reports;

use App\Jobs\ExtractLabResultsJob;
use App\Models\FamilyMember;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers structured lab-table extraction: Gemini parses the OCR'd table
 * into rows, and the job re-derives each row's Low/High/Normal flag itself
 * from a direct numeric comparison whenever the reference range is a plain
 * number range — a numeric check is strictly more reliable than trusting
 * whatever flag the model returned, so it must override a wrong model flag.
 */
class LabResultsTest extends TestCase
{
    use RefreshDatabase;

    private function makeBloodTestReport(): Report
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
            'type' => 'blood_test',
            'file_path' => 'reports/cbc.pdf',
            'original_filename' => 'cbc.pdf',
            'mime_type' => 'application/pdf',
            'ocr_status' => 'completed',
            'ocr_text' => 'Hemoglobin (Hb) 12.5 Low 13.0-17.0 g/dL. Total WBC count 9000 4000-11000 cumm.',
        ]);
    }

    public function test_lab_rows_are_parsed_and_stored(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode([
                        ['test' => 'Hemoglobin', 'value' => '12.5', 'unit' => 'g/dL', 'reference_range' => '13.0-17.0', 'flag' => 'low'],
                        ['test' => 'Total WBC count', 'value' => '9000', 'unit' => 'cumm', 'reference_range' => '4000-11000', 'flag' => 'normal'],
                    ])]]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 90, 'candidatesTokenCount' => 30],
            ]),
        ]);

        $report = $this->makeBloodTestReport();

        ExtractLabResultsJob::dispatchSync($report);

        $report->refresh();

        $this->assertCount(2, $report->lab_results);
        $this->assertSame('Hemoglobin', $report->lab_results[0]['test']);
        $this->assertSame('low', $report->lab_results[0]['flag']);
        $this->assertSame('normal', $report->lab_results[1]['flag']);
    }

    public function test_a_wrong_model_flag_is_overridden_by_the_numeric_recheck(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);

        // Model incorrectly says "normal" for a value that is clearly below
        // the printed reference range — the job's own numeric comparison
        // must catch and correct this.
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode([
                        ['test' => 'Hemoglobin', 'value' => '12.5', 'unit' => 'g/dL', 'reference_range' => '13.0-17.0', 'flag' => 'normal'],
                    ])]]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 60, 'candidatesTokenCount' => 15],
            ]),
        ]);

        $report = $this->makeBloodTestReport();

        ExtractLabResultsJob::dispatchSync($report);

        $report->refresh();

        $this->assertSame('low', $report->lab_results[0]['flag']);
    }

    public function test_qualitative_reference_ranges_defer_to_the_models_flag(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode([
                        ['test' => 'HIV Antibody', 'value' => 'Non-Reactive', 'unit' => null, 'reference_range' => 'Negative', 'flag' => 'normal'],
                    ])]]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 50, 'candidatesTokenCount' => 10],
            ]),
        ]);

        $report = $this->makeBloodTestReport();

        ExtractLabResultsJob::dispatchSync($report);

        $report->refresh();

        // Not a numeric range, so the numeric recheck can't run — the
        // model's own flag is trusted as-is.
        $this->assertSame('normal', $report->lab_results[0]['flag']);
    }

    public function test_markdown_code_fence_around_the_json_is_stripped(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);

        $json = json_encode([
            ['test' => 'Hemoglobin', 'value' => '15', 'unit' => 'g/dL', 'reference_range' => '13.0-17.0', 'flag' => 'normal'],
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => "```json\n{$json}\n```"]]]],
                ],
                'usageMetadata' => ['promptTokenCount' => 50, 'candidatesTokenCount' => 10],
            ]),
        ]);

        $report = $this->makeBloodTestReport();

        ExtractLabResultsJob::dispatchSync($report);

        $report->refresh();

        $this->assertCount(1, $report->lab_results);
        $this->assertSame('normal', $report->lab_results[0]['flag']);
    }

    public function test_skipped_entirely_for_non_blood_test_report_types(): void
    {
        config(['services.gemini.key' => 'test-gemini-key']);

        Http::fake();

        $report = $this->makeBloodTestReport();
        $report->update(['type' => 'prescription']);

        ExtractLabResultsJob::dispatchSync($report);

        $report->refresh();

        $this->assertNull($report->lab_results);
        Http::assertNothingSent();
    }

    public function test_lab_results_stays_null_when_gemini_fails(): void
    {
        config([
            'services.gemini.key' => 'test-gemini-key',
            'services.gemini.key_2' => null,
            'services.gemini.key_3' => null,
            'services.groq.key' => null,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('server error', 500),
        ]);

        $report = $this->makeBloodTestReport();

        ExtractLabResultsJob::dispatchSync($report);

        $report->refresh();

        $this->assertNull($report->lab_results);
    }
}
