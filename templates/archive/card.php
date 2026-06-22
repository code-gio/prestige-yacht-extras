<?php
/**
 * Single boat card. Matches the site's listing card.
 *
 * @var array{id:int,permalink:string,title:string,location:string,price:string,thumb_id:int} $card
 * @var string $logo_url Bundled Prestige logo URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<a class="pye-card" href="<?php echo esc_url( $card['permalink'] ); ?>">
	<div class="pye-card__media">
		<?php
		if ( $card['thumb_id'] ) {
			echo get_the_post_thumbnail( $card['id'], 'large', [ 'class' => 'pye-card__img', 'loading' => 'lazy', 'alt' => esc_attr( $card['title'] ) ] );
		} else {
			echo '<span class="pye-card__noimg"></span>';
		}
		?>
	</div>
	<div class="pye-card__body">
		<h3 class="pye-card__title"><?php echo esc_html( $card['title'] ); ?></h3>

		<?php if ( '' !== $card['location'] ) : ?>
			<div class="pye-card__loc">
				<svg class="pye-card__pin" viewBox="0 0 384 512" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"/></svg>
				<span><?php echo esc_html( $card['location'] ); ?></span>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $card['price'] ) : ?>
			<div class="pye-card__price"><?php echo esc_html( $card['price'] ); ?></div>
		<?php endif; ?>
	</div>
	<?php if ( '' !== $logo_url ) : ?>
		<div class="pye-card__footer">
			<img class="pye-card__logo" src="<?php echo esc_url( $logo_url ); ?>" alt="Prestige Yacht Sales" loading="lazy">
		</div>
	<?php endif; ?>
</a>
