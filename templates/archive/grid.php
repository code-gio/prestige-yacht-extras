<?php
/**
 * Results grid wrapper: cards + load-more + empty state.
 *
 * @var string $cards_html Rendered card markup for the first page.
 * @var bool   $has_more   Whether more pages exist.
 * @var int    $total      Total matching boats.
 * @var int    $columns    Grid columns on desktop.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pye-results" data-columns="<?php echo (int) $columns; ?>">
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
