<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    public const VISIBILITY_PUBLIC = 'public';

    /** Signed-in active members (Membership plugin, Member accounts feature) and admins */
    public const VISIBILITY_MEMBERS = 'members';

    public const VISIBILITIES = [
        self::VISIBILITY_PUBLIC => 'Everyone',
        self::VISIBILITY_MEMBERS => 'Members only',
    ];

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
        'visibility',
        'order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'blocks' => 'array',
        'builder_settings' => 'array',
    ];

    public function isMembersOnly(): bool
    {
        return $this->visibility === self::VISIBILITY_MEMBERS;
    }

    /**
     * Check if this page uses the page builder
     */
    public function usesBuilder(): bool
    {
        return $this->template_type === 'builder' && !empty($this->blocks);
    }
}
