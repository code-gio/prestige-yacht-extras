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
	 * @return array{id:int,permalink:string,title:string,location:string,price:string,thumb_id:int}
	 */
	public static function card( int $post_id ): array {
		$self = new self( $post_id );
		return [
			'id'        => $post_id,
			'permalink' => (string) get_permalink( $post_id ),
			'title'     => $self->title(),
			'location'  => $self->location(),
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
			'description'     => (string) $this->field( 'description' ),
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
					'Engines' => $this->field( 'num_engines' ),
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
				'Displacement'       => $this->field( 'displacement' ),
				'Dry Weight'         => $this->field( 'dry_weight' ),
				'Ballast Weight'     => $this->field( 'ballast_weight' ),
			],
			'Propulsion'     => [
				'Number of Engines' => $this->field( 'num_engines' ),
				'Total Power'       => $this->hp( 'total_power' ),
				'Engine Hours'      => $this->field( 'engine_hours' ),
				'Cruising Speed'    => $this->unit( 'cruising_speed', ' kn' ),
				'Maximum Speed'     => $this->unit( 'max_speed', ' kn' ),
				'Range'             => $this->unit( 'range', ' mi' ),
				'Drive Type'        => $this->field( 'drive_type' ),
				'Displacement Type' => $this->field( 'displacement_type' ),
				'Keel Type'         => $this->field( 'keel_type' ),
				'Windlass Type'     => $this->field( 'windlass_type' ),
			],
			'Capacities'     => [
				'Fuel Capacity'        => $this->field( 'fuel_capacity' ),
				'Fuel Tank Count'      => $this->field( 'fuel_tank_count' ),
				'Fuel Tank Material'   => $this->field( 'fuel_tank_material' ),
				'Water Capacity'       => $this->field( 'water_capacity' ),
				'Water Tank Count'     => $this->field( 'water_tank_count' ),
				'Water Tank Material'  => $this->field( 'water_tank_material' ),
				'Holding Tank Capacity'=> $this->field( 'holding_tank_capacity' ),
				'Holding Tank Count'   => $this->field( 'holding_tank_count' ),
				'Holding Tank Material'=> $this->field( 'holding_tank_material' ),
			],
			'Accommodations' => [
				'Cabins'             => $this->field( 'cabins' ),
				'Heads'              => $this->field( 'heads' ),
				'Maximum Passengers' => $this->field( 'max_passengers' ),
				'Convertible Saloon' => $this->yes_no( 'convertible_saloon' ),
				'Electrical Circuit' => $this->unit( 'electrical_circuit', ' V' ),
				'Trim Tabs'          => $this->yes_no( 'trim_tabs' ),
			],
			'Pricing & Status' => [
				'Original Price'     => $this->money( 'original_price' ),
				'Tax Status'         => $this->field( 'tax_status' ),
				'Item Received'      => $this->field( 'item_received_date' ),
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
					'Year'         => $engine['year'] ?? '',
					'Power (HP)'   => $engine['power'] ?? '',
					'Hours'        => $engine['hours'] ?? '',
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
			// Gallery may return IDs, arrays, or attachment objects depending on config.
			if ( is_array( $id ) && isset( $id['ID'] ) ) {
				$id = $id['ID'];
			} elseif ( is_object( $id ) && isset( $id->ID ) ) {
				$id = $id->ID;
			}
			$path = get_attached_file( (int) $id );
			if ( $path && is_readable( $path ) ) {
				$paths[] = $path;
			}
		}
		return $paths;
	}

	/**
	 * Display title: listing_title, else "Year Manufacturer Model", else the post title.
	 */
	private function title(): string {
		$title = $this->field( 'listing_title' );
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
		if ( '' === $title ) {
			$title = (string) get_the_title( $this->post_id );
		}
		return $title;
	}

	private function price_display(): string {
		if ( $this->raw( 'price_hidden' ) ) {
			return '';
		}
		// Prefer the numeric price so we control formatting ($ + thousands separators).
		$price = $this->field( 'price' );
		if ( '' !== $price && is_numeric( $price ) ) {
			return $this->money( 'price' );
		}
		// Fall back to the display string, formatting any number it contains.
		return $this->format_price_string( $this->field( 'price_display' ) );
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

	private function location(): string {
		$parts = array_filter(
			[
				(string) $this->field( 'city' ),
				(string) $this->field( 'state' ),
				(string) $this->field( 'country' ),
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

	private function unit( string $name, string $unit ): string {
		$value = $this->field( $name );
		return '' === $value ? '' : $value . $unit;
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
