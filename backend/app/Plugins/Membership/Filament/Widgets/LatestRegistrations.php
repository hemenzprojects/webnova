<?php

namespace App\Plugins\Membership\Filament\Widgets;

use App\Plugins\Membership\Filament\Resources\MembershipRegistrationResource;
use App\Plugins\Membership\Support\RegistrationQuery;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

class LatestRegistrations extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Latest registrations';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    /** Two thirds next to "Members by country" when that is shown (member portal on), else the full width */
    public function getColumnSpan(): int|string|array
    {
        return app(\App\Plugins\Membership\Support\MemberAccounts::class)->enabled() ? ['md' => 2] : 'full';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => RegistrationQuery::filtered($this->filters)->with('membershipType')->latest())
            ->defaultPaginationPageOption(10)
            ->columns(MembershipRegistrationResource::summaryColumns())
            ->recordUrl(fn ($record) => MembershipRegistrationResource::getUrl('view', ['record' => $record]));
    }
}
