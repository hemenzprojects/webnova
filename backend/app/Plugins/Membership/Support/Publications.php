<?php

namespace App\Plugins\Membership\Support;

use App\Filament\Resources\PageResource;
use App\Plugins\Membership\MembershipPlugin;
use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Models\MemberPublication;
use App\Plugins\PluginManager;
use App\Services\StorageQuota;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Member updates (Membership): members submit publications, milestones and
 * news in the portal; the editorial team publishes them or not. Cover images
 * go on the public disk under {tenant}/membership/updates/ with random names
 * (they are shown on the website once published).
 */
class Publications
{
    public static function enabled(): bool
    {
        return app(PluginManager::class)->featureIsActive('membership', MembershipPlugin::FEATURE_PUBLICATIONS);
    }

    /** Validation for the portal's form; a publication needs its link */
    public static function rules(): array
    {
        return [
            'kind' => ['required', Rule::in(array_keys(MemberPublication::KINDS))],
            'title' => 'required|string|max:200',
            'summary' => 'nullable|string|max:600',
            'link' => ['nullable', 'required_if:kind,publication', 'url:https,http', 'max:500'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:' . MemberPublication::IMAGE_MAX_KB, 'dimensions:max_width=6000,max_height=6000'],
            'remove_image' => 'boolean',
        ];
    }

    public static function messages(): array
    {
        return [
            'link.required_if' => 'Add the link to where the publication is hosted (journal, repository, Google Drive…).',
            'link.url' => 'Enter a full web address, starting with https://',
            'image.max' => 'The image must not be larger than 2 MB.',
            'image.mimes' => 'Use a JPG, PNG or WebP image.',
        ];
    }

    /** @throws ValidationException */
    public function submit(MemberAccount $member, array $data, ?UploadedFile $image): MemberPublication
    {
        if (! $member->canUseMemberArea()) {
            throw ValidationException::withMessages(['title' => 'Only members in good standing can submit updates.']);
        }
        if ($member->publications()->where('status', 'pending')->count() >= MemberPublication::MAX_PENDING) {
            throw ValidationException::withMessages(['title' => 'You have ' . MemberPublication::MAX_PENDING . ' submissions waiting for review. Please wait until they have been reviewed.']);
        }

        $publication = $member->publications()->create([
            'kind' => $data['kind'],
            'title' => trim($data['title']),
            'summary' => filled($data['summary'] ?? null) ? trim($data['summary']) : null,
            'link' => filled($data['link'] ?? null) ? trim($data['link']) : null,
            'image' => $image ? $this->storeImage($image) : null,
            'status' => 'pending',
            'author_name' => $member->name,
        ]);
        app(MembershipMailer::class)->publicationSubmitted($publication);

        return $publication;
    }

    /** A member's change to a submission waiting for review (or not published: it is reviewed again) */
    public function update(MemberPublication $publication, array $data, ?UploadedFile $image): MemberPublication
    {
        abort_unless($publication->editableByMember(), 403, 'Published updates can only be changed by the editorial team.');

        $values = [
            'kind' => $data['kind'],
            'title' => trim($data['title']),
            'summary' => filled($data['summary'] ?? null) ? trim($data['summary']) : null,
            'link' => filled($data['link'] ?? null) ? trim($data['link']) : null,
            'status' => 'pending',
            'review_note' => null,
        ];
        if ($image) {
            $values['image'] = $this->storeImage($image);
        } elseif ($data['remove_image'] ?? false) {
            $values['image'] = null;
        }
        $wasRejected = $publication->status === 'rejected';
        $publication->update($values);
        if ($wasRejected) {
            app(MembershipMailer::class)->publicationSubmitted($publication);
        }

        return $publication;
    }

    public function publish(MemberPublication $publication, bool $notify = true): void
    {
        $publication->update([
            'status' => 'approved',
            'review_note' => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'published_at' => $publication->published_at ?? now(),
        ]);
        if ($notify) {
            app(MembershipMailer::class)->publicationReviewed($publication);
        }
    }

    public function reject(MemberPublication $publication, ?string $reason, bool $notify = true): void
    {
        $publication->update([
            'status' => 'rejected',
            'review_note' => filled($reason) ? trim($reason) : null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'published_at' => null,
            'is_featured' => false,
        ]);
        if ($notify) {
            app(MembershipMailer::class)->publicationReviewed($publication);
        }
    }

    /** @throws ValidationException */
    private function storeImage(UploadedFile $image): string
    {
        if ($error = app(StorageQuota::class)->errorForUpload((int) $image->getSize())) {
            throw ValidationException::withMessages(['image' => $error]);
        }
        $extension = strtolower($image->extension() ?: $image->getClientOriginalExtension() ?: 'jpg');

        return Storage::disk('public')->putFileAs((PageResource::tenantDir('membership/updates'))(), $image, Str::ulid() . '.' . $extension);
    }
}
