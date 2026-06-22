<?php
/**
 * Grouped spec tables, one section per group.
 *
 * @var array<string,mixed> $boat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sections = $boat['sections'] ?? [];
?>
<?php foreach ( $sections as $name => $rows ) : ?>
	<div class="section">
		<h2><?php echo esc_html( $name ); ?></h2>
		<table class="spec-table">
			<?php foreach ( $rows as $label => $value ) : ?>
				<tr>
					<td><?php echo esc_html( $label ); ?></td>
					<td><?php echo esc_html( $value ); ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>
<?php endforeach; ?>
