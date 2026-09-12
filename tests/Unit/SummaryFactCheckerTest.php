<?php

namespace Tests\Unit;

use App\Services\Ai\SummaryFactChecker;
use PHPUnit\Framework\TestCase;

class SummaryFactCheckerTest extends TestCase
{
    public function test_no_unverified_numbers_when_all_are_in_source(): void
    {
        $source = 'Hemoglobin 13.8 g/dL, reference range 13.0-17.0';
        $content = 'Your hemoglobin is 13.8, within the normal 13.0-17.0 range.';

        $this->assertSame([], SummaryFactChecker::unverifiedNumbers($content, $source));
    }

    public function test_flags_a_number_not_present_in_source(): void
    {
        $source = 'Hemoglobin 13.8 g/dL, reference range 13.0-17.0';
        $content = 'Your hemoglobin is 15.2, which is high.';

        $this->assertSame(['15.2'], SummaryFactChecker::unverifiedNumbers($content, $source));
    }

    public function test_ignores_bare_single_digits(): void
    {
        $source = 'Blood test report';
        $content = 'Here are a few things to ask about, in 3 short points.';

        $this->assertSame([], SummaryFactChecker::unverifiedNumbers($content, $source));
    }
}
