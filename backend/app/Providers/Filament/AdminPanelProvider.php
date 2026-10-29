<?php

namespace App\Providers\Filament;

use App\Models\Branding;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // A site's admin carries its own name and logo; the central admin is WebNova
            ->brandName(fn () => static::brand()['name'])
            ->brandLogo(fn () => view('filament.admin.brand', static::brand()))
            ->brandLogoHeight('2rem')
            ->favicon(fn () => static::brand()['favicon'])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            // Functional areas: tabs in the top bar, the area's items in the sidebar
            ->renderHook(PanelsRenderHook::TOPBAR_START, fn () => view('filament.admin.areas-topbar'))
            ->renderHook(PanelsRenderHook::SIDEBAR_NAV_START, fn () => view('filament.admin.areas-sidebar'));

        // Each plugin's Filament classes; they hide themselves while the plugin is off
        foreach (config('plugins.plugins', []) as $class) {
            $plugin = app($class);
            $path = $plugin->filamentPath();
            $namespace = $plugin->filamentNamespace();
            $panel
                ->discoverResources(in: "{$path}/Resources", for: "{$namespace}\\Resources")
                ->discoverPages(in: "{$path}/Pages", for: "{$namespace}\\Pages")
                ->discoverWidgets(in: "{$path}/Widgets", for: "{$namespace}\\Widgets");
        }

        return $panel;
    }

    /**
     * The current site's name, logo and favicon (relative URLs, so they follow the
     * domain the admin is opened on). Read once per request.
     *
     * @return array{name: string, logo: ?string, favicon: ?string}
     */
    protected static function brand(): array
    {
        return once(function () {
            $tenant = tenancy()->tenant;
            $branding = $tenant ? rescue(fn () => Branding::settings(), null, false) : null;
            $url = fn (?string $path) => $path ? '/storage/'.ltrim($path, '/') : null;

            return [
                'name' => $branding?->site_name ?: $tenant?->name ?: 'WebNova',
                'logo' => $url($branding?->logo),
                'favicon' => $url($branding?->favicon),
            ];
        });
    }
}
