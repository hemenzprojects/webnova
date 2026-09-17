<?php

namespace App\Filament\Resources\BrandingResource\Pages;

use App\Filament\Resources\BrandingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBrandings extends ListRecords
{
    protected static string $resource = BrandingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
