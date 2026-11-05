<?php

namespace App\Plugins\Membership\Filament\Resources\MemberPublicationResource\Pages;

use App\Plugins\Membership\Filament\Resources\MemberPublicationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMemberPublication extends EditRecord
{
    protected static string $resource = MemberPublicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...MemberPublicationResource::reviewActions(Actions\Action::class),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return MemberPublicationResource::canManage() ? parent::getFormActions() : [];
    }
}
