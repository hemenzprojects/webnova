<?php

namespace App\Plugins\Membership\Filament\Widgets;

use App\Plugins\Membership\Models\MembershipType;
use App\Plugins\Membership\Support\RegistrationQuery;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class RegistrationsByType extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'By membership type';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $counts = RegistrationQuery::filtered($this->filters)
            ->selectRaw('membership_type_id, count(*) as total')
            ->groupBy('membership_type_id')
            ->pluck('total', 'membership_type_id');

        $names = MembershipType::whereIn('id', $counts->keys()->filter())->pluck('name', 'id');

        return [
            'datasets' => [[
                'data' => $counts->values()->all(),
                'backgroundColor' => ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ef4444', '#14b8a6', '#6b7280'],
            ]],
            'labels' => $counts->keys()->map(fn ($id) => $names[$id] ?? 'No type')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
