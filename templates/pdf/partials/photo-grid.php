<?php
/**
 * Gallery photo grid, on its own page(s). 2 columns; Dompdf paginates naturally.
 *
 * @var array<string,mixed> $boat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gallery = $boat['gallery'] ?? [];

// Drop the hero from the grid if it is the same file, to avoid duplication.
if ( '' !== ( $boat['hero'] ?? '' ) ) {
	$gallery = array_values( array_filter( $gallery, static fn( $p ) => $p !== $boat['hero'] ) );
}

if ( empty( $gallery ) ) {
	return;
}
?>
<div class="photos-page">
	<div class="section">
		<h2>Photo Gallery</h2>
		<table class="photo-grid">
			<?php
			$chunks = array_chunk( $gallery, 2 );
			foreach ( $chunks as $pair ) :
				?>
				<tr>
					<?php foreach ( $pair as $path ) : ?>
						<td><img src="<?php echo esc_attr( $path ); ?>" alt=""></td>
					<?php endforeach; ?>
					<?php if ( 1 === count( $pair ) ) : ?>
						<td></td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>
</div>
