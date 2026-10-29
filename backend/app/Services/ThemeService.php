<?php

namespace App\Services;

use App\Models\Tenant;

class ThemeService
{
    /**
     * All themes available in the system.
     *
     * @return array<string, array>
     */
    public function all(): array
    {
        return config('themes.themes', []);
    }

    /**
     * The theme's screenshot, resources/themes/{slug}/screenshot.{webp,png,jpg}, if it has one.
     */
    public function screenshotPath(string $slug): ?string
    {
        if (! array_key_exists($slug, $this->all())) {
            return null;
        }

        foreach (['webp', 'png', 'jpg'] as $ext) {
            $path = resource_path("themes/{$slug}/screenshot.{$ext}");
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Look up a theme by slug, falling back to 'default'.
     */
    public function get(string $slug): array
    {
        $themes = $this->all();
        $theme = $themes[$slug] ?? $themes['default'] ?? [];

        return array_merge(['slug' => isset($themes[$slug]) ? $slug : 'default'], $theme);
    }

    /**
     * Resolve the theme for the currently active tenant.
     */
    public function forCurrentTenant(): array
    {
        $tenant = tenant();
        $slug = $tenant?->theme_slug ?? 'default';

        return $this->get($slug);
    }

    /**
     * Persist a new theme slug on the tenant record (central DB).
     */
    public function applyToTenant(Tenant $tenant, string $slug): array
    {
        if (! array_key_exists($slug, $this->all())) {
            $slug = 'default';
        }

        $tenant->theme_slug = $slug;
        $tenant->save();

        return $this->get($slug);
    }
}