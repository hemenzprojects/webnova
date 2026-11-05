<?php

namespace App\Plugins\Membership\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A publication, milestone or news item a member submitted (Member updates):
 * waiting for review, on the website, or not published (with the reason).
 */
class MemberPublication extends Model
{
    public const KINDS = [
        'publication' => 'Publication',
        'milestone' => 'Milestone or award',
        'news' => 'News or update',
    ];

    public const STATUSES = [
        'pending' => 'Waiting for review',
        'approved' => 'Published',
        'rejected' => 'Not published',
    ];

    /** Waiting for review at once, per member */
    public const MAX_PENDING = 5;

    /** Cover image: a screenshot or the cover, kept small */
    public const IMAGE_MAX_KB = 2048;

    protected $fillable = [
        'member_account_id', 'kind', 'title', 'summary', 'link', 'image', 'status', 'review_note',
        'reviewed_by', 'reviewed_at', 'published_at', 'is_featured', 'author_name',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Its cover image goes with it, and when it is replaced
        static::deleted(fn (self $p) => $p->deleteImage($p->image));
        static::updated(function (self $p) {
            if ($p->wasChanged('image')) {
                $p->deleteImage($p->getOriginal('image'));
            }
        });
    }

    public function memberAccount(): BelongsTo
    {
        return $this->belongsTo(MemberAccount::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'approved')->whereNotNull('published_at');
    }

    /** Editable (and removable) by the member only while waiting or not published */
    public function editableByMember(): bool
    {
        return $this->status !== 'approved';
    }

    public function authorName(): ?string
    {
        return $this->memberAccount?->name ?? $this->author_name;
    }

    /** What the website's Member updates block shows */
    public function toPublicArray(): array
    {
        $profile = $this->memberAccount?->profile;

        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'kind_label' => self::KINDS[$this->kind] ?? $this->kind,
            'title' => $this->title,
            'summary' => $this->summary,
            'link' => $this->link,
            'image' => $this->image,
            'author' => $this->authorName(),
            'author_detail' => collect([$profile?->institution, $profile?->country])->filter()->implode(', ') ?: null,
            'published_at' => $this->published_at?->toDateString(),
            'is_featured' => $this->is_featured,
        ];
    }

    /** What the member sees of their own submission */
    public function toPortalArray(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'kind_label' => self::KINDS[$this->kind] ?? $this->kind,
            'title' => $this->title,
            'summary' => $this->summary,
            'link' => $this->link,
            'image' => $this->image,
            'status' => $this->status,
            'status_label' => self::STATUSES[$this->status] ?? $this->status,
            'review_note' => $this->status === 'rejected' ? $this->review_note : null,
            'submitted_at' => $this->created_at?->toDateString(),
            'published_at' => $this->published_at?->toDateString(),
            'editable' => $this->editableByMember(),
        ];
    }

    private function deleteImage(?string $path): void
    {
        if ($path && ! static::where('image', $path)->exists()) {
            Storage::disk('public')->delete($path);
        }
    }
}
