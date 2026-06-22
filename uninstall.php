<?php
/**
 * Uninstall handler. The plugin stores no options or custom tables, so this only
 * clears rewrite rules that referenced the PDF endpoint.
 *
 * @package PrestigeYacht\Extras
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'pye_rewrite_version' );
flush_rewrite_rules();
