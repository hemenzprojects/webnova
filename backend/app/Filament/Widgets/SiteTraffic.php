<?php

namespace App\Filament\Widgets;

use App\Models\SitePageView;
use Filament\Widgets\ChartWidget;

/**
 * Site dashboard: page views of this site per day (from Site Health's figures).
 */
class SiteTraffic extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Visitors';

    protected static ?string $pollingInterval = null;

    protected static ?string $maxHeight = '260px';

    public ?string $filter = '30';

    public static function canView(): bool
    {
        return tenancy()->initialized;
    }

    protected function getFilters(): ?array
    {
        return ['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days'];
    }

    public function getDescription(): ?string
    {
        $total = (int) SitePageView::where('tenant_id', tenant('id'))
            ->where('date', '>=', today()->subDays((int) $this->filter - 1))
            ->sum('views');

        return number_format($total).' page '.str('view')->plural($total);
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $views = SitePageView::where('tenant_id', tenant('id'))
            ->where('date', '>=', today()->subDays($days - 1))
            ->selectRaw('date, sum(views) as views')
            ->groupBy('date')
            ->pluck('views', 'date')
            ->mapWithKeys(fn ($v, $date) => [substr($date, 0, 10) => (int) $v]);

        $labels = $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $labels[] = $day->format('j M');
            $data[] = $views[$day->toDateString()] ?? 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Page views',
                'data' => $data,
                'borderColor' => '#f59e0b',
                'backgroundColor' => 'rgba(245, 158, 11, .12)',
                'fill' => true,
                'tension' => 0.3,
            ]],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
