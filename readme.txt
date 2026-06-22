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

== Installation ==

1. Upload the plugin to `wp-content/plugins/prestige-yacht-extras`.
2. From the plugin directory run `composer install` (installs Dompdf into `vendor/`).
   If you deploy a build with `vendor/` already committed, skip this step.
3. Activate the plugin.
4. Go to Settings → Permalinks and click Save to flush rewrite rules (registers the PDF endpoint).
5. Add `[boat_pdf_button]` to your single-boat template or a boat's content.

== Requirements ==

* The `boat` custom post type with the bundled ACF field group (Advanced Custom Fields).
* PHP 7.4+ with the GD or Imagick extension for image rendering in PDFs.

== Changelog ==

= 1.0.0 =
* Initial release: server-side PDF spec sheets + [boat_pdf_button] shortcode.
