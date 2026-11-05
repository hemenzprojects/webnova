<?php

namespace App\Plugins\Donations\Filament\Resources\DonationResource\Pages;

use App\Plugins\Donations\Filament\Resources\DonationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDonation extends ViewRecord
{
    protected static string $resource = DonationResource::class;

    protected function getHeaderActions(): array
    {
        return DonationResource::recordActions();
    }
}
