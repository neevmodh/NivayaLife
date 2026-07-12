<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $table = 'audit_log';

    protected $fillable = [
        'user_id',
        'family_member_id',
        'action',
        'target_type',
        'target_id',
        'ip_address',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    /**
     * Convenience helper that captures the current request's IP/user agent
     * automatically, so callers only need to supply what happened and to what.
     */
    public static function record(string $action, string $targetType, int $targetId, ?int $userId = null, ?int $familyMemberId = null): self
    {
        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'family_member_id' => $familyMemberId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
