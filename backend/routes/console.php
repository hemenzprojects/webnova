<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Data retention for membership applications (Membership plugin). Needs the scheduler running
// (`php artisan schedule:work`, or `schedule:run` every minute).
\Illuminate\Support\Facades\Schedule::command('membership:prune-applications')->dailyAt('03:00');

// Stanbic (N-Genius) membership payments whose payer did not come back to the website
\Illuminate\Support\Facades\Schedule::command('membership:check-payments')->everyFiveMinutes()->withoutOverlapping();
