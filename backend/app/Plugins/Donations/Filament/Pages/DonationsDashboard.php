<?php

namespace App\Plugins\Donations\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Plugins\Donations\Filament\Widgets;
use App\Plugins\Donations\Models\Donation;
use App\Plugins\Donations\Support\DonationQuery;
use App\Plugins\Donations\Support\DonationSettings;
use App\Plugins\Membership\Support\Paystack;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

/**
 * The Donations plugin's main screen: what has come in, with dropdown filters.
 */
class DonationsDashboard extends Dashboard
{
    use InFunctionalAreaPage;

    protected static string $area = 'donations';

    use HasFiltersForm;

    protected static ?string $plugin = 'donations';

    protected static string $routePath = 'donations';

    protected static ?string $title = 'Donations';

    protected static ?string $navigationLabel = 'Overview';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 1;

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('currency')
                        ->helperText('Totals are per currency: different currencies cannot be added together.')
                        // The currencies on offer, plus any that older donations were made in
                        ->options(fn () => collect(DonationSettings::currencies())
                            ->merge(Donation::query()->distinct()->pluck('currency'))
                            ->unique()
                            ->mapWithKeys(fn (string $code) => [$code => Paystack::CURRENCIES[$code] ?? $code]))
                        ->default(fn () => DonationSettings::defaultCurrency())
                        ->selectablePlaceholder(false),
                    Forms\Components\Select::make('method')
                        ->label('Received by')
                        ->placeholder('Any method')
                        ->options(Donation::METHODS),
                    Forms\Components\Select::make('period')
                        ->options(DonationQuery::PERIODS)
                        ->default('12m')
                        ->selectablePlaceholder(false),
                ]),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            Widgets\DonationStats::class,
            Widgets\DonationsChart::class,
            Widgets\TopDonors::class,
            Widgets\LatestDonations::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return ['md' => 3];
    }
}
