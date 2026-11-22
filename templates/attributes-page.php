<?php
/**
 * Product attributes page template.
 *
 * Expects $attributes (array of attribute objects, possibly id-keyed) to be
 * set by Bulk_Add_Cat_Admin::render_attributes_page().
 *
 * @package Bulk_Add_Cat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound */
$attributes = isset( $attributes ) ? (array) $attributes : array();
?>

<div class="wrap bulk-add-cat-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="bac-two-col">
		<div class="bac-two-col-main">

			<?php if ( ! empty( $attributes ) ) : ?>
			<div class="bac-form-section">
				<h3><?php esc_html_e( 'Existing attributes', 'mloun-bulk-category-creator-for-woocommerce' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Already present in your store:', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Attribute', 'mloun-bulk-category-creator-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Slug', 'mloun-bulk-category-creator-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Type', 'mloun-bulk-category-creator-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Value Count', 'mloun-bulk-category-creator-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $attributes as $attr ) : ?>
							<?php
							if ( ! is_object( $attr ) ) {
								continue;
							}
							$tax   = function_exists( 'wc_attribute_taxonomy_name' ) ? wc_attribute_taxonomy_name( $attr->attribute_name ) : 'pa_' . $attr->attribute_name;
							$count = wp_count_terms(
								array(
									'taxonomy'   => $tax,
									'hide_empty' => false,
								)
							);
							?>
							<tr>
								<td><?php echo esc_html( $attr->attribute_label ); ?></td>
								<td><code><?php echo esc_html( $attr->attribute_name ); ?></code></td>
								<td><?php echo esc_html( $attr->attribute_type ); ?></td>
								<td><?php echo esc_html( $count ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endif; ?>

			<div class="bac-form-section">
				<h2><?php esc_html_e( 'Bulk Add Attributes', 'mloun-bulk-category-creator-for-woocommerce' ); ?></h2>

				<form class="bac-form" data-type="attributes">
					<div class="bac-form-group">
						<label for="bulk_data"><?php esc_html_e( 'Attribute data', 'mloun-bulk-category-creator-for-woocommerce' ); ?></label>
						<textarea
							name="bulk_data"
							id="bulk_data"
							rows="15"
							placeholder="<?php echo esc_attr( __( 'Enter attribute data here, one per line...', 'mloun-bulk-category-creator-for-woocommerce' ) ); ?>"
						></textarea>
						<p class="description"><?php esc_html_e( 'Lines starting with # or // are comments; blank lines are skipped. Attribute values are comma-separated.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
					</div>

					<div class="bac-button-group">
						<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Start Bulk Add', 'mloun-bulk-category-creator-for-woocommerce' ); ?></button>
						<button type="button" class="button button-secondary bac-clear-btn"><?php esc_html_e( 'Clear Content', 'mloun-bulk-category-creator-for-woocommerce' ); ?></button>
						<button type="button" class="button button-secondary bac-load-example" data-type="attributes"><?php esc_html_e( 'Load Example', 'mloun-bulk-category-creator-for-woocommerce' ); ?></button>
					</div>
				</form>

				<div class="bac-loading">
					<span class="spinner is-active"></span>
					<span><?php esc_html_e( 'Working, please wait...', 'mloun-bulk-category-creator-for-woocommerce' ); ?></span>
				</div>

				<div class="bac-progress">
					<div class="bac-progress-bar"><div class="bac-progress-fill"></div></div>
					<div class="bac-progress-text"></div>
				</div>

				<div class="bac-results"></div>
			</div>

		</div><!-- .bac-two-col-main -->

		<div class="bac-two-col-side">
			<div class="bac-syntax-help">
				<h4><?php esc_html_e( 'Data format', 'mloun-bulk-category-creator-for-woocommerce' ); ?></h4>
				<p><?php esc_html_e( 'One attribute per line, fields separated by the pipe character:', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
				<pre><code><?php esc_html_e( 'Name | Values (comma separated) | Type | Slug', 'mloun-bulk-category-creator-for-woocommerce' ); ?></code></pre>
				<ul>
					<li><strong><?php esc_html_e( 'Name', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong> - <?php esc_html_e( 'required, display name of the attribute (e.g. "Color", "Size")', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
					<li><strong><?php esc_html_e( 'Values', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong> - <?php esc_html_e( 'optional, comma-separated attribute values (e.g. "Red,Blue,Black")', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
					<li><strong><?php esc_html_e( 'Type', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong> - <?php esc_html_e( 'optional, default "select". Accepted values: select, text, textarea, number, email, color, date, url', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
					<li><strong><?php esc_html_e( 'Slug', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong> - <?php esc_html_e( 'optional, URL-friendly identifier; auto-generated when empty', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
				</ul>
				<p><strong><?php esc_html_e( 'Example:', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong></p>
				<pre><code>Color | Red,Blue,Black,White | select | color
Size | S,M,L,XL,XXL | select | size
Material | Cotton,Polyester,Wool,Silk | select | material
Origin | | select | origin</code></pre>
				<p class="description"><?php esc_html_e( 'Tip: if the attribute already exists, its new values are added to the existing attribute.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
			</div>
		</div><!-- .bac-two-col-side -->
	</div><!-- .bac-two-col -->

	<p style="margin-top: 20px;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulk-add-categories' ) ); ?>" class="button">
			<span class="dashicons dashicons-arrow-left-alt" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;"></span>
			<?php esc_html_e( 'Back to Dashboard', 'mloun-bulk-category-creator-for-woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=product_attributes' ) ); ?>" class="button" target="_blank" rel="noopener">
			<span class="dashicons dashicons-external" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;"></span>
			<?php esc_html_e( 'Manage Product Attributes', 'mloun-bulk-category-creator-for-woocommerce' ); ?>
		</a>
	</p>
</div>