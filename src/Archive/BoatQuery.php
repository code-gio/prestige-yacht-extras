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

	/** Meta key used for both the length filter and the default sort — overall length (LOA). */
	private const LENGTH_KEY = 'length_overall';

	/** Filter keys this query understands. */
	public const KEYS = [ 'category', 'mfr', 'model', 'len_min', 'len_max' ];

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
			'model'    => isset( $input['model'] ) ? sanitize_text_field( (string) $input['model'] ) : '',
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
		if ( '' !== $f['model'] ) {
			// Partial, case-insensitive: "Oceanis" matches "Oceanis 34.1" etc.
			$meta_query[] = [
				'key'     => 'model',
				'value'   => $f['model'],
				'compare' => 'LIKE',
			];
		}

		if ( '' !== $f['len_min'] && '' !== $f['len_max'] ) {
			$meta_query[] = [ 'key' => self::LENGTH_KEY, 'value' => [ (float) $f['len_min'], (float) $f['len_max'] ], 'type' => 'NUMERIC', 'compare' => 'BETWEEN' ];
		} elseif ( '' !== $f['len_min'] ) {
			$meta_query[] = [ 'key' => self::LENGTH_KEY, 'value' => (float) $f['len_min'], 'type' => 'NUMERIC', 'compare' => '>=' ];
		} elseif ( '' !== $f['len_max'] ) {
			$meta_query[] = [ 'key' => self::LENGTH_KEY, 'value' => (float) $f['len_max'], 'type' => 'NUMERIC', 'compare' => '<=' ];
		}

		// Sort by overall length, longest first. WP can only ORDER BY a *leaf* meta clause
		// (one with a `key`) — ordering by an OR *group* is silently ignored, which is why
		// results were previously unsorted. So name the leaf clause and order by that name;
		// the OR'd NOT EXISTS keeps boats that have no length (they sort last).
		$meta_query[] = [
			'relation'        => 'OR',
			'has_length'      => [ 'key' => self::LENGTH_KEY, 'type' => 'NUMERIC', 'compare' => 'EXISTS' ],
			'missing_length'  => [ 'key' => self::LENGTH_KEY, 'compare' => 'NOT EXISTS' ],
		];

		$args = [
			'post_type'      => Plugin::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => (int) $f['paged'],
			'orderby'        => [ 'has_length' => 'DESC' ],
			'meta_query'     => $meta_query,
			'no_found_rows'  => false, // need found_posts for pagination.
		];

		return new WP_Query( $args );
	}
}
