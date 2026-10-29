<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Data retention for membership applications (Membership plugin). Needs the scheduler running
// (`php artisan schedule:work`, or `schedule:run` every minute).
\Illuminate\Support\Facades\Schedule::command('membership:prune-applications')->dailyAt('03:00');
