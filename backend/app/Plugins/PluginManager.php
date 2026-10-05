<?php

namespace App\Plugins;

use App\Models\Tenant;
use App\Models\TenantPlugin;
use Illuminate\Support\Facades\Schema;

/**
 * A plugin runs for a tenant when it is available to that site (see
 * Plugin::isAvailableFor, where subscriptions will be checked) and the site's
 * admin has switched it on (tenant_plugins table).
 */
class PluginManager
{
    /** @var array<string, Plugin>|null */
    private ?array $plugins = null;

    /** @var array<string, bool> active state per tenant id, for this request */
    private array $activeCache = [];

    /**
     * @return array<string, Plugin> every installed plugin, keyed by key()
     */
    public function all(): array
    {
        if ($this->plugins === null) {
            $this->plugins = [];
            foreach (config('plugins.plugins', []) as $class) {
                $plugin = app($class);
                $this->plugins[$plugin->key()] = $plugin;
            }
        }

        return $this->plugins;
    }

    public function get(string $key): ?Plugin
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * @return array<string, Plugin> plugins this site may activate
     */
    public function availableFor(?Tenant $tenant): array
    {
        if (! $tenant) {
            return [];
        }

        return array_filter($this->all(), fn (Plugin $plugin) => $plugin->isAvailableFor($tenant));
    }

    public function isAvailable(string $key, ?Tenant $tenant = null): bool
    {
        return array_key_exists($key, $this->availableFor($tenant ?? tenant()));
    }

    /**
     * Available to the site and switched on by its admin. Always false on the central domain.
     */
    public function isActive(string $key): bool
    {
        $tenant = tenant();
        if (! $tenant || ! $this->isAvailable($key, $tenant)) {
            return false;
        }

        $cacheKey = $tenant->getTenantKey() . ':' . $key;
        if (! array_key_exists($cacheKey, $this->activeCache)) {
            $this->activeCache[$cacheKey] = Schema::hasTable('tenant_plugins')
                && TenantPlugin::where('key', $key)->where('is_active', true)->exists();
        }

        return $this->activeCache[$cacheKey];
    }

    /**
     * @return string[] keys of the plugins running for the current tenant
     */
    public function activeKeys(): array
    {
        return array_values(array_filter(array_keys($this->all()), fn ($key) => $this->isActive($key)));
    }

    public function activate(string $key): void
    {
        $plugin = $this->get($key);
        if (! $plugin || ! $this->isAvailable($key)) {
            throw new \RuntimeException("Plugin \"{$key}\" is not available for this site.");
        }

        TenantPlugin::updateOrCreate(['key' => $key], ['is_active' => true, 'activated_at' => now()]);
        $this->forget();
        $plugin->activated();
    }

    public function deactivate(string $key): void
    {
        TenantPlugin::where('key', $key)->update(['is_active' => false]);
        $this->forget();
        $this->get($key)?->deactivated();
    }

    /** Plugin-specific settings stored with the tenant's activation row */
    public function settings(string $key): array
    {
        return TenantPlugin::where('key', $key)->value('settings') ?? [];
    }

    public function saveSettings(string $key, array $settings): void
    {
        TenantPlugin::updateOrCreate(['key' => $key], ['settings' => $settings]);
    }

    private function forget(): void
    {
        $this->activeCache = [];
    }
}
