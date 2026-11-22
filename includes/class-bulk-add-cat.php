<?php
/**
 * Main plugin class: hooks, text domain, AJAX handler.
 *
 * @package Bulk_Add_Cat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Singleton orchestrator.
 */
class Bulk_Add_Cat {

	/**
	 * Singleton instance.
	 *
	 * @var Bulk_Add_Cat|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Bulk_Add_Cat
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Register all hooks.
	 */
	private function init_hooks() {
		if ( $this->woocommerce_active() ) {
			new Bulk_Add_Cat_Admin();
			new Bulk_Add_Cat_Settings();
			add_action( 'wp_ajax_bulk_add_cat_process', array( $this, 'ajax_process_batch' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'render_woocommerce_missing_notice' ) );
		}
	}

	/**
	 * Whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public function woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Notice when WooCommerce is missing.
	 */
	public function render_woocommerce_missing_notice() {
		echo '<div class="notice notice-error"><p><strong>';
		echo esc_html__( 'Bulk Add Categories:', 'mloun-bulk-category-creator-for-woocommerce' );
		echo '</strong> ';
		echo esc_html__( 'This plugin requires WooCommerce. Please install and activate WooCommerce to enable bulk adding.', 'mloun-bulk-category-creator-for-woocommerce' );
		echo '</p></div>';
	}

	/**
	 * AJAX: process one batch of items.
	 *
	 * @return void Sends a JSON response and never falls through.
	 */
	public function ajax_process_batch() {
		// Verify nonce first.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! wp_verify_nonce( $nonce, 'bulk_add_cat_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed.', 'mloun-bulk-category-creator-for-woocommerce' ),
				),
				403
			);
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You are not allowed to perform this action.', 'mloun-bulk-category-creator-for-woocommerce' ),
				),
				403
			);
		}

		$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$data = array();
		if ( isset( $_POST['data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			// The payload is a JSON string; it is intentionally NOT text-sanitized.
			// It is validated via json_decode (strict) and every item field is
			// sanitized individually inside Bulk_Add_Cat_Processor.
			$decoded = json_decode( wp_unslash( $_POST['data'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( is_array( $decoded ) ) {
				$data = $decoded;
			}
		}

		$allowed_types = apply_filters( 'bulk_add_cat_process_types', array( 'categories', 'brands', 'attributes' ) );
		if ( '' === $type || ! in_array( $type, (array) $allowed_types, true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unknown operation type.', 'mloun-bulk-category-creator-for-woocommerce' ),
				),
				400
			);
		}

		if ( empty( $data ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No data to process.', 'mloun-bulk-category-creator-for-woocommerce' ),
				),
				400
			);
		}

		// Bound the payload so a single request can never grow unbounded.
		$max_items = (int) apply_filters( 'bulk_add_cat_max_items_per_request', 2000 );
		$data      = array_slice( $data, 0, $max_items );

		$logger = new Bulk_Add_Cat_Logger();
		$logger->info(
			'bulk operation started',
			array(
				'type'  => $type,
				'count' => count( $data ),
			)
		);

		$processor = new Bulk_Add_Cat_Processor();
		$result    = $processor->process( $type, $data );

		$summary = array(
			'type'        => $type,
			'created'     => isset( $result['created_count'] ) ? (int) $result['created_count'] : 0,
			'skipped'     => isset( $result['skipped_count'] ) ? (int) $result['skipped_count'] : 0,
			'failed'      => isset( $result['failed_count'] ) ? (int) $result['failed_count'] : 0,
		);

		if ( ! empty( $summary['failed'] ) ) {
			$logger->error( 'bulk operation finished with errors', $summary );
		} else {
			$logger->success( 'bulk operation finished', $summary );
		}

		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}
}
