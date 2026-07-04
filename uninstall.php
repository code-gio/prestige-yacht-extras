<?php
/**
 * Uninstall handler. The plugin stores no options or custom tables, so this only
 * clears rewrite rules that referenced the PDF endpoint and cached filter data.
 *
 * @package PrestigeYacht\Extras
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'pye_rewrite_version' );

// Manufacturer dropdown caches (base list + per-model variants).
delete_transient( 'pye_boat_manufacturers' );
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_pye\_boat\_manufacturers\_%' OR option_name LIKE '\_transient\_timeout\_pye\_boat\_manufacturers\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- uninstall cleanup.

flush_rewrite_rules();
