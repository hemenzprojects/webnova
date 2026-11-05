<?php

namespace App\Plugins\ComingSoon\Support;

use App\Models\Branding;
use App\Plugins\PluginManager;

/**
 * The Coming Soon plugin's settings for the current site, with defaults.
 * Stored on the tenant_plugins row.
 */
class ComingSoonSettings
{
    public const MODES = [
        'coming_soon' => 'Coming soon (the site has not launched yet)',
        'maintenance' => 'Under maintenance (the site is temporarily down)',
    ];

    public const DEFAULTS = [
        'mode' => 'coming_soon',
        'heading' => 'Something new is on the way',
        'message' => 'We are working hard on our new website. Please check back soon.',
        // Shows a countdown on the screen when set
        'launch_at' => null,
        'background_image' => null,
        'show_logo' => true,
        'show_contact' => true,
        'contact_heading' => 'Get in touch',
        'contact_email' => null,
        'contact_phone' => null,
        'contact_address' => null,
        'show_social' => true,
        // [{platform, url}], platforms from App\Support\SocialPlatforms
        'social_links' => [],
    ];

    public static function all(): array
    {
        return array_merge(self::DEFAULTS, self::saved());
    }

    /**
     * Settings for the admin form. Until contact details and social links have
     * been saved here, they start as a copy of the site's (Branding).
     */
    public static function forForm(): array
    {
        $settings = self::all();

        if (! array_key_exists('social_links', self::saved())) {
            $branding = Branding::settings();
            $settings['contact_email'] = $settings['contact_email'] ?: $branding->contact_email;
            $settings['contact_phone'] = $branding->contact_phone;
            $settings['contact_address'] = $branding->contact_address;
            $settings['social_links'] = $branding->social_links ?? [];
        }

        return $settings;
    }

    public static function get(string $key): mixed
    {
        return self::all()[$key] ?? null;
    }

    public static function save(array $values): void
    {
        app(PluginManager::class)->saveSettings('coming_soon', array_merge(self::all(), $values));
    }

    private static function saved(): array
    {
        return app(PluginManager::class)->settings('coming_soon');
    }
}
