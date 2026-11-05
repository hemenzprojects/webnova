<?php

namespace App\Plugins\Membership\Filament\Resources\MemberAccountResource\Pages;

use App\Plugins\Membership\Filament\Resources\MemberAccountResource;
use Filament\Resources\Pages\EditRecord;

class EditMemberAccount extends EditRecord
{
    protected static string $resource = MemberAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...MemberAccountResource::accountActions(\Filament\Actions\Action::class),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
