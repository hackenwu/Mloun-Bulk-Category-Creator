<?php
/**
 * Uninstall handler.
 *
 * Removes only the plugin's own options and log files. Categories, brands and
 * attributes created with the plugin belong to the store and are left in place.
 *
 * @package Bulk_Add_Cat
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/* phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound */
delete_option( 'bulk_add_cat_batch_size' );
delete_option( 'bulk_add_cat_batch_delay' );
delete_option( 'bulk_add_cat_logging' );

// Remove log files from the uploads directory through WP_Filesystem, the API
// WordPress.org expects for file operations. If it cannot initialise we simply
// leave the logs in place: cleanup must never block the uninstall.
try {
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	global $wp_filesystem;

	WP_Filesystem();

	if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
		$uploads = wp_upload_dir();

		foreach ( array( 'bulk-add-categories', 'bulk-add-cat' ) as $dir_name ) {
			$log_dir = trailingslashit( (string) $uploads['basedir'] ) . $dir_name;

			if ( $wp_filesystem->is_dir( $log_dir ) ) {
				$wp_filesystem->delete( $log_dir, true );
			}
		}
	}
} catch ( \Throwable $bac_uninstall_error ) {
	unset( $bac_uninstall_error ); // Never let log cleanup block the uninstall.
}
