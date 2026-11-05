# Handover prompt: apply the "Customize" feature set to another copy of this project

You are working on a copy of the GARNET website project that runs on a different server. Another copy of the same codebase has received a set of features, described below. Your job is to bring this copy to the same state, then verify it works. The person who gives you this note may not be a developer, so tell them plainly what you did, what you checked, and anything you could not check.

## The project

- **Backend:** Laravel 12 with Filament 3 admin, in `backend/`. Runs locally through Laravel Sail (`make up`, which calls `backend/vendor/bin/sail`).
- **Frontend:** Nuxt 3 (Vue, Tailwind), in `frontend/`. Server-side rendered. It talks to the backend through `/api/v1/...`.
- **Production:** Docker Compose (`docker-compose.prod.yml`), deployed with `./deploy-production.sh` from the project root on the server. Nginx serves the frontend's built JS/CSS from `frontend-public/` on disk.
- **Existing pieces the features build on:** a key/value `settings` table with `key`, `value`, `type`, `group` (model `App\Models\Setting`, helpers `Setting::get()` / `Setting::set()`); a single-row `brandings` table (`App\Models\Branding::settings()`); `menus` and `menu_items` tables with `Menu::getNestedItems()`; a page builder whose blocks are rendered by `frontend/components/PageBuilder/*`.

## Fastest route: take the commits

The work exists as eight commits in the source repository (`hemenzprojects/garnet.edu.gh`, merged to `master` through pull requests #22 and #23). If this copy shares history with that repository, merge or cherry-pick them in this order rather than rewriting anything:

| Commit | Subject |
|---|---|
| `ff0e9bf` | chore: point local frontend at the local backend |
| `ce876b3` | feat: show services as image cards with a read more link |
| `db7d42b` | feat: service and event detail pages with configurable sidebars |
| `4503a6b` | feat: footer built from columns and widgets in the admin |
| `89ffc28` | fix: carousel colour settings and missing-menu console errors |
| `6594066` | fix: stop stale Filament component cache hiding admin pages in production |
| `6ce71d5` | feat: manage social media links as an icon list under Branding |
| `e7667ec` | feat: selectable header designs with a Header Settings admin page |

Check first whether `ff0e9bf` suits this copy: it sets `frontend/.env` to `http://localhost` URLs for local development. That is right only if production gets its URLs from `docker-compose.prod.yml` build args and environment, as the source project does. If this server relies on `frontend/.env` for its real URLs, skip that commit.

If the commits are not available, rebuild the features from the specification below. Read the existing code first and match its style.

## What was built

### 1. Services shown as image cards, with a detail page

- New `frontend/components/ServiceCard.vue`: a card that links to `/services/{slug}`. Featured image on top (3:2), then the name, the description clamped to three lines, and a "Read more →" text link in the accent colour. This link style is the same one the news cards use; there is no button. A service with no featured image shows a primary-to-accent gradient placeholder with an icon. Props: `service`, `showImage`, `showDescription`, `showReadMore` (all default true).
- The card is used in three places: the page builder's services widget (`PageBuilder/DynamicServices.vue`, grid layout), the fallback home page (`pages/index.vue`), and the services slide of `PageBuilder/DynamicCarousel.vue`.
- `DynamicServices.vue` now honours its `columns` setting (2, 3 or 4) and gained a `showReadMore` option (default true). Its list layout also shows the image and a read-more link. The matching "Show Read More" checkbox was added to the services widget in `Elementor/SettingsPanel.vue`, and the default added in `composables/useElementorEditor.ts`.
- New page `frontend/pages/services/[slug].vue`: header band, service name, featured image, the `content` field (falling back to `description` when content is empty), a not-found state, and the sidebar described next.

### 2. Sidebars on service, news and event detail pages

- New page `frontend/pages/events/[slug].vue`. Event cards already linked to `/events/{slug}` but no page existed. It shows title, dates, venue and location, featured image, description, registration button and attachments.
- New `frontend/components/DetailSidebar.vue`: a card titled e.g. "Other Services" listing related items. Each row has a square thumbnail (gradient placeholder when there is no image), an optional date, and the title. Props: `title`, `items` (`id`, `label`, `to`, `image`, `date`), `showImage`, `showDate`. It is sticky on desktop and drops below the content on mobile.
- The service, news and event detail pages render it beside the article in a three-column grid (article spans two). The list excludes the item being viewed.
- **Per-item switch:** a boolean `show_sidebar` column, default true, on `services`, `news` and `events` (two migrations). It is added to each model's `$fillable` and `$casts`, and each Filament resource gets a "Show sidebar" toggle in the form and a "Sidebar" icon column in the table.
- **Global settings:** a Filament page `App\Filament\Pages\SidebarSettings` storing these keys in the `settings` table under group `sidebar`:
  - `sidebar_position`: `right` or `left`
  - for each of `services`, `news`, `events`: `sidebar_{section}_enabled`, `_title`, `_limit` (1–20), `_show_image`, `_order`, and `_show_date` for news and events
  - order values: services `order` (admin order) or `name`; news `latest` or `featured`; events `upcoming` (upcoming only, soonest first) or `latest` (all, newest first)
  - defaults: enabled, right, 5 items, thumbnails and dates on, titles "Other Services" / "Other News" / "Other Events"
- `frontend/composables/useSidebarSettings.ts` fetches `/api/v1/settings?group=sidebar` and returns a typed config per section, with the same defaults as a fallback. A page shows its sidebar only when the section is enabled, the item's `show_sidebar` is true, and there is at least one other item.
- **API changes:** `SettingController@index` casts values using the `type` column (`boolean`, `number`, `json`). `ServiceController@index` accepts `limit` and `sort=name`. `NewsController@index` accepts `sort=featured`. `EventController@index` accepts `sort=latest`. `useApi().fetchSettings` accepts query params.

### 3. Footer built from columns and widgets

- Filament page `App\Filament\Pages\FooterSettings`. The footer is a single row. The editor adds columns (up to six) with a Repeater, and inside each column stacks widgets with a Filament Builder. Widgets: `heading`, `text` (rich text editor: bold, italic, underline, strike, link, lists), `menu` (pick any menu, optional heading), `social` (optional heading; icons come from Branding), `contact` (optional heading; email, phone and address come from Branding). There is also a copyright text field where `{year}` is replaced with the current year.
- Stored in `settings` group `footer`: `footer_columns` (type `json`, shape `[{ "widgets": [{ "type": "...", "data": {...} }] }]`) and `footer_copyright`.
- New endpoint `GET /api/v1/footer` (`Api\FooterController`). It returns `{ columns, copyright }` with menu items resolved, rich text limited to a safe set of tags, and social links resolved to label, icon and URL. `columns` is `null` when nothing has been saved. Unknown widgets and menus that are missing or inactive are dropped.
- New `frontend/components/TheFooter.vue`, used by `app.vue` in place of the old hardcoded footer. The grid adapts to the number of columns. When `columns` is `null` it renders the old default (site name and tagline, Quick Links, Resources), so nothing disappears before the footer is configured.

### 4. Social media links as a shared icon list

- New `App\Support\SocialPlatforms`: the list of platforms (Facebook, X, LinkedIn, Instagram, YouTube, WhatsApp, TikTok), each with a label and the path of a 24×24 SVG icon. It provides the admin select options (labels rendered with the icon, used with `allowHtml()`), `links()` to resolve saved pairs, and `fromBranding()`.
- New JSON column `social_links` on `brandings`, holding `[{ "platform": "...", "url": "..." }]`. The migration copies any existing `facebook_url`, `twitter_url`, `linkedin_url`, `instagram_url` and `youtube_url` values into it. The old columns are left in place.
- In `BrandingResource`, the five fixed URL fields are replaced by a reorderable Repeater of icon and link.
- The header and the footer's social widget both read this one list.

### 5. Three selectable header designs

- Filament page `App\Filament\Pages\HeaderSettings`, storing in `settings` group `header`:
  - `header_layout`: `info_bar`, `classic` or `top_bar` (default `classic`, which matches the old header)
  - `header_sticky` (default true)
  - `header_show_phone`, `header_show_email`, `header_show_social` (default true)
  - `header_cta_enabled`, `header_cta_text`, `header_cta_url`, `header_cta_new_tab` (button off by default; text and link are required when it is on)
- The design is chosen from three picture cards, each a miniature drawing of that header in the brand colours. This is a custom field view at `backend/resources/views/filament/forms/header-layout-picker.blade.php`, written with inline styles because Filament's CSS does not include arbitrary Tailwind classes.
- The designs:
  - **Contact bar with menu strip** (`info_bar`): logo, telephone block, email block and the button on the first line; the menu below in a primary-coloured strip with white links.
  - **Simple** (`classic`): logo on the left, menu and button on the right, on one line.
  - **Top bar** (`top_bar`): a primary-coloured strip on top with social icons on the left and "Call us" / "Email" on the right; below it the logo, menu and a pill-shaped button.
- New endpoint `GET /api/v1/header` (`Api\HeaderController`) returning `{ layout, sticky, cta, phone, email, social }`. Phone, email and social links come from Branding and are returned only when the matching checkbox is ticked.
- `frontend/components/TheNavigation.vue` renders the three designs. New `frontend/components/HeaderMenu.vue` wraps the loading state, the dynamic menu and the fallback links, with a `variant` prop (`light` or `dark`). `MenuItems.vue` gained the same `variant` prop so top-level links can sit on a coloured strip. The button uses the accent colour and chooses white or dark text from the accent colour's brightness. On mobile all three designs collapse to one hamburger menu with the contact details and button at the bottom.

### 6. Admin navigation

A "Customize" navigation group contains, in this order: Branding & Settings, Menus, Header Settings, Sidebar Settings, Footer Settings (navigation sort 1 to 5). The three settings pages share one Blade view, `backend/resources/views/filament/pages/settings-form.blade.php`, which wraps the form and a "Save changes" action.

### 7. Fixes made along the way

- **Carousel colours:** `DynamicCarousel.vue` bound its navigation and pagination colours in CSS as `v-bind('navigationColor ...')` without the `data.` prefix. Colours chosen in the page editor were never applied, and Vue warned on every render. They are now `data.navigationColor` and so on.
- **Missing menu:** `MenuController@show` returned 404 when no menu existed at a location, which logged a console error on every page. It now returns 200 with an empty body, and `useMenu().fetchMenuByLocation` returns `null` unless the response has `items`.
- **Stale Filament cache in production:** the production image copied the host's `backend/bootstrap/cache`, including an old Filament component cache, so resources and pages added later never appeared in the admin. Fixed by a new `backend/.dockerignore` (excluding `bootstrap/cache/*.php` and `bootstrap/cache/filament`) and an extra step in `deploy-production.sh` that runs `php artisan filament:cache-components` after the other cache commands.
- **Local frontend reading production:** `frontend/.env` pointed at the production API, so the local site showed and edited production data. It now holds local URLs, and `backend/compose.yaml` sets `NUXT_PUBLIC_API_BASE` and `NUXT_PUBLIC_BACKEND_URL` for the Sail frontend service.

## Applying and deploying

1. Get the code in place (commits or rebuild).
2. Run the three migrations: `show_sidebar` on services; `show_sidebar` on news and events; `social_links` on brandings. All are additive. Locally: `make migrate`. In production the deploy script runs `php artisan migrate --force`.
3. Deploy with `./deploy-production.sh` from the project root on the server. Do not use `deployment/4-deploy.sh` (first-time install: it does not pull code or copy frontend assets, and it prompts to create an admin user) or `deployment/update.sh` (it pulls a `main` branch that may not exist).
4. Take a database backup before deploying. The deploy stops the site while the images rebuild.

## Things that will trip you up

- **New Vue components and the dev server.** After adding a component file, restart the frontend dev container. Otherwise the server renders the component but the browser build does not know it, and it disappears after hydration with "Failed to resolve component" in the console. Checking only server-rendered HTML will not reveal this.
- **A stale Filament component cache hides new admin pages.** If new pages or resources are missing from the admin, look for `bootstrap/cache/filament/panels/*.php` and rebuild it with `php artisan filament:cache-components`. In production PHP does not re-read changed files, so restart the backend container afterwards.
- **`response()->json(null)` returns `{}`**, not `null`. Frontend code must check for a real field such as `items`, not just truthiness.
- **Uploaded images** live in `backend/storage/app/public`, which is not in git. A fresh clone needs `php artisan storage:link` and the files copied over, or images return 403.
- **Do not wipe saved settings while testing.** Back up the relevant `settings` rows and Branding fields before a test that writes to them, and restore them afterwards.

## How to verify

- **Admin:** each of Header, Sidebar and Footer Settings loads, saves, and reloads the saved values; invalid input (a menu widget with no menu, a button switched on with no text) is rejected.
- **API:** `/api/v1/header`, `/api/v1/footer` and `/api/v1/settings?group=sidebar` return the saved values with the right types.
- **Site, in a real browser:** the home page service cards; a service, a news and an event detail page with their sidebars; the footer; and each of the three header designs. Check the browser console for errors.
- **Production build:** build `frontend/Dockerfile.prod` with the production build args and confirm the rendered pages contain only the production URLs.

## Known and still open

- The page editor's save and publish endpoints (`PUT /api/v1/pages/{id}/blocks`, `POST /api/v1/pages/{id}/publish`) and the media upload and delete endpoints have no authentication, and `User::canAccessPanel()` returns true for every user. Both predate this work and should be fixed.
- The mobile view of the headers and the events sidebar ordering options were not visually checked in the source project.
