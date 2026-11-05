<?php

namespace App\Filament\Widgets;

use App\Services\SiteHealth;
use Filament\Widgets\ChartWidget;

/**
 * A line per site over the last days (Platform → Site Health).
 */
abstract class SiteHealthChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $pollingInterval = null;

    protected static ?string $maxHeight = '280px';

    public ?string $filter = '14';

    private const COLORS = ['#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16'];

    /** 'views' or 'avg_ms' */
    abstract protected function metric(): string;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return ['7' => 'Last 7 days', '14' => 'Last 14 days', '30' => 'Last 30 days', '90' => 'Last 90 days'];
    }

    protected function getData(): array
    {
        $data = app(SiteHealth::class)->daily($this->metric(), (int) $this->filter);

        $datasets = [];
        foreach ($data['series'] as $site => $values) {
            $color = self::COLORS[count($datasets) % count(self::COLORS)];
            $datasets[] = [
                'label' => $site,
                'data' => $values,
                'borderColor' => $color,
                'backgroundColor' => $color,
                'tension' => 0.3,
                'spanGaps' => true,
            ];
        }

        return ['labels' => $data['labels'], 'datasets' => $datasets];
    }

    protected function getOptions(): array
    {
        return ['scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]];
    }
}
