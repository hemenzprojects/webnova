<?php

namespace App\Http\Middleware;

use App\Plugins\PluginManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware "plugin:{key}": 404 unless the plugin is active for this tenant.
 */
class EnsurePluginActive
{
    public function __construct(private PluginManager $plugins) {}

    public function handle(Request $request, Closure $next, string $key): Response
    {
        abort_unless($this->plugins->isActive($key), 404);

        return $next($request);
    }
}
