---
name: refined-admin-ui
description: Refine admin UI so it looks like a real, hand-built product — not AI-generated
triggers:
  - user
  - model
---

# Refined Admin UI

Apply this when restyling any page in `cemetery_mapping/admin/` (or shared styles in
`assets/css/admin.css`). The goal is a quiet, editorial, product-grade admin interface —
like Linear, Stripe Dashboard, or a well-built internal tool — NOT a generated
"gradient dashboard" template.

## Hard rules — never do these

- **No gradients** anywhere: no `linear-gradient`, no `radial-gradient`, no multi-stop
  color washes on backgrounds, buttons, avatars, cards, or stat tiles.
- **No glows**: no colored `box-shadow` (e.g. `0 8px 24px rgba(16,185,129,.35)`), no
  drop-shadow halos, no neon rings.
- **No decorative layers**: no noise textures, blob shapes, dot grids, `::before/::after`
  ornaments, glassmorphism (`backdrop-filter: blur`), or semi-transparent tinted panels.
- **No oversized marketing styling** in admin pages: huge hero headings, giant rounded
  corners (>14px), excessive whitespace, center-aligned empty padding.
- **No emoji** in UI chrome. Icons come from Lucide only, sized `w-4 h-4`–`w-5 h-5`.
- **No rainbow stat cards**: don't give each stat tile a different accent color
  (green/amber/violet/rose). Stats are neutral; color is reserved for meaning.
- **No hover theatrics**: no `scale()` pops, no big lift shadows, no multi-property
  `transition-all` fireworks. Subtle background/darken change only.

## Design language

### Color
- Page background: `#f7f8fa` (or `bg-slate-50` in Tailwind). White surfaces `#ffffff`.
- Borders: `1px solid #e5e7eb` / `#e2e8f0` — prefer borders over shadows.
- Text: headings `#0f172a` (slate-900), body `#334155` (slate-700),
  meta/muted `#64748b`–`#94a3b8` (slate-500/400).
- **One accent**: emerald. Primary action `#059669` (emerald-600), hover `#047857`.
  Soft accent tint `#ecfdf5` for active nav / selected rows.
- Destructive `#dc2626`, warning `#d97706`, info `#2563eb` — used sparingly,
  only where semantics demand it.

### Elevation
- Cards/panels: `background:#fff; border:1px solid #e5e7eb; border-radius:10–12px`.
- Shadow only for floating layers (dropdowns, modals, toasts):
  `0 8px 24px rgba(15,23,42,.08)` — never colored, never on resting cards.
- Hover on rows/cards: background `#f8fafc` or border darkens. No lift.

### Typography
- Font: Poppins (already loaded). Weights: 400 body, 500 labels, 600 headings,
  700 page titles only.
- Page title: `1.05–1.25rem`, weight 600–700, `letter-spacing:-0.01em`.
- Section labels / table headers: `0.68–0.75rem`, weight 600, uppercase,
  `letter-spacing:.05em`, color slate-500.
- Body/UI text: `0.8–0.875rem`. Meta: `0.75rem` slate-500.
- Numbers in tables/stats: `font-variant-numeric: tabular-nums` when aligned in columns.

### Density & layout
- Admin pages are dense tools, not landing pages. Content padding `24–32px` top-level,
  `14–20px` inside cards. Rows `40–52px` tall.
- Content width: let it fill `admin-main` (no `max-w-7xl mx-auto` centering on data pages).
  Forms/detail pages may cap at `max-w-3xl`.
- Use a consistent spacing rhythm: gaps of `8/12/16/20/24px` only.

### Components

- **Buttons**: height `34–36px`, `border-radius:8px`, `padding:0 14px`, font `0.8rem/600`.
  Primary = solid `#059669` white text, hover `#047857`. Secondary = white, `1px #e2e8f0`
  border, slate-700 text, hover `#f8fafc`. Danger = white bg, `#dc2626` text/border,
  hover `#fef2f2`. Icon buttons `32–34px` square.
- **Tables**: header row `background:#f8fafc`, uppercase small-caps label style,
  `border-bottom:1px solid #e5e7eb`. Body rows separated by `1px #f1f5f9` borders,
  hover `#f8fafc`. No row shadows, no zebra striping, no rounded corners inside.
- **Badges/status pills**: `font-size:0.68rem`, weight 600, `padding:2px 8px`,
  `border-radius:999px`, soft tint bg + matching darker text — e.g. scheduled
  `#fef3c7/#92400e`, completed `#d1fae5/#065f46`, pending `#e0e7ff/#3730a3` —
  never saturated fills with white text unless tiny.
- **Forms**: labels `0.78rem/500` slate-700, inputs `height:36px`, `1px #d1d5db` border,
  `radius:8px`, focus = `border-color:#059669` + `box-shadow:0 0 0 3px rgba(5,150,105,.12)`.
- **Modals**: white, `radius:12px`, `1px #e5e7eb`, shadow `0 16px 40px rgba(15,23,42,.12)`.
  Header = title + muted subtitle + close icon, separated by border-bottom. No colored
  header bands.
- **Stat tiles** (if a page needs them): flat white card, `1px` border, small icon in a
  neutral `#f1f5f9` square, big slate-900 number, small muted label. Optionally ONE
  accent card, not four.
- **Empty states**: small muted icon (`#cbd5e1`), one line of slate-500 text.
  No giant illustrations or gradient blobs.
- **Avatars**: flat circle/square, solid `#0f172a` or `#059669`, initials white.
  No gradients.

## Process

1. Read the target page and `assets/css/admin.css` first — most pages share classes,
   so prefer fixing the shared class once over patching each page.
2. Keep the shared chrome conventions: fixed sidebar `--sidebar-width`, fixed 60px
   header, `.admin-main { margin-left: var(--sidebar-width); padding: 84px 28px 40px; }`.
3. Preserve all PHP/JS behavior, form fields, table columns, and Lucide icon usage —
   this is a visual pass only.
4. Prefer Tailwind utilities already loaded via CDN for page markup; put repeated
   component styles in `admin.css`.
5. Bump the `admin.css?v=` query in `admin/includes/header.php` and
   `visitor/includes/header.php` after CSS changes.
6. Run `php -l` on every PHP file touched. Check CSS brace balance.
7. Consistency is the point: two admin pages should look like the same product.
   Match existing conventions in `dashboard.php` / `records.php` when in doubt.
