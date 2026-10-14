<?php

namespace App\Admin\Concerns;

use App\Admin\Access;
use App\Admin\Areas;
use App\Plugins\PluginManager;

/**
 * Parts shared by InFunctionalArea (resources) and InFunctionalAreaPage (pages).
 */
trait SharedAreaAccess
{
    public static function getArea(): string
    {
        return static::$area;
    }

    /** Key stored in a role's permissions, e.g. "news" or "header-settings" */
    public static function permissionKey(): string
    {
        return static::getSlug();
    }

    public static function pluginKey(): ?string
    {
        return property_exists(static::class, 'plugin') ? static::$plugin : null;
    }

    /** False for a plugin's screens while the plugin is switched off */
    public static function pluginIsActive(): bool
    {
        $plugin = static::pluginKey();

        return ! $plugin || app(PluginManager::class)->isActive($plugin);
    }

    public static function canAccessArea(): bool
    {
        if (static::getArea() === 'platform') {
            return ! tenancy()->initialized;
        }

        return static::pluginIsActive() && Access::canView(static::permissionKey());
    }

    public static function canManage(): bool
    {
        if (static::getArea() === 'platform') {
            return ! tenancy()->initialized;
        }

        return static::pluginIsActive() && Access::canManage(static::permissionKey());
    }

    /** Only listed in the sidebar while its area is the one being shown */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccessArea() && Areas::current() === static::getArea();
    }

    /** Areas replace navigation groups */
    public static function getNavigationGroup(): ?string
    {
        return null;
    }
}
