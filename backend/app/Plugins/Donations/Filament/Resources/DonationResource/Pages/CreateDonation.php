<?php

namespace App\Plugins\Donations\Filament\Resources\DonationResource\Pages;

use App\Plugins\Donations\Filament\Resources\DonationResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * An admin recording a gift that did not come through the website.
 */
class CreateDonation extends CreateRecord
{
    protected static string $resource = DonationResource::class;

    protected static ?string $title = 'Record a donation';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + ['payment_status' => 'paid'];
    }

    protected function getRedirectUrl(): string
    {
        return DonationResource::getUrl('view', ['record' => $this->record]);
    }
}
