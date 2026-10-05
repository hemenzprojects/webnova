<?php

namespace App\Plugins\Membership\Filament\Pages;

use App\Admin\Concerns\InFunctionalAreaPage;
use App\Plugins\Membership\Filament\Widgets;
use App\Plugins\Membership\Models\MembershipRegistration;
use App\Plugins\Membership\Models\MembershipType;
use App\Plugins\Membership\Support\RegistrationQuery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

/**
 * The Membership plugin's main screen: registration numbers with dropdown filters.
 */
class MembershipDashboard extends Dashboard
{
    use InFunctionalAreaPage;

    protected static string $area = 'membership';

    use HasFiltersForm;

    protected static ?string $plugin = 'membership';

    protected static string $routePath = 'membership';

    protected static ?string $title = 'Membership';

    protected static ?string $navigationLabel = 'Overview';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 1;

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->columns(4)
                ->schema([
                    Forms\Components\Select::make('membership_type_id')
                        ->label('Membership type')
                        ->placeholder('All types')
                        ->options(fn () => MembershipType::orderBy('order')->pluck('name', 'id')),
                    Forms\Components\Select::make('status')
                        ->label('Review status')
                        ->placeholder('Any status')
                        ->options(MembershipRegistration::STATUSES),
                    Forms\Components\Select::make('payment_status')
                        ->label('Payment')
                        ->placeholder('Any payment status')
                        ->options(MembershipRegistration::PAYMENT_STATUSES),
                    Forms\Components\Select::make('period')
                        ->label('Period')
                        ->options(RegistrationQuery::PERIODS)
                        ->default('12m')
                        ->selectablePlaceholder(false),
                ]),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            Widgets\MembershipStats::class,
            Widgets\RegistrationsChart::class,
            Widgets\RegistrationsByType::class,
            Widgets\LatestRegistrations::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return ['md' => 3];
    }
}
