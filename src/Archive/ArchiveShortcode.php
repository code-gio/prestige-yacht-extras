<?php
declare( strict_types=1 );

namespace PrestigeYacht\Extras\Archive;

use PrestigeYacht\Extras\Boat\BoatData;
use PrestigeYacht\Extras\Pdf\Branding;
use WP_Query;

/**
 * [boat_archive] — filterable, AJAX-driven grid of boat listings.
 *
 * Attributes:
 *   per_page  Cards per page / per Load More (default 12).
 *   columns   Desktop grid columns (default 3).
 */
final class ArchiveShortcode {

	public const AJAX_ACTION  = 'boat_archive_query';
	public const NONCE_ACTION = 'pye_boat_archive';

	private bool $assets_registered = false;

	/**
	 * Register front-end asset handles (enqueued on demand when the shortcode runs).
	 */
	public function register_assets(): void {
		wp_register_style( 'pye-archive', PYE_URL . 'assets/css/archive.css', [], PYE_VERSION );
		wp_register_script( 'pye-archive', PYE_URL . 'assets/js/archive.js', [], PYE_VERSION, true );
		$this->assets_registered = true;
	}

	/**
	 * Shortcode handler.
	 *
	 * @param array<string,string>|string $atts
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			[
				'per_page'     => (string) BoatQuery::DEFAULT_PER_PAGE,
				'columns'      => '3',
				'category'     => '', // preset Power|Sail
				'manufacturer' => '', // preset manufacturer
				'len_min'      => '',
				'len_max'      => '',
				'lock'         => '', // comma list of locked keys: category,manufacturer,length
			],
			$atts,
			'boat_archive'
		);

		$per_page = max( 1, (int) $atts['per_page'] );
		$columns  = min( 4, max( 1, (int) $atts['columns'] ) );

		// Which filters are page-fixed (enforced + hidden from the bar).
		$locked = array_filter( array_map( 'trim', explode( ',', strtolower( (string) $atts['lock'] ) ) ) );

		// Preset defaults from shortcode attributes.
		$presets = BoatQuery::sanitize(
			[
				'category' => $atts['category'],
				'mfr'      => $atts['manufacturer'],
				'len_min'  => $atts['len_min'],
				'len_max'  => $atts['len_max'],
			]
		);

		// Merge presets with the URL: locked keys always use the preset; others let the URL win.
		$get     = BoatQuery::sanitize( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only public filter, sanitized.
		$filters = [
			'category' => in_array( 'category', $locked, true ) ? $presets['category'] : ( '' !== $get['category'] ? $get['category'] : $presets['category'] ),
			'mfr'      => in_array( 'manufacturer', $locked, true ) ? $presets['mfr'] : ( '' !== $get['mfr'] ? $get['mfr'] : $presets['mfr'] ),
			'len_min'  => in_array( 'length', $locked, true ) ? $presets['len_min'] : ( '' !== $get['len_min'] ? $get['len_min'] : $presets['len_min'] ),
			'len_max'  => in_array( 'length', $locked, true ) ? $presets['len_max'] : ( '' !== $get['len_max'] ? $get['len_max'] : $presets['len_max'] ),
			'paged'    => $get['paged'],
		];

		if ( ! $this->assets_registered ) {
			$this->register_assets();
		}
		wp_enqueue_style( 'pye-archive' );
		wp_enqueue_script( 'pye-archive' );
		wp_localize_script(
			'pye-archive',
			'pyeArchive',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::AJAX_ACTION,
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
				'perPage' => $per_page,
			]
		);

		$query = BoatQuery::run( $filters, $per_page );

		$cards_html    = self::render_cards( $query );
		$has_more      = (int) $filters['paged'] < (int) $query->max_num_pages;
		$total         = (int) $query->found_posts;
		$manufacturers = ManufacturerOptions::get();

		ob_start();
		echo '<div class="pye-archive">';
		require PYE_DIR . 'templates/archive/filter-bar.php';
		require PYE_DIR . 'templates/archive/grid.php';
		echo '</div>';
		return (string) ob_get_clean();
	}

	/**
	 * Render all posts in a query to card markup. Shared by the shortcode and AJAX.
	 */
	public static function render_cards( WP_Query $query ): string {
		if ( ! $query->have_posts() ) {
			return '';
		}
		$logo_url = Branding::logo_url();

		ob_start();
		while ( $query->have_posts() ) {
			$query->the_post();
			$card = BoatData::card( get_the_ID() );
			require PYE_DIR . 'templates/archive/card.php';
		}
		wp_reset_postdata();
		return (string) ob_get_clean();
	}
}
