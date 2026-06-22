# Prestige Yacht Extras — Boat PDF Plugin (Design)

**Date:** 2026-06-21
**Status:** Approved
**Scope:** Modern WordPress plugin scaffold + server-side PDF generation for the `boat` custom post type. The boat **filter shortcode is deferred** to a separate spec; this scaffold leaves a clean slot for it.

## Context

- Site: `https://prestigeapi.wpenginepowered.com/` (WP Engine).
- Custom post type `boat` with an ACF field group (`group_boat_listing.json`, ~70 fields):
  identity (manufacturer, model, model_year, boat_name, listing_title), classification
  (sale_class, boat_category, boat_class, hull_material), dimensions (nominal/overall length,
  beam, drafts, clearances, weights), propulsion (num_engines, total_power, engine_hours +
  `engines` repeater), capacities (fuel/water/holding tanks), accommodations (cabins, heads,
  max_passengers), pricing (price, price_display, price_hidden, original_price), location
  (city/state/country), broker (sales_rep_name, office_phone, office_email), media (`images`
  gallery returning attachment IDs, `video_urls` repeater), and descriptions.

## Decisions (from brainstorming)

| Decision | Choice |
|---|---|
| PDF generation | **Server-side, Dompdf** (Composer). Real downloadable `.pdf` file. |
| PDF content | **Brochure spec sheet + photo grid pages**: cover (hero image, title, price), curated spec sections, then gallery photo grid page(s). Empty fields hidden. |
| Branding | User provides logo/colors/font. Build proceeds with placeholder navy/white + placeholder logo; assets swap in via `Branding.php` + `assets/brand/`. **Not** an admin settings page. |
| Button placement | **`[boat_pdf_button]` shortcode** (optional `id` attr). No auto-append. |
| `vendor/` | **Committed** to the repo (no build step on WP Engine). |
| Filter shortcode | **Deferred** to a follow-up spec. |

## Plugin structure

```
prestige-yacht-extras/
├── prestige-yacht-extras.php     # plugin header + bootstrap; guards PHP version + autoload presence
├── composer.json                 # require dompdf/dompdf; PSR-4 "PrestigeYacht\Extras\" => src/
├── uninstall.php                 # flush rewrite rules cleanup (no DB options to remove yet)
├── readme.txt
├── src/
│   ├── Plugin.php                # single place that registers all hooks; instantiated from main file
│   ├── Boat/BoatData.php         # given a post ID, returns a normalized array of boat data; drops empty fields
│   ├── Pdf/PdfController.php     # registers rewrite endpoint + query var; on template_redirect builds & streams PDF
│   ├── Pdf/PdfRenderer.php       # renders template to HTML, configures + runs Dompdf, returns PDF bytes
│   ├── Pdf/Branding.php          # central config: logo file path, primary/accent hex, font name
│   ├── Shortcodes/PdfButtonShortcode.php   # [boat_pdf_button id="123"] -> styled <a> to the PDF URL
│   └── Support/Assets.php        # enqueues assets/css/button.css on front end
├── templates/pdf/
│   ├── boat-spec.php             # master template: includes partials in page order
│   └── partials/
│       ├── cover.php             # hero image + title + price + key facts
│       ├── specs-table.php       # labeled spec tables grouped by section
│       ├── engines.php           # engines repeater table
│       └── photo-grid.php        # gallery images in a grid, paginated
├── assets/
│   ├── css/button.css            # front-end button styling
│   ├── pdf/pdf.css               # stylesheet consumed by the PDF template
│   └── brand/                    # logo placeholder ships here; user drops real logo
└── acf-json/
    └── group_boat_listing.json   # ACF Local JSON so the field group travels with the plugin
```

## Components & responsibilities

- **`prestige-yacht-extras.php`** — WordPress plugin header, defines path/URL constants, requires
  `vendor/autoload.php` (bails with an admin notice if missing), instantiates `Plugin`.
- **`Plugin`** — wires hooks: registers shortcode, PDF controller (rewrite + `template_redirect`),
  assets, and ACF Local JSON save/load path for `acf-json/`. Owns activation/deactivation hooks
  (flush rewrite rules).
- **`BoatData`** — `BoatData::forPost( int $id ): array`. Reads each field via `get_field()` when
  ACF is active, else `get_post_meta()`. Returns a structured array grouped by section
  (identity, pricing, dimensions, propulsion, capacities, accommodations, location, broker,
  media, descriptions). Drops null/empty values. Resolves the `images` gallery IDs and the
  featured image to **local file paths** via `get_attached_file()` for Dompdf. Computes a
  display price honoring `price_hidden`/`price_display`.
- **`PdfController`** — adds rewrite endpoint so `/boat/{slug}/pdf/` works; also accepts a
  `?boat_pdf={id}` fallback for plain permalinks. On match: validate post is published `boat`
  (else 404), load `BoatData`, call `PdfRenderer`, stream with
  `Content-Type: application/pdf` and `Content-Disposition: attachment; filename="{slug}.pdf"`,
  then `exit`.
- **`PdfRenderer`** — loads `Branding`, renders `templates/pdf/boat-spec.php` into HTML (template
  has access to the boat data array + branding + a path helper for the stylesheet/logo),
  configures Dompdf (`isRemoteEnabled` off — images are local paths; A4/Letter portrait;
  default DPI), renders, returns bytes.
- **`Branding`** — returns logo absolute path (placeholder if user file absent), primary hex,
  accent hex, font family. Single edit point when real assets arrive.
- **`PdfButtonShortcode`** — `[boat_pdf_button]` uses current post when inside a boat; `id`
  attr overrides. Outputs a styled anchor to the PDF URL. Returns nothing if target isn't a
  valid boat.
- **`Assets`** — enqueues `button.css` front end only.

## PDF layout

1. **Cover (page 1):** logo header, hero image (featured image; fallback first gallery image),
   listing title (or `{model_year} {manufacturer} {model}`), location, display price, and a
   key-facts strip (length, year, engines, hours).
2. **Specs (page 1–2):** sectioned tables — Dimensions, Propulsion summary + **Engines** repeater
   table, Capacities (tanks), Accommodations, Construction (hull/keel/drive), other specs.
   Then the Description and Additional Specs text.
3. **Photos (page 3+):** gallery images in a responsive grid, page-broken as needed.
4. **Footer (every page):** broker contact (sales_rep_name, office_phone, office_email),
   stock number, and brand line.

## Data flow

```
[boat_pdf_button]  ->  <a href="/boat/{slug}/pdf/">
        |
   user clicks
        v
template_redirect -> PdfController validates boat
        v
BoatData::forPost(id)  -> normalized array (+ local image paths)
        v
PdfRenderer(template + branding) -> HTML -> Dompdf -> PDF bytes
        v
stream as attachment "{slug}.pdf" ; exit
```

## Error handling

- Missing `vendor/autoload.php` → admin notice, plugin no-ops (no fatal).
- PDF request for non-boat / unpublished / missing post → `404` via `wp_die` with status.
- Missing hero/gallery images → sections omitted gracefully; no broken-image boxes.
- ACF inactive → fall back to `get_post_meta`; repeaters read raw meta safely.
- Dompdf exception → log via `error_log`, `wp_die` with a friendly message (avoid white screen).
- All field output escaped/sanitized before entering the template.

## Verification (manual, on WP Engine — no local WP here)

1. Install plugin, run `composer install` (or deploy with committed `vendor/`), activate.
2. Flush permalinks (Settings → Permalinks → Save) so the rewrite endpoint registers.
3. Place `[boat_pdf_button]` on the single-boat template / a boat's content.
4. Click on `1999-sabreline-36-express` → PDF downloads named `1999-sabreline-36-express.pdf`.
5. Confirm: cover hero + price, spec tables populated, engines table, photo grid, broker footer.
6. Edge case: a boat with most fields empty / no gallery → still renders without errors or
   broken images.
7. Plain-permalink fallback: `?boat_pdf={id}` produces the same PDF.

## Out of scope (future specs)

- Boat **filter shortcode** (faceted search/filter UI).
- Admin settings page for branding.
- Caching/persisting generated PDFs.
