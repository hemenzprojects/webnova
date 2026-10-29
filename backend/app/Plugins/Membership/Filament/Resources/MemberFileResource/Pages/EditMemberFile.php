<?php

namespace App\Plugins\Membership\Filament\Resources\MemberFileResource\Pages;

use App\Plugins\Membership\Filament\Resources\MemberFileResource;
use Filament\Resources\Pages\EditRecord;

class EditMemberFile extends EditRecord
{
    protected static string $resource = MemberFileResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\DeleteAction::make()];
    }
}
