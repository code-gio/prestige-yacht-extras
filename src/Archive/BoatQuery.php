<?php
declare( strict_types=1 );

namespace PrestigeYacht\Extras\Archive;

use PrestigeYacht\Extras\Plugin;
use WP_Query;

/**
 * Translates a set of sanitized filter params into a WP_Query of boat listings.
 */
final class BoatQuery {

	public const DEFAULT_PER_PAGE = 12;

	/** Meta key used for the length filter (advertised length). */
	private const LENGTH_KEY = 'nominal_length';

	/**
	 * Sanitize a raw input array (from $_GET or an AJAX POST) into known filter params.
	 *
	 * @param array<string,mixed> $input
	 * @return array<string,mixed>
	 */
	public static function sanitize( array $input ): array {
		$num = static function ( $v ): string {
			$v = is_scalar( $v ) ? trim( (string) $v ) : '';
			return ( '' !== $v && is_numeric( $v ) && (float) $v >= 0 ) ? (string) ( $v + 0 ) : '';
		};

		$category = isset( $input['category'] ) ? ucfirst( strtolower( (string) $input['category'] ) ) : '';
		$category = in_array( $category, [ 'Power', 'Sail' ], true ) ? $category : '';

		$condition = isset( $input['condition'] ) ? ucfirst( strtolower( (string) $input['condition'] ) ) : '';
		$condition = in_array( $condition, [ 'New', 'Used' ], true ) ? $condition : '';

		$paged = isset( $input['paged'] ) ? max( 1, (int) $input['paged'] ) : 1;

		return [
			'category'  => $category,
			'condition' => $condition,
			'mfr'       => isset( $input['mfr'] ) ? sanitize_text_field( (string) $input['mfr'] ) : '',
			'len_min'   => $num( $input['len_min'] ?? '' ),
			'len_max'   => $num( $input['len_max'] ?? '' ),
			'price_min' => $num( $input['price_min'] ?? '' ),
			'price_max' => $num( $input['price_max'] ?? '' ),
			'paged'     => $paged,
		];
	}

	/**
	 * Run the query for the given sanitized params.
	 *
	 * @param array<string,mixed> $f        Sanitized filters (see sanitize()).
	 * @param int                 $per_page Posts per page.
	 */
	public static function run( array $f, int $per_page = self::DEFAULT_PER_PAGE ): WP_Query {
		$meta_query = [ 'relation' => 'AND' ];

		if ( '' !== $f['category'] ) {
			$meta_query[] = [
				'key'     => 'boat_category',
				'value'   => $f['category'],
				'compare' => '=',
			];
		}
		if ( '' !== $f['condition'] ) {
			$meta_query[] = [
				'key'     => 'sale_class',
				'value'   => $f['condition'],
				'compare' => '=',
			];
		}
		if ( '' !== $f['mfr'] ) {
			$meta_query[] = [
				'key'     => 'manufacturer',
				'value'   => $f['mfr'],
				'compare' => '=',
			];
		}

		$range = static function ( string $key, string $min, string $max ): ?array {
			if ( '' !== $min && '' !== $max ) {
				return [ 'key' => $key, 'value' => [ (float) $min, (float) $max ], 'type' => 'NUMERIC', 'compare' => 'BETWEEN' ];
			}
			if ( '' !== $min ) {
				return [ 'key' => $key, 'value' => (float) $min, 'type' => 'NUMERIC', 'compare' => '>=' ];
			}
			if ( '' !== $max ) {
				return [ 'key' => $key, 'value' => (float) $max, 'type' => 'NUMERIC', 'compare' => '<=' ];
			}
			return null;
		};

		$len = $range( self::LENGTH_KEY, $f['len_min'], $f['len_max'] );
		if ( $len ) {
			$meta_query[] = $len;
		}
		$price = $range( 'price', $f['price_min'], $f['price_max'] );
		if ( $price ) {
			$meta_query[] = $price;
		}

		$args = [
			'post_type'      => Plugin::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => (int) $f['paged'],
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => false, // need found_posts for pagination.
		];

		// Only attach meta_query if it has real clauses (beyond the relation key).
		if ( count( $meta_query ) > 1 ) {
			$args['meta_query'] = $meta_query;
		}

		return new WP_Query( $args );
	}
}
