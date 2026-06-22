<?php
declare( strict_types=1 );

namespace PrestigeYacht\Extras\Pdf;

/**
 * Central branding config for the PDF. Single edit point when real assets arrive.
 *
 * To apply real branding:
 *   1. Drop the logo file into assets/brand/ (PNG or JPG render best in Dompdf; SVG is not supported).
 *   2. Set LOGO_FILE to its filename.
 *   3. Set PRIMARY / ACCENT to your brand hex colors.
 *   4. Set FONT_FAMILY (a CSS font stack; for a custom font, also add an @font-face in pdf.css).
 */
final class Branding {

	/** Logo filename inside assets/brand/. */
	private const LOGO_FILE = 'prestige-ys-logo.png';

	/** Primary brand color — deep navy from the logo. */
	public const PRIMARY = '#13294b';

	/** Accent color — gold diamond accents from the logo. */
	public const ACCENT = '#b8962f';

	/** CSS font stack for the document body. */
	public const FONT_FAMILY = 'DejaVu Sans, sans-serif';

	/** Company name shown in the header / footer. */
	public const COMPANY = 'Prestige Yacht Sales';

	/**
	 * Absolute path to the logo, or '' if it is missing.
	 */
	public static function logo_path(): string {
		$path = PYE_DIR . 'assets/brand/' . self::LOGO_FILE;
		return is_readable( $path ) ? $path : '';
	}
}
