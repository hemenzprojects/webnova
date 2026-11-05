<?php

namespace App\Filament\Widgets;

class ResponseTimeChart extends SiteHealthChart
{
    protected static ?string $heading = 'Response time';

    protected static ?string $description = 'Average time the server took to answer, in milliseconds. Lower is better.';

    protected function metric(): string
    {
        return 'avg_ms';
    }
}
