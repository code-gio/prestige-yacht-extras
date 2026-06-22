<?php
declare( strict_types=1 );

namespace PrestigeYacht\Extras\Archive;

use PrestigeYacht\Extras\Plugin;
use WP_Query;

/**
 * Translates a set of sanitized filter params into a WP_Query of boat listings.
 *
 * Results are ALWAYS ordered by length, longest to shortest, regardless of the active
 * filters (per client requirement). Boats without a length sort last.
 */
final class BoatQuery {

	public const DEFAULT_PER_PAGE = 12;

	/** Meta key used for the length filter + ordering (advertised length). */
	private const LENGTH_KEY = 'nominal_length';

	/** Filter keys this query understands. */
	public const KEYS = [ 'category', 'mfr', 'len_min', 'len_max' ];

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

		$paged = isset( $input['paged'] ) ? max( 1, (int) $input['paged'] ) : 1;

		return [
			'category' => $category,
			'mfr'      => isset( $input['mfr'] ) ? sanitize_text_field( (string) $input['mfr'] ) : '',
			'len_min'  => $num( $input['len_min'] ?? '' ),
			'len_max'  => $num( $input['len_max'] ?? '' ),
			'paged'    => $paged,
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
		if ( '' !== $f['mfr'] ) {
			$meta_query[] = [
				'key'     => 'manufacturer',
				'value'   => $f['mfr'],
				'compare' => '=',
			];
		}

		if ( '' !== $f['len_min'] && '' !== $f['len_max'] ) {
			$meta_query[] = [ 'key' => self::LENGTH_KEY, 'value' => [ (float) $f['len_min'], (float) $f['len_max'] ], 'type' => 'NUMERIC', 'compare' => 'BETWEEN' ];
		} elseif ( '' !== $f['len_min'] ) {
			$meta_query[] = [ 'key' => self::LENGTH_KEY, 'value' => (float) $f['len_min'], 'type' => 'NUMERIC', 'compare' => '>=' ];
		} elseif ( '' !== $f['len_max'] ) {
			$meta_query[] = [ 'key' => self::LENGTH_KEY, 'value' => (float) $f['len_max'], 'type' => 'NUMERIC', 'compare' => '<=' ];
		}

		// Order by length DESC for every query, including boats missing a length (sorted last).
		$meta_query['length_order'] = [
			'relation' => 'OR',
			[ 'key' => self::LENGTH_KEY, 'type' => 'NUMERIC', 'compare' => 'EXISTS' ],
			[ 'key' => self::LENGTH_KEY, 'type' => 'NUMERIC', 'compare' => 'NOT EXISTS' ],
		];

		$args = [
			'post_type'      => Plugin::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => (int) $f['paged'],
			'orderby'        => [ 'length_order' => 'DESC' ],
			'meta_query'     => $meta_query,
			'no_found_rows'  => false, // need found_posts for pagination.
		];

		return new WP_Query( $args );
	}
}
