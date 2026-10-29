<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\Membership\Models\MembershipRegistration;
use App\Services\StorageQuota;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Documents attached to membership applications (form fields of type "file"),
 * e.g. CVs and certificates. Personal data, so they are kept on the private
 * disk under {tenant_id}/membership/applications/ (counted in the site's
 * storage quota) and only admins can download them.
 *
 * In the stored answers a file is ['file' => path, 'name' => original name, 'size' => bytes, 'mime' => type].
 */
class ApplicationFiles
{
    public const DISK = 'local';

    public const EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    public const MAX_KB = 5120;

    /** Shown beside every file field */
    public const HINT = 'PDF, Word, JPG or PNG, up to 5 MB.';

    /** Message for applicants when the site has no room left (admins see the details in their own uploads) */
    public const QUOTA_MESSAGE = 'We cannot accept files at the moment. Please try again later, or contact us.';

    public static function isFile(mixed $answer): bool
    {
        return is_array($answer) && isset($answer['file'], $answer['name']);
    }

    /** Null if the site has room for these uploads, else the message for the applicant */
    public function quotaError(array $uploads): ?string
    {
        $bytes = array_sum(array_map(fn (UploadedFile $file) => (int) $file->getSize(), $uploads));

        return $bytes > 0 && app(StorageQuota::class)->errorForUpload($bytes) ? self::QUOTA_MESSAGE : null;
    }

    /** @return array{file: string, name: string, size: int, mime: ?string} */
    public function store(UploadedFile $file): array
    {
        $dir = tenant()->getTenantKey() . '/membership/applications';
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $path = Storage::disk(self::DISK)->putFileAs($dir, $file, Str::ulid() . '.' . $extension);

        return [
            'file' => $path,
            'name' => Str::limit(basename($file->getClientOriginalName()), 150, ''),
            'size' => (int) $file->getSize(),
            'mime' => $file->getClientMimeType(),
        ];
    }

    /** Remove every file attached to an application */
    public function deleteFor(MembershipRegistration $registration): void
    {
        foreach ($registration->answers ?? [] as $answer) {
            if (self::isFile($answer) && $this->belongsToSite($answer['file'])) {
                Storage::disk(self::DISK)->delete($answer['file']);
            }
        }
    }

    /** Stored paths always sit in this site's folder; anything else is never served */
    public function belongsToSite(string $path): bool
    {
        return str_starts_with($path, tenant()->getTenantKey() . '/membership/applications/') && ! str_contains($path, '..');
    }
}
