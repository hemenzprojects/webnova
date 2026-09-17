<?php

namespace App\Filament\Resources\BrandingResource\Pages;

use App\Filament\Resources\BrandingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBranding extends EditRecord
{
    protected static string $resource = BrandingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
