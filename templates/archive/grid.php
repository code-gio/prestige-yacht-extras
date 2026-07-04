<?php
/**
 * Results grid wrapper: cards + load-more + empty state.
 *
 * @var string $cards_html   Rendered card markup for the first page.
 * @var bool   $has_more     Whether more pages exist.
 * @var int    $total        Total matching boats.
 * @var int    $showing_from First visible result number (1-based; 0 when empty).
 * @var int    $showing_to   Last visible result number.
 * @var int    $columns      Grid columns on desktop.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pye-results" data-columns="<?php echo (int) $columns; ?>">
	<p class="pye-count"<?php echo $total > 0 ? '' : ' hidden'; ?>>
		<?php
		printf(
			/* translators: 1: visible result range (e.g. 1–12), 2: total result count. */
			esc_html__( 'Showing %1$s of %2$s yachts', 'prestige-yacht-extras' ),
			'<strong class="pye-count__range">' . esc_html( $showing_from . '–' . $showing_to ) . '</strong>',
			'<strong class="pye-count__total">' . esc_html( number_format_i18n( $total ) ) . '</strong>'
		);
		?>
	</p>

	<div class="pye-grid" style="--pye-cols: <?php echo (int) $columns; ?>;">
		<?php
		echo $cards_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped card markup.
		?>
	</div>

	<p class="pye-empty"<?php echo $total > 0 ? ' hidden' : ''; ?>>
		<?php esc_html_e( 'No vessels match your search.', 'prestige-yacht-extras' ); ?>
	</p>

	<p class="pye-error" hidden role="alert">
		<?php esc_html_e( 'Something went wrong. Please try again.', 'prestige-yacht-extras' ); ?>
	</p>

	<div class="pye-loadmore-wrap"<?php echo $has_more ? '' : ' hidden'; ?>>
		<button type="button" class="pye-loadmore"><?php esc_html_e( 'Load More', 'prestige-yacht-extras' ); ?></button>
	</div>
</div>
