<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'attachments',
        'category',
        'is_published',
        'is_featured',
        'show_sidebar',
        'published_at',
        'order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'show_sidebar' => 'boolean',
        'published_at' => 'datetime',
        'attachments' => 'array',
    ];
}
