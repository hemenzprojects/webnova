<?php

namespace App\Plugins\Donations\Filament\Resources\DonationResource\Pages;

use App\Plugins\Donations\Filament\Resources\DonationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDonations extends ListRecords
{
    protected static string $resource = DonationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Record a donation')];
    }
}
