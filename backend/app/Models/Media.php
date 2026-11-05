<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'filename',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'alt_text',
    ];

    public function getSizeFormattedAttribute(): string
    {
        if (!$this->size) return '—';
        if ($this->size >= 1048576) return round($this->size / 1048576, 1) . ' MB';
        return round($this->size / 1024, 1) . ' KB';
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }
}