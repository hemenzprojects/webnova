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

1. Log into the central admin → Platform → Tenants → New: `id`, `name`, `domain` (optionally "Also add www.") and the first administrator's email and password
2. The `TenantCreated` event pipeline auto-creates the database and runs tenant migrations
3. Each domain added requests its Let's Encrypt certificate from Fly automatically; the tenant's page → **Domains** shows the certificate status and the DNS records to give the client ("DNS records" → "Check again"). The client's DNS is the only manual step
4. Run `php artisan tenants:migrate` if the tenant DB already existed without migrations
5. Or use `make seed` to recreate the default `garnet` and `apba` tenants locally

### Deleting a Tenant

Central admin → Tenants → **Delete site** (row action or edit page; the admin types the site ID and their own password), or `php artisan tenants:delete {tenant}`. There is no backup or undo. The `TenantDeleted` pipeline drops the `customer_{id}` database, then `App\Jobs\DeleteTenantData` removes `{id}/` on the `public` disk, its theme backups, its Site Health rows and its cached usage. Its domains are deleted one by one first (`DeletingTenant`), which removes their certificates.

### Domains and Certificates

A site's domains are edited on its tenant page (Domains: add, remove, DNS records, request certificate). To change a domain, add the new one, then remove the old one. `DomainCreated`/`DomainDeleted` → `App\Support\SyncDomainCertificate` → `App\Services\FlyCertificates`, which calls Fly's Machines API (`/v1/apps/{app}/certificates`). It needs `FLY_API_TOKEN` (`fly tokens create deploy -a cms-platform`, set with `fly secrets set`); `FLY_APP_NAME` is set by Fly. Without the token (e.g. locally) nothing is called. A Fly error never blocks a domain change: it is logged and the domain shows "No certificate" with a "Request certificate" button. Typed domains are normalised (`TenantResource::normalizeDomain`) and looked up in public DNS as the admin leaves the field (`App\Services\DomainCheck`, Google DNS-over-HTTPS, cached 2 min): the field shows "Domain not found" / "Domain exists" (pointing elsewhere or nowhere yet) / "Points to the platform". A domain that does not exist can only be saved by ticking "I have checked the spelling".

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
| `http://localhost:8026` | Mailpit: every email the local site sends (nothing is really delivered) |

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

Do not hardcode a plain string like `->directory('pages/my-section')` — files would be written to a shared path across all tenants, and would not count towards the site's storage quota. Other resources call it as `PageResource::tenantDir(...)`.

## Storage Quota

Each site has a storage limit; uploads that would take it past the limit are refused with an error in the CMS.

- **Limit:** `tenants.storage_limit_mb`, set per site in the central admin (Platform → Tenants). Empty = `TENANT_STORAGE_LIMIT_MB` (default 1024). The Tenants list shows each site's usage and how much of the server's storage the limits add up to (`STORAGE_CAPACITY_MB`, or the size of the storage volume if unset). Settings: `backend/config/quota.php`.
- **Usage** is the site's files plus its database (`App\Services\StorageQuota`). Files are measured from `{tenant_id}/` on the `public` disk, not from the `media` table, so only tenant-scoped paths count. The database is `pg_database_size('customer_{id}')` (about 10 MB for an empty site); turn it off with `TENANT_STORAGE_INCLUDE_DATABASE=false`. Only uploads are blocked at the limit: saving text content is never refused.
- **Enforcement:** every Filament `FileUpload` gets the check automatically (`FileUpload::configureUsing` in `AppServiceProvider`); `MediaController::upload` checks it for the page editor. Any new upload path that writes files itself must call `StorageQuota::errorForUpload($bytes)` first.
- Storage figures are for the platform only: site admins never see them (they only get the error when an upload is refused). The central admin sees the total across all sites on its dashboard and above the Tenants list (`App\Filament\Widgets\PlatformStorage`); those figures are cached for 5 minutes.

## Site Health

Central admin → Platform → Site Health: per-site traffic, response times, server errors, slow queries and storage, with a Healthy / Needs attention / Critical status (thresholds in `backend/config/health.php`). All figures live in the **central** database (`site_stats`, `site_page_views`, `site_slow_queries`) and are written by `App\Services\SiteHealth`, which swallows its own errors so statistics can never break a site.

- **Requests and response time:** `RecordSiteHealth` middleware (global, in `terminate()`, after the response is sent) adds each tenant request to that day's row.
- **Page views:** `frontend/plugins/track.client.ts` posts the path to `POST /api/v1/track` on each navigation of the public site. Path only: no cookies, IPs or visitor details.
- **Slow queries:** a `DB::listen` in `AppServiceProvider` logs queries on the `tenant` connection over `SITE_HEALTH_SLOW_QUERY_MS` (default 200), without bindings.
- Daily figures are kept 90 days and slow queries 30; old rows are pruned from the middleware now and then, so no scheduler is needed. `SITE_HEALTH_ENABLED=false` turns collection off.

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
- **Screenshot:** `backend/resources/themes/{slug}/screenshot.{webp,png,jpg}` (top of the home page, 16:10, ~960px wide) is shown on the theme's card, served by `GET /api/v1/themes/{slug}/screenshot`; without one the card draws a miniature from the colour tokens. To make one: create a throwaway tenant, `tenants:install-theme {it} {slug} --force`, screenshot its home page at 1440×900, then `tenants:delete {it} --force`.
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
- API routes use the `plugin:{key}` middleware (404 while inactive). `GET /api/v1/plugins` lists active keys (and `features`); the page editor hides blocks whose `plugin` is inactive.
- **Feature switches:** a plugin can split into parts its site switches on separately (`Plugin::features()`: key => label, description, `requires`). They are stored in `tenant_plugins.features`, switched under the plugin's Settings, and checked with `PluginManager::featureIsActive($plugin, $feature)`; `setFeatures()` also switches on what a feature requires. Screens add `protected static ?string $feature = '{key}'` (hidden while off), routes use `plugin:{key},{feature}`. `Plugin::isFeatureAvailableFor($tenant, $feature)` is where a paid-feature check will go. A feature that is off keeps its data.
- Plugin tables are normal tenant migrations and are **not** cleared by theme installs; after a theme install, active plugins' `activated()` runs again to restore anything they add (e.g. the Membership page).
- **Coming Soon** (`app/Plugins/ComingSoon`): while active, visitors see a "coming soon" or "under maintenance" screen (`frontend/components/ComingSoonScreen.vue`, swapped in by `app.vue`) and signed-in admins see the real site with a notice bar. `GET /api/v1/coming-soon` runs on the admin session, so the SSR fetch forwards the visitor's cookies; `?coming-soon-preview=1` shows admins the screen. Maintenance mode answers 503 with `Retry-After`; both modes add `noindex`. Its screen (own Coming Soon tab; also a Settings button on the Plugins card via `Plugin::settingsUrl()`) edits the text, look, contact details and social icons; contact details and social links start as a copy of Branding's until first saved (`ComingSoonSettings::forForm`).
- **Donations** (`app/Plugins/Donations`): the `donation_form` page block (currency, amount, title, name, email, phone, comments), Paystack payments (`Support/Donor`), a dashboard with per-currency totals, the list of all donations with CSV export, and manual recording of cash or bank gifts. Paystack returns donors to `/donation/complete`; the webhook is `POST /api/v1/donations/paystack/webhook`. It reuses Membership's `Support/Paystack` client, and falls back to Membership's Paystack keys when it has none of its own. A Paystack account has one webhook URL, so each plugin's webhook passes payments it does not recognise to the other.
- **Membership** (`app/Plugins/Membership`): dashboard with filters, registrations, membership types (fee + period), a form builder (`Support/FormSchema` defines the JSON shape and builds validation rules), Paystack payments (`Support/Paystack`, `Support/Registrar`), and the `membership_form` page block. Each registration stores a snapshot of the form it was submitted with. Paystack returns payers to `/membership/complete`; the webhook is `POST /api/v1/membership/paystack/webhook`.
  - **Member portal** (feature `accounts`): members' logins, separate from admin `users`: `member_accounts` table, `MemberAccount` model, `member` session guard, password brokers `member_accounts` (reset, 60 min) and `member_invitations` (welcome, 14 days). An account is created when a registration becomes approved with nothing owed (`MembershipRegistration::saved` → `Support/MemberAccounts::syncFromRegistration`), gets a Member ID (Settings: prefix, default the tenant id, and format `running` = `APBA0042` or `yearly` = `APBA-2026-0042`; numbering continues after the highest existing ID in that format, imported ones included). Members sign in with their email or Member ID and a welcome email to set a password (`Mail/MemberPortalMail`, from Settings' sender address; `resources/views/vendor/mail/*/message.blade.php` take `site-name`/`site-url` so emails show the site's name, not the platform's). Admins manage accounts under Membership → Members. API: `/api/v1/membership/account…` on the web session (`Http/MemberAccountController`); the Nuxt portal is `pages/account/*` (`definePageMeta({ portal: true, middleware: 'member' })` → rendered without the site's header and footer by `app.vue`, inside `components/Account/Shell.vue`), state in `composables/useMember.ts`. The header shows "Member login" / "My account" while the feature is on.
  - **Emails** (Membership → Emails): `Support/MembershipEmails` defines each email (application received / accepted / not accepted, portal welcome / password reset) with default subject and Markdown body; sites edit them (stored in the plugin settings, `null` = default) and set the sender there. `{member_details}` and `{button}` insert blocks; an email with a link always gets its button. Sent by `Support/MembershipMailer` through `Mail/MembershipMail` (`resources/views/mail/membership/message.blade.php`). `Support/ApplicationFlow` runs on every application create/update (model `created`/`updated`, not `saved`: `wasRecentlyCreated` stays true all request): approved → "accepted" email (with Member ID and password link once nothing is owed), paid after approval → account + "welcome". Rejections are emailed by the Reject action (reason kept in `decision_reason`). "Received" is sent by `Registrar::submit`.
  - **Application documents:** form field type `file` (PDF, Word, JPG, PNG, 5 MB). Sent as multipart (`answers` JSON + `files[{key}]`), stored by `Support/ApplicationFiles` on the private `local` disk under `{tenant_id}/membership/applications/`, the answer keeps `{file, name, size, mime}`. Only admins who may view Applications download them (`GET /api/v1/membership/registrations/{reference}/files/{key}`); deleted with the application. The storage quota counts `{tenant_id}/` on the private disk too (`quota.private_disk`), and `DeleteTenantData` removes it.
  - **Online payment providers** (Membership → Settings → Online payments): Paystack or **Stanbic Bank card payments** (N-Genius by Network International, `Support/Gateways/NGenius`), both behind `Support/Gateways/PaymentGateway` (`MembershipSettings::gateway()`). N-Genius: Basic API key → access token (`/identity/auth/access-token` with `realmName`, cached 4 min) → `POST /transactions/outlets/{outlet}/orders` (action PURCHASE, amount in minor units, `merchantOrderReference` = our attempt reference) → hosted payment page → back to `/membership/complete`; checked with `GET …/orders/{id}` (paid = PURCHASED/CAPTURED). Each application stores `payment_provider`, `gateway_order_id` (latest attempt) and `gateway_orders` (every attempt's order id) and is always checked with the provider it was sent to. Tokens are refreshed once on a 401. The API address must be https with a public host name (`NGenius::validApiUrl`): the API key is sent there. No webhook: `membership:check-payments` (every 5 min) asks the bank about unpaid Stanbic applications of the last 3 days. Empty API address = Stanbic's sandbox; the outlet has one currency (GHS). Donations still uses Paystack only.
  - **Payments and receipts:** `membership_payments` (`MembershipPayment`, `Support/Payments`): one row per payment with a receipt number (`RCT-{year}-{0001}`, restarting each year), the membership **year** it pays for (membership runs by calendar year: a payment any day in a year covers that year), method and reference. `ApplicationFlow` records it when an application becomes paid and emails the "Payment receipt" (before the acceptance); the row is linked to the member account once it exists. With "Review each registration" off, `MembershipRegistration::saving` approves a pending application in the same save that makes it paid (Paystack, "Mark as paid", free), so the receipt already carries the Member ID; the "received" email is then not sent. Receipts: `/membership/complete` + `/membership/receipt?reference=` (printable, `components/Membership/Receipt*.vue`), portal `/account/payments` (payment history) and `/account/receipts/{number}`; admins see payments on the member's page. **PDF receipts** (`Support/ReceiptPdf`, barryvdh/laravel-dompdf, view `resources/views/membership/receipt-pdf.blade.php`; the logo is embedded as a data URI, PNG/JPG only) are attached to the receipt email and downloaded from `/api/v1/membership/registrations/{reference}/receipt`, `/api/v1/membership/account/payments/{number}/pdf` (own receipts) and `/api/v1/membership/admin/payments/{id}/receipt` (admin session).
  - **One Member ID per person:** `Registrar::refuseExistingMember` refuses an application whose email already has a member account (or an approved application) and points them to the portal.
  - **Profiles and documents** (portal: My profile, My documents): My documents is its own feature (`documents`, requires `accounts`; routes `plugin:membership,documents`, `Member.features.documents` in the portal), off unless the site checks qualifications. `member_profiles` (contact, institution, crops and expertise chosen from the lists in Settings, `MembershipSettings::cropOptions()/expertiseOptions()`), `member_documents` (CV, certificates…; `source` member / application / admin; members cannot delete application ones). When an application becomes a member, `MemberAccounts::importApplication` copies its documents to My documents and its phone, country, institution and position to the profile. Admins see both on the member's page in Members.
  - **Files for members** (`MemberFileResource`, `member_resources`): minutes, reports… shared with all members, downloadable by active members (portal: Members' area) and admins only. Files for members must go here, never in the Media Library (public).
  - **Private files** (`Support/PrivateFiles`): application documents, member documents and files for members live on the private `local` disk under `{tenant_id}/membership/{applications|members/{id}|resources}/` and are only sent through routes that check who asks (`PrivateFiles::download`). Admin downloads: `/api/v1/membership/admin/member-documents|member-resources/{id}` (admin session + role).
  - **Consent and retention:** a privacy notice in Membership → Settings (Markdown, shown at `/membership/privacy`). While there is one, applicants tick "I agree" on the form and members when they set their password; the agreed version is stored (`consent_version` on registrations and accounts). Saving with "Ask everyone to agree again" bumps `privacy_version` and the portal shows a banner until members agree. `membership:prune-applications` (scheduled daily, 03:00) deletes rejected and never-paid applications older than the site's retention period (default 24 months), with their documents; applications that became members are kept.
  - Membership types with "Offered on the application form" off (e.g. Honorary) are granted by the committee: admins give them under Members.
  - **Members-only pages:** `pages.visibility` (`public` / `members`, "Who can see this page" in the page form). `GET /api/v1/pages/{slug}` runs on the web session and answers 403 `{restricted, reason: login|inactive|unavailable}` unless an active member or an admin is signed in; the site shows `MembersOnlyNotice.vue`. While the feature is off these pages stay hidden (admins only), never public. Files linked from them are still public URLs.

## Scheduler

`deploy/fly/docker/supervisord.conf` runs `php artisan schedule:work` and `php artisan queue:work` (both as `www-data`, like php-fpm, so files they write in `storage/` stay writable by the site); scheduled jobs are in `routes/console.php` (membership data retention, Stanbic payment checks). `fly.toml` keeps one machine always running (`auto_stop_machines = 'off'`, `min_machines_running = 1`): a stopped machine runs no scheduler. Site Health does not depend on it.

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