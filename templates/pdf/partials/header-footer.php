<?php
/**
 * Fixed running header + footer (repeat on every PDF page).
 *
 * @var array<string,mixed> $boat
 * @var array<string,mixed> $branding
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$broker = $boat['broker'] ?? [];
$broker_line = array_filter(
	[
		$broker['Sales Rep'] ?? '',
		$broker['Phone'] ?? '',
		$broker['Email'] ?? '',
	]
);
?>
<div class="doc-header">
	<table style="width:100%; border:0;">
		<tr>
			<td style="border:0; vertical-align:middle;">
				<?php if ( '' !== $branding['logo'] ) : ?>
					<img class="logo" src="<?php echo esc_attr( $branding['logo'] ); ?>" alt="">
				<?php else : ?>
					<span class="company"><?php echo esc_html( $branding['company'] ); ?></span>
				<?php endif; ?>
			</td>
			<td style="border:0; text-align:right; vertical-align:middle;">
				<span class="tagline">Yacht Specification Sheet</span>
			</td>
		</tr>
	</table>
</div>

<div class="doc-footer">
	<table style="width:100%; border:0;">
		<tr>
			<td style="border:0;">
				<?php if ( ! empty( $broker_line ) ) : ?>
					<span class="broker"><?php echo esc_html( implode( '  •  ', $broker_line ) ); ?></span>
				<?php else : ?>
					<span class="broker"><?php echo esc_html( $branding['company'] ); ?></span>
				<?php endif; ?>
			</td>
			<td style="border:0; text-align:right;">
				Page <span class="page-num"></span>
			</td>
		</tr>
	</table>
</div>
