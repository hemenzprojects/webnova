<?php

namespace App\Plugins\Membership\Filament\Resources\MemberAccountResource\Pages;

use App\Plugins\Membership\Filament\Resources\MemberAccountResource;
use Filament\Resources\Pages\ListRecords;

class ListMemberAccounts extends ListRecords
{
    protected static string $resource = MemberAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()->label('Add member')];
    }
}
