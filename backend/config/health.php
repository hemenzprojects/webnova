<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site health statistics
    |--------------------------------------------------------------------------
    |
    | Per-site figures shown to the platform admin under Platform → Site Health:
    | page views, response times, server errors and slow database queries.
    | Collected by App\Services\SiteHealth into the central database.
    |
    */

    'enabled' => (bool) env('SITE_HEALTH_ENABLED', true),

    // A database query taking at least this long is logged as slow (ms)
    'slow_query_ms' => (int) env('SITE_HEALTH_SLOW_QUERY_MS', 200),

    // A request taking at least this long counts as slow (ms)
    'slow_request_ms' => (int) env('SITE_HEALTH_SLOW_REQUEST_MS', 1000),

    // Daily figures are kept this long; logged slow queries for a shorter time
    'keep_days' => 90,
    'keep_slow_queries_days' => 30,

    /*
    | When a site is flagged, judged on its last 7 days.
    */
    'attention' => ['avg_ms' => 800, 'error_percent' => 1, 'slow_queries' => 10],
    'critical' => ['avg_ms' => 2000, 'error_percent' => 5],

];
