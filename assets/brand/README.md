# Brand assets

Drop your logo here and point `src/Pdf/Branding.php` at it.

## Logo

1. Add your logo file to this folder. **Use PNG or JPG** — Dompdf does **not** render SVG.
   Recommended: a horizontal logo around 400–600px wide on a transparent or white background.
2. In `src/Pdf/Branding.php`, set:
   ```php
   private const LOGO_FILE = 'your-logo.png';
   ```
   If the file is missing, the header falls back to the company name as text.

## Colors & font

Also in `src/Pdf/Branding.php`:

```php
public const PRIMARY     = '#1a2b4a'; // headers, title, price chip
public const ACCENT      = '#b8932f'; // rules, labels, engine table header
public const FONT_FAMILY = 'DejaVu Sans, sans-serif';
public const COMPANY     = 'Prestige Yacht';
```

For a **custom font**, add an `@font-face` to `assets/pdf/pdf.css` pointing at a `.ttf`
placed in this folder, then set `FONT_FAMILY` to its name. Dompdf bundles DejaVu Sans,
which covers most needs without extra setup.
