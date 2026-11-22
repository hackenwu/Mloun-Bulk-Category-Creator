<?php
/**
 * Plugin Name:       Mloun Bulk Category Creator for WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/mloun-bulk-category-creator-for-woocommerce/
 * Description:       Bulk create WooCommerce product categories, brands and global attributes (with values) from simple pipe-separated text lists.
 * Version:           1.2.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * WC requires at least: 6.0
 * Requires Plugins:   woocommerce
 * WC tested up to:   11.2
 * Author:            Mloun
 * Author URI:        https://mloun.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mloun-bulk-category-creator-for-woocommerce
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BULK_ADD_CAT_VERSION', '1.2.1' );
define( 'BULK_ADD_CAT_PLUGIN_FILE', __FILE__ );
define( 'BULK_ADD_CAT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BULK_ADD_CAT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BULK_ADD_CAT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload Bulk_Add_Cat_* classes from /includes.
 *
 * @param string $class Class name requested by PHP.
 */
spl_autoload_register( function ( $class ) {
	$prefix = 'Bulk_Add_Cat_';

	if ( 0 !== strncmp( $prefix, $class, strlen( $prefix ) ) ) {
		return;
	}

	$file = BULK_ADD_CAT_PLUGIN_DIR . 'includes/class-' . str_replace( '_', '-', strtolower( substr( $class, strlen( $prefix ) ) ) ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

require_once BULK_ADD_CAT_PLUGIN_DIR . 'includes/class-bulk-add-cat.php';

/**
 * Bootstrap the plugin.
 *
 * The global function is guarded so that two copies of this plugin can never
 * coexist in wp-content/plugins (e.g. during the v1.0 -> v1.1 folder rename)
 * and cause a "Cannot redeclare" fatal error.
 */
if ( ! function_exists( 'bulk_add_cat_bootstrap' ) ) {
	function bulk_add_cat_bootstrap() {
		Bulk_Add_Cat::get_instance();
	}
}
add_action( 'plugins_loaded', 'bulk_add_cat_bootstrap' );

/**
 * Register default options on activation.
 */
if ( ! function_exists( 'bulk_add_cat_activate' ) ) {
	function bulk_add_cat_activate() {
		add_option( 'bulk_add_cat_batch_size', 50 );
		add_option( 'bulk_add_cat_batch_delay', 1000 );
		add_option( 'bulk_add_cat_logging', 0 );
	}
}
register_activation_hook( __FILE__, 'bulk_add_cat_activate' );
