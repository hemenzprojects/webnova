<?php

namespace App\Services;

use App\Models\Branding;
use App\Models\Media;
use App\Models\Menu;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Installs a theme's starter package into the active tenant, WordPress
 * "import demo content" style: back up the current content, clear it, then
 * import the theme's pages, menus, settings, branding and sample content.
 *
 * A package lives in resources/themes/{slug}/:
 *   starter.json   content to import (see the default theme for the format)
 *   images/        files referenced from starter.json as "theme:{filename}"
 *
 * Users and API tokens are never touched, so nobody is locked out.
 */
class ThemeInstaller
{
    /**
     * Tenant tables cleared before an install and captured in the backup.
     * Order matters on restore: parents before children.
     */
    public const CONTENT_TABLES = [
        'brandings',
        'settings',
        'pages',
        'menus',
        'menu_items',
        'services',
        'news',
        'events',
        'members',
        'team_members',
        'media',
    ];

    private const DATE_FIELDS = ['published_at', 'start_date', 'end_date'];

    public function __construct(private ThemeService $themes) {}

    public function packagePath(string $slug): string
    {
        return resource_path("themes/{$slug}");
    }

    public function hasPackage(string $slug): bool
    {
        return File::exists($this->packagePath($slug) . '/starter.json');
    }

    /**
     * Back up, clear and import. Returns the backup file path.
     */
    public function install(Tenant $tenant, string $slug): string
    {
        $this->assertTenantContext($tenant);

        if (! array_key_exists($slug, $this->themes->all()) || ! $this->hasPackage($slug)) {
            throw new RuntimeException("Theme \"{$slug}\" has no starter package.");
        }

        $package = json_decode(File::get($this->packagePath($slug) . '/starter.json'), true, flags: JSON_THROW_ON_ERROR);

        $backup = $this->backup($tenant);

        // Copy images first: if this fails nothing has been cleared yet
        $images = $this->copyImages($tenant, $slug);

        DB::transaction(function () use ($package, $images) {
            $this->clear();
            $this->import($package, $images);
        });

        $this->themes->applyToTenant($tenant, $slug);

        // Active plugins put back what they add to a site (e.g. the Membership page).
        // Their own data lives in tables a theme install does not clear.
        $plugins = app(\App\Plugins\PluginManager::class);
        foreach ($plugins->activeKeys() as $key) {
            $plugins->get($key)->activated();
        }

        return $backup;
    }

    /**
     * Save every content table to storage/app/theme-backups/{tenant}/.
     */
    public function backup(Tenant $tenant): string
    {
        $this->assertTenantContext($tenant);

        $data = [
            'tenant' => $tenant->id,
            'theme' => $tenant->theme_slug ?? 'default',
            'created_at' => now()->toIso8601String(),
            'tables' => [],
        ];

        foreach (self::CONTENT_TABLES as $table) {
            $data['tables'][$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        }

        $path = "theme-backups/{$tenant->id}/" . now()->format('Ymd_His') . "-{$data['theme']}.json";
        Storage::disk('local')->put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return Storage::disk('local')->path($path);
    }

    /**
     * Put a backup made by backup() back in place, replacing current content.
     */
    public function restore(Tenant $tenant, string $file): void
    {
        $this->assertTenantContext($tenant);

        $data = json_decode(File::get($file), true, flags: JSON_THROW_ON_ERROR);

        if (($data['tenant'] ?? null) !== $tenant->id) {
            throw new RuntimeException("Backup belongs to tenant \"{$data['tenant']}\", not \"{$tenant->id}\".");
        }

        DB::transaction(function () use ($data) {
            $this->clear();

            foreach (self::CONTENT_TABLES as $table) {
                $rows = $data['tables'][$table] ?? [];
                // Self-referencing menu items: insert parents before children
                if ($table === 'menu_items') {
                    usort($rows, fn ($a, $b) => ($a['parent_id'] ? 1 : 0) <=> ($b['parent_id'] ? 1 : 0));
                }
                foreach (array_chunk($rows, 200) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
                $this->resetSequence($table);
            }
        });

        $this->themes->applyToTenant($tenant, $data['theme'] ?? 'default');
    }

    private function clear(): void
    {
        foreach (array_reverse(self::CONTENT_TABLES) as $table) {
            if ($table === 'menu_items') {
                DB::table($table)->update(['parent_id' => null]);
            }
            DB::table($table)->delete();
        }
    }

    /**
     * Copy package images to {tenant}/themes/{slug}/ on the public disk.
     *
     * @return array<string, string> filename => stored relative path
     */
    private function copyImages(Tenant $tenant, string $slug): array
    {
        $dir = $this->packagePath($slug) . '/images';
        if (! File::isDirectory($dir)) {
            return [];
        }

        $stored = [];
        foreach (File::files($dir) as $file) {
            $path = "{$tenant->id}/themes/{$slug}/" . $file->getFilename();
            Storage::disk('public')->put($path, File::get($file->getPathname()));
            $stored[$file->getFilename()] = $path;
        }

        return $stored;
    }

    private function import(array $package, array $images): void
    {
        $package = $this->resolveImages($package, $images);

        foreach ($images as $name => $path) {
            Media::create([
                'filename' => $name,
                'original_name' => $name,
                'path' => $path,
                'disk' => 'public',
                'mime_type' => Storage::disk('public')->mimeType($path),
                'size' => Storage::disk('public')->size($path),
                'alt_text' => Str::headline(pathinfo($name, PATHINFO_FILENAME)),
            ]);
        }

        // Branding::settings() reads the single row with id 1
        (new Branding)->forceFill(['id' => 1])->fill($package['branding'] ?? [])->save();
        $this->resetSequence('brandings');

        $pages = [];
        foreach ($package['pages'] ?? [] as $i => $page) {
            $model = \App\Models\Page::create([
                'title' => $page['title'],
                'slug' => $page['slug'],
                'meta_title' => $page['meta_title'] ?? null,
                'meta_description' => $page['meta_description'] ?? null,
                'is_published' => $page['is_published'] ?? true,
                'order' => $page['order'] ?? $i,
                'template_type' => 'builder',
                'blocks' => $this->toEditorBlocks($page['slug'], $page['blocks'] ?? []),
            ]);
            $pages[$page['slug']] = $model->id;
        }

        $menus = [];
        foreach ($package['menus'] ?? [] as $menu) {
            $model = Menu::create([
                'name' => $menu['name'],
                'location' => $menu['location'],
                'description' => $menu['description'] ?? null,
                'is_active' => $menu['is_active'] ?? true,
            ]);
            $this->createMenuItems($model, $menu['items'] ?? [], $pages);
            $menus[$menu['location']] = $model->id;
        }

        foreach (['services' => \App\Models\Service::class, 'news' => \App\Models\News::class, 'events' => \App\Models\Event::class,
            'members' => \App\Models\Member::class, 'team_members' => \App\Models\TeamMember::class] as $key => $model) {
            foreach ($package[$key] ?? [] as $i => $row) {
                foreach (self::DATE_FIELDS as $field) {
                    if (isset($row[$field])) {
                        // Relative dates ("-3 days", "+2 weeks 09:00") keep demo content fresh
                        $row[$field] = Carbon::parse($row[$field]);
                    }
                }
                if (Schema::hasColumn((new $model)->getTable(), 'order')) {
                    $row['order'] ??= $i + 1;
                }
                $model::create($row);
            }
        }

        foreach ($package['settings'] ?? [] as $setting) {
            $value = $setting['value'];
            if (($setting['type'] ?? 'text') === 'json') {
                $value = json_encode($this->resolveMenuRefs($value, $menus));
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            \App\Models\Setting::create([
                'key' => $setting['key'],
                'value' => (string) $value,
                'type' => $setting['type'] ?? 'text',
                'group' => $setting['group'] ?? 'general',
            ]);
        }
    }

    /**
     * Expand a theme's simple block list into the visual editor's format:
     * one full-width, single-column section per block.
     */
    private function toEditorBlocks(string $pageSlug, array $blocks): array
    {
        $out = [];
        foreach (array_values($blocks) as $i => $block) {
            $section = "section-{$pageSlug}-{$i}";
            $column = "column-{$pageSlug}-{$i}";
            $out[] = [
                'id' => $section,
                'type' => '_section_meta',
                'data' => [
                    'columns' => [['id' => $column, 'width' => 100]],
                    'settings' => array_merge(['layout' => 'full', 'gap' => 'default', 'height' => 'default'], $block['section'] ?? []),
                ],
                'order' => count($out),
            ];
            $out[] = [
                'id' => "widget-{$pageSlug}-{$i}",
                'type' => $block['type'],
                'data' => $block['data'] ?? [],
                '_columnId' => $column,
                '_sectionId' => $section,
                'order' => count($out),
            ];
        }

        return $out;
    }

    private function createMenuItems(Menu $menu, array $items, array $pages, ?int $parentId = null): void
    {
        foreach (array_values($items) as $i => $item) {
            $model = $menu->items()->create([
                'parent_id' => $parentId,
                'label' => $item['label'],
                'type' => isset($item['page']) ? 'page' : ($item['type'] ?? 'custom'),
                'url' => $item['url'] ?? null,
                'page_id' => isset($item['page']) ? ($pages[$item['page']] ?? null) : null,
                'open_in_new_tab' => $item['open_in_new_tab'] ?? false,
                'is_published' => true,
                'order' => $i + 1,
            ]);
            $this->createMenuItems($menu, $item['children'] ?? [], $pages, $model->id);
        }
    }

    /**
     * Replace "theme:{filename}" strings anywhere in the package with the
     * stored relative path.
     */
    private function resolveImages(mixed $value, array $images): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->resolveImages($v, $images), $value);
        }
        if (is_string($value) && str_starts_with($value, 'theme:')) {
            $name = substr($value, 6);
            if (! isset($images[$name])) {
                throw new RuntimeException("Theme image \"{$name}\" is referenced but not in the package.");
            }

            return $images[$name];
        }

        return $value;
    }

    /**
     * Footer menu widgets refer to menus by location ("menu": "footer-pages");
     * swap that for the new menu's id.
     */
    private function resolveMenuRefs(mixed $value, array $menus): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (isset($value['menu']) && is_string($value['menu']) && ! isset($value['menu_id'])) {
            $value['menu_id'] = $menus[$value['menu']] ?? null;
            unset($value['menu']);
        }

        return array_map(fn ($v) => $this->resolveMenuRefs($v, $menus), $value);
    }

    private function resetSequence(string $table): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 0) + 1, false)");
        }
    }

    private function assertTenantContext(Tenant $tenant): void
    {
        if (tenant('id') !== $tenant->id) {
            throw new RuntimeException('Tenancy must be initialized for the tenant before installing a theme.');
        }
    }
}
