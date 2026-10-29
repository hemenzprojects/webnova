<?php

namespace App\Plugins\Donations\Filament\Widgets;

use App\Plugins\Donations\Support\DonationQuery;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class DonationsChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Donations received over time';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['md' => 2];

    protected function getData(): array
    {
        $period = $this->filters['period'] ?? '12m';
        $daily = in_array($period, ['30d', '90d'], true);
        $received = fn () => DonationQuery::filtered($this->filters)->where('payment_status', 'paid');

        $first = $received()->min('created_at');
        $since = DonationQuery::since($period) ?? ($first ? Carbon::parse($first)->startOfMonth() : now()->startOfMonth());

        // Group in PHP: portable across databases and small enough per site
        $totals = $received()->where('created_at', '>=', $since)->get(['created_at', 'amount'])
            ->groupBy(fn ($donation) => $donation->created_at->format($daily ? 'Y-m-d' : 'Y-m'))
            ->map(fn ($donations) => round((float) $donations->sum('amount'), 2));

        $labels = [];
        $data = [];
        foreach (CarbonPeriod::create($since, $daily ? '1 day' : '1 month', now()) as $date) {
            $labels[] = $date->format($daily ? 'j M' : 'M Y');
            $data[] = $totals[$date->format($daily ? 'Y-m-d' : 'Y-m')] ?? 0;
        }

        return [
            'datasets' => [[
                'label' => 'Received (' . DonationQuery::currency($this->filters) . ')',
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
        return ['scales' => ['y' => ['beginAtZero' => true]], 'plugins' => ['legend' => ['display' => false]]];
    }
}
