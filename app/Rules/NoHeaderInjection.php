<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects any embedded CR/LF in a value bound for an outbound mail header
 * (recipient, subject, etc.) — a defense-in-depth layer independent of
 * Laravel's own "email" rule, which has an unpatched CRLF-injection gap on
 * this app's framework line (CVE-2026-48019, fixed upstream only in
 * 12.60.0+/13.10.0+; a major-version upgrade is a separate undertaking).
 * Without this, a crafted value like "user@example.com\r\nBcc: x@evil.com"
 * could inject extra headers into a real outbound email.
 */
class NoHeaderInjection implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && preg_match('/[\r\n]/', $value)) {
            $fail('The :attribute field contains invalid characters.');
        }
    }
}
