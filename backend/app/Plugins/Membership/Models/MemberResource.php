<?php

namespace App\Plugins\Membership\Models;

use App\Plugins\Membership\Support\PrivateFiles;
use Illuminate\Database\Eloquent\Model;

/**
 * A file the Association shares with its members (meeting minutes, reports,
 * guidelines…). Private: listed in the member portal's Members' area and
 * downloadable by active members and admins only.
 */
class MemberResource extends Model
{
    protected $fillable = ['title', 'description', 'category', 'file', 'name', 'size', 'mime', 'is_published', 'order'];

    protected $casts = ['is_published' => 'boolean'];

    protected static function booted(): void
    {
        // Size and type of a newly uploaded file (uploaded through the admin's file field)
        static::saving(function (self $resource) {
            if ($resource->isDirty('file') && $resource->file && \Illuminate\Support\Facades\Storage::disk(PrivateFiles::DISK)->exists($resource->file)) {
                $disk = \Illuminate\Support\Facades\Storage::disk(PrivateFiles::DISK);
                $resource->size = $disk->size($resource->file);
                $resource->mime = $disk->mimeType($resource->file) ?: null;
                $resource->name = $resource->name ?: basename($resource->file);
            }
        });
        static::deleted(fn (self $resource) => PrivateFiles::delete($resource->file));
        // A replaced file goes too
        static::updated(function (self $resource) {
            if ($resource->wasChanged('file')) {
                PrivateFiles::delete($resource->getOriginal('file'));
            }
        });
    }

    public function toPortalArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'name' => $this->name,
            'size' => $this->size,
            'updated_at' => $this->updated_at?->toDateString(),
        ];
    }
}
