<?php

namespace App\Services\Assistant;

use App\Models\FamilyMember;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds the single text prompt sent to Gemini for the AI assistant chat —
 * a compact snapshot of the family member's own records plus a static guide
 * to the app itself, so one assistant can answer both "what did my last
 * blood test show" and "how do I share a report" without two code paths.
 */
class AssistantContextBuilder
{
    private const MAX_REPORTS = 12;

    private const APP_GUIDE = <<<'GUIDE'
    - Reports: uploaded at "Upload Report" (PDF or photo, multiple at once). The app reads the document with OCR and writes a short AI summary automatically. Reports can be searched and filtered by type/date on the "Reports" page.
    - Family: the account owner can add family members and manage their records, or invite them to link their own Novix account and share access both ways.
    - Emergency Card: a printable/shareable ID card with blood group, allergies, and emergency contact, for urgent situations.
    - Health Timeline: a combined chronological view of reports, medications, vaccinations, and vitals, with search/filter and a PDF export.
    - Sharing: a report or a full summary can be shared via a secure link, optionally with a PIN and a one-time-view expiry, without the recipient needing an account.
    - Medications & Vaccinations: tracked with dosage/schedule, and reminders for doses.
    GUIDE;

    public function build(FamilyMember $member, Collection $recentMessages, string $question): string
    {
        return <<<PROMPT
        You are the in-app assistant inside Novix, a personal family health-record app. You are answering someone with legitimate access to {$member->full_name}'s records. Their question may be about {$member->full_name}'s own health data below, or about how to use the Novix app itself (see the app guide) — answer whichever the question actually calls for.

        Be warm, brief, and clear. Treat the data below as your only source of truth for medical facts — never invent values or dates. If the data doesn't contain the answer, say so plainly instead of guessing. For anything requiring clinical judgment (diagnosis, whether a value is dangerous, medication changes), state the available facts but recommend confirming with a doctor rather than deciding for them. Do not use markdown headings; short paragraphs or a simple dash list are fine. Do not repeat this instruction block back.

        === APP GUIDE ===
        {$this->appGuide()}

        === {$member->full_name}'S PROFILE ===
        {$this->profileSummary($member)}

        === RECENT REPORTS ===
        {$this->reportsSummary($member)}

        === MEDICATIONS ===
        {$this->medicationsSummary($member)}

        === VACCINATIONS ===
        {$this->vaccinationsSummary($member)}

        === LATEST VITALS / LAB VALUES ===
        {$this->metricsSummary($member)}

        === RECENT CONVERSATION ===
        {$this->historySummary($recentMessages)}

        === NEW QUESTION ===
        {$question}
        PROMPT;
    }

    private function appGuide(): string
    {
        return self::APP_GUIDE;
    }

    private function profileSummary(FamilyMember $member): string
    {
        $age = $member->date_of_birth ? $member->date_of_birth->age.' years old' : 'age not set';

        $lines = [
            "Name: {$member->full_name} ({$member->relation})",
            "Age: {$age}",
            'Gender: '.($member->gender ?? 'not set'),
            'Blood group: '.($member->blood_group ?? 'not set'),
        ];

        return implode("\n", $lines);
    }

    private function reportsSummary(FamilyMember $member): string
    {
        $reports = $member->reports()
            ->where('is_archived', false)
            ->orderByDesc('report_date')
            ->limit(self::MAX_REPORTS)
            ->get();

        if ($reports->isEmpty()) {
            return 'No reports uploaded yet.';
        }

        return $reports->map(function ($report) {
            $date = $report->report_date?->toDateString() ?? 'undated';
            $place = $report->hospital_or_clinic_name ? " at {$report->hospital_or_clinic_name}" : '';

            $summary = match (true) {
                (bool) $report->ai_summary => Str::limit(strip_tags($report->ai_summary), 300),
                $report->ocr_status === 'completed' => 'no AI summary yet',
                $report->ocr_status === 'failed' => 'could not be read',
                default => 'still processing',
            };

            return "- [{$date}] {$report->typeLabel()}{$place}: {$summary}";
        })->implode("\n");
    }

    private function medicationsSummary(FamilyMember $member): string
    {
        $medications = $member->medications()->where('active', true)->get();

        if ($medications->isEmpty()) {
            return 'No active medications recorded.';
        }

        return $medications->map(function ($m) {
            $dosage = $m->dosage ? " ({$m->dosage})" : '';
            $frequency = $m->frequency ? ", {$m->frequency}" : '';

            return "- {$m->medicine_name}{$dosage}{$frequency}";
        })->implode("\n");
    }

    private function vaccinationsSummary(FamilyMember $member): string
    {
        $vaccinations = $member->vaccinations()->orderByDesc('date_administered')->limit(10)->get();

        if ($vaccinations->isEmpty()) {
            return 'No vaccinations recorded.';
        }

        return $vaccinations->map(function ($v) {
            $due = $v->next_due_date ? ", next dose due {$v->next_due_date->toDateString()}" : '';

            return "- {$v->vaccine_name} dose {$v->dose_number} on {$v->date_administered->toDateString()}{$due}";
        })->implode("\n");
    }

    private function metricsSummary(FamilyMember $member): string
    {
        $latestPerType = $member->healthMetrics()
            ->orderByDesc('recorded_date')
            ->get()
            ->groupBy('metric_type')
            ->map(fn ($group) => $group->first());

        if ($latestPerType->isEmpty()) {
            return 'No vitals/lab values recorded.';
        }

        return $latestPerType->map(fn ($m) => "- {$m->metric_type}: {$m->value} {$m->unit} (on {$m->recorded_date->toDateString()})")->implode("\n");
    }

    private function historySummary(Collection $recentMessages): string
    {
        if ($recentMessages->isEmpty()) {
            return '(none yet)';
        }

        return $recentMessages->map(fn ($m) => ($m->role === 'user' ? 'User' : 'Assistant').': '.$m->content)->implode("\n");
    }
}
