<?php
declare( strict_types=1 );

namespace PrestigeYacht\Extras\Archive;

use PrestigeYacht\Extras\Plugin;

/**
 * Provides the distinct list of manufacturers across published boats for the filter dropdown.
 * Cached in a transient and invalidated whenever a boat is saved.
 */
final class ManufacturerOptions {

	private const CACHE_KEY = 'pye_boat_manufacturers';

	/**
	 * @return array<int,string> Alphabetized, de-duplicated manufacturer names.
	 */
	public static function get(): array {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		// Distinct non-empty manufacturer meta values for published boats.
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_value
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = %s
				   AND pm.meta_value <> ''
				   AND p.post_type = %s
				   AND p.post_status = 'publish'",
				'manufacturer',
				Plugin::POST_TYPE
			)
		);

		$rows = array_map( 'trim', (array) $rows );
		$rows = array_values( array_unique( array_filter( $rows ) ) );
		natcasesort( $rows );
		$rows = array_values( $rows );

		set_transient( self::CACHE_KEY, $rows, DAY_IN_SECONDS );
		return $rows;
	}

	/**
	 * Clear the cache (hooked to save_post_boat / deleted_post).
	 */
	public static function flush(): void {
		delete_transient( self::CACHE_KEY );
	}
}
