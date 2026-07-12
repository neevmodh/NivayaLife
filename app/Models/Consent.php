<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class Consent extends Model
{
    use HasValidation;

    protected $fillable = [
        'user_id',
        'consent_type',
        'granted_at',
        'revoked_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'consent_type' => ['required', 'in:upload,ai_processing,sharing,account_creation'],
            'granted_at' => ['required', 'date'],
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    public static function grant(User $user, string $consentType, ?Request $request = null): self
    {
        return static::create([
            'user_id' => $user->id,
            'consent_type' => $consentType,
            'granted_at' => now(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
