<?php

namespace App\Plugins\Membership\Support;

use App\Services\StorageQuota;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The Membership plugin's private files: application documents, member
 * documents and member resources. Kept on the private disk under
 * {tenant_id}/membership/{area}/ (never served by nginx, counted in the
 * site's storage quota) and only ever sent through a route that checks who
 * is asking.
 *
 * A stored file is described as ['file' => path, 'name' => original name, 'size' => bytes, 'mime' => type].
 */
class PrivateFiles
{
    public const DISK = 'local';

    public const EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    public const MAX_KB = 5120;

    public const HINT = 'PDF, Word, JPG or PNG, up to 5 MB.';

    /** Validation rules for one upload */
    public static function rules(): array
    {
        return ['file', 'mimes:' . implode(',', self::EXTENSIONS), 'max:' . self::MAX_KB];
    }

    /** The quota error for these uploads, or null if the site has room */
    public static function quotaError(int $bytes): ?string
    {
        return $bytes > 0 ? app(StorageQuota::class)->errorForUpload($bytes) : null;
    }

    /** @return array{file: string, name: string, size: int, mime: ?string} */
    public static function store(UploadedFile $file, string $area): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $path = Storage::disk(self::DISK)->putFileAs(self::dir($area), $file, Str::ulid() . '.' . $extension);

        return [
            'file' => $path,
            'name' => Str::limit(basename($file->getClientOriginalName()), 150, ''),
            'size' => (int) $file->getSize(),
            'mime' => $file->getClientMimeType(),
        ];
    }

    /** A copy of a stored file in another area (e.g. an application document becoming a member document) */
    public static function copy(string $path, string $area): ?string
    {
        if (! self::belongsToSite($path) || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }
        $copy = self::dir($area) . '/' . Str::ulid() . '.' . pathinfo($path, PATHINFO_EXTENSION);

        return Storage::disk(self::DISK)->copy($path, $copy) ? $copy : null;
    }

    public static function delete(?string $path): void
    {
        if ($path && self::belongsToSite($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /** The file as a download, or a 404 if it is not one of this site's */
    public static function download(string $path, string $name)
    {
        abort_unless(self::belongsToSite($path) && Storage::disk(self::DISK)->exists($path), 404);

        return Storage::disk(self::DISK)->download($path, $name, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Stored paths always sit in this site's membership folder; anything else is never served */
    public static function belongsToSite(string $path): bool
    {
        return str_starts_with($path, tenant()->getTenantKey() . '/membership/') && ! str_contains($path, '..');
    }

    private static function dir(string $area): string
    {
        return tenant()->getTenantKey() . '/membership/' . trim($area, '/');
    }
}
