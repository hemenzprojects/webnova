<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'content',
        'icon',
        'category',
        'price_label',
        'featured_image',
        'is_active',
        'is_featured',
        'show_sidebar',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'show_sidebar' => 'boolean',
    ];
}
