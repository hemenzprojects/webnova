<?php

namespace App\Admin;

use App\Models\Role;

/**
 * What the signed-in admin may do with each menu item: nothing, view, or manage
 * (create, edit, delete). Levels come from the user's role in this site;
 * the Administrator role may do everything.
 */
class Access
{
    public const NONE = null;

    public const VIEW = 'view';

    public const MANAGE = 'manage';

    public const LEVELS = [
        'none' => 'None',
        self::VIEW => 'View',
        self::MANAGE => 'Manage',
    ];

    /** @var array<int, Role|null> role per user id, for this request */
    private static array $roles = [];

    public static function level(string $permission): ?string
    {
        // Roles exist per site; the central (platform) admin has no tenant permissions
        if (! tenancy()->initialized) {
            return null;
        }

        $role = static::role();
        if (! $role) {
            return null;
        }
        if ($role->is_admin) {
            return self::MANAGE;
        }

        $level = $role->permissions[$permission] ?? null;

        return in_array($level, [self::VIEW, self::MANAGE], true) ? $level : null;
    }

    public static function canView(string $permission): bool
    {
        return static::level($permission) !== null;
    }

    public static function canManage(string $permission): bool
    {
        return static::level($permission) === self::MANAGE;
    }

    public static function role(): ?Role
    {
        $user = auth()->user();
        if (! $user || ! $user->role_id) {
            return null;
        }

        return static::$roles[$user->id] ??= Role::find($user->role_id);
    }

    public static function flush(): void
    {
        static::$roles = [];
    }
}
