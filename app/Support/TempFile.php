<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Storage::disk(...)->path() only works on the local driver — it throws on
 * S3-compatible disks (R2 included), which have no real filesystem path.
 * Callers that need one (Imagick, OCR) get it via a temp file downloaded
 * for the duration of the callback and removed afterward, so a switch to a
 * remote disk driver doesn't change their code, only where the bytes
 * physically live in between.
 */
class TempFile
{
    public static function fromDisk(string $disk, string $path, callable $callback): mixed
    {
        if (config("filesystems.disks.{$disk}.driver") === 'local') {
            return $callback(Storage::disk($disk)->path($path));
        }

        $base = tempnam(sys_get_temp_dir(), 'novix_');
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $tmpPath = $extension ? "{$base}.{$extension}" : $base;

        if ($tmpPath !== $base) {
            rename($base, $tmpPath);
        }

        file_put_contents($tmpPath, Storage::disk($disk)->get($path));

        try {
            return $callback($tmpPath);
        } finally {
            @unlink($tmpPath);
        }
    }
}
