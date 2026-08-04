<?php

namespace App\Services\Assistant;

use Illuminate\Support\Str;

/**
 * Deterministic safety checks that run before the model is ever called.
 *
 * Anything here is a rule we are not willing to leave to a language model's
 * judgement: if someone describes a heart attack, they get emergency guidance
 * immediately and identically every time, rather than whatever the model
 * happens to generate that day. The model handles nuance; this handles the
 * cases where nuance is the wrong answer.
 */
class AssistantSafety
{
    /**
     * Red-flag symptom patterns. Matched on word boundaries so "chest pain"
     * fires but "chest x-ray" does not.
     *
     * These intentionally over-trigger slightly: showing an emergency notice
     * to someone asking a calm question is a small annoyance, missing someone
     * describing a stroke is not.
     */
    private const EMERGENCY_PATTERNS = [
        // Cardiac / respiratory
        'chest pain', 'chest tightness', 'crushing chest', 'heart attack',
        'can\'t breathe', 'cannot breathe', 'can not breathe', 'trouble breathing',
        'difficulty breathing', 'gasping', 'choking',
        // Neurological
        'stroke', 'face drooping', 'slurred speech', 'sudden numbness',
        'worst headache', 'seizure', 'convulsion', 'unconscious', 'unresponsive',
        'passed out', 'fainted',
        // Bleeding / trauma
        'severe bleeding', 'bleeding heavily', 'won\'t stop bleeding',
        'coughing blood', 'coughing up blood', 'vomiting blood',
        // Other
        'anaphylaxis', 'severe allergic reaction', 'throat closing',
        'overdose', 'poisoned', 'poisoning',
    ];

    /** Self-harm needs a different response than a physical emergency. */
    private const CRISIS_PATTERNS = [
        'kill myself', 'killing myself', 'end my life', 'ending my life',
        'suicide', 'suicidal', 'want to die', 'wanna die', 'self harm',
        'self-harm', 'hurt myself', 'hurting myself', 'no reason to live',
    ];

    public function emergencyNoticeFor(string $message): ?string
    {
        $normalized = Str::lower($message);

        if ($this->matchesAny($normalized, self::CRISIS_PATTERNS)) {
            return <<<'TEXT'
            I'm really glad you told me, and I want to be honest with you: I'm a health-records assistant, and this is bigger than anything I can help with properly.

            Please reach out to someone who can right now — a crisis helpline in your country, a doctor, or someone you trust who can be with you. In India you can call Tele-MANAS on 14416 (24x7, free). If you are in immediate danger, please call your local emergency number.

            You deserve real support, not an app. I'll still be here for your records whenever you need them.
            TEXT;
        }

        if ($this->matchesAny($normalized, self::EMERGENCY_PATTERNS)) {
            return <<<'TEXT'
            This sounds like it could be a medical emergency, so I'm not going to try to answer it as a records question.

            Please call your local emergency number now — 112 in India, or 911 / 999 depending on where you are — or get to the nearest emergency department. If someone is with you, tell them what's happening.

            Your Emergency Card in Nivaya Life has the blood group, allergies, and contacts a paramedic would ask for — you can open it from the dashboard without logging in again.
            TEXT;
        }

        return null;
    }

    /**
     * Neutralizes instruction-shaped text coming from record content.
     *
     * Report summaries in the prompt are produced by OCR of documents the app
     * did not write, so a PDF containing "ignore previous instructions" would
     * otherwise be read by the model as if the app had said it. Defanging the
     * few phrases that matter is cheap and keeps the record readable.
     */
    public function sanitizeRecordText(string $text): string
    {
        return preg_replace(
            '/\b(ignore (all |any )?(previous|prior|above) instructions?|disregard (the )?(above|previous)|system prompt|you are now|new instructions?)\b/i',
            '[redacted directive]',
            $text
        ) ?? $text;
    }

    private function matchesAny(string $haystack, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_contains($haystack, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
