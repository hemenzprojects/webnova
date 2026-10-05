<?php

namespace App\Plugins\Membership\Filament\Resources\MembershipTypeResource\Pages;

use App\Plugins\Membership\Filament\Resources\MembershipTypeResource;
use Filament\Resources\Pages\EditRecord;

class EditMembershipType extends EditRecord
{
    protected static string $resource = MembershipTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\DeleteAction::make()];
    }
}
