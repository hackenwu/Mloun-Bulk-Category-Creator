<?php
/**
 * Settings registration (Settings API).
 *
 * @package Bulk_Add_Cat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers plugin options with sane bounds.
 */
class Bulk_Add_Cat_Settings {

	/**
	 * Option group.
	 */
	const OPTION_GROUP = 'bulk_add_cat_settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			'bulk_add_cat_batch_size',
			array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_batch_size' ),
			)
		);

		register_setting(
			self::OPTION_GROUP,
			'bulk_add_cat_batch_delay',
			array(
				'type'              => 'number',
				'sanitize_callback' => array( $this, 'sanitize_batch_delay' ),
			)
		);

		register_setting(
			self::OPTION_GROUP,
			'bulk_add_cat_logging',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'absint',
			)
		);
	}

	/**
	 * Sanitize batch size (1–500, default 50).
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public function sanitize_batch_size( $value ) {
		$value = absint( $value );
		if ( $value < 1 || $value > 500 ) {
			$value = 50;
		}
		return $value;
	}

	/**
	 * Sanitize batch delay in milliseconds (0–30000, default 1000).
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public function sanitize_batch_delay( $value ) {
		$value = absint( $value );
		if ( $value < 0 || $value > 30000 ) {
			$value = 1000;
		}
		return $value;
	}
}
