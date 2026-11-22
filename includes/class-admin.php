<?php
/**
 * Admin menus, page rendering and assets.
 *
 * @package Bulk_Add_Cat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the menu, submenus, assets and page templates.
 */
class Bulk_Add_Cat_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 20 );
		add_filter( 'plugin_action_links_' . BULK_ADD_CAT_PLUGIN_BASENAME, array( $this, 'add_action_links' ), 10, 2 );
	}

	/**
	 * Register the admin menu.
	 */
	public function add_admin_menu() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		add_menu_page(
			__( 'Bulk Category Creator', 'mloun-bulk-category-creator-for-woocommerce' ),
			__( 'Bulk Category Creator', 'mloun-bulk-category-creator-for-woocommerce' ),
			'manage_woocommerce',
			'bulk-add-categories',
			array( $this, 'render_main_page' ),
			'dashicons-plus-alt',
			58
		);

		// Explicit dashboard submenu (same slug as the parent) prevents
		// WordPress from auto-injecting a duplicate menu entry.
		add_submenu_page(
			'bulk-add-categories',
			__( 'Dashboard', 'mloun-bulk-category-creator-for-woocommerce' ),
			__( 'Dashboard', 'mloun-bulk-category-creator-for-woocommerce' ),
			'manage_woocommerce',
			'bulk-add-categories',
			array( $this, 'render_main_page' )
		);

		add_submenu_page(
			'bulk-add-categories',
			__( 'Product Categories', 'mloun-bulk-category-creator-for-woocommerce' ),
			__( 'Categories', 'mloun-bulk-category-creator-for-woocommerce' ),
			'manage_woocommerce',
			'bulk-add-cat-categories',
			array( $this, 'render_categories_page' )
		);

		add_submenu_page(
			'bulk-add-categories',
			__( 'Product Brands', 'mloun-bulk-category-creator-for-woocommerce' ),
			__( 'Brands', 'mloun-bulk-category-creator-for-woocommerce' ),
			'manage_woocommerce',
			'bulk-add-cat-brands',
			array( $this, 'render_brands_page' )
		);

		add_submenu_page(
			'bulk-add-categories',
			__( 'Product Attributes', 'mloun-bulk-category-creator-for-woocommerce' ),
			__( 'Attributes', 'mloun-bulk-category-creator-for-woocommerce' ),
			'manage_woocommerce',
			'bulk-add-cat-attributes',
			array( $this, 'render_attributes_page' )
		);

		add_submenu_page(
			'bulk-add-categories',
			__( 'Settings', 'mloun-bulk-category-creator-for-woocommerce' ),
			__( 'Settings', 'mloun-bulk-category-creator-for-woocommerce' ),
			'manage_woocommerce',
			'bulk-add-cat-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Pre-register assets so late enqueues never warn about registration order.
	 */
	public function register_assets() {
		wp_register_style(
			'bulk-add-cat-admin',
			BULK_ADD_CAT_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			BULK_ADD_CAT_VERSION
		);

		wp_register_script(
			'bulk-add-cat-admin',
			BULK_ADD_CAT_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			BULK_ADD_CAT_VERSION,
			true
		);
	}

	/**
	 * Enqueue assets only on our own screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		$screen_ids = array(
			'toplevel_page_bulk-add-categories',
			'bulk-add-categories_page_bulk-add-cat-categories',
			'bulk-add-categories_page_bulk-add-cat-brands',
			'bulk-add-categories_page_bulk-add-cat-attributes',
			'bulk-add-categories_page_bulk-add-cat-settings',
		);

		// Submenu hooks are built from the (translatable) menu title, so a
		// translated admin produces a hook name this plugin cannot recognise —
		// the stylesheets were then silently skipped on every submenu page. The
		// ?page= argument is language independent, so it is checked as well.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		$ours = ( '' !== $page && 0 === strpos( $page, 'bulk-add-cat' ) )
			|| false !== strpos( (string) $hook, 'bulk-add-cat' )
			|| in_array( $hook, $screen_ids, true );

		if ( ! $ours ) {
			return;
		}

		// Enqueue by handle only (assets were registered earlier).
		wp_enqueue_style( 'bulk-add-cat-admin' );
		wp_enqueue_script( 'bulk-add-cat-admin' );

		wp_localize_script(
			'bulk-add-cat-admin',
			'bulkAddCat',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'bulk_add_cat_nonce' ),
				'batchSize'  => (int) get_option( 'bulk_add_cat_batch_size', 50 ),
				'batchDelay' => (int) get_option( 'bulk_add_cat_batch_delay', 1000 ),
				'strings'    => $this->get_localized_strings(),
			)
		);
	}

	/**
	 * Every user-facing string the front-end JavaScript needs.
	 *
	 * @return array<string,string>
	 */
	public function get_localized_strings() {
		return array(
			'enterData'     => __( 'Please enter some data first.', 'mloun-bulk-category-creator-for-woocommerce' ),
			'noValidData'   => __( 'No valid data found. Please check the format.', 'mloun-bulk-category-creator-for-woocommerce' ),
			'processing'    => __( 'Processing...', 'mloun-bulk-category-creator-for-woocommerce' ),
			'confirmClear'  => __( 'Clear all content?', 'mloun-bulk-category-creator-for-woocommerce' ),
			'created'       => __( 'Created', 'mloun-bulk-category-creator-for-woocommerce' ),
			'skipped'       => __( 'Skipped', 'mloun-bulk-category-creator-for-woocommerce' ),
			'failed'        => __( 'Failed', 'mloun-bulk-category-creator-for-woocommerce' ),
			'skippedItems'  => __( 'Skipped items:', 'mloun-bulk-category-creator-for-woocommerce' ),
			'errorDetails'  => __( 'Error details:', 'mloun-bulk-category-creator-for-woocommerce' ),
			'progressLabel' => __( 'Batch', 'mloun-bulk-category-creator-for-woocommerce' ),
			'done'          => __( 'All batches processed.', 'mloun-bulk-category-creator-for-woocommerce' ),
			'requestFailed' => __( 'Request failed: ', 'mloun-bulk-category-creator-for-woocommerce' ),
			'requestError'  => __( 'Request error', 'mloun-bulk-category-creator-for-woocommerce' ),
			'startLabel'    => __( 'Start Bulk Add', 'mloun-bulk-category-creator-for-woocommerce' ),
			'reloadHint'    => __( 'Existing items list will refresh in 3 seconds…', 'mloun-bulk-category-creator-for-woocommerce' ),
			'reloadNow'     => __( 'Reload now', 'mloun-bulk-category-creator-for-woocommerce' ),
		);
	}

	/**
	 * Add a Dashboard link to the plugin row on the plugins screen.
	 *
	 * @param array  $links Existing action links.
	 * @param string $file  Plugin file.
	 * @return array
	 */
	public function add_action_links( $links, $file ) {
		$link = '<a href="' . esc_url( admin_url( 'admin.php?page=bulk-add-categories' ) ) . '">' . esc_html__( 'Dashboard', 'mloun-bulk-category-creator-for-woocommerce' ) . '</a>';
		array_unshift( $links, $link );
		return $links;
	}

	/**
	 * Render the dashboard / overview page.
	 */
	public function render_main_page() {
		include BULK_ADD_CAT_PLUGIN_DIR . 'templates/main-page.php';
	}

	/**
	 * Render the categories page.
	 */
	public function render_categories_page() {
		$processor = new Bulk_Add_Cat_Processor();

		$categories = $processor->get_term_options( 'product_cat' );
		$top_terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'parent'     => 0,
				'number'     => 50,
				'orderby'    => 'name',
			)
		);

		include BULK_ADD_CAT_PLUGIN_DIR . 'templates/categories-page.php';
	}

	/**
	 * Render the brands page.
	 */
	public function render_brands_page() {
		$processor    = new Bulk_Add_Cat_Processor();
		$brand_taxoms = $processor->get_brand_taxonomies();

		$default_taxonomy = '';
		if ( ! empty( $brand_taxoms ) ) {
			$default_taxonomy = array_key_first( $brand_taxoms );
		}

		$parent_options = array();
		foreach ( $brand_taxoms as $taxonomy => $label ) {
			$parent_options[ $taxonomy ] = $processor->get_term_options( $taxonomy );
		}

		include BULK_ADD_CAT_PLUGIN_DIR . 'templates/brands-page.php';
	}

	/**
	 * Render the attributes page.
	 */
	public function render_attributes_page() {
		$attributes = array();
		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			$raw = wc_get_attribute_taxonomies();
			if ( is_array( $raw ) ) {
				$attributes = $raw;
			}
		}

		include BULK_ADD_CAT_PLUGIN_DIR . 'templates/attributes-page.php';
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		include BULK_ADD_CAT_PLUGIN_DIR . 'templates/settings-page.php';
	}
}
