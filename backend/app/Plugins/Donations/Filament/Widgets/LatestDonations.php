<?php

namespace App\Plugins\Donations\Filament\Widgets;

use App\Plugins\Donations\Filament\Resources\DonationResource;
use App\Plugins\Donations\Support\DonationQuery;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

class LatestDonations extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Latest donations';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => DonationQuery::filtered($this->filters)->latest())
            ->defaultPaginationPageOption(10)
            ->columns(DonationResource::summaryColumns())
            ->recordUrl(fn ($record) => DonationResource::getUrl('view', ['record' => $record]));
    }
}
