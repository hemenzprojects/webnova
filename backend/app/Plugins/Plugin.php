<?php

namespace App\Plugins;

/**
 * A feature module a tenant can switch on, WordPress-style.
 *
 * A plugin lives in app/Plugins/{Name}/ and is registered in config/plugins.php.
 * Its Filament resources and pages go in app/Plugins/{Name}/Filament/{Resources,Pages,Widgets}
 * and use the InFunctionalArea / InFunctionalAreaPage traits with $plugin set, so they only
 * appear while the plugin is active.
 * Its tables are ordinary tenant migrations, so they exist whether or not it is active.
 */
abstract class Plugin
{
    /** Unique key, e.g. "membership". Used in config, routes ("plugin:membership") and the API. */
    abstract public function key(): string;

    abstract public function name(): string;

    abstract public function description(): string;

    /** Heroicon name for the Plugins page card */
    public function icon(): string
    {
        return 'heroicon-o-puzzle-piece';
    }

    /**
     * Whether this site may activate the plugin. Every plugin is available to
     * every site for now; a subscription check for paid plugins goes here.
     */
    public function isAvailableFor(\App\Models\Tenant $tenant): bool
    {
        return true;
    }

    /**
     * A functional area (top-bar tab) for the plugin's admin screens, e.g.
     * ['label' => 'Membership', 'icon' => 'heroicon-o-identification'].
     * Its screens declare protected static string $area = '{plugin key}'.
     */
    public function area(): ?array
    {
        return null;
    }

    /** Page-builder block types this plugin adds, hidden in the editor while inactive */
    public function blocks(): array
    {
        return [];
    }

    /** Runs inside the tenant each time the plugin is switched on (keep it idempotent) */
    public function activated(): void {}

    /** Runs inside the tenant when the plugin is switched off. Data is kept. */
    public function deactivated(): void {}

    /** Directory holding this plugin's Filament classes */
    public function filamentPath(): string
    {
        return dirname((new \ReflectionClass($this))->getFileName()) . '/Filament';
    }

    public function filamentNamespace(): string
    {
        return (new \ReflectionClass($this))->getNamespaceName() . '\\Filament';
    }
}
