<?php

namespace App\Plugins\Membership\Filament\Resources\MemberFileResource\Pages;

use App\Plugins\Membership\Filament\Resources\MemberFileResource;
use Filament\Resources\Pages\ListRecords;

class ListMemberFiles extends ListRecords
{
    protected static string $resource = MemberFileResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()->label('Add file')];
    }
}
