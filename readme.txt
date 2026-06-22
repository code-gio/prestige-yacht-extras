=== Prestige Yacht Extras ===
Contributors: codegio
Tags: pdf, boats, acf, custom post type, brochure
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generates branded PDF spec sheets for boat listings (the `boat` custom post type) via a shortcode button.

== Description ==

Adds a [boat_pdf_button] shortcode that links to a downloadable, brochure-quality PDF spec
sheet for any boat listing. The PDF is generated server-side with Dompdf and includes a cover
(hero image, title, price, key facts), grouped specification tables, an engines table, the
description, and a gallery photo grid.

= Shortcode =

`[boat_pdf_button]` — uses the current boat post.
`[boat_pdf_button id="123"]` — targets a specific boat.
`[boat_pdf_button label="Download Spec Sheet"]` — custom button text.

= PDF URL =

* Pretty permalinks: `/boat/{slug}/pdf/`
* Plain permalinks: `?boat_pdf={id}`

= Boat archive =

`[boat_archive]` — a filterable, AJAX-driven grid of boat listings (Yacht Search bar +
3-column cards + Load More). Optional attributes:

`[boat_archive per_page="12" columns="3"]`

Filters: category (Power/Sail), condition (New/Used), manufacturer, length range, price range.
Filter state is reflected in the URL so searches are shareable. Initial results are
server-rendered (SEO + works without JS); changes and Load More use AJAX.

== Installation ==

The plugin is upload-and-go — Dompdf is bundled in `vendor/`, so no Composer step is needed.

1. Upload the plugin ZIP via Plugins → Add New → Upload Plugin (or copy the folder to
   `wp-content/plugins/prestige-yacht-extras`).
2. Activate the plugin. The PDF URL is registered automatically — no need to touch
   Settings → Permalinks. (If you ever hit a 404 on a PDF link, saving Permalinks once
   forces a rewrite refresh.)
3. Add `[boat_pdf_button]` to your single-boat template or a boat's content.

Developers may instead run `composer install` to manage Dompdf via Composer; the bundled
`vendor/` already satisfies the dependency at runtime.

== Requirements ==

* The `boat` custom post type with the bundled ACF field group (Advanced Custom Fields).
* PHP 7.4+ with the GD or Imagick extension for image rendering in PDFs.

== Changelog ==

= 1.1.0 =
* Add [boat_archive] filterable AJAX boat grid (category, condition, manufacturer, length, price).

= 1.0.0 =
* Initial release: server-side PDF spec sheets + [boat_pdf_button] shortcode.
