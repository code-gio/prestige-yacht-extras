# Prestige Yacht Extras

A WordPress plugin for the Prestige Yacht Sales site. It adds two things to the `boat`
custom post type (Advanced Custom Fields):

1. **Branded PDF spec sheets** — a button/link that generates a downloadable, brochure-quality
   PDF for any boat listing.
2. **A filterable boat archive** — a shortcode that renders an AJAX-driven, filterable grid of
   boat listings ("Yacht Search").

The plugin is **upload-and-go**: Dompdf is bundled in `vendor/`, so no `composer install` is
required on the server.

---

## Requirements

- WordPress 6.0+
- PHP 7.4+ with the **GD** or **Imagick** extension (needed so images render inside PDFs)
- The `boat` custom post type with the bundled ACF field group (Advanced Custom Fields)

---

## Installation

1. Upload the plugin ZIP via **Plugins → Add New → Upload Plugin** (or copy the folder to
   `wp-content/plugins/prestige-yacht-extras`).
2. **Activate** the plugin.
3. Add the shortcodes where you want them (see below).

The PDF URL registers itself automatically — you do **not** need to visit Settings → Permalinks.
(If a PDF link ever 404s, saving Permalinks once forces a rewrite refresh.)

---

## Shortcodes

### `[boat_pdf_button]` — download a boat's PDF spec sheet

Renders a "Download PDF" link for a boat.

| Attribute | Default        | Description                                  |
|-----------|----------------|----------------------------------------------|
| `id`      | current post   | Boat post ID to target.                      |
| `label`   | `Download PDF` | Button text.                                 |

```text
[boat_pdf_button]
[boat_pdf_button id="123"]
[boat_pdf_button label="Download Spec Sheet"]
```

The PDF includes a cover (hero image, title, price, key facts), grouped specification tables,
an engines table, the description (rendered HTML), and gallery photo pages.

**PDF URL** (in case you want to link directly):

- Pretty permalinks: `/boat/{slug}/pdf/`
- Plain permalinks: `?boat_pdf={id}`

### `[boat_archive]` — filterable boat grid

Renders the "Yacht Search" filter bar, a responsive card grid, and a "Load More" button.

| Attribute      | Default | Description                                                        |
|----------------|---------|--------------------------------------------------------------------|
| `per_page`     | `12`    | Cards per page / per Load More.                                    |
| `columns`      | `3`     | Desktop grid columns (1–4).                                        |
| `category`     | —       | Preset category: `Power` or `Sail`.                               |
| `manufacturer` | —       | Preset manufacturer (exact match).                                |
| `model`        | —       | Preset model line — case-insensitive partial match, so `Oceanis` matches "Oceanis 34.1" etc. No visible control; applies to every search on the page. |
| `len_min`      | —       | Preset minimum length (ft).                                       |
| `len_max`      | —       | Preset maximum length (ft).                                       |
| `lock`         | —       | Comma list of filters to lock (hide + enforce): `category`, `manufacturer`, `model`, `length`. |

**Filters:** category (All / Power / Sail), manufacturer (auto-populated from your listings),
and a length range. Results are **always sorted longest → shortest** by length, regardless of
the filters. Filter state is reflected in the URL (shareable/bookmarkable). The first page is
server-rendered (works without JS, indexable); filter changes and Load More use AJAX.

```text
[boat_archive]
[boat_archive per_page="9" columns="3"]
```

#### Pre-filtered pages

There are two ways to start the archive with a filter already applied:

1. **URL parameter** (soft default — the visitor can change it). Link to the page that holds
   `[boat_archive]` with any of `category`, `mfr`, `model`, `len_min`, `len_max`:

   ```text
   /boats-for-sale/?mfr=Beneteau
   /boats-for-sale/?category=Sail
   ```

   The bar opens pre-selected to that filter, and the visitor can clear or change it. This is
   ideal for the homepage brand slider — point each brand at `?mfr=<Brand>` instead of a custom
   search URL.

2. **Locked shortcode** (hard — the filter is enforced and hidden, the rest of the bar refines
   within it). Use a dedicated page per fixed category/brand:

   ```text
   Power Yachts page:  [boat_archive category="Power" lock="category"]
   Sail page:          [boat_archive category="Sail"  lock="category"]
   Beneteau page:      [boat_archive manufacturer="Beneteau" lock="manufacturer"]
   Oceanis page:       [boat_archive model="Oceanis" lock="model"]
   First page:         [boat_archive model="First" lock="model"]
   ```

   On the Power page the Power/Sail toggle is hidden and *every* result (and Load More, and any
   manufacturer/length search) stays within Power. On a brand page the manufacturer dropdown is
   hidden and results stay within that brand.

   **Manufacturer vs. model:** use `manufacturer` for brands (Beneteau, Regal…) and `model` for
   model lines within a brand (Oceanis, First, Flyer, Antares…). Manufacturer matches exactly;
   model matches partially. On a model page the filter stays applied through every search, and
   the manufacturer dropdown only lists brands that carry that model.

---

## Branding

Branding lives in one file: `src/Pdf/Branding.php`, and the logo file lives in `assets/brand/`.

```php
private const LOGO_FILE   = 'prestige-ys-logo.png'; // PNG/JPG only — Dompdf does not support SVG
public  const PRIMARY     = '#13294b'; // navy — headers, titles, price
public  const ACCENT      = '#b8962f'; // gold — rules, labels
public  const FONT_FAMILY = 'DejaVu Sans, sans-serif';
public  const COMPANY     = 'Prestige Yacht Sales';
```

The same logo and colors are used by both the PDF and the archive cards. See
[`assets/brand/README.md`](assets/brand/README.md) for swapping assets and custom fonts.

---

## Project structure

```
prestige-yacht-extras.php   Bootstrap: constants, autoloader, hooks
src/
  Plugin.php                Registers every hook (single source of truth)
  Boat/BoatData.php         Normalizes ACF fields; for_post() (PDF) + card() (grid)
  Pdf/
    PdfController.php        /boat/{slug}/pdf/ route + ?boat_pdf={id}; streams the file
    PdfRenderer.php          Template -> Dompdf -> PDF bytes
    Branding.php             Logo / colors / company name
  Shortcodes/PdfButtonShortcode.php   [boat_pdf_button]
  Archive/
    ArchiveShortcode.php     [boat_archive]; renders bar + first page; shared card renderer
    BoatQuery.php            Filter params -> WP_Query (meta_query ranges/equality)
    AjaxController.php        admin-ajax handler -> JSON { html, has_more, total }
    ManufacturerOptions.php  Distinct manufacturers (transient-cached)
  Support/Assets.php         Front-end button styles
templates/
  pdf/                       PDF template + partials
  archive/                   filter-bar, grid, card
assets/
  css/ (button, archive)  js/ (archive)  pdf/ (pdf.css)  brand/ (logo)
acf-json/                   Boat field group (ACF Local JSON sync)
vendor/                     Bundled Dompdf (committed; no composer install needed)
```

---

## Development

- Classes are PSR-4 autoloaded (`PrestigeYacht\Extras\` → `src/`) via a self-contained
  autoloader in the bootstrap, so Composer is **not** required at runtime.
- Developers may still run `composer install` to manage Dompdf via Composer; the bundled
  `vendor/` already satisfies the dependency.
- Design specs live in `docs/superpowers/specs/`.

### Building the distributable ZIP

```sh
cd ..
zip -rq prestige-yacht-extras.zip prestige-yacht-extras \
  -x 'prestige-yacht-extras/.git/*' -x 'prestige-yacht-extras/.gitignore' \
  -x 'prestige-yacht-extras/docs/*' -x 'prestige-yacht-extras/.claude/*' \
  -x '*.DS_Store' -x 'prestige-yacht-extras/*.zip'
```

---

## Changelog

- **1.1.0** — Add `[boat_archive]` filterable AJAX boat grid (category, condition, manufacturer,
  length, price). Render boat descriptions as HTML; format prices as `$189,000`.
- **1.0.0** — Initial release: server-side PDF spec sheets + `[boat_pdf_button]` shortcode.
