<?php

namespace App\Http\Middleware;

use App\Plugins\PluginManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware "plugin:{key}" or "plugin:{key},{feature}": 404 unless the
 * plugin (and that feature of it) is switched on for this tenant.
 */
class EnsurePluginActive
{
    public function __construct(private PluginManager $plugins) {}

    public function handle(Request $request, Closure $next, string $key, ?string $feature = null): Response
    {
        abort_unless($this->plugins->isActive($key), 404);
        abort_if($feature && ! $this->plugins->featureIsActive($key, $feature), 404);

        return $next($request);
    }
}
