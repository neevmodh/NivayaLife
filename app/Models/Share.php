<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Share extends Model
{
    use HasValidation;

    protected $fillable = [
        'report_id',
        'family_member_id',
        'token',
        'access_type',
        'shared_with_label',
        'pin_hash',
        'is_one_time',
        'expires_at',
        'revoked_at',
        'first_viewed_at',
        'view_count',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'first_viewed_at' => 'datetime',
            'view_count' => 'integer',
            'is_one_time' => 'boolean',
        ];
    }

    public function rules(): array
    {
        return [
            'report_id' => ['nullable', 'integer', 'exists:reports,id'],
            'family_member_id' => ['nullable', 'integer', 'exists:family_members,id'],
            'access_type' => ['required', 'in:single_report,full_summary,emergency_card'],
            'expires_at' => ['required', 'date'],
        ];
    }

    protected function applyDefaults(): void
    {
        $this->token ??= Str::random(32);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /** The share's real, session-independent state — used for the sharer's own Share History page. */
    public function isActive(): bool
    {
        return $this->revoked_at === null && ! $this->isExpired();
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->isExpired() => 'expired',
            $this->revoked_at !== null && $this->is_one_time && $this->first_viewed_at !== null => 'viewed',
            $this->revoked_at !== null => 'revoked',
            default => 'active',
        };
    }

    public function requiresPin(): bool
    {
        return $this->pin_hash !== null;
    }

    public function setPin(?string $pin): void
    {
        $this->pin_hash = $pin ? Hash::make($pin) : null;
    }

    public function verifyPin(string $pin): bool
    {
        return $this->pin_hash !== null && Hash::check($pin, $this->pin_hash);
    }

    private function sessionKey(): string
    {
        return 'share_unlocked_'.$this->id;
    }

    public function isUnlockedInSession(): bool
    {
        return (bool) session($this->sessionKey(), false);
    }

    public function unlockInSession(): void
    {
        session([$this->sessionKey() => true]);
    }

    /**
     * Whether THIS browser session may view the share right now. Expiry is
     * absolute and can never be bypassed by anyone. Revocation is a hard
     * stop too — EXCEPT the specific case of a one-time link auto-revoking
     * itself immediately after its own first view, which the same session
     * that triggered it may still finish acting on (e.g. downloading the
     * PDF right after viewing). An owner's manual revoke always blocks
     * everyone immediately, including whoever was already viewing it.
     */
    public function isAccessibleNow(): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        if ($this->revoked_at === null) {
            return true;
        }

        $isOwnAutoRevocation = $this->is_one_time
            && $this->first_viewed_at !== null
            && $this->revoked_at->equalTo($this->first_viewed_at);

        return $isOwnAutoRevocation && $this->isUnlockedInSession();
    }

    /**
     * Records a successful view: increments view_count, and on the very
     * first view of a one-time-view share, sets revoked_at immediately
     * (per spec) while also unlocking this session so the same visitor can
     * still finish what they were doing (e.g. download the PDF) afterward.
     */
    public function recordSuccessfulView(): void
    {
        $isFirstView = $this->first_viewed_at === null;
        $now = now();

        $updates = ['view_count' => $this->view_count + 1];

        if ($isFirstView) {
            $updates['first_viewed_at'] = $now;

            if ($this->is_one_time) {
                $updates['revoked_at'] = $now;
            }
        }

        $this->update($updates);
        $this->unlockInSession();
    }
}
