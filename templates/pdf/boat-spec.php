<?php
/**
 * Master PDF template. Rendered by PdfRenderer.
 *
 * @var array<string,mixed> $boat     Normalized boat data from BoatData.
 * @var array<string,mixed> $branding Branding values (company, logo, primary, accent, font).
 * @var string              $css      Stylesheet contents (brand tokens already replaced).
 *
 * @package PrestigeYacht\Extras
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pye_dir = defined( 'PYE_DIR' ) ? PYE_DIR : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title><?php echo esc_html( $boat['title'] ); ?></title>
	<style><?php echo $css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- internal CSS. ?></style>
</head>
<body>

	<?php require $pye_dir . 'templates/pdf/partials/header-footer.php'; ?>

	<?php require $pye_dir . 'templates/pdf/partials/cover.php'; ?>

	<?php require $pye_dir . 'templates/pdf/partials/specs-table.php'; ?>

	<?php require $pye_dir . 'templates/pdf/partials/engines.php'; ?>

	<?php if ( '' !== trim( (string) $boat['description'] ) || '' !== trim( (string) $boat['additional'] ) ) : ?>
		<div class="section">
			<h2>Description</h2>
			<?php if ( '' !== trim( (string) $boat['description'] ) ) : ?>
				<div class="description"><?php echo esc_html( $boat['description'] ); ?></div>
			<?php endif; ?>
			<?php if ( '' !== trim( (string) $boat['additional'] ) ) : ?>
				<div class="description" style="margin-top:8px;"><?php echo esc_html( $boat['additional'] ); ?></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php require $pye_dir . 'templates/pdf/partials/photo-grid.php'; ?>

</body>
</html>
