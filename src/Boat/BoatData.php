<?php
declare( strict_types=1 );

namespace PrestigeYacht\Extras\Boat;

/**
 * Reads a boat's ACF / post-meta fields and returns a normalized structure for rendering.
 *
 * Empty values are dropped so templates can simply skip absent rows. Image fields are
 * resolved to local filesystem paths so Dompdf can read them off disk (no remote fetch).
 */
final class BoatData {

	/** Shown instead of a $0 or blank price when no usable price is available. */
	private const CALL_FOR_PRICING = 'Call for Pricing';

	/**
	 * Build the normalized data array for a boat post.
	 *
	 * @param int $post_id Boat post ID.
	 * @return array<string,mixed>
	 */
	public static function for_post( int $post_id ): array {
		$self = new self( $post_id );
		return $self->build();
	}

	private int $post_id;

	private function __construct( int $post_id ) {
		$this->post_id = $post_id;
	}

	/**
	 * Minimal data for an archive/grid card. Reuses the title/location/price formatting
	 * without the heavy gallery path resolution used by the PDF.
	 *
	 * @return array{id:int,permalink:string,title:string,location:string,callout:string,price:string,thumb_id:int}
	 */
	public static function card( int $post_id ): array {
		$self = new self( $post_id );
		return [
			'id'        => $post_id,
			'permalink' => (string) get_permalink( $post_id ),
			'title'     => $self->title(),
			'location'  => $self->location(),
			// New-boat status ("In Stock" / "On Order"). Manually set; only present on new
			// boats via the field's conditional logic, so used boats read back as ''.
			'callout'   => $self->callout(),
			'price'     => $self->price_display(),
			'thumb_id'  => (int) get_post_thumbnail_id( $post_id ),
		];
	}

	private function build(): array {
		return [
			'id'              => $this->post_id,
			'title'           => $this->title(),
			'price'           => $this->price_display(),
			'location'        => $this->location(),
			'hero'            => $this->hero_image(),
			'gallery'         => $this->gallery_images(),
			'description'     => $this->description(),
			'additional'      => (string) $this->field( 'additional_specs' ),
			'broker'          => $this->non_empty(
				[
					'Sales Rep' => $this->field( 'sales_rep_name' ),
					'Phone'     => $this->field( 'office_phone' ),
					'Email'     => $this->field( 'office_email' ),
					'Stock #'   => $this->field( 'stock_number' ),
				]
			),
			'key_facts'       => $this->non_empty(
				[
					'Year'    => $this->field( 'model_year' ),
					'Length'  => $this->length( 'length_overall' ) ?: $this->length( 'nominal_length' ),
					'Engines' => $this->number( 'num_engines' ),
					'Power'   => $this->hp( 'total_power' ),
				]
			),
			'engines'         => $this->engines(),
			'sections'        => $this->spec_sections(),
		];
	}

	/**
	 * Grouped spec tables. Each section => label => value (already filtered for empties).
	 *
	 * @return array<string,array<string,string>>
	 */
	private function spec_sections(): array {
		// Engine hours carry their own label, so the row is resolved before the table.
		$hours = $this->engine_hours_row();

		$sections = [
			'Overview'       => [
				'Manufacturer' => $this->field( 'manufacturer' ),
				'Model'        => $this->field( 'model' ),
				'Year'         => $this->field( 'model_year' ),
				'Boat Name'    => $this->field( 'boat_name' ),
				'Category'     => $this->field( 'boat_category' ),
				'Class'        => $this->field( 'boat_class' ),
				'Condition'    => $this->field( 'sale_class' ),
				'Hull Material'=> $this->field( 'hull_material' ),
				'Builder'      => $this->field( 'builder_name' ),
				'Designer'     => $this->field( 'designer_name' ),
				'Hull ID'      => $this->field( 'hull_id' ),
			],
			'Dimensions'     => [
				'Nominal Length'     => $this->length( 'nominal_length' ),
				'Length Overall'     => $this->length( 'length_overall' ),
				'Length at Waterline'=> $this->length( 'length_at_waterline' ),
				'Length of Deck'     => $this->length( 'length_of_deck' ),
				'Beam'               => $this->length( 'beam' ),
				'Max Draft'          => $this->length( 'max_draft' ),
				'Drive Up Draft'     => $this->length( 'drive_up' ),
				'Bridge Clearance'   => $this->length( 'bridge_clearance' ),
				'Cabin Headroom'     => $this->length( 'cabin_headroom' ),
				'Freeboard'          => $this->length( 'freeboard' ),
				'Deadrise'           => $this->unit( 'deadrise', '°' ),
				'Displacement'       => $this->weight( 'displacement' ),
				'Dry Weight'         => $this->weight( 'dry_weight' ),
				'Ballast Weight'     => $this->weight( 'ballast_weight' ),
			],
			'Propulsion'     => [
				'Number of Engines' => $this->number( 'num_engines' ),
				'Total Power'       => $this->hp( 'total_power' ),
				$hours['label']     => $hours['value'],
				'Cruising Speed'    => $this->unit( 'cruising_speed', ' kn' ),
				'Maximum Speed'     => $this->unit( 'max_speed', ' kn' ),
				'Range'             => $this->unit( 'range', ' mi' ),
				'Drive Type'        => $this->field( 'drive_type' ),
				'Displacement Type' => $this->field( 'displacement_type' ),
				'Keel Type'         => $this->field( 'keel_type' ),
				'Windlass Type'     => $this->field( 'windlass_type' ),
			],
			'Capacities'     => [
				'Fuel Capacity'        => $this->gallons( 'fuel_capacity' ),
				'Fuel Tank Count'      => $this->number( 'fuel_tank_count' ),
				'Fuel Tank Material'   => $this->field( 'fuel_tank_material' ),
				'Water Capacity'       => $this->gallons( 'water_capacity' ),
				'Water Tank Count'     => $this->number( 'water_tank_count' ),
				'Water Tank Material'  => $this->field( 'water_tank_material' ),
				'Holding Tank Capacity'=> $this->gallons( 'holding_tank_capacity' ),
				'Holding Tank Count'   => $this->number( 'holding_tank_count' ),
				'Holding Tank Material'=> $this->field( 'holding_tank_material' ),
			],
			'Accommodations' => [
				'Cabins'             => $this->number( 'cabins' ),
				'Heads'              => $this->number( 'heads' ),
				'Maximum Passengers' => $this->number( 'max_passengers' ),
				'Convertible Saloon' => $this->yes_no( 'convertible_saloon' ),
				'Electrical Circuit' => $this->unit( 'electrical_circuit', ' V' ),
				'Trim Tabs'          => $this->yes_no( 'trim_tabs' ),
			],
			'Pricing & Status' => [
				'Original Price'     => $this->money( 'original_price' ),
				'Tax Status'         => $this->field( 'tax_status' ),
			],
		];

		$out = [];
		foreach ( $sections as $name => $rows ) {
			$filtered = $this->non_empty( $rows );
			if ( ! empty( $filtered ) ) {
				$out[ $name ] = $filtered;
			}
		}
		return $out;
	}

	/**
	 * The Propulsion table's engine-hours row, as label + value.
	 *
	 * Hours belong to an engine, not to the boat: a twin with 900 hours on each engine has
	 * 900 hours, not 1,800. The feed field behind `engine_hours` is TotalEngineHoursNumeric
	 * — the sum across engines — so on its own it reads doubled on a twin and tripled on a
	 * triple. Prefer the per-engine figures from the `engines` repeater and list them one by
	 * one ("900 hrs, 900 hrs"), which also keeps engines with unequal hours honest instead of
	 * flattening them into one number.
	 *
	 * With no per-engine hours recorded there is nothing to list, so the feed total is shown
	 * under a label saying what it is rather than being divided down into a figure no one
	 * entered.
	 *
	 * @return array{label:string,value:string}
	 */
	private function engine_hours_row(): array {
		$per_engine = $this->per_engine_hours();
		if ( ! empty( $per_engine ) ) {
			return [
				'label' => 'Engine Hours',
				'value' => implode( ', ', $per_engine ),
			];
		}

		$count = (int) $this->field( 'num_engines' );
		return [
			'label' => $count > 1 ? 'Total Engine Hours' : 'Engine Hours',
			'value' => $this->unit( 'engine_hours', ' hrs' ),
		];
	}

	/**
	 * Formatted hours for each engine, in repeater row order.
	 *
	 * Reuses engines(), so the figures here are the same strings the Engines table prints.
	 * Rows with no hours recorded drop out rather than showing up as a blank entry.
	 *
	 * @return array<int,string>
	 */
	private function per_engine_hours(): array {
		$hours = [];
		foreach ( $this->engines() as $row ) {
			if ( isset( $row['Hours'] ) && '' !== $row['Hours'] ) {
				$hours[] = $row['Hours'];
			}
		}
		return $hours;
	}

	/**
	 * The engines repeater as an array of rows.
	 *
	 * @return array<int,array<string,string>>
	 */
	private function engines(): array {
		$raw = $this->raw( 'engines' );
		if ( ! is_array( $raw ) || empty( $raw ) ) {
			return [];
		}

		$rows = [];
		foreach ( $raw as $engine ) {
			if ( ! is_array( $engine ) ) {
				continue;
			}
			$row = $this->non_empty(
				[
					'Make'         => $engine['make'] ?? '',
					'Model'        => $engine['model'] ?? '',
					// Year is a plain integer — never thousands-separated ("2020", not "2,020").
					'Year'         => $engine['year'] ?? '',
					'Power'        => $this->unit_value( $engine['power'] ?? '', ' HP' ),
					'Hours'        => $this->unit_value( $engine['hours'] ?? '', ' hrs' ),
					'Fuel'         => $engine['fuel'] ?? '',
					'Type'         => $engine['type'] ?? '',
					'Transmission' => $engine['transmission'] ?? '',
					'Propeller'    => $engine['propeller_type'] ?? '',
					'Location'     => $engine['location'] ?? '',
				]
			);
			if ( ! empty( $row ) ) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Featured image local path, falling back to the first gallery image.
	 *
	 * @return string Absolute file path or ''.
	 */
	private function hero_image(): string {
		$thumb_id = get_post_thumbnail_id( $this->post_id );
		if ( $thumb_id ) {
			$path = get_attached_file( (int) $thumb_id );
			if ( $path && is_readable( $path ) ) {
				return $path;
			}
		}
		$gallery = $this->gallery_images();
		return $gallery[0] ?? '';
	}

	/**
	 * Gallery images resolved to readable local file paths.
	 *
	 * @return array<int,string>
	 */
	private function gallery_images(): array {
		$ids = $this->raw( 'images' );
		if ( ! is_array( $ids ) ) {
			return [];
		}
		$paths = [];
		foreach ( $ids as $id ) {
			// Gallery may return bare IDs, image arrays, or attachment objects depending on the
			// field's return format. Array format keys the ID as 'ID' or 'id' across ACF versions.
			if ( is_array( $id ) ) {
				$id = $id['ID'] ?? $id['id'] ?? 0;
			} elseif ( is_object( $id ) ) {
				$id = $id->ID ?? 0;
			}
			$path = get_attached_file( (int) $id );
			if ( $path && is_readable( $path ) ) {
				$paths[] = $path;
			}
		}
		return $paths;
	}

	/**
	 * Long description for the spec sheet: the ACF `description` field, falling back to
	 * the WordPress editor content for listings whose copy lives in the post body.
	 */
	private function description(): string {
		$desc = $this->field( 'description' );
		if ( '' !== $desc ) {
			return $desc;
		}
		$content = get_post_field( 'post_content', $this->post_id );
		return is_string( $content ) ? trim( $content ) : '';
	}

	/**
	 * Display title: the boat post title (editor title), falling back to listing_title,
	 * then "Year Manufacturer Model".
	 */
	private function title(): string {
		$title = trim( (string) get_the_title( $this->post_id ) );
		if ( '' === $title ) {
			$title = $this->field( 'listing_title' );
		}
		if ( '' === $title ) {
			$title = trim(
				sprintf(
					'%s %s %s',
					$this->field( 'model_year' ),
					$this->field( 'manufacturer' ),
					$this->field( 'model' )
				)
			);
		}
		return $title;
	}

	private function price_display(): string {
		// Explicitly hidden price → invite an inquiry rather than showing a blank.
		if ( $this->raw( 'price_hidden' ) ) {
			return self::CALL_FOR_PRICING;
		}
		// Prefer the numeric price so we control formatting ($ + thousands separators).
		// A zero (or negative) price means "no price set" → Call for Pricing, not "$0".
		$price = $this->field( 'price' );
		if ( '' !== $price && is_numeric( $price ) ) {
			return (float) $price > 0 ? $this->money( 'price' ) : self::CALL_FOR_PRICING;
		}
		// Fall back to the display string, formatting any number it contains. When there
		// is nothing usable to show, fall back to Call for Pricing.
		$formatted = $this->format_price_string( $this->field( 'price_display' ) );
		return '' === $formatted ? self::CALL_FOR_PRICING : $formatted;
	}

	/**
	 * Turn a free-form price string like "189000.00 USD" into "$189,000".
	 * Leaves non-numeric strings (e.g. "Call for price") untouched.
	 */
	private function format_price_string( string $value ): string {
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/[\d.,]+/', $value, $m ) ) {
			$number = (float) str_replace( ',', '', $m[0] );
			if ( $number > 0 ) {
				return '$' . number_format( $number );
			}
		}
		return $value;
	}

	/**
	 * New-boat status label for the archive card. Reads the manually-set `new_boat_callout`
	 * select and normalizes it to a display label regardless of the field's return format,
	 * so a value-format field ("on-order") and a label-format field ("On Order") both render
	 * cleanly. Unknown values pass through untouched.
	 */
	private function callout(): string {
		$value = $this->field( 'new_boat_callout' );
		$labels = [
			'in-stock' => 'In Stock',
			'on-order' => 'On Order',
		];
		return $labels[ $value ] ?? $value;
	}

	/**
	 * Location line for cards and the PDF. Country is intentionally omitted — the
	 * listings are US-only, so only city/state are shown (e.g. "Newport, RI").
	 */
	private function location(): string {
		$parts = array_filter(
			[
				(string) $this->field( 'city' ),
				(string) $this->field( 'state' ),
			],
			static fn( $v ): bool => '' !== trim( $v )
		);
		return implode( ', ', $parts );
	}

	// --- field helpers -----------------------------------------------------

	/**
	 * Read a field via ACF when available, else post meta. Returns scalar-ish value.
	 *
	 * @return mixed
	 */
	private function raw( string $name ) {
		if ( function_exists( 'get_field' ) ) {
			return get_field( $name, $this->post_id );
		}
		return get_post_meta( $this->post_id, $name, true );
	}

	/**
	 * Trimmed string form of a field.
	 */
	private function field( string $name ): string {
		$value = $this->raw( $name );
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}
		return trim( (string) $value );
	}

	private function length( string $name ): string {
		return $this->unit( $name, ' ft' );
	}

	private function hp( string $name ): string {
		return $this->unit( $name, ' HP' );
	}

	/** Weights (displacement, dry/ballast) are pounds in the listing feed. */
	private function weight( string $name ): string {
		return $this->unit( $name, ' lbs' );
	}

	/** Tank capacities are US gallons in the listing feed. */
	private function gallons( string $name ): string {
		return $this->unit( $name, ' gal' );
	}

	/** A count with no unit — formatted, so four-digit counts still read cleanly. */
	private function number( string $name ): string {
		return $this->unit( $name, '' );
	}

	private function unit( string $name, string $unit ): string {
		return $this->unit_value( $this->field( $name ), $unit );
	}

	/**
	 * Append a unit to an already-read value (repeater sub-fields have no field name here).
	 *
	 * @param mixed $value Raw sub-field value.
	 */
	private function unit_value( $value, string $unit ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		// Unset numeric ACF fields read back as 0 — drop them here so the unit doesn't
		// smuggle a "0 lbs" row past non_empty()'s bare-zero filter.
		if ( '' === $value || ( is_numeric( $value ) && 0.0 === (float) $value ) ) {
			return '';
		}
		return $this->format_number( $value ) . $unit;
	}

	/**
	 * Thousands-separate a number while keeping the decimals it actually carries
	 * (20000 → "20,000", 44.33 → "44.33", 13.50 → "13.5"). Non-numeric values pass through.
	 */
	private function format_number( string $value ): string {
		if ( ! is_numeric( $value ) ) {
			return $value;
		}
		$decimals = 0;
		$dot      = strpos( $value, '.' );
		if ( false !== $dot ) {
			$decimals = strlen( rtrim( substr( $value, $dot + 1 ), '0' ) );
		}
		return number_format( (float) $value, $decimals );
	}

	private function money( string $name ): string {
		$value = $this->field( $name );
		if ( '' === $value || ! is_numeric( $value ) ) {
			return $value;
		}
		return '$' . number_format( (float) $value );
	}

	private function yes_no( string $name ): string {
		$value = $this->raw( $name );
		return $value ? 'Yes' : '';
	}

	/**
	 * Drop empty values, cast to strings.
	 *
	 * @param array<string,mixed> $rows
	 * @return array<string,string>
	 */
	private function non_empty( array $rows ): array {
		$out = [];
		foreach ( $rows as $label => $value ) {
			$value = is_scalar( $value ) ? trim( (string) $value ) : '';
			// Drop blanks and bare zeros — unset numeric ACF fields read back as 0.
			if ( '' !== $value && '0' !== $value ) {
				$out[ $label ] = $value;
			}
		}
		return $out;
	}
}
