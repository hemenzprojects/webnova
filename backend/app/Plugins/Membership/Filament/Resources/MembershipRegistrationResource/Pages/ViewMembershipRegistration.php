<?php

namespace App\Plugins\Membership\Filament\Resources\MembershipRegistrationResource\Pages;

use App\Plugins\Membership\Filament\Resources\MembershipRegistrationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMembershipRegistration extends ViewRecord
{
    protected static string $resource = MembershipRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return MembershipRegistrationResource::recordActions();
    }
}
