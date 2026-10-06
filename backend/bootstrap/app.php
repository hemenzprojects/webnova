<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(
            \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
        );
        // Site Health statistics; outermost, so its terminate() sees every request
        $middleware->prepend(\App\Http\Middleware\RecordSiteHealth::class);
        $middleware->alias([
            'plugin' => \App\Http\Middleware\EnsurePluginActive::class,
            'admin.can' => \App\Http\Middleware\EnsureAdminCan::class,
        ]);
        $middleware->api(append: [
            \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
