<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'expires_at',
        'revoked_at',
        'view_count',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'view_count' => 'integer',
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

    public function isActive(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }

    public function recordView(): void
    {
        $this->increment('view_count');
    }
}
