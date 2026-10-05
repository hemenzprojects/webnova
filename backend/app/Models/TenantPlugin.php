<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Whether a plugin is switched on for this tenant, plus its settings.
 * Not cleared by theme installs.
 */
class TenantPlugin extends Model
{
    protected $fillable = ['key', 'is_active', 'settings', 'activated_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'encrypted:array',
        'activated_at' => 'datetime',
    ];
}
