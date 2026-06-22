<?php
/**
 * Cover block: hero image, title, location, price, key facts.
 *
 * @var array<string,mixed> $boat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h1 class="cover-title"><?php echo esc_html( $boat['title'] ); ?></h1>

<?php if ( '' !== $boat['location'] ) : ?>
	<div class="cover-meta"><?php echo esc_html( $boat['location'] ); ?></div>
<?php endif; ?>

<?php if ( '' !== $boat['price'] ) : ?>
	<div class="cover-price"><?php echo esc_html( $boat['price'] ); ?></div>
<?php endif; ?>

<?php if ( '' !== $boat['hero'] ) : ?>
	<div class="hero">
		<img src="<?php echo esc_attr( $boat['hero'] ); ?>" alt="">
	</div>
<?php endif; ?>

<?php if ( ! empty( $boat['key_facts'] ) ) : ?>
	<table class="key-facts">
		<tr>
			<?php foreach ( $boat['key_facts'] as $label => $value ) : ?>
				<td>
					<span class="kf-label"><?php echo esc_html( $label ); ?></span>
					<span class="kf-value"><?php echo esc_html( $value ); ?></span>
				</td>
			<?php endforeach; ?>
		</tr>
	</table>
<?php endif; ?>
