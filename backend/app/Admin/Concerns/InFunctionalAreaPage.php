<?php

namespace App\Admin\Concerns;

/**
 * For Filament pages (settings screens, dashboards): puts the page in a
 * functional area. View = open it read-only; Manage = also save changes.
 * Pages with forms call static::canManage() to disable the form and hide
 * their save buttons, and guard their save() with authorizeManage().
 *
 * The class declares:  protected static string $area = 'appearance';
 * and, for plugins:    protected static ?string $plugin = 'membership';
 */
trait InFunctionalAreaPage
{
    use SharedAreaAccess;

    public static function canAccess(): bool
    {
        return static::canAccessArea() && parent::canAccess();
    }

    public static function permissionLabel(): string
    {
        return static::getNavigationLabel();
    }

    protected function authorizeManage(): void
    {
        abort_unless(static::canManage(), 403, 'You can view this page but not change it.');
    }
}
