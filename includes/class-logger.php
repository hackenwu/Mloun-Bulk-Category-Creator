<?php
/**
 * Lightweight file logger.
 *
 * Logs go to {uploads}/bulk-add-cat/logs/ instead of the plugin directory so
 * they survive plugin updates and are never bundled with releases.
 *
 * @package Bulk_Add_Cat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Append-only operation log, off unless enabled in Settings.
 */
class Bulk_Add_Cat_Logger {

	/**
	 * Log file path.
	 *
	 * @var string
	 */
	private $log_file;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$uploads = wp_upload_dir();
		$this->log_file = trailingslashit( $uploads['basedir'] ) . 'bulk-add-categories/logs/bulk-add-categories.log';
	}

	/**
	 * Whether logging is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) get_option( 'bulk_add_cat_logging', 0 );
	}

	/**
	 * Write a log entry.
	 *
	 * @param string $message Log message.
	 * @param string $level   Log level (info|success|warning|error).
	 * @param array  $context Optional structured context.
	 * @return bool|false
	 */
	public function log( $message, $level = 'info', $context = array() ) {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		$timestamp = current_time( 'mysql' );
		$user_id   = get_current_user_id();
		$user_name = $user_id ? wp_get_current_user()->user_login : __( 'system', 'mloun-bulk-category-creator-for-woocommerce' );

		$entry = sprintf(
			'[%s] [%s] [User: %s] %s',
			$timestamp,
			strtoupper( $level ),
			$user_name,
			$message
		);

		if ( ! empty( $context ) ) {
			$entry .= ' | ' . wp_json_encode( $context );
		}

		$filesystem = $this->filesystem();

		if ( ! $filesystem ) {
			return false;
		}

		$directory = dirname( $this->log_file );

		if ( ! $filesystem->is_dir( $directory ) ) {
			$filesystem->mkdir( $directory, FS_CHMOD_DIR );
		}

		$existing = $filesystem->exists( $this->log_file )
			? (string) $filesystem->get_contents( $this->log_file )
			: '';

		return (bool) $filesystem->put_contents( $this->log_file, $existing . $entry . PHP_EOL, FS_CHMOD_FILE );
	}

	/**
	 * Info level.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return bool|false
	 */
	public function info( $message, $context = array() ) {
		return $this->log( $message, 'info', $context );
	}

	/**
	 * Success level.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return bool|false
	 */
	public function success( $message, $context = array() ) {
		return $this->log( $message, 'success', $context );
	}

	/**
	 * Warning level.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return bool|false
	 */
	public function warning( $message, $context = array() ) {
		return $this->log( $message, 'warning', $context );
	}

	/**
	 * Error level.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return bool|false
	 */
	public function error( $message, $context = array() ) {
		return $this->log( $message, 'error', $context );
	}

	/**
	 * Initialise WP_Filesystem once and hand it back.
	 *
	 * Direct PHP file calls are discouraged by WordPress.org (Plugin Check) and
	 * may fail on hosts where the uploads directory is not writable by the web
	 * user. WP_Filesystem picks the right transport and handles both cases.
	 *
	 * @return WP_Filesystem_Base|null
	 */
	private function filesystem() {
		global $wp_filesystem;

		if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
			return $wp_filesystem;
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		return ( $wp_filesystem instanceof WP_Filesystem_Base ) ? $wp_filesystem : null;
	}

	/**
	 * Absolute path of the log file (for admins / support).
	 *
	 * @return string
	 */
	public function get_file_path() {
		return $this->log_file;
	}
}
