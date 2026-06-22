<?php
declare( strict_types=1 );

namespace PrestigeYacht\Extras\Shortcodes;

use PrestigeYacht\Extras\Pdf\PdfController;
use PrestigeYacht\Extras\Plugin;

/**
 * [boat_pdf_button] — renders a "Download PDF" link for a boat.
 *
 * Attributes:
 *   id    Optional boat post ID. Defaults to the current post.
 *   label Optional button text. Defaults to "Download PDF".
 */
final class PdfButtonShortcode {

	/**
	 * @param array<string,string>|string $atts
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			[
				'id'    => '',
				'label' => __( 'Download PDF', 'prestige-yacht-extras' ),
			],
			$atts,
			'boat_pdf_button'
		);

		$post_id = '' !== $atts['id'] ? (int) $atts['id'] : (int) get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$post = get_post( $post_id );
		if ( ! $post || Plugin::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return '';
		}

		$url = PdfController::url_for( $post_id );
		if ( '' === $url ) {
			return '';
		}

		return sprintf(
			'<a class="pye-pdf-button" href="%s" rel="nofollow">%s%s</a>',
			esc_url( $url ),
			$this->icon(),
			esc_html( $atts['label'] )
		);
	}

	private function icon(): string {
		return '<svg class="pye-pdf-button__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';
	}
}
