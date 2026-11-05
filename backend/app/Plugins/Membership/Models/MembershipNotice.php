<?php

namespace App\Plugins\Membership\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A renewal notice emailed to a member (reminder, overdue, suspended,
 * forfeited): sent once per member, year and kind, and kept as the record
 * of formal notices.
 */
class MembershipNotice extends Model
{
    public const KINDS = [
        'reminder' => 'Renewal reminder',
        'overdue' => 'Overdue notice',
        'suspended' => 'Suspension notice',
        'forfeited' => 'Forfeiture notice',
    ];

    protected $fillable = ['member_account_id', 'year', 'kind', 'email', 'sent', 'sent_at'];

    protected $casts = [
        'year' => 'integer',
        'sent' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function memberAccount(): BelongsTo
    {
        return $this->belongsTo(MemberAccount::class);
    }
}
