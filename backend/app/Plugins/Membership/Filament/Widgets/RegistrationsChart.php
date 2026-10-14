<?php

namespace App\Plugins\Membership\Filament\Widgets;

use App\Plugins\Membership\Support\RegistrationQuery;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class RegistrationsChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Registrations over time';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['md' => 2];

    protected function getData(): array
    {
        $period = $this->filters['period'] ?? '12m';
        $daily = in_array($period, ['30d', '90d'], true);
        $since = RegistrationQuery::since($period)
            ?? (RegistrationQuery::filtered($this->filters)->min('created_at') ? \Carbon\Carbon::parse(RegistrationQuery::filtered($this->filters)->min('created_at'))->startOfMonth() : now()->startOfMonth());

        // Group in PHP: portable across databases and small enough per site
        $counts = RegistrationQuery::filtered($this->filters)
            ->where('created_at', '>=', $since)
            ->pluck('created_at')
            ->countBy(fn ($date) => $date->format($daily ? 'Y-m-d' : 'Y-m'));

        $buckets = CarbonPeriod::create($since, $daily ? '1 day' : '1 month', now());
        $labels = [];
        $data = [];
        foreach ($buckets as $date) {
            $labels[] = $date->format($daily ? 'j M' : 'M Y');
            $data[] = $counts[$date->format($daily ? 'Y-m-d' : 'Y-m')] ?? 0;
        }

        return [
            'datasets' => [[
                'label' => 'Registrations',
                'data' => $data,
                'fill' => true,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return ['scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]], 'plugins' => ['legend' => ['display' => false]]];
    }
}
