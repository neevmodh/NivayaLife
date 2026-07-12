<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FamilyInvitation extends Model
{
    use HasValidation;

    protected $fillable = [
        'primary_account_id',
        'family_member_id',
        'invited_email',
        'invited_by',
        'token',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function rules(): array
    {
        return [
            'primary_account_id' => ['required', 'integer', 'exists:users,id'],
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'invited_email' => ['required', 'email', 'max:255'],
            'status' => ['required', 'in:pending,accepted,expired'],
        ];
    }

    protected function applyDefaults(): void
    {
        $this->token ??= Str::random(64);
        $this->expires_at ??= now()->addDays(7);
    }

    public function primaryAccount(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_account_id');
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }
}
