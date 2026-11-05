<?php

namespace App\Filament\Widgets;

class PageViewsChart extends SiteHealthChart
{
    protected static ?string $heading = 'Traffic';

    protected static ?string $description = 'Page views on each public site, per day';

    protected function metric(): string
    {
        return 'views';
    }
}
