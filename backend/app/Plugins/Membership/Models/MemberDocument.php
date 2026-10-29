<?php

namespace App\Plugins\Membership\Models;

use App\Plugins\Membership\Support\PrivateFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member's own document (CV, certificate, licence…), uploaded in the
 * member portal (My documents), copied from their application, or added by
 * an admin. A private file: only the member and admins can open it.
 */
class MemberDocument extends Model
{
    public const CATEGORIES = [
        'cv' => 'CV',
        'academic' => 'Academic certificate',
        'professional' => 'Professional certificate or licence',
        'publication' => 'Publication',
        'identification' => 'Identification',
        'other' => 'Other',
    ];

    public const SOURCES = [
        'member' => 'Uploaded by the member',
        'application' => 'From their application',
        'admin' => 'Added by an admin',
    ];

    /** Most documents one member may keep */
    public const MAX_PER_MEMBER = 30;

    protected $fillable = ['member_account_id', 'category', 'title', 'file', 'name', 'size', 'mime', 'source', 'membership_registration_id'];

    protected static function booted(): void
    {
        // Size and type of a file uploaded through the admin's file field
        static::saving(function (self $document) {
            if ($document->isDirty('file') && ! $document->size && \Illuminate\Support\Facades\Storage::disk(PrivateFiles::DISK)->exists($document->file)) {
                $disk = \Illuminate\Support\Facades\Storage::disk(PrivateFiles::DISK);
                $document->size = $disk->size($document->file);
                $document->mime = $disk->mimeType($document->file) ?: null;
                $document->name = $document->name ?: basename($document->file);
            }
        });
        static::deleted(fn (self $document) => PrivateFiles::delete($document->file));
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MemberAccount::class, 'member_account_id');
    }

    /** Members cannot remove what came with their application: the secretariat relied on it */
    public function deletableByMember(): bool
    {
        return $this->source === 'member';
    }

    public function toPortalArray(): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'category_label' => self::CATEGORIES[$this->category] ?? $this->category,
            'title' => $this->title,
            'name' => $this->name,
            'size' => $this->size,
            'source' => $this->source,
            'source_label' => self::SOURCES[$this->source] ?? $this->source,
            'can_delete' => $this->deletableByMember(),
            'uploaded_at' => $this->created_at?->toDateString(),
        ];
    }
}
