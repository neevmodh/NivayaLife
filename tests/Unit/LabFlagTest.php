<?php

namespace Tests\Unit;

use App\Services\Reports\LabFlag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * This is the safety net that overrides whatever flag a model guessed, so it
 * is worth pinning down exhaustively — particularly the one-sided ranges,
 * which were previously unhandled and silently let a wrong model flag stand
 * on cholesterol/HDL values.
 */
class LabFlagTest extends TestCase
{
    #[DataProvider('twoSidedRanges')]
    public function test_two_sided_ranges(string $value, string $range, ?string $expected): void
    {
        $this->assertSame($expected, LabFlag::for($value, $range));
    }

    public static function twoSidedRanges(): array
    {
        return [
            'below the band' => ['11.2', '13.0 - 17.0', 'low'],
            'inside the band' => ['15.0', '13.0 - 17.0', 'normal'],
            'above the band' => ['18.4', '13.0 - 17.0', 'high'],
            'exactly the low bound' => ['13.0', '13.0 - 17.0', 'normal'],
            'exactly the high bound' => ['17.0', '13.0 - 17.0', 'normal'],
            'no spaces' => ['1.9', '1.5-4.1', 'normal'],
            'en dash' => ['11.2', '13.0 – 17.0', 'low'],
            'spelled out with to' => ['7.8', '4.0 to 5.6', 'high'],
            'thousands separators' => ['9800', '4,000 - 10,000', 'normal'],
            'integers' => ['250', '150-200', 'high'],
        ];
    }

    /**
     * The regression this class was created for: a lipid panel prints
     * "< 200" and "> 40", the model called both "normal", and the old
     * implementation returned null so the wrong answer survived.
     */
    #[DataProvider('oneSidedRanges')]
    public function test_one_sided_ranges(string $value, string $range, ?string $expected): void
    {
        $this->assertSame($expected, LabFlag::for($value, $range));
    }

    public static function oneSidedRanges(): array
    {
        return [
            'cholesterol over the max' => ['248', '< 200', 'high'],
            'cholesterol under the max' => ['180', '< 200', 'normal'],
            'hdl under the min' => ['38', '> 40', 'low'],
            'hdl over the min' => ['55', '> 40', 'normal'],
            'triglycerides over' => ['210', '<150', 'high'],
            'lte within' => ['5.0', '≤ 5', 'normal'],
            'lte above' => ['6.1', '≤ 5', 'high'],
            'gte within' => ['0.5', '≥ 0.5', 'normal'],
            'gte below' => ['0.4', '≥ 0.5', 'low'],
            'ascii lte' => ['4', '<=5', 'normal'],
            'ascii gte' => ['9', '>=10', 'low'],
        ];
    }

    #[DataProvider('deferToModel')]
    public function test_defers_to_the_model_when_it_cannot_decide(string $value, ?string $range): void
    {
        $this->assertNull(LabFlag::for($value, $range));
    }

    public static function deferToModel(): array
    {
        return [
            'no range at all' => ['13.8', null],
            'qualitative range' => ['Positive', 'Negative'],
            'qualitative value' => ['Reactive', '13.0 - 17.0'],
            'free text range' => ['12', 'See attached note'],
            'empty range' => ['12', ''],
        ];
    }

    public function test_reads_the_leading_number_when_the_unit_is_glued_on(): void
    {
        $this->assertSame('low', LabFlag::for('11.2 g/dL', '13.0 - 17.0'));
    }

    public function test_handles_a_value_with_thousands_separators(): void
    {
        $this->assertSame('normal', LabFlag::for('9,800', '4000-10000'));
    }

    /**
     * Found by running a real low-resolution lab report through the live
     * pipeline: Tesseract read HbA1c "7.8" as "78" and the summary then stated
     * "HbA1c at 78%" as fact. These pin the detector that flags it.
     */
    #[DataProvider('lostDecimalCases')]
    public function test_detects_a_value_that_lost_its_decimal_point(string $value, ?string $range, bool $expected): void
    {
        $this->assertSame($expected, LabFlag::looksLikeLostDecimal($value, $range));
    }

    public static function lostDecimalCases(): array
    {
        return [
            'hba1c 7.8 read as 78' => ['78', '4.0 - 5.6', true],
            'creatinine 1.1 read as 14' => ['14', '0.7 - 1.3', true],
            'platelets 1.9 read as 19' => ['19', '1.5 - 4.1', true],
            'correct decimal value that is genuinely high' => ['7.8', '4.0 - 5.6', false],
            'integer analyte with an integer range' => ['9800', '4000 - 10000', false],
            'integer value under a one-sided integer range' => ['248', '< 200', false],
            'integer below the range maximum' => ['12', '13.0 - 17.0', false],
            'no range to compare against' => ['78', null, false],
        ];
    }
}
