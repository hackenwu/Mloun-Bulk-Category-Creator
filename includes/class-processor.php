<?php
/**
 * Bulk processor: creates categories, brands and attributes.
 *
 * @package Bulk_Add_Cat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processes bulk create requests.
 *
 * Every per-item result carries one of three statuses:
 *  - created: a new item (or new values on an existing attribute) was added.
 *  - skipped: the item already exists (never overwritten).
 *  - failed:  a real error (empty name, missing parent, DB error, ...).
 */
class Bulk_Add_Cat_Processor {

	/**
	 * Attribute input types accepted by WooCommerce.
	 *
	 * @var string[]
	 */
	const ATTRIBUTE_TYPES = array( 'select', 'text', 'textarea', 'number', 'email', 'color', 'date', 'url' );

	/**
	 * Process a batch.
	 *
	 * @param string $type One of: categories, brands, attributes.
	 * @param array  $data Normalized items.
	 * @return array
	 */
	public function process( $type, $data ) {
		switch ( $type ) {
			case 'categories':
				return $this->process_categories( (array) $data );
			case 'brands':
				return $this->process_brands( (array) $data );
			case 'attributes':
				return $this->process_attributes( (array) $data );
			default:
				return $this->build_summary(
					array(),
					__( 'Unknown operation type.', 'mloun-bulk-category-creator-for-woocommerce' )
				);
		}
	}

	/* -----------------------------------------------------------------
	 * Hierarchical term helpers (used by the admin dropdowns).
	 * ----------------------------------------------------------------- */

	/**
	 * Hierarchy-aware term list for a taxonomy.
	 *
	 * @param string $taxonomy  Taxonomy slug.
	 * @param int    $max_depth Optional recursion depth cap.
	 * @return array[] Each entry: id, name, level.
	 */
	public function get_term_options( $taxonomy, $max_depth = 30 ) {
		return $this->collect_term_options( $taxonomy, 0, 0, $max_depth );
	}

	/**
	 * Recursively collect terms, parent level first.
	 *
	 * @param string $taxonomy  Taxonomy slug.
	 * @param int    $parent_id Parent term ID.
	 * @param int    $level     Depth.
	 * @param int    $max_depth Depth cap.
	 * @return array[]
	 */
	private function collect_term_options( $taxonomy, $parent_id, $level, $max_depth ) {
		$options = array();

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'parent'     => $parent_id,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			$options[] = array(
				'id'    => (int) $term->term_id,
				'name'  => $term->name,
				'level' => $level,
			);

			if ( $level < $max_depth ) {
				$options = array_merge( $options, $this->collect_term_options( $taxonomy, (int) $term->term_id, $level + 1, $max_depth ) );
			}
		}

		return $options;
	}

	/**
	 * Brand taxonomies available from known brand plugins.
	 *
	 * @return array<string,string> Taxonomy slug => display label.
	 */
	public function get_brand_taxonomies() {
		$known = array(
			'pwb-brand'          => __( 'Perfect WooCommerce Brands', 'mloun-bulk-category-creator-for-woocommerce' ),
			'yith_product_brand' => __( 'YITH WooCommerce Brands', 'mloun-bulk-category-creator-for-woocommerce' ),
			'product_brand'      => __( 'WooCommerce Brands', 'mloun-bulk-category-creator-for-woocommerce' ),
		);

		$available = array();
		foreach ( $known as $taxonomy => $label ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				$available[ $taxonomy ] = $label;
			}
		}

		/**
		 * Allow third-party brand plugins to register their taxonomy.
		 *
		 * Expects an array of taxonomy slug => display label.
		 *
		 * @param array<string,string> $extra Extra taxonomies.
		 */
		$extra = apply_filters( 'bulk_add_cat_brand_taxonomies', array() );
		if ( is_array( $extra ) ) {
			foreach ( $extra as $taxonomy => $label ) {
				if ( is_string( $taxonomy ) && taxonomy_exists( $taxonomy ) ) {
					$available[ $taxonomy ] = is_string( $label ) ? $label : $taxonomy;
				}
			}
		}

		return $available;
	}

	/* -----------------------------------------------------------------
	 * Categories.
	 * ----------------------------------------------------------------- */

	/**
	 * Process a batch of product categories.
	 *
	 * @param array $data Items: name, parent_id, parent_name, description, slug, thumbnail_id.
	 * @return array
	 */
	private function process_categories( $data ) {
		$items = array();
		foreach ( $data as $entry ) {
			$items[] = $this->create_category( $entry );
		}
		return $this->build_summary( $items );
	}

	/**
	 * Create a single product category.
	 *
	 * @param mixed $entry Raw item.
	 * @return array
	 */
	private function create_category( $entry ) {
		$entry = (array) $entry;

		$name        = sanitize_text_field( $entry['name'] ?? '' );
		$slug        = sanitize_title( $entry['slug'] ?? '' );
		$description = sanitize_textarea_field( $entry['description'] ?? '' );
		$parent_id   = absint( $entry['parent_id'] ?? 0 );
		$parent_name = sanitize_text_field( $entry['parent_name'] ?? '' );

		if ( '' === $name ) {
			return array(
				'name'    => __( 'Unnamed item', 'mloun-bulk-category-creator-for-woocommerce' ),
				'status'  => 'failed',
				'message' => __( 'Category name must not be empty.', 'mloun-bulk-category-creator-for-woocommerce' ),
			);
		}

		// Resolve the parent: dropdown selection first, then by name.
		if ( 0 === $parent_id && '' !== $parent_name ) {
			$parent_term = get_term_by( 'name', $parent_name, 'product_cat' );
			if ( ! $parent_term ) {
				$parent_term = get_term_by( 'slug', sanitize_title( $parent_name ), 'product_cat' );
			}
			if ( ! $parent_term || is_wp_error( $parent_term ) ) {
				return array(
					'name'    => $name,
					'status'  => 'failed',
					'message' => sprintf(
						/* translators: %s: requested parent category name. */
						__( 'Parent category "%s" not found.', 'mloun-bulk-category-creator-for-woocommerce' ),
						$parent_name
					),
				);
			}
			$parent_id = (int) $parent_term->term_id;
		}

		// Duplicate detection: never overwrite. A name only collides inside the same
		// parent, so the same child name may exist under different parents.
		$existing = $this->find_existing_term( 'product_cat', $name, $slug, $parent_id );
		if ( $existing ) {
			return array(
				'name'    => $name,
				'status'  => 'skipped',
				'message' => __( 'Category already exists.', 'mloun-bulk-category-creator-for-woocommerce' ),
				'id'      => (int) $existing->term_id,
			);
		}

		$result = wp_insert_term(
			$name,
			'product_cat',
			array(
				'description' => $description,
				'parent'      => $parent_id,
				'slug'        => '' !== $slug ? $slug : sanitize_title( $name ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return array(
				'name'    => $name,
				'status'  => 'failed',
				'message' => $result->get_error_message(),
			);
		}

		$term_id = (int) $result['term_id'];

		if ( ! empty( $entry['thumbnail_id'] ) ) {
			update_term_meta( $term_id, 'thumbnail_id', absint( $entry['thumbnail_id'] ) );
		}

		/**
		 * Fires after a category has been created.
		 *
		 * @param int    $term_id Term ID.
		 * @param string $name    Category name.
		 */
		do_action( 'bulk_add_cat_category_created', $term_id, $name );

		return array(
			'name'    => $name,
			'status'  => 'created',
			'message' => sprintf(
				/* translators: %s: category name. */
				__( 'Category "%s" created.', 'mloun-bulk-category-creator-for-woocommerce' ),
				$name
			),
			'id'      => $term_id,
		);
	}

	/* -----------------------------------------------------------------
	 * Brands.
	 * ----------------------------------------------------------------- */

	/**
	 * Process a batch of brands.
	 *
	 * @param array $data Items: taxonomy, name, parent_id, description, slug, thumbnail_id.
	 * @return array
	 */
	private function process_brands( $data ) {
		$taxonomies = $this->get_brand_taxonomies();

		if ( empty( $taxonomies ) ) {
			return array(
				'success'       => false,
				'message'       => __( 'No brand plugin detected. Install one of: Perfect WooCommerce Brands, YITH WooCommerce Brands, WooCommerce Brands.', 'mloun-bulk-category-creator-for-woocommerce' ),
				'created_count' => 0,
				'skipped_count' => 0,
				'failed_count'  => count( $data ),
				'skipped'       => array(),
				'errors'        => array(
					array(
						'name'  => __( 'All items', 'mloun-bulk-category-creator-for-woocommerce' ),
						'error' => __( 'no brand taxonomy available', 'mloun-bulk-category-creator-for-woocommerce' ),
					),
				),
			);
		}

		$default_taxonomy = array_key_first( $taxonomies );

		$items = array();
		foreach ( $data as $entry ) {
			$entry = (array) $entry;

			$taxonomy = sanitize_key( $entry['taxonomy'] ?? '' );
			if ( '' === $taxonomy || ! isset( $taxonomies[ $taxonomy ] ) ) {
				$taxonomy = $default_taxonomy;
			}

			$entry['taxonomy'] = $taxonomy;
			$items[]           = $this->create_brand( $entry, $taxonomy );
		}

		return $this->build_summary( $items );
	}

	/**
	 * Find an existing term the incoming item would collide with.
	 *
	 * Hierarchical taxonomies are matched by parent + name/slug so two children
	 * with the same name under different parents can coexist.
	 *
	 * @param string $taxonomy  Taxonomy slug.
	 * @param string $name      Term name.
	 * @param string $slug      Term slug (may be empty).
	 * @param int    $parent_id Parent term ID (0 for top level).
	 * @return WP_Term|null
	 */
	private function find_existing_term( $taxonomy, $name, $slug, $parent_id ) {
		$needles = array();

		if ( '' !== (string) $slug ) {
			$needles[] = (string) $slug;
		}

		$needles[] = (string) $name;

		$hierarchical = is_taxonomy_hierarchical( $taxonomy );

		foreach ( $needles as $needle ) {
			if ( '' === $needle ) {
				continue;
			}

			$found = $hierarchical
				? term_exists( $needle, $taxonomy, (int) $parent_id )
				: term_exists( $needle, $taxonomy );

			if ( ! $found ) {
				continue;
			}

			$term_id = is_array( $found ) ? (int) ( $found['term_id'] ?? 0 ) : (int) $found;
			$term    = $term_id > 0 ? get_term( $term_id, $taxonomy ) : null;

			if ( $term && ! is_wp_error( $term ) ) {
				return $term;
			}
		}

		return null;
	}

	/**
	 * Create a single brand term.
	 *
	 * @param array  $entry    Raw item (includes 'taxonomy').
	 * @param string $taxonomy Target taxonomy.
	 * @return array
	 */
	private function create_brand( $entry, $taxonomy ) {
		$name        = sanitize_text_field( $entry['name'] ?? '' );
		$slug        = sanitize_title( $entry['slug'] ?? '' );
		$description = sanitize_textarea_field( $entry['description'] ?? '' );
		$parent_id   = absint( $entry['parent_id'] ?? 0 );

		if ( '' === $name ) {
			return array(
				'name'    => __( 'Unnamed item', 'mloun-bulk-category-creator-for-woocommerce' ),
				'status'  => 'failed',
				'message' => __( 'Brand name must not be empty.', 'mloun-bulk-category-creator-for-woocommerce' ),
			);
		}

		// Duplicate detection: never overwrite. Scoped to the parent for
		// hierarchical brand taxonomies.
		$existing = $this->find_existing_term( $taxonomy, $name, $slug, $parent_id );
		if ( $existing ) {
			return array(
				'name'    => $name,
				'status'  => 'skipped',
				'message' => __( 'Brand already exists.', 'mloun-bulk-category-creator-for-woocommerce' ),
				'id'      => (int) $existing->term_id,
			);
		}

		$result = wp_insert_term(
			$name,
			$taxonomy,
			array(
				'description' => $description,
				'slug'        => '' !== $slug ? $slug : sanitize_title( $name ),
				'parent'      => $parent_id,
			)
		);

		if ( is_wp_error( $result ) ) {
			return array(
				'name'    => $name,
				'status'  => 'failed',
				'message' => $result->get_error_message(),
			);
		}

		$term_id = (int) $result['term_id'];

		if ( ! empty( $entry['thumbnail_id'] ) ) {
			update_term_meta( $term_id, 'thumbnail_id', absint( $entry['thumbnail_id'] ) );
		}

		// Perfect WooCommerce Brands stores the brand website URL.
		if ( 'pwb-brand' === $taxonomy && ! empty( $entry['brand_url'] ) ) {
			update_term_meta( $term_id, 'pwb-brand-url', esc_url_raw( $entry['brand_url'] ) );
		}

		/**
		 * Fires after a brand has been created.
		 *
		 * @param int    $term_id  Term ID.
		 * @param string $taxonomy Taxonomy the brand was created in.
		 * @param string $name     Brand name.
		 */
		do_action( 'bulk_add_cat_brand_created', $term_id, $taxonomy, $name );

		return array(
			'name'    => $name,
			'status'  => 'created',
			'message' => sprintf(
				/* translators: %s: brand name. */
				__( 'Brand "%s" created.', 'mloun-bulk-category-creator-for-woocommerce' ),
				$name
			),
			'id'      => $term_id,
		);
	}

	/* -----------------------------------------------------------------
	 * Attributes.
	 * ----------------------------------------------------------------- */

	/**
	 * Process a batch of global attributes.
	 *
	 * @param array $data Items: name, values, type, slug.
	 * @return array
	 */
	private function process_attributes( $data ) {
		$items = array();
		foreach ( $data as $entry ) {
			$items[] = $this->create_attribute( $entry );
		}
		return $this->build_summary( $items );
	}

	/**
	 * Create a single global attribute (and its values).
	 *
	 * @param mixed $entry Raw item.
	 * @return array
	 */
	private function create_attribute( $entry ) {
		global $wpdb;

		$entry = (array) $entry;

		$name   = sanitize_text_field( $entry['name'] ?? '' );
		$slug   = sanitize_title( $entry['slug'] ?? '' );
		$type   = strtolower( sanitize_key( $entry['type'] ?? 'select' ) );
		$values = isset( $entry['values'] ) && is_array( $entry['values'] ) ? $entry['values'] : array();

		// Validate the type against the WooCommerce whitelist.
		if ( ! in_array( $type, self::ATTRIBUTE_TYPES, true ) ) {
			$type = 'select';
		}

		if ( '' === $name ) {
			return array(
				'name'    => __( 'Unnamed item', 'mloun-bulk-category-creator-for-woocommerce' ),
				'status'  => 'failed',
				'message' => __( 'Attribute name must not be empty.', 'mloun-bulk-category-creator-for-woocommerce' ),
			);
		}

		$attribute_slug = '' !== $slug ? $slug : sanitize_title( $name );
		$existing       = $this->get_attribute_by_name( $name, $attribute_slug );

		if ( $existing ) {
			$attribute_id   = (int) $existing->attribute_id;
			$attribute_slug = (string) $existing->attribute_name;

			if ( ! empty( $values ) ) {
				$values_result = $this->add_attribute_values( $attribute_slug, $values );
				return array(
					'name'    => $name,
					'status'  => 'created',
					'message' => sprintf(
						/* translators: 1: attribute name, 2: number of new values. */
						__( 'Attribute "%1$s" already exists; %2$d new value(s) added.', 'mloun-bulk-category-creator-for-woocommerce' ),
						$name,
						count( $values_result['added'] )
					),
					'id'      => $attribute_id,
				);
			}

			return array(
				'name'    => $name,
				'status'  => 'skipped',
				'message' => __( 'Attribute already exists.', 'mloun-bulk-category-creator-for-woocommerce' ),
				'id'      => $attribute_id,
			);
		}

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'woocommerce_attribute_taxonomies',
			array(
				'attribute_name'    => $attribute_slug,
				'attribute_label'   => $name,
				'attribute_type'    => $type,
				'attribute_orderby' => 'menu_order',
				'attribute_public'  => 1,
			),
			array( '%s', '%s', '%s', '%s', '%d' )
		);

		if ( false === $inserted ) {
			return array(
				'name'    => $name,
				'status'  => 'failed',
				'message' => __( 'Could not create the attribute record in the database.', 'mloun-bulk-category-creator-for-woocommerce' ),
			);
		}

		$attribute_id = (int) $wpdb->insert_id;

		// Make sure the pa_* taxonomy is registered for this request.
		$this->ensure_attribute_taxonomy( $attribute_slug, $name );

		if ( ! empty( $values ) ) {
			$this->add_attribute_values( $attribute_slug, $values );
		}

		// Same cache routine WooCommerce itself uses when creating attributes.
		$this->invalidate_attribute_caches();

		/**
		 * Fires after a global attribute has been created.
		 *
		 * @param int    $attribute_id   Attribute ID.
		 * @param string $attribute_slug Attribute slug (taxonomy without the pa_ prefix).
		 * @param string $name           Attribute label.
		 */
		do_action( 'bulk_add_cat_attribute_created', $attribute_id, $attribute_slug, $name );

		return array(
			'name'    => $name,
			'status'  => 'created',
			'message' => sprintf(
				/* translators: %s: attribute name. */
				__( 'Attribute "%s" created.', 'mloun-bulk-category-creator-for-woocommerce' ),
				$name
			),
			'id'      => $attribute_id,
		);
	}

	/**
	 * Find a global attribute by label or slug.
	 *
	 * @param string $label Label (display name).
	 * @param string $slug  Slug.
	 * @return object|null
	 */
	private function get_attribute_by_name( $label, $slug ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery -- table is a fixed WooCommerce table.
		$existing = $wpdb->get_row(
			$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.InterpretableTable
				"SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_label = %s OR attribute_name = %s LIMIT 1",
				$label,
				$slug
			)
		);
		// phpcs:enable

		return $existing;
	}

	/**
	 * Ensure the pa_* taxonomy of an attribute is registered in this request.
	 *
	 * WooCommerce registers global attribute taxonomies dynamically; for a
	 * freshly inserted attribute row the taxonomy may not exist yet, which
	 * would make get_term_by()/wp_insert_term() fail with "Unknown taxonomy".
	 *
	 * @param string $attribute_slug Attribute slug.
	 * @param string $label          Display label.
	 */
	private function ensure_attribute_taxonomy( $attribute_slug, $label = '' ) {
		$taxonomy = function_exists( 'wc_attribute_taxonomy_name' )
			? wc_attribute_taxonomy_name( $attribute_slug )
			: 'pa_' . $attribute_slug;

		if ( taxonomy_exists( $taxonomy ) ) {
			return;
		}

		register_taxonomy(
			$taxonomy,
			'product',
			array(
				'labels'       => array(
					'name'          => '' !== $label ? $label : ucwords( str_replace( '-', ' ', $attribute_slug ) ),
					'singular_name' => '' !== $label ? $label : ucwords( str_replace( '-', ' ', $attribute_slug ) ),
				),
				'public'       => true,
				'show_ui'      => true,
				'show_in_menu' => false,
				'hierarchical' => true,
			)
		);
	}

	/**
	 * Add values to an attribute taxonomy, skipping existing ones.
	 *
	 * @param string $attribute_slug Attribute slug.
	 * @param array  $values         Values as strings or {name, slug} arrays.
	 * @return array{added: string[], skipped: string[]}
	 */
	private function add_attribute_values( $attribute_slug, $values ) {
		$taxonomy = function_exists( 'wc_attribute_taxonomy_name' )
			? wc_attribute_taxonomy_name( $attribute_slug )
			: 'pa_' . $attribute_slug;

		$this->ensure_attribute_taxonomy( $attribute_slug );

		$added   = array();
		$skipped = array();

		foreach ( (array) $values as $value ) {
			if ( is_array( $value ) ) {
				$value_name = sanitize_text_field( $value['name'] ?? '' );
				$value_slug = sanitize_title( $value['slug'] ?? '' );
			} else {
				$value_name = sanitize_text_field( (string) $value );
				$value_slug = '';
			}

			if ( '' === $value_name ) {
				continue;
			}

			$existing = get_term_by( 'name', $value_name, $taxonomy );
			if ( ! $existing && '' !== $value_slug ) {
				$existing = get_term_by( 'slug', $value_slug, $taxonomy );
			}
			if ( $existing && ! is_wp_error( $existing ) ) {
				$skipped[] = $value_name;
				continue;
			}

			$result = wp_insert_term(
				$value_name,
				$taxonomy,
				array(
					'slug' => '' !== $value_slug ? $value_slug : sanitize_title( $value_name ),
				)
			);

			if ( is_wp_error( $result ) ) {
				$skipped[] = $value_name;
			} else {
				$added[] = $value_name;
			}
		}

		return array(
			'added'   => $added,
			'skipped' => $skipped,
		);
	}

	/**
	 * Invalidate the caches WooCommerce keeps for attribute data.
	 */
	private function invalidate_attribute_caches() {
		delete_transient( 'wc_attribute_taxonomies' );

		if ( class_exists( 'WC_Cache_Helper' ) && method_exists( 'WC_Cache_Helper', 'invalidate_cache_group' ) ) {
			WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
		}

		// Force a rewrite flush so new attribute archives resolve.
		if ( function_exists( 'wp_schedule_single_event' ) ) {
			wp_schedule_single_event( time(), 'woocommerce_flush_rewrite_rules' );
		}
	}

	/* -----------------------------------------------------------------
	 * Result summary.
	 * ----------------------------------------------------------------- */

	/**
	 * Aggregate per-item results into the response contract.
	 *
	 * @param array  $items   Per-item results (name, status, message, id?).
	 * @param string $message Optional summary message override.
	 * @return array
	 */
	private function build_summary( array $items, $message = '' ) {
		$created = 0;
		$skipped = 0;
		$failed  = 0;

		$skipped_list = array();
		$errors_list  = array();

		foreach ( $items as $item ) {
			switch ( $item['status'] ?? '' ) {
				case 'created':
					$created++;
					break;
				case 'skipped':
					$skipped++;
					$skipped_list[] = array(
						'name'   => $item['name'] ?? '',
						'reason' => $item['message'] ?? '',
						'id'     => isset( $item['id'] ) ? $item['id'] : null,
					);
					break;
				case 'failed':
					$failed++;
					$errors_list[] = array(
						'name'  => $item['name'] ?? '',
						'error' => $item['message'] ?? '',
					);
					break;
			}
		}

		if ( '' === $message ) {
			/* translators: 1: created count, 2: skipped count, 3: failed count. */
			$message = sprintf( __( '%1$d created, %2$d skipped, %3$d failed.', 'mloun-bulk-category-creator-for-woocommerce' ), $created, $skipped, $failed );
		}

		return array(
			'success'       => 0 === $failed,
			'message'       => $message,
			'created_count' => $created,
			'skipped_count' => $skipped,
			'failed_count'  => $failed,
			'skipped'       => $skipped_list,
			'errors'        => $errors_list,
		);
	}
}
