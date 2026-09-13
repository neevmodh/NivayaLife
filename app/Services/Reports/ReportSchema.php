<?php

namespace App\Services\Reports;

/**
 * What structured data each report type yields, in one place.
 *
 * Previously only blood tests were parsed into structure (a hardcoded lab-row
 * shape). Everything else went to the model as raw text and came back as
 * prose. This lets every type produce data the UI can render and the
 * summariser can be grounded in.
 *
 * `shape` names the renderer/normaliser to use, so several types can share one
 * output format (blood_test and pathology are both result tables; the three
 * imaging types are all findings+impression).
 */
class ReportSchema
{
    /**
     * @return array{shape: string, instruction: string, example: string}
     */
    public static function for(string $type): array
    {
        return self::SCHEMAS[$type] ?? self::SCHEMAS['other'];
    }

    public static function shapeFor(string $type): string
    {
        return self::for($type)['shape'];
    }

    /** Shapes whose rows carry a numeric value + reference range, so LabFlag applies. */
    public static function hasMeasuredResults(string $type): bool
    {
        return self::shapeFor($type) === 'results';
    }

    private const SCHEMAS = [
        'blood_test' => [
            'shape' => 'results',
            'instruction' => 'Extract EVERY individual test result row into "results" — do not stop after the first, and do not summarise. Each element: "test" (parameter name), "value" (the measured result), "unit" (or null), "reference_range" (exactly as printed, e.g. "13.0-17.0" or "< 200", or null), "flag" (low|high|normal|unknown). Skip headers, patient demographics, lab addresses, doctor names and interpretation notes.',
            'example' => '{"results":[{"test":"<parameter name>","value":"<measured value only, no unit>","unit":"<unit or null>","reference_range":"<range exactly as printed, or null>","flag":"low|high|normal|unknown"}]}',
        ],
        'pathology' => [
            'shape' => 'results',
            'instruction' => 'Extract EVERY measured parameter into "results" — do not stop after the first — with "test", "value", "unit", "reference_range", "flag" (low|high|normal|unknown). If the report is descriptive rather than numeric, return an empty "results" array and put the microscopic/gross findings in "notes" as an array of short strings.',
            'example' => '{"results":[{"test":"<parameter name>","value":"<value only>","unit":"<unit or null>","reference_range":"<range or null>","flag":"low|high|normal|unknown"}],"notes":["<finding sentence>"]}',
        ],
        'prescription' => [
            'shape' => 'medicines',
            'instruction' => 'Extract every prescribed medicine into "medicines". Each element: "name" (drug name as written), "dosage" (strength, e.g. "500mg"), "frequency" (e.g. "twice daily", "1-0-1"), "duration" (e.g. "5 days"), "instructions" (e.g. "after food"). Use null for anything not stated. Do not invent a dose. If handwriting is unclear for an item, still include it and set "uncertain" to true for that item.',
            'example' => '{"medicines":[{"name":"<drug name>","dosage":"<strength or null>","frequency":"<frequency or null>","duration":"<duration or null>","instructions":"<instructions or null>","uncertain":false}]}',
        ],
        'xray' => [
            'shape' => 'imaging',
            'instruction' => 'Extract "body_part" (what was imaged), "findings" (array of short factual observation strings, exactly as reported), and "impression" (the radiologist\'s summary line, or null).',
            'example' => '{"body_part":"<region imaged>","findings":["<one observation per element>"],"impression":"<concluding line or null>"}',
        ],
        'sonography' => [
            'shape' => 'imaging',
            'instruction' => 'Extract "body_part" (region scanned), "findings" (array of short factual observation strings, including any measurements exactly as printed), and "impression" (the concluding line, or null).',
            'example' => '{"body_part":"<region scanned>","findings":["<one observation per element, measurements exactly as printed>"],"impression":"<concluding line or null>"}',
        ],
        'mri_ct' => [
            'shape' => 'imaging',
            'instruction' => 'Extract "body_part" (region and modality), "findings" (array of short factual observation strings), and "impression" (the concluding line, or null).',
            'example' => '{"body_part":"<modality and region>","findings":["<one observation per element>"],"impression":"<concluding line or null>"}',
        ],
        'ecg' => [
            'shape' => 'ecg',
            'instruction' => 'Extract "heart_rate" (bpm as a number-like string, or null), "rhythm" (e.g. "Sinus rhythm", or null), "intervals" (object of any printed intervals such as PR, QRS, QT, QTc — omit those not present), and "interpretation" (the machine or doctor conclusion line, or null).',
            'example' => '{"heart_rate":"<bpm or null>","rhythm":"<rhythm or null>","intervals":{"<interval name>":"<value>"},"interpretation":"<conclusion or null>"}',
        ],
        'discharge_summary' => [
            'shape' => 'discharge',
            'instruction' => 'Extract "admission_date" and "discharge_date" (YYYY-MM-DD if determinable, else as printed, else null), "diagnoses" (array of strings), "procedures" (array of strings), "medicines_on_discharge" (array of {name, dosage, frequency, duration}), and "follow_up" (the advice line, or null).',
            'example' => '{"admission_date":"<YYYY-MM-DD or null>","discharge_date":"<YYYY-MM-DD or null>","diagnoses":["<one per element>"],"procedures":["<one per element>"],"medicines_on_discharge":[{"name":"<drug>","dosage":"<or null>","frequency":"<or null>","duration":"<or null>"}],"follow_up":"<advice or null>"}',
        ],
        'bill' => [
            'shape' => 'bill',
            'instruction' => 'Extract "line_items" (array of {description, amount}), "total_amount" (the final payable figure as a number-like string), "currency" (e.g. "INR"), and "bill_date" (YYYY-MM-DD if determinable, else null). Do not attempt any clinical interpretation of a bill.',
            'example' => '{"line_items":[{"description":"<item>","amount":"<amount>"}],"total_amount":"<final payable>","currency":"<code>","bill_date":"<YYYY-MM-DD or null>"}',
        ],
        'insurance' => [
            'shape' => 'insurance',
            'instruction' => 'Extract "policy_number", "insurer", "policyholder", "sum_insured", "valid_from", "valid_to" (dates YYYY-MM-DD if determinable, else as printed), and "notes" (array of any stated exclusions or conditions). Use null for anything absent.',
            'example' => '{"policy_number":"<or null>","insurer":"<or null>","policyholder":"<or null>","sum_insured":"<or null>","valid_from":"<or null>","valid_to":"<or null>","notes":["<exclusion or condition>"]}',
        ],
        'dental' => [
            'shape' => 'findings',
            'instruction' => 'Extract "findings" (array of short strings, including tooth numbers where stated) and "procedures" (array of treatments done or advised).',
            'example' => '{"findings":["<one finding per element, include tooth numbers>"],"procedures":["<one per element>"]}',
        ],
        'eye_care' => [
            'shape' => 'eye',
            'instruction' => 'Extract "right_eye" and "left_eye", each an object with "sphere", "cylinder", "axis", "va" (visual acuity) — use null for any not printed. Also "diagnosis" (or null) and "advice" (or null).',
            'example' => '{"right_eye":{"sphere":"<or null>","cylinder":"<or null>","axis":"<or null>","va":"<or null>"},"left_eye":{"sphere":"<or null>","cylinder":"<or null>","axis":"<or null>","va":"<or null>"},"diagnosis":"<or null>","advice":"<or null>"}',
        ],
        'other' => [
            'shape' => 'key_values',
            'instruction' => 'This document may not be a medical report at all. If it clearly is not (an email, receipt, article, or unrelated document), return {"key_values":[],"not_a_medical_report":true}. Otherwise extract the meaningful labelled facts into "key_values" as an array of {"label","value"}.',
            'example' => '{"key_values":[{"label":"<label>","value":"<value>"}],"not_a_medical_report":false}',
        ],
    ];
}
