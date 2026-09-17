<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'blocks',
        'template_type',
        'builder_settings',
        'featured_image',
        'meta_title',
        'meta_description',
        'is_published',
        'order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'blocks' => 'array',
        'builder_settings' => 'array',
    ];

    /**
     * Check if this page uses the page builder
     */
    public function usesBuilder(): bool
    {
        return $this->template_type === 'builder' && !empty($this->blocks);
    }
}
