<?php
/**
 * "Yacht Search" filter bar.
 *
 * @var array<string,mixed> $filters       Current sanitized filter values.
 * @var array<int,string>   $manufacturers Distinct manufacturer options.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cat = (string) $filters['category'];
?>
<form class="pye-filters" role="search" aria-label="<?php esc_attr_e( 'Yacht search', 'prestige-yacht-extras' ); ?>">
	<h2 class="pye-filters__title"><?php esc_html_e( 'Yacht Search', 'prestige-yacht-extras' ); ?></h2>

	<div class="pye-filters__row">
		<div class="pye-seg" role="group" aria-label="<?php esc_attr_e( 'Category', 'prestige-yacht-extras' ); ?>">
			<?php foreach ( [ '' => __( 'All', 'prestige-yacht-extras' ), 'Power' => __( 'Power', 'prestige-yacht-extras' ), 'Sail' => __( 'Sail', 'prestige-yacht-extras' ) ] as $val => $label ) : ?>
				<button type="button" class="pye-seg__btn<?php echo $cat === $val ? ' is-active' : ''; ?>" data-filter="category" data-value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></button>
			<?php endforeach; ?>
		</div>

		<div class="pye-seg" role="group" aria-label="<?php esc_attr_e( 'Condition', 'prestige-yacht-extras' ); ?>">
			<?php foreach ( [ '' => __( 'All', 'prestige-yacht-extras' ), 'New' => __( 'New', 'prestige-yacht-extras' ), 'Used' => __( 'Used', 'prestige-yacht-extras' ) ] as $val => $label ) : ?>
				<button type="button" class="pye-seg__btn<?php echo (string) $filters['condition'] === $val ? ' is-active' : ''; ?>" data-filter="condition" data-value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></button>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="pye-filters__row">
		<label class="pye-field">
			<span class="pye-field__label"><?php esc_html_e( 'Manufacturer', 'prestige-yacht-extras' ); ?></span>
			<select class="pye-field__input" data-filter="mfr">
				<option value=""><?php esc_html_e( 'All Manufacturers', 'prestige-yacht-extras' ); ?></option>
				<?php foreach ( $manufacturers as $m ) : ?>
					<option value="<?php echo esc_attr( $m ); ?>" <?php selected( (string) $filters['mfr'], $m ); ?>><?php echo esc_html( $m ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>

		<label class="pye-field pye-field--sm">
			<span class="pye-field__label"><?php esc_html_e( 'Minimum Length (ft)', 'prestige-yacht-extras' ); ?></span>
			<input type="number" min="0" inputmode="numeric" class="pye-field__input" data-filter="len_min" placeholder="<?php esc_attr_e( 'Min', 'prestige-yacht-extras' ); ?>" value="<?php echo esc_attr( $filters['len_min'] ); ?>">
		</label>

		<label class="pye-field pye-field--sm">
			<span class="pye-field__label"><?php esc_html_e( 'Maximum Length (ft)', 'prestige-yacht-extras' ); ?></span>
			<input type="number" min="0" inputmode="numeric" class="pye-field__input" data-filter="len_max" placeholder="<?php esc_attr_e( 'Max', 'prestige-yacht-extras' ); ?>" value="<?php echo esc_attr( $filters['len_max'] ); ?>">
		</label>
	</div>

	<div class="pye-filters__row">
		<label class="pye-field pye-field--sm">
			<span class="pye-field__label"><?php esc_html_e( 'Minimum Price', 'prestige-yacht-extras' ); ?></span>
			<input type="number" min="0" inputmode="numeric" class="pye-field__input" data-filter="price_min" placeholder="<?php esc_attr_e( 'Min', 'prestige-yacht-extras' ); ?>" value="<?php echo esc_attr( $filters['price_min'] ); ?>">
		</label>

		<label class="pye-field pye-field--sm">
			<span class="pye-field__label"><?php esc_html_e( 'Maximum Price', 'prestige-yacht-extras' ); ?></span>
			<input type="number" min="0" inputmode="numeric" class="pye-field__input" data-filter="price_max" placeholder="<?php esc_attr_e( 'Max', 'prestige-yacht-extras' ); ?>" value="<?php echo esc_attr( $filters['price_max'] ); ?>">
		</label>

		<button type="submit" class="pye-filters__submit"><?php esc_html_e( 'Search Vessels', 'prestige-yacht-extras' ); ?></button>
	</div>
</form>
