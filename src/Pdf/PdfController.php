<?php
declare( strict_types=1 );

namespace PrestigeYacht\Extras\Pdf;

use PrestigeYacht\Extras\Plugin;

/**
 * Handles the PDF request: clean URL `/boat/{slug}/pdf/` plus a `?boat_pdf={id}` fallback.
 * Validates the target, renders the PDF, and streams it as a download.
 */
final class PdfController {

	private const QUERY_VAR = 'boat_pdf';
	private const ENDPOINT  = 'pdf';

	/**
	 * Register an explicit rewrite rule for `/boat/{slug}/pdf/`.
	 *
	 * Scoped to the boat CPT slug (confirmed by the live URL structure), which is more
	 * reliable for custom post types than add_rewrite_endpoint( EP_PERMALINK ).
	 */
	public function register_rewrite(): void {
		add_rewrite_rule(
			'^' . Plugin::POST_TYPE . '/([^/]+)/' . self::ENDPOINT . '/?$',
			'index.php?' . Plugin::POST_TYPE . '=$matches[1]&' . self::QUERY_VAR . '=1',
			'top'
		);
	}

	/**
	 * @param array<int,string> $vars
	 * @return array<int,string>
	 */
	public function register_query_var( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Detect a PDF request and stream it. Runs on template_redirect.
	 */
	public function maybe_render(): void {
		$post_id = $this->resolve_post_id();
		if ( null === $post_id ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post || Plugin::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			wp_die(
				esc_html__( 'Boat listing not found.', 'prestige-yacht-extras' ),
				esc_html__( 'Not found', 'prestige-yacht-extras' ),
				[ 'response' => 404 ]
			);
		}

		try {
			$renderer = new PdfRenderer();
			$pdf      = $renderer->render( $post_id );
		} catch ( \Throwable $e ) {
			error_log( '[Prestige Yacht Extras] PDF render failed: ' . $e->getMessage() );
			wp_die(
				esc_html__( 'Sorry, the PDF could not be generated. Please try again later.', 'prestige-yacht-extras' ),
				esc_html__( 'PDF error', 'prestige-yacht-extras' ),
				[ 'response' => 500 ]
			);
		}

		$this->stream( $pdf, $post->post_name ?: ( 'boat-' . $post_id ) );
	}

	/**
	 * Figure out which boat is being requested, from the endpoint or the fallback query var.
	 */
	private function resolve_post_id(): ?int {
		$flag = get_query_var( self::QUERY_VAR );

		// Raw GET fallback so plain-permalink links work even before a rewrite flush.
		if ( ( '' === $flag || null === $flag ) && isset( $_GET[ self::QUERY_VAR ] ) ) {
			$flag = sanitize_text_field( wp_unslash( $_GET[ self::QUERY_VAR ] ) );
		}

		if ( '' === $flag || null === $flag ) {
			return null;
		}

		// Pretty URL (/boat/{slug}/pdf/) and ?boat_pdf={id} both resolve the boat in the main query.
		if ( is_singular( Plugin::POST_TYPE ) ) {
			return (int) get_queried_object_id();
		}

		// Otherwise treat the flag as an explicit post ID.
		$id = (int) $flag;
		return $id > 1 ? $id : null;
	}

	/**
	 * Send the PDF to the browser as an attachment and stop.
	 */
	private function stream( string $pdf, string $slug ): void {
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $slug ) . '.pdf"' );
		header( 'Content-Length: ' . strlen( $pdf ) );
		echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binary PDF.
		exit;
	}

	/**
	 * Build the public PDF URL for a boat.
	 */
	public static function url_for( int $post_id ): string {
		$permalink = get_permalink( $post_id );
		if ( ! $permalink ) {
			return '';
		}

		// Pretty permalinks: append the endpoint. Plain permalinks: use the query var.
		if ( get_option( 'permalink_structure' ) ) {
			return trailingslashit( $permalink ) . self::ENDPOINT . '/';
		}
		return add_query_arg( self::QUERY_VAR, $post_id, $permalink );
	}
}
