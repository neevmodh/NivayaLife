<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A snapshot of everything a deleted account owned — written once, at
 * deletion time, then never touched by the running app again. Not linked
 * to any live table by foreign key on purpose: it must survive regardless
 * of what happens to the (now-deleted) original records, and a later
 * signup with the same email must never automatically reattach to it.
 */
class ArchivedAccount extends Model
{
    protected $fillable = [
        'original_user_id',
        'email',
        'name',
        'phone',
        'phone_country_code',
        'password',
        'google_id',
        'was_admin',
        'original_created_at',
        'data',
        'archived_files',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'was_admin' => 'boolean',
            'original_created_at' => 'datetime',
            'data' => 'array',
            'archived_files' => 'array',
            'archived_at' => 'datetime',
        ];
    }
}
