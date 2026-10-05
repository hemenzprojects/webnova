# WebNova — Agent Guide

Multi-tenant CMS platform. Each client gets their own subdomain, database, and storage. The central admin manages tenants; each tenant has their own Filament admin panel.

## Stack

| Layer | Tech |
|---|---|
| Backend | Laravel 12 + PHP 8.4 |
| Admin panel | Filament v3 |
| Multi-tenancy | stancl/tenancy v3 (domain-based) |
| Frontend | Nuxt 3 (SSR) + Tailwind CSS |
| Database | PostgreSQL (central DB + one DB per tenant) |
| Cache / Queue / Session | Redis |
| File storage | Local disk (dev) → Tigris/S3 (production) |
| Hosting | Fly.io |

## Project Layout

```
webnova/
├── backend/                    # Laravel app
│   ├── app/
│   │   ├── Filament/Resources/ # Admin panel resources (one per model)
│   │   ├── Http/Controllers/Api/
│   │   ├── Models/             # Tenant models extend plain Eloquent Model
│   │   └── Providers/TenancyServiceProvider.php
│   ├── config/tenancy.php
│   ├── database/
│   │   ├── migrations/         # Central DB migrations
│   │   └── migrations/tenant/  # Tenant DB migrations (run per tenant)
│   └── routes/api.php
├── frontend/
│   ├── components/
│   │   ├── Elementor/          # Page builder editor components
│   │   ├── PageBuilder/        # Public-facing page renderer components
│   │   └── MediaLibrary.vue    # Tenant media picker modal
│   ├── composables/
│   │   ├── useApi.ts           # All backend API calls
│   │   ├── useImageUrl.ts      # Image URL construction (dynamic host)
│   │   └── usePageBuilder.ts   # Block registry + transformImageUrl
│   └── pages/
├── deploy/
│   ├── fly/
│   │   ├── fly.toml
│   │   ├── Dockerfile          # Multi-stage: Nuxt build → PHP/nginx/Node runtime
│   │   └── docker/
│   │       ├── nginx.conf
│   │       └── supervisord.conf
│   └── local/
│       └── php-local.ini       # Disables OPcache for live PHP reloads
└── docker-compose.local.yml    # Local dev stack
```

## Multi-Tenancy Architecture

- **Central domain** (`platform.local` / `cms-platform.fly.dev`) — Filament admin for managing tenants, no tenant context
- **Tenant domains** (`garnet.edu.gh`, etc.) — each has its own PostgreSQL database (`customer_garnet`) and Filament admin panel
- Tenancy is initialized by domain via `InitializeTenancyByDomain` middleware (global, not just web group)
- Central domains bypass tenant middleware via the `$onFail` handler in `TenancyServiceProvider`
- Tenant database name pattern: `customer_{tenant_id}`

### Adding a New Tenant

1. Log into the central admin → Platform → Tenants → New: `id`, `name`, `domain` and the first administrator's email and password
2. The `TenantCreated` event pipeline auto-creates the database and runs tenant migrations
3. Run `php artisan tenants:migrate` if the tenant DB already existed without migrations
4. Or use `make seed` to recreate the default `garnet` and `apba` tenants locally

## Local Development

### Start

```bash
cp backend/.env.docker.example backend/.env.docker  # first time only
docker compose -f docker-compose.local.yml up --build -d
make migrate    # central DB
make seed       # creates garnet + apba tenants
```

### /etc/hosts (required)

```
127.0.0.1  platform.local
127.0.0.1  garnet.edu.gh
127.0.0.1  apba.edu.gh
```

### Access

| URL | What |
|---|---|
| `http://platform.local:8080/admin` | Central Filament admin |
| `http://garnet.edu.gh:8080/admin` | Garnet tenant admin |
| `http://garnet.edu.gh:8080` | Garnet tenant frontend |

Default admin credentials: `admin@webnova.edu.gh` / `password`

### Common Commands

```bash
make migrate              # php artisan migrate (central)
make seed                 # seed central admin + tenants (idempotent)
make shell                # bash into app container
make logs                 # tail app logs
make db-shell             # psql into postgres
docker compose -f docker-compose.local.yml exec app php artisan tenants:migrate
docker compose -f docker-compose.local.yml exec app php artisan config:clear
docker compose -f docker-compose.local.yml build app && docker compose -f docker-compose.local.yml up -d  # rebuild after frontend changes
```

### When to Rebuild vs Not

| Change | Rebuild needed? |
|---|---|
| PHP files (backend/) | No — volume mounted |
| Vue/Nuxt files (frontend/) | **Yes** — baked into image |
| nginx.conf / Dockerfile | **Yes** |
| .env.docker | No — restart only (`up -d`) |

## Image URLs — Critical Rules

### Never hardcode a domain in image src

All image rendering must go through `useImageUrl().getImageUrl(path)` or `usePageBuilder().transformImageUrl(path)`. This composable:
- On SSR: reads the `Host` header from the incoming request
- On client: uses `window.location.origin`
- Automatically rewrites old `http://localhost:8080/...` paths

### Storage paths are relative and tenant-scoped

Files are stored as **relative paths** in the database (e.g. `garnet/pages/general/01ABC.jpg`), never as full URLs. The frontend constructs the full URL at render time.

Storage layout on disk:
```
storage/app/public/
└── {tenant_id}/
    ├── pages/
    │   ├── general/
    │   ├── featured/
    │   ├── hero/
    │   └── ...
    └── media/       ← media library uploads
```

Served via nginx alias: `/storage/` → `public/storage/` → symlink → `storage/app/public/`

The storage symlink (`public/storage`) must exist. It's created by `php artisan storage:link` (runs in Dockerfile and must be run manually in local containers after fresh start).

### MediaController always saves a Media record

Every upload through `POST /api/v1/media/upload` creates a row in the tenant's `media` table. This powers the media library in the page builder.

## Filament FileUpload — Always Use tenantDir()

When adding a new `FileUpload` component in any Filament resource, use the `tenantDir()` helper defined in `PageResource` to scope the directory to the active tenant:

```php
Forms\Components\FileUpload::make('image')
    ->disk('public')
    ->directory(self::tenantDir('pages/my-section'))
```

Do not hardcode a plain string like `->directory('pages/my-section')` — files would be written to a shared path across all tenants.

## API — SSR Host Forwarding

All `$fetch` calls in `useApi.ts` forward the original `Host` header on the server side so Laravel's tenant middleware can identify the tenant during SSR. If you add a new API composable or fetch outside `useApi`, include:

```typescript
const ssrHeaders = (): Record<string, string> => {
  if (!process.server) return {}
  const event = useRequestEvent()
  const host = event?.node?.req?.headers?.host
  return host ? { Host: host, 'X-Forwarded-Host': host } : {}
}
```

`X-Forwarded-Host` is the header that actually works: Node's built-in `fetch` silently drops a custom `Host` header, so without it SSR requests reach Laravel as `127.0.0.1` and every tenant API call 404s. Laravel honours it because `trustProxies(at: '*')` is set in `bootstrap/app.php`.

## Media Library

- `GET  /api/v1/media` — paginated list, supports `?search=` and `?page=`
- `POST /api/v1/media/upload` — upload file, saves Media record, returns `{ path, id }`
- `DELETE /api/v1/media/{id}` — deletes file from storage + DB record

The `MediaLibrary.vue` modal is used inside `Elementor/ImageUpload.vue`. It lets tenants pick from previously uploaded files or upload new ones.

## Themes

A tenant picks a theme under **Customize → Themes** in their admin. Installing one works like a WordPress demo import: the current content is backed up, cleared, and replaced with the theme's starter pages, menus, settings and sample content. Users are never touched.

- **Registry and design tokens:** `backend/config/themes.php` (colours, fonts, radius). `frontend/plugins/theme.ts` turns the active theme's tokens into CSS variables (`--color-primary`, `--color-surface-muted`, `--font-heading`, …). Tailwind's `primary`/`accent` read those variables, so `bg-primary` follows the theme.
- **Starter package:** `backend/resources/themes/{slug}/starter.json` plus `images/`. Strings `"theme:{file}"` become stored paths; dates like `"-3 days"` are relative; footer menu widgets reference menus by `"menu": "<location>"`. Blocks are listed as `{type, data}` and expanded into the editor's section/column format. Image credits: `backend/resources/themes/CREDITS.md`.
- **Installer:** `App\Services\ThemeInstaller` (clears the tables in `CONTENT_TABLES`). CLI: `php artisan tenants:install-theme {tenant} {theme}` and `tenants:restore-theme {tenant} [file]`. Backups go to `storage/app/private/theme-backups/{tenant}/`.
- **Theme widgets:** `frontend/themes/{slug}/manifest.json` maps a block type to an override component in `frontend/themes/{slug}/widgets/`. Blocks without a hand-written form in `SettingsPanel.vue` are described in `frontend/utils/blockSchemas.ts`.
- **Widget styles:** every widget and section has shared Style and Advanced settings in the editor (colours, fonts, alignment, corner radius, spacing, background, border, shadow, max width, hide per device, anchor ID, CSS class), stored as `data._style` (sections: `settings._style`). `PageBuilder/BlockWrapper.vue` applies them by overriding the theme's CSS variables for that widget (`utils/blockStyle.ts`), so new blocks should style themselves with those variables (`var(--color-primary)`, `var(--font-heading)`, `var(--radius-card)`, …) rather than fixed colours.
- Block components and theme widgets are registered **globally** in `nuxt.config.ts`, because they are rendered by name (`<component :is>`). Keep folder prefixes on for `components/` — templates use `PageBuilderHero`, `ElementorWidget`, etc.

## Admin Areas, Users and Roles

The admin is organised into **functional areas**: tabs in the top bar (Content, Appearance, plugin areas such as Membership, System Administration; Platform on the central domain). The sidebar lists only the current area's items (`App\Admin\Areas`; views in `resources/views/filament/admin/`).

- Every Filament resource uses `App\Admin\Concerns\InFunctionalArea` and every page `InFunctionalAreaPage`, declaring `protected static string $area = '...'`. **New resources and pages must do the same**, or they will not appear and will not be permission-checked.
- **Roles are per site** (tenant `roles` table; `users.role_id`), managed under System Administration → Roles. A role maps each menu item's key (the Filament slug, e.g. `news`, `header-settings`) to `view` (read-only) or `manage` (create, edit, delete, save). The built-in Administrator role (`is_admin`) can do everything and cannot be deleted; the last Administrator cannot be removed. `App\Admin\Access` answers "can this user view/manage X".
- Pages with forms disable the form and hide Save for view-only roles (`->disabled(! static::canManage())`, empty `getFormActions()`, `$this->authorizeManage()` in `save()`). Custom actions that change data use `->authorize(fn () => static::canManage())`.
- A site's users need a role to sign in (`User::canAccessPanel`). Central users (platform admins) have no roles and only see the Platform area.
- API endpoints used by admin tools run on the admin session: `->middleware(['web', 'admin.can:{key},{view|manage}'])`. The page editor and media library (`/api/v1/pages/{id}/edit|blocks|publish`, `/api/v1/media…`) work this way, so the Nuxt editor sends `xsrfHeaders()` (`frontend/utils/xsrf.ts`) on those calls.

## Plugins

Optional feature modules that each site's admin activates under Customize → Plugins (`tenant_plugins` table, which also holds each plugin's encrypted settings). Every plugin is available to every site for now; `Plugin::isAvailableFor($tenant)` is where a subscription check for paid plugins will go.

- A plugin is a class extending `App\Plugins\Plugin`, registered in `config/plugins.php`, with its code in `app/Plugins/{Name}/`. Filament classes go in `app/Plugins/{Name}/Filament/{Resources,Pages,Widgets}` (auto-discovered), use the area traits (see Admin Areas) with `protected static ?string $plugin = '{key}'` so they hide while the plugin is off, and the plugin declares its top-bar tab in `Plugin::area()`. Plugin widgets set `$isDiscovered = false` to stay off the main dashboard.
- API routes use the `plugin:{key}` middleware (404 while inactive). `GET /api/v1/plugins` lists active keys; the page editor hides blocks whose `plugin` is inactive.
- Plugin tables are normal tenant migrations and are **not** cleared by theme installs; after a theme install, active plugins' `activated()` runs again to restore anything they add (e.g. the Membership page).
- **Membership** (`app/Plugins/Membership`): dashboard with filters, registrations, membership types (fee + period), a form builder (`Support/FormSchema` defines the JSON shape and builds validation rules), Paystack payments (`Support/Paystack`, `Support/Registrar`), and the `membership_form` page block. Each registration stores a snapshot of the form it was submitted with. Paystack returns payers to `/membership/complete`; the webhook is `POST /api/v1/membership/paystack/webhook`.

## Tenancy Bootstrapper Notes

`FilesystemTenancyBootstrapper` is enabled but **only for the `s3` disk**. The `local` and `public` disks are excluded (`config/tenancy.php → filesystem.disks`). This is intentional — the bootstrapper changes the storage root path but not the URL, which breaks nginx's symlink-based static file serving. Tenant isolation for local storage is handled by manually prefixing paths with `{tenant_id}/`.

## Production (Fly.io) — Pending

**Not yet deployed.** Before deploying:

1. All `->disk('public')` calls in `MediaController` and Filament `FileUpload` components need to switch to the configured default disk (`config('filesystems.default')`) so uploads go to Tigris/S3 in production
2. Run: `fly postgres create`, `fly redis create`, `fly storage create`
3. Set secrets: `APP_KEY`, `APP_URL`, `CENTRAL_DOMAIN`, `REDIS_URL`
4. `fly deploy --config deploy/fly/fly.toml`
5. `fly ssh console -C "php artisan db:seed"` for initial admin + tenants
6. `fly certs add {domain}` for each tenant domain

The `release_command` in fly.toml runs `php artisan migrate --force` automatically on each deploy.

## Known Gotchas

- **Bootstrap cache** (`bootstrap/cache/packages.php`, `bootstrap/cache/services.php`) must not be committed — they include dev-only providers (e.g. `PailServiceProvider`) that crash the production container. Both are in `.gitignore`.
- **`confirm()` naming** — don't name a Vue component method `confirm` — it shadows `window.confirm` in script setup scope.
- **Filament assets** — run `php artisan filament:assets` in the Dockerfile, not at runtime. Tenant middleware must not be active when serving `/css/` and `/js/` paths (they're handled by direct nginx aliases, not Laravel).
- **OPcache** — disabled in local Docker via `deploy/local/php-local.ini`. PHP changes take effect immediately without rebuilding.
- **Redis port** — mapped to `6380:6379` in local Docker to avoid conflicts with any local Redis instance.