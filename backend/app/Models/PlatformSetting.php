<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Central database: a platform-wide setting (central admin), stored
 * encrypted. One row per group, e.g. "mail".
 */
class PlatformSetting extends Model
{
    use CentralConnection;

    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'encrypted:array'];

    public static function read(string $key): array
    {
        return (array) (static::where('key', $key)->first()?->value ?? []);
    }

    public static function write(string $key, array $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
