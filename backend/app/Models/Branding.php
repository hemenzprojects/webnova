<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branding extends Model
{
    protected $fillable = [
        'site_name',
        'site_tagline',
        'site_description',
        'logo',
        'logo_light',
        'favicon',
        'primary_color',
        'primary_light',
        'primary_dark',
        'accent_color',
        'accent_light',
        'accent_dark',
        'text_color',
        'background_color',
        'contact_email',
        'contact_phone',
        'contact_address',
        'facebook_url',
        'twitter_url',
        'linkedin_url',
        'instagram_url',
        'youtube_url',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'meta_keywords' => 'array',
    ];

    /**
     * Get the branding settings (singleton pattern)
     */
    public static function settings()
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'site_name' => 'WEBNOVA',
                'site_tagline' => 'Ghanaian Academic and Research Network',
                'site_description' => 'Connecting Ghana\'s education sector through high-speed internet and innovation',
                'primary_color' => '#0A1E3E',
                'primary_light' => '#1A3A5C',
                'primary_dark' => '#050F1F',
                'accent_color' => '#00D9FF',
                'accent_light' => '#33E3FF',
                'accent_dark' => '#00A8CC',
                'text_color' => '#1F2937',
                'background_color' => '#FFFFFF',
            ]
        );
    }
}
