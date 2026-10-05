<?php

namespace App\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * For Filament resources: puts the resource in a functional area and applies
 * the role's privilege for it. View = list and open records; Manage = also
 * create, edit and delete.
 *
 * The class declares:  protected static string $area = 'content';
 * and, for plugins:    protected static ?string $plugin = 'membership';
 */
trait InFunctionalArea
{
    use SharedAreaAccess;

    public static function canViewAny(): bool
    {
        return static::canAccessArea();
    }

    public static function canView(Model $record): bool
    {
        return static::canAccessArea();
    }

    public static function canCreate(): bool
    {
        return static::canManage();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canManage();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canManage();
    }

    public static function canDeleteAny(): bool
    {
        return static::canManage();
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::canManage();
    }

    public static function canForceDeleteAny(): bool
    {
        return static::canManage();
    }

    public static function canRestore(Model $record): bool
    {
        return static::canManage();
    }

    public static function canRestoreAny(): bool
    {
        return static::canManage();
    }

    public static function canReplicate(Model $record): bool
    {
        return static::canManage();
    }

    public static function canReorder(): bool
    {
        return static::canManage();
    }

    /** Menu label, also used on the Roles screen */
    public static function permissionLabel(): string
    {
        return static::getNavigationLabel();
    }
}
