<?php
declare( strict_types=1 );

namespace PrestigeYacht\Extras\Archive;

/**
 * Handles the AJAX query for the boat archive grid.
 */
final class AjaxController {

	/**
	 * Respond to a filter/load-more request with rendered cards + pagination state.
	 */
	public function handle(): void {
		check_ajax_referer( ArchiveShortcode::NONCE_ACTION, 'nonce' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
		$raw      = wp_unslash( $_POST );
		$per_page = isset( $raw['per_page'] ) ? max( 1, (int) $raw['per_page'] ) : BoatQuery::DEFAULT_PER_PAGE;
		$filters  = BoatQuery::sanitize( $raw );

		$query = BoatQuery::run( $filters, $per_page );

		wp_send_json_success(
			[
				'html'     => ArchiveShortcode::render_cards( $query ),
				'has_more' => (int) $filters['paged'] < (int) $query->max_num_pages,
				'total'    => (int) $query->found_posts,
				'paged'    => (int) $filters['paged'],
			]
		);
	}
}
