<?php
/**
 * "Yacht Search" filter bar.
 *
 * @var array<string,mixed> $filters       Current filter values.
 * @var array<int,string>   $manufacturers Distinct manufacturer options.
 * @var array<int,string>   $locked        Filter keys that are page-fixed (hidden + enforced).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$locked       = isset( $locked ) ? (array) $locked : [];
$lock_cat     = in_array( 'category', $locked, true );
$lock_mfr     = in_array( 'manufacturer', $locked, true );
$lock_len     = in_array( 'length', $locked, true );
$cat          = (string) $filters['category'];
?>
<form class="pye-filters" role="search" aria-label="<?php esc_attr_e( 'Yacht search', 'prestige-yacht-extras' ); ?>">
	<h2 class="pye-filters__title"><?php esc_html_e( 'Yacht Search', 'prestige-yacht-extras' ); ?></h2>

	<?php // Model has no visible control — it is a page-level filter that rides along with every search. ?>
	<?php if ( '' !== (string) $filters['model'] ) : ?>
		<input type="hidden" data-filter="model" value="<?php echo esc_attr( (string) $filters['model'] ); ?>">
	<?php endif; ?>

	<div class="pye-filters__row">
		<?php if ( $lock_cat ) : ?>
			<input type="hidden" data-filter="category" value="<?php echo esc_attr( $cat ); ?>">
		<?php else : ?>
			<div class="pye-seg" role="group" aria-label="<?php esc_attr_e( 'Category', 'prestige-yacht-extras' ); ?>">
				<?php foreach ( [ '' => __( 'All', 'prestige-yacht-extras' ), 'Power' => __( 'Power', 'prestige-yacht-extras' ), 'Sail' => __( 'Sail', 'prestige-yacht-extras' ) ] as $val => $label ) : ?>
					<button type="button" class="pye-seg__btn<?php echo $cat === $val ? ' is-active' : ''; ?>" data-filter="category" data-value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="pye-filters__row">
		<?php if ( $lock_mfr ) : ?>
			<input type="hidden" data-filter="mfr" value="<?php echo esc_attr( (string) $filters['mfr'] ); ?>">
		<?php else : ?>
			<label class="pye-field">
				<span class="pye-field__label"><?php esc_html_e( 'Manufacturer', 'prestige-yacht-extras' ); ?></span>
				<select class="pye-field__input" data-filter="mfr">
					<option value=""><?php esc_html_e( 'All Manufacturers', 'prestige-yacht-extras' ); ?></option>
					<?php foreach ( $manufacturers as $m ) : ?>
						<option value="<?php echo esc_attr( $m ); ?>" <?php selected( (string) $filters['mfr'], $m ); ?>><?php echo esc_html( $m ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		<?php endif; ?>

		<?php if ( $lock_len ) : ?>
			<input type="hidden" data-filter="len_min" value="<?php echo esc_attr( $filters['len_min'] ); ?>">
			<input type="hidden" data-filter="len_max" value="<?php echo esc_attr( $filters['len_max'] ); ?>">
		<?php else : ?>
			<label class="pye-field pye-field--sm">
				<span class="pye-field__label"><?php esc_html_e( 'Minimum Length (ft)', 'prestige-yacht-extras' ); ?></span>
				<input type="number" min="0" inputmode="numeric" class="pye-field__input" data-filter="len_min" placeholder="<?php esc_attr_e( 'Min', 'prestige-yacht-extras' ); ?>" value="<?php echo esc_attr( $filters['len_min'] ); ?>">
			</label>

			<label class="pye-field pye-field--sm">
				<span class="pye-field__label"><?php esc_html_e( 'Maximum Length (ft)', 'prestige-yacht-extras' ); ?></span>
				<input type="number" min="0" inputmode="numeric" class="pye-field__input" data-filter="len_max" placeholder="<?php esc_attr_e( 'Max', 'prestige-yacht-extras' ); ?>" value="<?php echo esc_attr( $filters['len_max'] ); ?>">
			</label>
		<?php endif; ?>

		<button type="submit" class="pye-filters__submit"><?php esc_html_e( 'Search Vessels', 'prestige-yacht-extras' ); ?></button>
	</div>
</form>
