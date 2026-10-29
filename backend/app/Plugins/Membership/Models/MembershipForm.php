<?php

namespace App\Plugins\Membership\Models;

use App\Plugins\Membership\Support\FormSchema;
use Illuminate\Database\Eloquent\Model;

/**
 * The site's registration form (a single row). See Support\FormSchema for the shape.
 */
class MembershipForm extends Model
{
    protected $fillable = ['schema', 'version'];

    protected $casts = ['schema' => 'array'];

    public static function current(): self
    {
        return static::query()->first()
            ?? static::create(['schema' => FormSchema::default(), 'version' => 1]);
    }
}
