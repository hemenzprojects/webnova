<?php

namespace App\Admin;

use App\Plugins\PluginManager;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;

/**
 * Functional areas of the admin. Each is a tab in the top bar; the sidebar
 * shows the menu items of the area you are in. Resources and pages join an
 * area with the InFunctionalArea / InFunctionalAreaPage traits.
 */
class Areas
{
    public const AREAS = [
        'content' => ['label' => 'Content', 'icon' => 'heroicon-o-document-text', 'sort' => 1],
        'appearance' => ['label' => 'Appearance', 'icon' => 'heroicon-o-swatch', 'sort' => 2],
        'system' => ['label' => 'System Administration', 'icon' => 'heroicon-o-cog-6-tooth', 'sort' => 90],
        // Central domain only
        'platform' => ['label' => 'Platform', 'icon' => 'heroicon-o-building-office-2', 'sort' => 1],
    ];

    private const SESSION_KEY = 'admin_area';

    /**
     * All areas, including those added by plugins (Plugin::area()).
     */
    public static function all(): array
    {
        $areas = self::AREAS;
        foreach (app(PluginManager::class)->all() as $plugin) {
            if ($area = $plugin->area()) {
                $areas[$plugin->key()] = $area + ['sort' => 50, 'plugin' => $plugin->key()];
            }
        }
        uasort($areas, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        return $areas;
    }

    /**
     * Every resource and page class in the panel that belongs to an area.
     *
     * @return Collection<int, class-string>
     */
    public static function members(): Collection
    {
        $panel = Filament::getCurrentPanel() ?? Filament::getDefaultPanel();

        return collect(array_merge($panel->getResources(), $panel->getPages()))
            ->unique()
            ->filter(fn (string $class) => method_exists($class, 'getArea'))
            ->values();
    }

    /**
     * Menu items of an area, in menu order, whether or not the user can see them.
     *
     * @return Collection<int, class-string>
     */
    public static function itemsOf(string $area): Collection
    {
        return static::members()
            ->filter(fn ($class) => $class::getArea() === $area)
            ->sortBy(fn ($class) => $class::getNavigationSort() ?? 0)
            ->values();
    }

    /**
     * Areas the signed-in admin can open, with the address of their first item.
     *
     * @return array<string, array{label: string, icon: string, url: string}>
     */
    public static function visible(): array
    {
        $out = [];
        foreach (static::all() as $key => $area) {
            $first = static::itemsOf($key)->first(fn ($class) => $class::canAccessArea());
            if ($first) {
                $out[$key] = $area + ['url' => $first::getUrl()];
            }
        }

        return $out;
    }

    /**
     * The area of the page being shown. Remembered in the session so Livewire
     * updates (which don't carry the page's route) keep the same sidebar.
     */
    public static function current(): ?string
    {
        $class = static::currentPageClass();
        if ($class) {
            $area = method_exists($class, 'getArea') ? $class::getArea() : null;
            if ($area) {
                session([self::SESSION_KEY => $area]);
            }

            return $area ?? session(self::SESSION_KEY);
        }

        return session(self::SESSION_KEY);
    }

    /**
     * Resource or page class behind the current request, if it is a panel page.
     */
    private static function currentPageClass(): ?string
    {
        $controller = request()->route()?->getAction('controller');
        if (! is_string($controller) || ! class_exists($controller)) {
            return null;
        }

        // Resource pages (List, Edit, ...) belong to their resource
        if (method_exists($controller, 'getResource')) {
            return $controller::getResource();
        }

        return $controller;
    }
}
