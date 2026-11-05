<?php

namespace App\Plugins\Membership\Filament\Widgets;

use App\Plugins\Membership\Models\MemberProfile;
use Filament\Widgets\ChartWidget;

/**
 * Where members are (My profile → country): the 12 countries with the most
 * members, the rest together.
 */
class MembersByCountry extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Members by country';

    protected static ?int $sort = 4;

    // One column of the dashboard's three, next to the other charts
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected function getData(): array
    {
        $counts = MemberProfile::query()
            ->whereHas('account', fn ($q) => $q->where('status', 'active'))
            ->selectRaw("coalesce(nullif(country, ''), 'Not given') as place, count(*) as total")
            ->groupBy('place')
            ->orderByDesc('total')
            ->pluck('total', 'place');

        $top = $counts->take(12);
        if ($counts->count() > 12) {
            $top['Other countries'] = $counts->slice(12)->sum();
        }

        return [
            'datasets' => [[
                'label' => 'Members',
                'data' => $top->values()->all(),
                'backgroundColor' => '#3b82f6',
                'borderWidth' => 0,
                'maxBarThickness' => 14,
                'borderRadius' => 3,
            ]],
            'labels' => $top->keys()->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['ticks' => ['precision' => 0], 'grid' => ['display' => false]],
                'y' => ['grid' => ['display' => false], 'ticks' => ['font' => ['size' => 11]]],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
