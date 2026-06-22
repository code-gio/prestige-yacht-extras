<?php
/**
 * Engines repeater table.
 *
 * @var array<string,mixed> $boat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$engines = $boat['engines'] ?? [];
if ( empty( $engines ) ) {
	return;
}

// Build the union of columns present across all engine rows, preserving order.
$columns = [];
foreach ( $engines as $row ) {
	foreach ( array_keys( $row ) as $col ) {
		$columns[ $col ] = true;
	}
}
$columns = array_keys( $columns );
?>
<div class="section">
	<h2>Engines</h2>
	<table class="engines-table">
		<thead>
			<tr>
				<?php foreach ( $columns as $col ) : ?>
					<th><?php echo esc_html( $col ); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $engines as $row ) : ?>
				<tr>
					<?php foreach ( $columns as $col ) : ?>
						<td><?php echo esc_html( $row[ $col ] ?? '' ); ?></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
