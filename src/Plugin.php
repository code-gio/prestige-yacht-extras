<?php
declare( strict_types=1 );

namespace PrestigeYacht\Extras;

use PrestigeYacht\Extras\Pdf\PdfController;
use PrestigeYacht\Extras\Shortcodes\PdfButtonShortcode;
use PrestigeYacht\Extras\Support\Assets;

/**
 * Wires every hook the plugin needs. Single source of truth for what the plugin does.
 */
final class Plugin {

	/**
	 * The custom post type the plugin operates on.
	 */
	public const POST_TYPE = 'boat';

	private PdfController $pdf_controller;

	public function __construct() {
		$this->pdf_controller = new PdfController();
	}

	/**
	 * Register every hook. Called on `plugins_loaded`.
	 */
	public function boot(): void {
		// ACF Local JSON: keep the boat field group in sync from acf-json/.
		add_filter( 'acf/settings/load_json', [ $this, 'register_acf_json_path' ] );

		// PDF endpoint + rendering.
		add_action( 'init', [ $this->pdf_controller, 'register_rewrite' ] );
		add_action( 'init', [ $this, 'maybe_flush_rewrite' ], 11 );
		add_filter( 'query_vars', [ $this->pdf_controller, 'register_query_var' ] );
		add_action( 'template_redirect', [ $this->pdf_controller, 'maybe_render' ] );

		// Shortcode + assets.
		add_shortcode( 'boat_pdf_button', [ new PdfButtonShortcode(), 'render' ] );
		add_action( 'wp_enqueue_scripts', [ new Assets(), 'enqueue' ] );
	}

	/**
	 * Register the rewrite rules only (used by the activation hook before flushing).
	 */
	public function register_rewrite(): void {
		$this->pdf_controller->register_rewrite();
	}

	/**
	 * Self-healing rewrite flush: runs once after each install/update so the PDF URL
	 * works without anyone visiting Settings → Permalinks. Gated by a version option,
	 * so it does not flush on every request.
	 */
	public function maybe_flush_rewrite(): void {
		if ( get_option( 'pye_rewrite_version' ) !== PYE_VERSION ) {
			flush_rewrite_rules();
			update_option( 'pye_rewrite_version', PYE_VERSION );
		}
	}

	/**
	 * Tell ACF to load the bundled field group JSON.
	 *
	 * @param array<int,string> $paths Existing load paths.
	 * @return array<int,string>
	 */
	public function register_acf_json_path( array $paths ): array {
		$paths[] = PYE_DIR . 'acf-json';
		return $paths;
	}
}
