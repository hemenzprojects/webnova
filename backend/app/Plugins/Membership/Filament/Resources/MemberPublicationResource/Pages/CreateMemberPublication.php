<?php

namespace App\Plugins\Membership\Filament\Resources\MemberPublicationResource\Pages;

use App\Plugins\Membership\Filament\Resources\MemberPublicationResource;
use App\Plugins\Membership\Models\MemberAccount;
use Filament\Resources\Pages\CreateRecord;

/** An update the editorial team adds itself (e.g. sent in by email); it waits for Publish like any other */
class CreateMemberPublication extends CreateRecord
{
    protected static string $resource = MemberPublicationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + [
            'status' => 'pending',
            'author_name' => MemberAccount::find($data['member_account_id'] ?? null)?->name,
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
