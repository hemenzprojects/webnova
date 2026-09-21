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

1. Log into the central admin → create Tenant record with `id`, `name`, `domain`
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
  return host ? { Host: host } : {}
}
```

## Media Library

- `GET  /api/v1/media` — paginated list, supports `?search=` and `?page=`
- `POST /api/v1/media/upload` — upload file, saves Media record, returns `{ path, id }`
- `DELETE /api/v1/media/{id}` — deletes file from storage + DB record

The `MediaLibrary.vue` modal is used inside `Elementor/ImageUpload.vue`. It lets tenants pick from previously uploaded files or upload new ones.

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