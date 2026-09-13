<?php

namespace Tests\Feature\Reports;

use App\Jobs\ExtractStructuredDataJob;
use App\Jobs\GenerateShortSummaryJob;
use App\Models\FamilyMember;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Structured extraction now runs for every report type, not just blood tests.
 *
 * These cover the contract the rest of the pipeline depends on: the per-type
 * shape is stored, numeric flags are recomputed rather than trusted, and a
 * failure degrades to null structured_data instead of breaking the report.
 *
 * Ollama is left unconfigured throughout so extraction falls through to the
 * faked Gemini chain — the fallback path is itself asserted below.
 */
class StructuredExtractionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => 'test-gemini-key', 'services.ollama.url' => null]);
        // The job chains the summary in a finally block; capture it rather
        // than letting it run and make its own calls.
        Queue::fake([GenerateShortSummaryJob::class]);
    }

    private function makeReport(string $type, string $ocrText): Report
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
            'type' => $type,
            'file_path' => "reports/{$type}.pdf",
            'original_filename' => "{$type}.pdf",
            'mime_type' => 'application/pdf',
            'ocr_status' => 'completed',
            'ocr_text' => $ocrText,
        ]);
    }

    private function fakeGemini(string $payload): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => $payload]]]]],
                'usageMetadata' => ['promptTokenCount' => 90, 'candidatesTokenCount' => 30],
            ]),
        ]);
    }

    public function test_blood_test_rows_are_parsed_and_stored(): void
    {
        $this->fakeGemini(json_encode(['results' => [
            ['test' => 'Hemoglobin', 'value' => '12.5', 'unit' => 'g/dL', 'reference_range' => '13.0-17.0', 'flag' => 'low'],
            ['test' => 'Total WBC count', 'value' => '9000', 'unit' => 'cumm', 'reference_range' => '4000-11000', 'flag' => 'normal'],
        ]]));

        $report = $this->makeReport('blood_test', 'Hemoglobin 12.5 13.0-17.0 g/dL');
        ExtractStructuredDataJob::dispatchSync($report);
        $report->refresh();

        $this->assertCount(2, $report->structured_data['results']);
        $this->assertSame('Hemoglobin', $report->structured_data['results'][0]['test']);
        // lab_results stays populated so existing readers keep working.
        $this->assertCount(2, $report->lab_results);
    }

    public function test_a_wrong_model_flag_is_overridden_by_the_numeric_recheck(): void
    {
        $this->fakeGemini(json_encode(['results' => [
            ['test' => 'Hemoglobin', 'value' => '12.5', 'unit' => 'g/dL', 'reference_range' => '13.0-17.0', 'flag' => 'normal'],
        ]]));

        $report = $this->makeReport('blood_test', 'Hemoglobin 12.5');
        ExtractStructuredDataJob::dispatchSync($report);

        $this->assertSame('low', $report->refresh()->structured_data['results'][0]['flag']);
    }

    /**
     * The regression LabFlag was written for: a one-sided range used to be
     * skipped, so a wrong model flag survived on exactly the lipid values
     * where being wrong matters.
     */
    public function test_one_sided_ranges_are_now_rechecked(): void
    {
        $this->fakeGemini(json_encode(['results' => [
            ['test' => 'Total Cholesterol', 'value' => '248', 'unit' => 'mg/dL', 'reference_range' => '< 200', 'flag' => 'normal'],
            ['test' => 'HDL Cholesterol', 'value' => '38', 'unit' => 'mg/dL', 'reference_range' => '> 40', 'flag' => 'normal'],
        ]]));

        $report = $this->makeReport('blood_test', 'Cholesterol 248');
        ExtractStructuredDataJob::dispatchSync($report);
        $rows = $report->refresh()->structured_data['results'];

        $this->assertSame('high', $rows[0]['flag']);
        $this->assertSame('low', $rows[1]['flag']);
    }

    public function test_qualitative_ranges_defer_to_the_models_flag(): void
    {
        $this->fakeGemini(json_encode(['results' => [
            ['test' => 'Dengue NS1', 'value' => 'Positive', 'unit' => null, 'reference_range' => 'Negative', 'flag' => 'abnormal'],
        ]]));

        $report = $this->makeReport('blood_test', 'Dengue NS1 Positive');
        ExtractStructuredDataJob::dispatchSync($report);

        $this->assertSame('abnormal', $report->refresh()->structured_data['results'][0]['flag']);
    }

    public function test_a_markdown_code_fence_around_the_json_is_stripped(): void
    {
        $this->fakeGemini("```json\n".json_encode(['results' => [
            ['test' => 'Hemoglobin', 'value' => '14.0', 'unit' => 'g/dL', 'reference_range' => '13.0-17.0', 'flag' => 'normal'],
        ]])."\n```");

        $report = $this->makeReport('blood_test', 'Hemoglobin 14.0');
        ExtractStructuredDataJob::dispatchSync($report);

        $this->assertCount(1, $report->refresh()->structured_data['results']);
    }

    /** The new capability: types that previously got no structured extraction at all. */
    public function test_a_prescription_yields_its_own_shape(): void
    {
        $this->fakeGemini(json_encode(['medicines' => [
            ['name' => 'Metformin', 'dosage' => '500mg', 'frequency' => '1-0-1', 'duration' => '30 days', 'instructions' => 'after food', 'uncertain' => false],
        ]]));

        $report = $this->makeReport('prescription', 'Tab Metformin 500mg 1-0-1');
        ExtractStructuredDataJob::dispatchSync($report);
        $report->refresh();

        $this->assertSame('Metformin', $report->structured_data['medicines'][0]['name']);
        // Not a results-shaped type, so lab_results must stay untouched.
        $this->assertNull($report->lab_results);
    }

    public function test_a_non_medical_document_is_flagged_rather_than_summarised_as_medical(): void
    {
        $this->fakeGemini(json_encode(['key_values' => [], 'not_a_medical_report' => true]));

        $report = $this->makeReport('other', 'Thanks for your application. We will get back to you.');
        ExtractStructuredDataJob::dispatchSync($report);

        $this->assertTrue($report->refresh()->structured_data['not_a_medical_report']);
    }

    public function test_structured_data_stays_null_when_the_provider_fails(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 500)]);

        $report = $this->makeReport('blood_test', 'Hemoglobin 12.5');
        ExtractStructuredDataJob::dispatchSync($report);

        $this->assertNull($report->refresh()->structured_data);
    }

    public function test_the_summary_is_always_chained_even_when_extraction_fails(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 500)]);

        $report = $this->makeReport('blood_test', 'Hemoglobin 12.5');
        ExtractStructuredDataJob::dispatchSync($report);

        Queue::assertPushed(GenerateShortSummaryJob::class);
    }

    /**
     * Reconstructed from two real production runs of the same lab report at
     * different resolutions. At low resolution OCR dropped decimals from both
     * the value AND its range ("7.8"/"4.0 - 5.6" became "78"/"40-56"), so the
     * impossible value still looked consistent with its own range — only the
     * proportion of rows that lost their range distinguishes the two.
     */
    public function test_a_poorly_scanned_report_is_flagged(): void
    {
        $this->fakeGemini(json_encode(['results' => [
            ['test' => 'Haemoglobin', 'value' => '12', 'unit' => 'gid.', 'reference_range' => '13.0-17.0', 'flag' => 'low'],
            ['test' => 'Total Leucocyte Count', 'value' => '9800', 'unit' => null, 'reference_range' => '4000 - 10000', 'flag' => 'normal'],
            ['test' => 'Platelet Count', 'value' => '19', 'unit' => null, 'reference_range' => null, 'flag' => 'unknown'],
            ['test' => 'Serum Creatinine', 'value' => '14', 'unit' => null, 'reference_range' => null, 'flag' => 'unknown'],
            ['test' => 'HbAtc', 'value' => '78', 'unit' => '%', 'reference_range' => '40-56', 'flag' => 'high'],
        ]]));

        $report = $this->makeReport('blood_test', 'garbled scan');
        ExtractStructuredDataJob::dispatchSync($report);

        $this->assertTrue($report->refresh()->structured_data['scan_quality_warning']);
    }

    public function test_a_clean_scan_is_not_flagged(): void
    {
        $this->fakeGemini(json_encode(['results' => [
            ['test' => 'Haemoglobin', 'value' => '11.2', 'unit' => 'g/dL', 'reference_range' => '13.0 - 17.0', 'flag' => 'low'],
            ['test' => 'Total Cholesterol', 'value' => '248', 'unit' => 'mg/dL', 'reference_range' => '< 200', 'flag' => 'high'],
            ['test' => 'HbA1c', 'value' => '7.8', 'unit' => '%', 'reference_range' => '4.0 - 5.6', 'flag' => 'high'],
            ['test' => 'Serum Creatinine', 'value' => '1.1', 'unit' => 'mg/dL', 'reference_range' => '0.7 - 1.3', 'flag' => 'normal'],
        ]]));

        $report = $this->makeReport('blood_test', 'clean scan');
        ExtractStructuredDataJob::dispatchSync($report);

        $this->assertFalse($report->refresh()->structured_data['scan_quality_warning']);
    }

    /**
     * Observed during a full report-type sweep: a blood test came back as a
     * bare list rather than {"results": [...]}, and every row was silently
     * dropped. Tolerating the shape is cheaper than losing the data.
     */
    public function test_a_bare_list_response_is_rewrapped_rather_than_dropped(): void
    {
        $this->fakeGemini(json_encode([
            ['test' => 'Haemoglobin', 'value' => '11.2', 'unit' => 'g/dL', 'reference_range' => '13.0 - 17.0', 'flag' => 'normal'],
            ['test' => 'HbA1c', 'value' => '7.8', 'unit' => '%', 'reference_range' => '4.0 - 5.6', 'flag' => 'normal'],
        ]));

        $report = $this->makeReport('blood_test', 'Haemoglobin 11.2');
        ExtractStructuredDataJob::dispatchSync($report);
        $rows = $report->refresh()->structured_data['results'];

        $this->assertCount(2, $rows);
        // And the numeric recheck still applies to the recovered rows.
        $this->assertSame('low', $rows[0]['flag']);
        $this->assertSame('high', $rows[1]['flag']);
    }
}
