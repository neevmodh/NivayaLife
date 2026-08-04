<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * Singleton settings row (always id=1, created by the create_site_settings_table
 * migration). Read through current() rather than find(1) so callers get the
 * cached copy; SiteSettingController flushes the cache explicitly after saving.
 */
class SiteSetting extends Model
{
    protected $fillable = [
        'maintenance_mode',
        'maintenance_message',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_mode' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current(): self
    {
        return Cache::rememberForever('site_setting', fn () => static::firstOrCreate(['id' => 1]));
    }
}
