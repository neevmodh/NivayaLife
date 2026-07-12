<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A linked family member's explicit, revocable grant of view access to their
 * own records, given to the primary account (or another user).
 */
class SharingPermission extends Model
{
    use HasValidation;

    protected $fillable = [
        'family_member_id',
        'granted_to_user_id',
        'scope',
        'granted_at',
        'revoked_at',
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
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'granted_to_user_id' => ['required', 'integer', 'exists:users,id'],
            'scope' => ['required', 'in:full,reports_only,summary_only'],
            'granted_at' => ['required', 'date'],
        ];
    }

    protected function applyDefaults(): void
    {
        $this->granted_at ??= now();
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function grantedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_to_user_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }
}
