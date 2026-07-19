<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * One row per anonymous visit to a tracked public page (currently just the
 * landing page) — no auth required to generate one, so no user_id. Append-
 * only log, same spirit as AuditLog but for anonymous traffic rather than
 * authenticated actions.
 */
class PageView extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'path',
        'ip_address',
        'referer',
        'user_agent',
    ];

    public static function record(string $path): void
    {
        static::create([
            'path' => $path,
            'ip_address' => Request::ip(),
            'referer' => Request::header('referer'),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
