<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UploadStorage
{
    public static function diskName(): string
    {
        return (string) config('filesystems.upload_disk', 'minio');
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(self::diskName());
    }

    /**
     * Prefer the configured upload disk; fall back to local for legacy files.
     */
    public static function diskFor(string $storedPath): Filesystem
    {
        $primary = self::disk();
        if ($primary->exists($storedPath)) {
            return $primary;
        }

        if (self::diskName() !== 'local' && Storage::disk('local')->exists($storedPath)) {
            return Storage::disk('local');
        }

        return $primary;
    }

    /**
     * Stream a stored file inline (works for local and S3/MinIO).
     */
    public static function inlineResponse(string $storedPath, string $downloadName, ?string $mime = null): StreamedResponse
    {
        $disk = self::diskFor($storedPath);
        abort_unless($disk->exists($storedPath), 404);

        if ($mime === null) {
            try {
                $mime = $disk->mimeType($storedPath) ?: 'application/octet-stream';
            } catch (\Throwable) {
                $mime = 'application/octet-stream';
            }
        }

        return $disk->response($storedPath, $downloadName, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($downloadName).'"',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
