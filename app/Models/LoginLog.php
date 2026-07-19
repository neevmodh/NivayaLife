<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per authentication attempt across every login path — the regular
 * form, Google OAuth, invitation accept, and registration complete all end
 * up calling Auth::login()/Auth::attempt() under the hood, so a single
 * listener on Laravel's own Login/Failed events (see RecordLoginAttempt)
 * catches all of them without touching each controller individually.
 */
class LoginLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'email',
        'successful',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
