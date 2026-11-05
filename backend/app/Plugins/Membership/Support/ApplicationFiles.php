<?php

namespace App\Plugins\Membership\Support;

use App\Plugins\Membership\Models\MembershipRegistration;
use Illuminate\Http\UploadedFile;

/**
 * Documents attached to membership applications (form fields of type "file"),
 * e.g. CVs and certificates. Private files (PrivateFiles) under
 * {tenant_id}/membership/applications/; only admins can download them, and
 * approved members find copies under My documents in the member portal.
 *
 * In the stored answers a file is ['file' => path, 'name' => original name, 'size' => bytes, 'mime' => type].
 */
class ApplicationFiles
{
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

        return PrivateFiles::quotaError($bytes) ? self::QUOTA_MESSAGE : null;
    }

    /** @return array{file: string, name: string, size: int, mime: ?string} */
    public function store(UploadedFile $file): array
    {
        return PrivateFiles::store($file, 'applications');
    }

    /** Remove every file attached to an application */
    public function deleteFor(MembershipRegistration $registration): void
    {
        foreach ($registration->answers ?? [] as $answer) {
            if (self::isFile($answer)) {
                PrivateFiles::delete($answer['file']);
            }
        }
    }
}
