<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A type of admin user in this site. permissions maps a menu item's key
 * (e.g. "news") to "view" or "manage"; missing means no access.
 * The Administrator role (is_admin) can do everything and cannot be removed.
 */
class Role extends Model
{
    protected $fillable = ['name', 'description', 'is_admin', 'permissions'];

    protected $casts = [
        'is_admin' => 'boolean',
        'permissions' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
