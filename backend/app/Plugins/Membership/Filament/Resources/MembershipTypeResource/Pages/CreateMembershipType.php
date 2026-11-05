<?php

namespace App\Plugins\Membership\Filament\Resources\MembershipTypeResource\Pages;

use App\Plugins\Membership\Filament\Resources\MembershipTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMembershipType extends CreateRecord
{
    protected static string $resource = MembershipTypeResource::class;
}
