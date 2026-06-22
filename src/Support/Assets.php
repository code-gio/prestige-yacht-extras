<?php
declare( strict_types=1 );

namespace PrestigeYacht\Extras\Support;

/**
 * Enqueues front-end assets.
 */
final class Assets {

	public function enqueue(): void {
		wp_enqueue_style(
			'pye-button',
			PYE_URL . 'assets/css/button.css',
			[],
			PYE_VERSION
		);
	}
}
