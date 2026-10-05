<?php

namespace App\Plugins\Membership\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipType extends Model
{
    public const PERIODS = [
        'year' => 'per year',
        'month' => 'per month',
        'once' => 'one-time',
    ];

    /** Short suffix shown after the price on the public form */
    public const PERIOD_SUFFIX = ['year' => '/ yr', 'month' => '/ mo', 'once' => ''];

    protected $fillable = ['name', 'slug', 'description', 'price', 'period', 'is_active', 'order'];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function registrations(): HasMany
    {
        return $this->hasMany(MembershipRegistration::class);
    }
}
