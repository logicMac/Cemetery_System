# Matinao Memorial Cemetery Mapping and Management System — Codebase Index

## Project
- **Root**: `C:\wamp64\www\client_cemeteryV2`
- **Application**: `cemetery_mapping/` (deployed to web root as-is)
- **Type**: PHP/MySQL web app — admin panel + visitor portal + public landing page
- **Version**: 1.0.0 (per `cemetery_mapping/README.md`, which is outdated — most "pending" features are implemented)

## Stack
- **Backend**: PHP 7.4+ (native, no framework, no Composer). Vendored PHPMailer in `lib/PHPMailer/`.
- **Database**: MySQL 8.0+ (InnoDB, utf8mb4). **Actual DB name: `cemetery_mapping1`** (README says `cemetery_mapping` — wrong).
- **Frontend**: Tailwind CSS 3.x (CDN), vanilla ES6+, Poppins font, **Lucide icons** (README says Heroicons — outdated).
- **Mapping**: Leaflet.js 1.9.4 + Leaflet-Rotate 0.2.8, Fullscreen 2.4.0, MarkerCluster. Center: 6.1834, 125.0846 (Polomolok, South Cotabato).
- **AI**: Groq API via PHP cURL; model/keys resolved from `ai_api_keys` DB table with file fallback (`config/groq_key.local.php`).
- **Mail**: PHPMailer over Gmail SMTP; settings resolved from `mail_settings` DB table with `config/mail_config.php` fallback.
- **Deploy**: `.github/workflows/deploy.yml` — FTP-Deploy-Action pushes `cemetery_mapping/` to `/htdocs/` (InfinityFree) on push to `main`. Excludes `test_*`, `check_*`, `setup_*`, `create_admin.php`.

## Root Layout
- `cemetery_mapping/` — main web app
- `Chapter4_SystemOverview.md` / `.docx` — thesis documentation
- `*.svg` — system/dataflow/ER/context diagrams
- `.devin/skills/refined-admin-ui/` — project skill

## Entry Points & Auth
- `index.php` — public landing page (live counts from `burial_records`, `available_plots`, `plot_reservations`)
- `login.php` — **unified login**; auto-detects admin vs visitor (email → visitor table first, else admin). Sets `$_SESSION['admin_id']`/`admin_username` or `$_SESSION['visitor_id']`/`visitor_name`/`visitor_email` + `last_activity`.
- `admin/login.php`, `visitor/login.php` — thin redirects to `../login.php`
- **Session guard pattern**: `admin/includes/header.php` and `visitor/includes/header.php` check the role session var + 30-min inactivity timeout, then emit `<head>` (Tailwind, Lucide, theme init, page CSS). Pages include header → sidebar → content → topbar.
- **Theme**: `data-theme="dark"` on `<html>`, persisted in `localStorage['cm-theme']`, applied pre-paint; `toggleTheme()` global.
- Default admin: `admin` / `admin123`.

## Directory Map (`cemetery_mapping/`)

### `admin/` — admin panel pages (all behind `$_SESSION['admin_id']`)
- Core: `dashboard.php`, `records.php`, `add-record.php`, `edit-record.php`, `map-view.php`
- Plots: `available-plots.php`, `plot-grid.php`, `plot-grids.php` (+ `plot-grids-orig.php` legacy)
- Business: `reservations.php`, `reservations_simple.php`, `process_reservation.php`, `process_payment.php`, `renewals.php`, `expiring-plots.php`, `burial-calendar.php`, `relocation` handled via API
- Reports/print: `statistics.php`, `reports.php`, `print-all-records.php`, `print-available-plots.php`, `print-statistics.php`
- Config: `settings.php`, `api-keys.php` (manages `ai_api_keys`), `assistant.php`
- Layout: `includes/header.php` (auth guard + head), `includes/sidebar.php`, `includes/topbar.php`

### `visitor/` — visitor portal (behind `$_SESSION['visitor_id']`)
- `dashboard.php` (~65 KB — main map + reservations UI), `register.php`, `renewals.php`, `logout.php`
- `available-plots.php`, `my-reservations.php` — **stubs that redirect to `dashboard.php`** (features folded into dashboard)
- Layout: `includes/header.php`, `sidebar.php`, `topbar.php` (reuses admin CSS classes)

### `api/` — JSON endpoints (`session_start()`, `header('Content-Type: application/json')`, admin APIs check `$_SESSION['admin_id']`, visitor APIs check `$_SESSION['visitor_id']`)
- Records CRUD: `add_record.php`, `update_record.php`, `delete_record.php`, `get_record.php`, `get_all_records.php`, `get_recent_records.php`, `filter_records.php`, `import_records.php`, `download_template.php`, `mark_buried.php`, `relocate_record.php`, `relocation_area.php`
- Plots: `add_available_plot.php`, `update_available_plot.php`, `delete_available_plot.php`, `get_available_plots.php`, `save_plot_grid.php`, `get_plot_grid.php`, `delete_plot_grid.php`, `get_grid_deceased.php`, `get_reserved_compartments.php`
- Grids (record-linked cell maps): **`grids.php`** (~21 KB — action router: `list`/`get`/`create`/`update`/`delete`/`select_cell`/`assign_cell_record`/`unassign_cell_record`; accepts GET, POST, or JSON body)
- Reservations/payments: `create_reservation.php`, `get_my_reservations.php`, `search_reservations.php`, `approve_reservation.php`, `admin_approve_reservation.php`, `admin_get_reservations.php`, `submit_payment.php`, `admin_verify_payment.php`, `get_pricing.php`, `test_approve.php` (debug)
- Renewals/expiration: `renew_plot.php`, `direct_renew.php`, `process_renewal.php`
- Search/meta: `search.php`, `get_barangays.php`, `get_statistics.php`, `generate_report.php`, `check_email.php`
- AI assistants: `assistant_api.php` (admin), `visitor_assistant.php` (public) — both call `sendGroqRequest()` from `config/groq_config.php`; support `[NAV_TO: lat, lng, name]` commands parsed by `extractNavigationCommand()`
- Notifications: `admin_notifications.php`, `visitor_notifications.php` (polling endpoints)

### `config/`
- `database.php` — defines `DB_*` constants + `$pdo` (PDO, exceptions, real prepares) + `executeQuery()` helper
- `groq_config.php` — resolves Groq key/URL/model DB-first → `groq_key.local.php` fallback; `sendGroqRequest()`, `extractNavigationCommand()`. **SSL verification disabled** for WAMP (`CURLOPT_SSL_VERIFYPEER false`)
- `mail_config.php` — SMTP constants fallback (placeholder creds; real values live in `mail_settings` table)
- `groq_key.local.php` — **private, gitignored; do not read/expose**

### `includes/`
- `mailer.php` — `mail_settings($pdo)` + `send_mail()`; lazy-loads vendored PHPMailer, fails soft (returns false, logs)
- `expiry_notifier.php` — `runExpiryNotices($pdo)`; sends 30-day reminders + expired notices to owners, daily digest to admins; deduped via `expiration_notices` table; safe to call on every admin page load

### `assets/`
- `css/`: `theme.css` (design tokens, dark mode), `admin.css` (panel layout), `public-theme.css` (landing/login), `mobile-responsive.css`, `smooth-scroll.css`, `style.css`
- `js/`: `admin.js`, `visitor.js` (map/search/assistant for public UI), `theme.js`, `smooth-scroll.js`
- `images/`: logos, banner, favicons

### `uploads/` — `photos/` (burial), `plots/` (plot), `payments/` (proof of payment). `.htaccess` blocks PHP execution (file is gitignored but present).

## Database Schema
Core (`database.sql`, uses `cemetery_mapping` — **recreate as `cemetery_mapping1`**):
- `admin_users`, `visitors`, `visitor_activity_log`
- `burial_records` — decedent, dates, barangay, plot_number, lat/lng, `polygon` (GeoJSON-ish TEXT), `is_fenced`, `family_name`, `visitor_id`, `photo`, `expiration_date`, `renewal_count`, relocation cols (`is_relocated`, `orig_*`)
- `available_plots` — lat/lng, polygon, photo, grid config (`has_grid`, `grid_rows/cols`, `grid_data`, `compartment_count`), expiration fields
- `plot_compartments` — cells within a plot (`plot_id`, row/col, `is_occupied`, `record_id`)

Migrations (apply in order; each is `CREATE IF NOT EXISTS`/`ALTER`):
- `add_reservation_tables.sql` → `plot_reservations` (pending→approved/rejected/cancelled, payment_status), `payments` (proof upload, verification_status), `plot_pricing`
- `plot_grids_migration.sql` / `plot_grids_standalone_migration.sql` → `plot_grids` + `plot_grid_cells` (record-linked grid drawing)
- `add_ai_api_keys.sql` → `ai_api_keys` (provider/label/key/model/url/is_active)
- `add_mail_settings.sql` → `mail_settings` (single row id=1)
- `add_expiration_notices.sql` → `expiration_notices` (mail dedup log)
- `add_plot_renewal_requests.sql` → `plot_renewal_requests` (visitor renewal workflow)
- `add_relocation.sql` → `system_settings` + burial_records relocation columns
- `add_plot_expiration.sql`, `add_burial_visitor_link.sql`, `add_polygon_columns.sql` → column additions (already reflected above)

## Setup / Ops Helpers (excluded from deploy)
- `check_database.php`, `check_reservation_structure.php`, `check_reservation_tables.php`, `setup_check.php`, `setup_reservations.php`, `create_admin.php`, `_check_schema.php`
- `test_api.html`, `test_assistant.php`, `test_models.php`, `test_outbound.php`, `test_rotation.html`, `test_voice.php`

## Conventions
- **Config resolution pattern**: DB table first → config-file constants fallback (used by both Groq and mail).
- **API style**: JSON `{success, message, ...data}`; `respond()` helper pattern; verbose `error_log()` tracing; PDO prepared statements everywhere.
- **No CSRF tokens** anywhere; auth = session check only.
- **`.htaccess`**: blocks `config/` web access, protects `.sql/.log/.ini`, no dir listing, security headers, 5 MB upload cap.
- Payments upload proofs to `uploads/payments/`; burial photos to `uploads/photos/`; plot photos to `uploads/plots/`.

## How to Run
1. Start WAMP Apache + MySQL.
2. Create DB `cemetery_mapping1`; import `database.sql` (edit its `CREATE DATABASE`/`USE` lines) then each `add_*.sql` migration.
3. Configure `config/database.php`; set Groq key via Admin → API Keys or `groq_key.local.php`; mail via Admin → Settings.
4. Open `http://localhost/client_cemeteryV2/cemetery_mapping/`.

## Cautions
- Do not read/expose `config/groq_key.local.php` or real `mail_settings` SMTP password.
- `README.md` is stale: wrong DB name, wrong icon library, lists completed features as pending. Trust this file.
- Reservation approval has **three parallel implementations**: `api/approve_reservation.php`, `api/admin_approve_reservation.php`, `admin/process_reservation.php` — check which the calling page uses before editing.
- `admin/plot-grids.php` vs `plot-grid.php` vs `plot-grids-orig.php` are separate pages — don't conflate.
- `nul` file at repo root — accidental Windows artifact, safe to ignore/delete.
