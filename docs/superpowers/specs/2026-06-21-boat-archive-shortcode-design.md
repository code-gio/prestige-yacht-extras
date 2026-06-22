# Boat Archive Shortcode (Design)

**Date:** 2026-06-21
**Status:** Approved
**Scope:** A `[boat_archive]` shortcode added to the existing `prestige-yacht-extras` plugin that
renders a filterable, AJAX-driven grid of `boat` listings matching the site's current
"Yacht Search" UI and card design.

## Context

- Existing plugin: `prestige-yacht-extras` (PSR-4 `PrestigeYacht\Extras\`, self-contained
  autoloader, bundled Dompdf, branding in `src/Pdf/Branding.php`).
- CPT `boat` with the ACF field group in `acf-json/group_boat_listing.json` (identical to the
  `yacht-api` source of truth). Filters use ACF meta/select fields, not taxonomies.
- Reference UI provided by the user: a "Yacht Search" filter bar and a 3-up boat card grid.

## Decisions (from brainstorming)

| Decision | Choice |
|---|---|
| Feature | Full filterable archive in one shortcode `[boat_archive]`. |
| Filter mechanism | AJAX, no reload; URL state via history API (shareable/bookmarkable). |
| Initial render | Server-rendered first page from URL params (SEO + no-JS baseline); JS enhances. |
| Filters | Category (Power/Sail), Condition (New/Used), Manufacturer dropdown, Min/Max Length, Min/Max Price, "Search Vessels" button. |
| Card | Featured image, navy bold title, location w/ map-pin, navy bold price, gray footer w/ Prestige logo; whole card links to the listing. |
| Grid | 3 columns desktop (responsive: 2 tablet, 1 mobile). |
| Pagination | "Load More" button, 12 per page, AJAX-appends. |
| Default sort | Newest first (date DESC). |
| Length field | `nominal_length` (advertised length); single constant, easy to switch to `length_overall`. |

## Components

```
src/Archive/
  ArchiveShortcode.php    # registers [boat_archive]; enqueues assets; renders filter bar + first page
  BoatQuery.php           # turns filter params into WP_Query args + runs the query
  AjaxController.php       # wp_ajax_(nopriv_)boat_archive_query -> JSON { html, has_more, total }
  ManufacturerOptions.php # DISTINCT published manufacturer values (transient-cached)
src/Boat/BoatData.php     # + card(int $id): array  (minimal: title, location, price) reusing existing helpers
templates/archive/
  filter-bar.php          # the "Yacht Search" bar
  grid.php                # wrapper: grid + load-more + empty state
  card.php                # single boat card markup
assets/css/archive.css
assets/js/archive.js
```

### ArchiveShortcode
- Shortcode `[boat_archive]` with optional attrs: `per_page` (default 12), `columns` (default 3).
- Enqueues `archive.css` + `archive.js`; localizes `{ ajaxUrl, nonce, perPage, columns }`.
- Reads current filter values from `$_GET` (sanitized) for the initial server render so a shared
  URL reproduces the same results.
- Renders `filter-bar.php` (pre-filled from current params + manufacturer options) then `grid.php`
  with the first page of cards and the correct `has_more` state.

### BoatQuery
- Input: associative array of sanitized filters (`category`, `condition`, `mfr`, `len_min`,
  `len_max`, `price_min`, `price_max`, `paged`, `per_page`).
- Builds `WP_Query`:
  - `post_type => boat`, `post_status => publish`, `orderby => date`, `order => DESC`.
  - `meta_query` (AND):
    - category: `boat_category = Power|Sail` (omitted for "All").
    - condition: `sale_class = New|Used` (omitted for "All").
    - manufacturer: `manufacturer = <value>` (omitted for "All Manufacturers").
    - length: `nominal_length` numeric `>=`/`<=`/`BETWEEN` per min/max present.
    - price: `price` numeric `>=`/`<=`/`BETWEEN` per min/max present.
  - `paged`, `posts_per_page = per_page`.
- Returns the `WP_Query`. Caller derives `has_more` from `paged < max_num_pages` and `total` from
  `found_posts`.

### AjaxController
- Hooks `wp_ajax_boat_archive_query` and `wp_ajax_nopriv_boat_archive_query`.
- `check_ajax_referer` against the localized nonce.
- Sanitizes params, runs `BoatQuery`, renders each result via `card.php` into an HTML string.
- Responds `wp_send_json_success([ 'html' => ..., 'has_more' => bool, 'total' => int ])`.

### ManufacturerOptions
- `get(): string[]` — `DISTINCT meta_value` of `manufacturer` for published boats, non-empty,
  sorted alphabetically (case-insensitive). Cached in a transient
  (`pye_boat_manufacturers`) invalidated on `save_post_boat`.

### BoatData::card()
- `card(int $id): array` returning `{ id, permalink, title, location, price, thumb_id }`,
  reusing the existing private `title`/`location`/`price_display` logic. Does NOT resolve gallery
  file paths (that heavy work is PDF-only). `card.php` uses `get_the_post_thumbnail($id,'large')`
  for responsive images.

## Data flow

```
[boat_archive]
   |
   v
ArchiveShortcode: read $_GET filters -> BoatQuery -> render filter-bar + first 12 cards (server)
   |
user changes a filter / clicks Search Vessels / Load More
   v
archive.js -> POST admin-ajax (action=boat_archive_query, nonce, filters, paged)
   v
AjaxController -> BoatQuery -> card.php x N -> JSON { html, has_more, total }
   v
archive.js: replace grid (filter change) or append (load more); update URL via history.pushState
```

## Markup / styling (match reference)

- Filter bar: white rounded panel, "Yacht Search" heading (navy), segmented toggles (navy active
  pill), labeled inputs, navy "Search Vessels" button. Colors from `Branding` (navy `#13294b`,
  gold `#b8962f`).
- Card: rounded container with subtle border/shadow; image top (object-fit cover, fixed aspect);
  body padding with title (navy, bold), location row (gray, map-pin SVG), price (navy, bold);
  light-gray footer centering the bundled Prestige logo. Entire card wrapped in an `<a>`.
- Grid: CSS grid, `columns` on desktop, collapsing to 2 then 1 via media queries.

## Error handling

- No matching boats → empty-state message ("No vessels match your search.") in the grid area.
- AJAX/network failure → leave current grid intact, show an inline notice, re-enable the button.
- Invalid/negative numeric inputs → coerced to empty (ignored) during sanitization.
- No manufacturers → dropdown shows only "All Manufacturers".
- Nonce failure → JSON error; JS shows the inline notice.

## Verification (manual, on WP Engine — no local WP here)

1. Place `[boat_archive]` on a page; confirm the bar + first 12 cards render server-side.
2. Each filter narrows results (category, condition, manufacturer, length range, price range).
3. Manufacturer dropdown is populated from real data and alphabetized.
4. "Load More" appends the next 12 and hides when no more remain.
5. Apply filters, copy the URL, open in a new tab → same filtered results render server-side.
6. Cards link to the correct single boat page; price shows as `$549,000`.
7. Empty state: a filter combination with zero matches shows the message.
8. Responsive: 3 → 2 → 1 columns across breakpoints.

## Out of scope (future)

- Sort control (newest-first only for now).
- Map view, saved searches, comparison.
- Per-card PDF button (the PDF feature exists; can be added later).
