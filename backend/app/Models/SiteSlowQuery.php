<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Central database: written by App\Services\SiteHealth.
 */
class SiteSlowQuery extends Model
{
    use CentralConnection;

    protected $table = 'site_slow_queries';

    public $timestamps = false;

    protected $casts = ['created_at' => 'datetime'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
