# Matinao Memorial Cemetery Mapping and Management System — Agent Notes

## Project
- **Root**: `C:\wamp64\www\client_cemeteryV2`
- **Application**: `cemetery_mapping/`
- **Type**: PHP/MySQL web app with admin panel and visitor portal
- **Version**: 1.0.0 (per `cemetery_mapping/README.md`)

## Stack
- **Backend**: PHP 7.4+ (native, no framework)
- **Database**: MySQL 8.0+ (InnoDB)
- **Frontend**: Tailwind CSS 3.x (CDN), vanilla ES6+, Google Fonts Poppins, Heroicons
- **Mapping**: Leaflet.js 1.9.4 + Leaflet-Rotate, Fullscreen, MarkerCluster
- **AI**: Groq API (llama-3.1-70b-versatile) via PHP cURL

## Root Layout
- `cemetery_mapping/` — main web app
- `Chapter4_SystemOverview.md` / `Chapter_4_System_Overview.docx` — documentation
- `system_flowchart.svg`, `data_flow_diagram.svg`, `entity_relationship_diagram.svg` — diagrams

## Application Structure (`cemetery_mapping/`)
- `index.php` — public landing/entry
- `admin/` — admin panel
  - `dashboard.php`, `login.php`, `logout.php`, `records.php`, `add-record.php`, `edit-record.php`
  - `map-view.php`, `available-plots.php`, `plot-grid.php`, `statistics.php`, `reports.php`
  - `reservations.php`, `reservations_simple.php`, `process_reservation.php`, `process_payment.php`
  - `settings.php`, `assistant.php`
  - `includes/header.php`, `includes/sidebar.php`
  - print pages: `print-all-records.php`, `print-available-plots.php`, `print-statistics.php`
- `api/` — JSON/HTTP endpoints for records, plots, reservations, search, assistant, payments
- `assets/` — `css/`, `js/`, image directories
- `config/` — `database.php`, `groq_config.php`, `groq_key.local.php` (private)
- `visitor/` — `login.php`, `register.php`, `dashboard.php`, `logout.php`, `my-reservations.php`
- `database.sql` — full schema
- `add_reservation_tables.sql`, `setup_reservations_simple.sql` — migration files
- `check_database.php`, `check_reservation_structure.php`, `setup_reservations.php` — setup/validation helpers

## How to Run
1. Start WAMP Apache and MySQL.
2. Import `cemetery_mapping/database.sql` (and any additional `.sql` migrations as needed).
3. Ensure this repo is under `C:\wamp64\www\client_cemeteryV2`.
4. Configure `cemetery_mapping/config/database.php` and `cemetery_mapping/config/groq_config.php`.
5. Open `http://localhost/client_cemeteryV2/cemetery_mapping/` in a browser.
6. Default admin: `admin` / `admin123` (change for production).

## Notes for Agents
- Native PHP: no Composer, no framework, no routing layer. Pages are file-based.
- API endpoints are under `api/` and return JSON; they are called from both admin and visitor frontends.
- AI assistant logic is split between `api/assistant_api.php` and `api/visitor_assistant.php`.
- Do not read or expose `config/groq_key.local.php` (likely contains a live API key).
- Upload directories `uploads/photos` and `uploads/plots` should be writable and not execute PHP (`.htaccess` enforces this).
- `cemetery_mapping/README.md` lists many features as pending; the actual file tree shows most are now implemented.
