<?php

namespace App\Plugins\Membership\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a member keeps up to date about themselves in the member portal (My profile).
 * Crops and areas of expertise are chosen from the site's lists (Membership settings).
 */
class MemberProfile extends Model
{
    public const INSTITUTION_TYPES = [
        'university' => 'University or college',
        'research' => 'Research institute or centre',
        'government' => 'Government or public agency',
        'company' => 'Company or seed enterprise',
        'ngo' => 'NGO or international organisation',
        'independent' => 'Independent or consultant',
        'other' => 'Other',
    ];

    public const TITLES = ['Prof.', 'Dr', 'Mr', 'Mrs', 'Ms', 'Mx'];

    /** Fields a member may change themselves */
    public const EDITABLE = ['title', 'phone', 'country', 'city', 'institution', 'institution_type', 'position', 'crops', 'expertise', 'bio', 'orcid', 'website'];

    protected $fillable = ['member_account_id', ...self::EDITABLE];

    protected $casts = [
        'crops' => 'array',
        'expertise' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MemberAccount::class, 'member_account_id');
    }

    public function toPortalArray(): array
    {
        return collect(self::EDITABLE)->mapWithKeys(fn ($field) => [$field => $this->{$field} ?? (in_array($field, ['crops', 'expertise'], true) ? [] : null)])->all();
    }
}
