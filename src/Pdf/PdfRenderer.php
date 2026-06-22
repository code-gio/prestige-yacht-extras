<?php
declare( strict_types=1 );

namespace PrestigeYacht\Extras\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use PrestigeYacht\Extras\Boat\BoatData;

/**
 * Renders the boat spec template to HTML and converts it to PDF bytes with Dompdf.
 */
final class PdfRenderer {

	/**
	 * @param int $post_id Boat post ID.
	 * @return string Raw PDF bytes.
	 */
	public function render( int $post_id ): string {
		$boat = BoatData::for_post( $post_id );
		$html = $this->html( $boat );

		$options = new Options();
		$options->set( 'isRemoteEnabled', false ); // images are local file paths.
		$options->set( 'isHtml5ParserEnabled', true );
		$options->set( 'defaultFont', 'DejaVu Sans' );
		$options->set( 'chroot', PYE_DIR ); // restrict file access to the plugin dir + uploads.

		// Allow Dompdf to read the WordPress uploads dir where gallery images live.
		$uploads = wp_get_upload_dir();
		if ( empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) ) {
			$options->set( 'chroot', [ PYE_DIR, $uploads['basedir'] ] );
		}

		$dompdf = new Dompdf( $options );
		$dompdf->loadHtml( $html, 'UTF-8' );
		$dompdf->setPaper( 'letter', 'portrait' );
		$dompdf->render();

		return (string) $dompdf->output();
	}

	/**
	 * Render the PHP template to an HTML string.
	 *
	 * @param array<string,mixed> $boat Normalized boat data.
	 */
	private function html( array $boat ): string {
		// Variables exposed to the template + partials.
		$branding = [
			'company'   => Branding::COMPANY,
			'logo'      => Branding::logo_path(),
			'primary'   => Branding::PRIMARY,
			'accent'    => Branding::ACCENT,
			'font'      => Branding::FONT_FAMILY,
		];

		// Dompdf has weak support for CSS custom properties, so inline the brand values via tokens.
		$css = strtr(
			(string) file_get_contents( PYE_DIR . 'assets/pdf/pdf.css' ),
			[
				'{{PRIMARY}}' => Branding::PRIMARY,
				'{{ACCENT}}'  => Branding::ACCENT,
				'{{FONT}}'    => Branding::FONT_FAMILY,
			]
		);

		ob_start();
		// $boat, $branding, $css are in scope inside the template.
		include PYE_DIR . 'templates/pdf/boat-spec.php';
		return (string) ob_get_clean();
	}
}
