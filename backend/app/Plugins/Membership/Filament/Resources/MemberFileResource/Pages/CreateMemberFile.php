<?php

namespace App\Plugins\Membership\Filament\Resources\MemberFileResource\Pages;

use App\Plugins\Membership\Filament\Resources\MemberFileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMemberFile extends CreateRecord
{
    protected static string $resource = MemberFileResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
