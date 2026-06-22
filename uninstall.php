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

flush_rewrite_rules();
