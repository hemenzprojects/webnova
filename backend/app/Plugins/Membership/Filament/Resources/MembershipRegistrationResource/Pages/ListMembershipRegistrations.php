<?php

namespace App\Plugins\Membership\Filament\Resources\MembershipRegistrationResource\Pages;

use App\Plugins\Membership\Filament\Resources\MembershipRegistrationResource;
use Filament\Resources\Pages\ListRecords;

class ListMembershipRegistrations extends ListRecords
{
    protected static string $resource = MembershipRegistrationResource::class;
}
