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
	 * @param string $model Optional model-line filter (partial match) — on a model page the
	 *                      dropdown only lists manufacturers that carry that model.
	 * @return array<int,string> Alphabetized, de-duplicated manufacturer names.
	 */
	public static function get( string $model = '' ): array {
		$cache_key = '' === $model ? self::CACHE_KEY : self::CACHE_KEY . '_' . md5( strtolower( $model ) );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		// Distinct non-empty manufacturer meta values for published boats.
		$sql    = "SELECT DISTINCT pm.meta_value
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = %s
				   AND pm.meta_value <> ''
				   AND p.post_type = %s
				   AND p.post_status = 'publish'";
		$params = [ 'manufacturer', Plugin::POST_TYPE ];

		if ( '' !== $model ) {
			$sql     .= " AND EXISTS (
					SELECT 1 FROM {$wpdb->postmeta} mm
					WHERE mm.post_id = p.ID
					  AND mm.meta_key = 'model'
					  AND mm.meta_value LIKE %s
				 )";
			$params[] = '%' . $wpdb->esc_like( $model ) . '%';
		}

		$rows = $wpdb->get_col( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders built above.

		$rows = array_map( 'trim', (array) $rows );
		$rows = array_values( array_unique( array_filter( $rows ) ) );
		natcasesort( $rows );
		$rows = array_values( $rows );

		set_transient( $cache_key, $rows, DAY_IN_SECONDS );
		return $rows;
	}

	/**
	 * Clear the cache (hooked to save_post_boat / deleted_post).
	 * Removes the base list plus every model-scoped variant.
	 */
	public static function flush(): void {
		delete_transient( self::CACHE_KEY );

		global $wpdb;
		$like = $wpdb->esc_like( '_transient_' . self::CACHE_KEY . '_' ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- targeted transient cleanup.
		$like = $wpdb->esc_like( '_transient_timeout_' . self::CACHE_KEY . '_' ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- targeted transient cleanup.
	}
}
