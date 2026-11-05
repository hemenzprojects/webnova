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

    protected $fillable = ['name', 'slug', 'description', 'price', 'included_seats', 'seat_type_id', 'period', 'is_active', 'order'];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'included_seats' => 'integer',
    ];

    /** No fee to pay (e.g. Honorary): always in good standing */
    public function isExempt(): bool
    {
        return (float) $this->price <= 0;
    }

    /** The category the included seats are (e.g. Ordinary) */
    public function seatType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'seat_type_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(MembershipRegistration::class);
    }
}
