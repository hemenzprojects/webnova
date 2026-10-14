<?php

namespace App\Http\Middleware;

use App\Services\SiteHealth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Counts each request to a site and how long it took (Platform → Site Health).
 * Runs after the response has been sent, so it adds nothing to response times.
 */
class RecordSiteHealth
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // The page-view beacon is a statistic itself, not site traffic
        if ($request->is('api/v1/track')) {
            return;
        }

        $started = defined('LARAVEL_START') ? LARAVEL_START : $request->server('REQUEST_TIME_FLOAT');

        app(SiteHealth::class)->recordRequest(
            tenancy()->tenant?->id,
            (int) round((microtime(true) - $started) * 1000),
            $response->getStatusCode(),
        );
    }
}
