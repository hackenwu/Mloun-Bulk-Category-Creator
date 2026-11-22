<?php
/**
 * Dashboard / overview page template.
 *
 * @package Bulk_Add_Cat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound */
$processor    = new Bulk_Add_Cat_Processor();
$brand_taxoms = $processor->get_brand_taxonomies();
?>

<div class="wrap bulk-add-cat-wrap">
	<h1>
		<?php echo esc_html( get_admin_page_title() ); ?>
		<span class="bac-menu-version">v<?php echo esc_html( BULK_ADD_CAT_VERSION ); ?></span>
	</h1>

	<p><?php esc_html_e( 'Welcome to Bulk Add Categories for WooCommerce. Pick a feature below to bulk-create product categories, brands or attributes.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>

	<div class="bac-cards">

		<div class="bac-card">
			<h3>
				<span class="dashicons dashicons-category"></span>
				<?php esc_html_e( 'Product Categories', 'mloun-bulk-category-creator-for-woocommerce' ); ?>
			</h3>
			<p><?php esc_html_e( 'Create product categories in bulk, including multi-level hierarchies. Pick a parent from the dropdown or per line.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulk-add-cat-categories' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Get started', 'mloun-bulk-category-creator-for-woocommerce' ); ?>
			</a>
		</div>

		<div class="bac-card">
			<h3>
				<span class="dashicons dashicons-tag"></span>
				<?php esc_html_e( 'Product Brands', 'mloun-bulk-category-creator-for-woocommerce' ); ?>
			</h3>
			<?php if ( ! empty( $brand_taxoms ) ) : ?>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: comma-separated list of detected brand plugins. */
							__( 'Detected brand plugins: %s', 'mloun-bulk-category-creator-for-woocommerce' ),
							implode( ', ', $brand_taxoms )
						)
					);
					?>
				</p>
			<?php else : ?>
				<p><?php esc_html_e( 'No brand plugin detected. Install Perfect WooCommerce Brands, YITH WooCommerce Brands or WooCommerce Brands to enable this feature.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
			<?php endif; ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulk-add-cat-brands' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Get started', 'mloun-bulk-category-creator-for-woocommerce' ); ?>
			</a>
		</div>

		<div class="bac-card">
			<h3>
				<span class="dashicons dashicons-admin-generic"></span>
				<?php esc_html_e( 'Product Attributes', 'mloun-bulk-category-creator-for-woocommerce' ); ?>
			</h3>
			<p><?php esc_html_e( 'Create global attributes (color, size, material, ...) and their values in one pass. Values are added to existing attributes automatically.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulk-add-cat-attributes' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Get started', 'mloun-bulk-category-creator-for-woocommerce' ); ?>
			</a>
		</div>

	</div>

	<div class="bac-form-section">
		<h2><?php esc_html_e( 'How it works', 'mloun-bulk-category-creator-for-woocommerce' ); ?></h2>

		<h3><?php esc_html_e( 'Data format', 'mloun-bulk-category-creator-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'Every feature reads a simple text list: one item per line, fields separated by the pipe character.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Feature', 'mloun-bulk-category-creator-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Data format', 'mloun-bulk-category-creator-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Example', 'mloun-bulk-category-creator-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Product categories', 'mloun-bulk-category-creator-for-woocommerce' ); ?></td>
					<td><code><?php esc_html_e( 'Name | Parent | Description | Slug', 'mloun-bulk-category-creator-for-woocommerce' ); ?></code></td>
					<td><code>Mobile Phones | Electronics | Smartphones and tablets | mobile-phones</code></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Product brands', 'mloun-bulk-category-creator-for-woocommerce' ); ?></td>
					<td><code><?php esc_html_e( 'Name | Description | Slug', 'mloun-bulk-category-creator-for-woocommerce' ); ?></code></td>
					<td><code>Apple | Apple Inc. | apple</code></td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Product attributes', 'mloun-bulk-category-creator-for-woocommerce' ); ?></td>
					<td><code><?php esc_html_e( 'Name | Values (comma separated) | Type | Slug', 'mloun-bulk-category-creator-for-woocommerce' ); ?></code></td>
					<td><code>Color | Red,Blue,Black | select | color</code></td>
				</tr>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Notes', 'mloun-bulk-category-creator-for-woocommerce' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'One item per line; blank lines are ignored.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
			<li><?php esc_html_e( 'Lines starting with # or // are treated as comments.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
			<li><?php esc_html_e( 'Existing items are skipped, never overwritten.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
			<li><?php esc_html_e( 'Slug is optional and auto-generated when left empty.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
			<li><?php esc_html_e( 'A parent must already exist with the exact name given; otherwise the item is reported as failed.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
		</ul>
	</div>
</div>
