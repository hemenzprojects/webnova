<?php

namespace App\Plugins\ComingSoon;

use App\Plugins\ComingSoon\Filament\Pages\ComingSoonSettingsPage;
use App\Plugins\Plugin;

/**
 * While active, visitors to the public site see a "coming soon" or "under
 * maintenance" screen. Signed-in admins still see the real site, so they can
 * keep building it. Its screen has its own Coming Soon tab in the admin.
 */
class ComingSoonPlugin extends Plugin
{
    public function key(): string
    {
        return 'coming_soon';
    }

    public function name(): string
    {
        return 'Coming Soon';
    }

    public function description(): string
    {
        return 'Hide the website behind a "coming soon" or "under maintenance" screen while you work on it. Anyone signed in to the admin still sees the real site.';
    }

    public function icon(): string
    {
        return 'heroicon-o-rocket-launch';
    }

    public function area(): ?array
    {
        return ['label' => 'Coming Soon', 'icon' => 'heroicon-o-rocket-launch'];
    }

    public function settingsUrl(): ?string
    {
        return ComingSoonSettingsPage::getUrl();
    }
}
