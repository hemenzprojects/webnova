<?php

namespace App\Plugins\Donations\Filament\Widgets;

use App\Plugins\Donations\Models\Donation;
use App\Plugins\Donations\Support\DonationQuery;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

/**
 * Who has given the most in the chosen period, counted by email.
 */
class TopDonors extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Top donors';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['md' => 1];

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $totals = DonationQuery::filtered($this->filters)
                    ->where('payment_status', 'paid')
                    ->whereNotNull('email')
                    ->groupBy('email')
                    ->selectRaw("min(id) as id, email, max(first_name || ' ' || last_name) as donor, sum(amount) as amount, count(*) as gifts");

                // As a subquery, so the table can sort the totals like plain columns
                return Donation::query()->fromSub($totals, 'donations');
            })
            ->columns([
                Tables\Columns\TextColumn::make('donor')->description(fn (Donation $record) => $record->email),
                Tables\Columns\TextColumn::make('amount')->label('Given')->alignEnd()
                    ->formatStateUsing(fn ($state) => DonationQuery::currency($this->filters) . ' ' . number_format((float) $state, 2))
                    ->description(fn (Donation $record) => $record->gifts . ' ' . str('donation')->plural((int) $record->gifts)),
            ])
            ->defaultSort('amount', 'desc')
            ->paginated([5])
            ->emptyStateHeading('No donations yet')
            ->emptyStateDescription(null);
    }
}
