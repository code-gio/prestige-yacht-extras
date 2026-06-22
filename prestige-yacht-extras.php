<?php
/**
 * Plugin Name:       Prestige Yacht Extras
 * Plugin URI:        https://prestigeyacht.com/
 * Description:        Generates branded PDF spec sheets for boat listings, plus tools for the Prestige Yacht site.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Codegio
 * Author URI:        https://codegio.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       prestige-yacht-extras
 *
 * @package PrestigeYacht\Extras
 */

declare( strict_types=1 );

namespace PrestigeYacht\Extras;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PYE_VERSION', '1.0.0' );
define( 'PYE_FILE', __FILE__ );
define( 'PYE_DIR', plugin_dir_path( __FILE__ ) );
define( 'PYE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoload this plugin's own classes (PSR-4: PrestigeYacht\Extras\ => src/).
 * Lightweight and self-contained so the plugin does not depend on Composer at runtime.
 */
spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'PrestigeYacht\\Extras\\';
		if ( 0 !== strncmp( $class, $prefix, strlen( $prefix ) ) ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = PYE_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

/**
 * Load the bundled Dompdf library (ships inside the plugin's vendor/ directory, so the
 * plugin is upload-and-go with no `composer install` required). If it is somehow missing,
 * show an admin notice and bail gracefully instead of fataling.
 */
$pye_autoload = PYE_DIR . 'vendor/autoload.php';

if ( ! is_readable( $pye_autoload ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p><strong>Prestige Yacht Extras:</strong> the bundled PDF library is missing. Re-upload the full plugin (including its <code>vendor/</code> folder) or run <code>composer install</code>.</p></div>';
		}
	);
	return;
}

require_once $pye_autoload;

// Activation / deactivation: rewrite rules need flushing because the PDF endpoint is registered.
register_activation_hook(
	__FILE__,
	static function () {
		( new Plugin() )->register_rewrite();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		flush_rewrite_rules();
	}
);

add_action(
	'plugins_loaded',
	static function () {
		( new Plugin() )->boot();
	}
);
