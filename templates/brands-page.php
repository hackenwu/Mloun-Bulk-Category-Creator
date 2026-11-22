<?php
/**
 * Product brands page template.
 *
 * Expects $brand_taxoms (array), $default_taxonomy (string) and
 * $parent_options (array) to be set by Bulk_Add_Cat_Admin::render_brands_page().
 *
 * @package Bulk_Add_Cat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound */
$brand_taxoms     = isset( $brand_taxoms ) ? (array) $brand_taxoms : array();
$default_taxonomy = isset( $default_taxonomy ) ? $default_taxonomy : '';
$parent_options   = isset( $parent_options ) ? (array) $parent_options : array();
?>

<div class="wrap bulk-add-cat-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( empty( $brand_taxoms ) ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'No brand plugin detected', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong><br>
				<?php esc_html_e( 'This feature needs one of the following brand plugins:', 'mloun-bulk-category-creator-for-woocommerce' ); ?>
			</p>
			<ul style="list-style: disc; margin-left: 20px;">
				<li><a href="https://wordpress.org/plugins/perfect-woocommerce-brands/" target="_blank" rel="noopener">Perfect WooCommerce Brands</a> (<?php esc_html_e( 'free', 'mloun-bulk-category-creator-for-woocommerce' ); ?>)</li>
				<li><a href="https://wordpress.org/plugins/yith-woocommerce-brands-add-on/" target="_blank" rel="noopener">YITH WooCommerce Brands</a> (<?php esc_html_e( 'free version', 'mloun-bulk-category-creator-for-woocommerce' ); ?>)</li>
				<li>WooCommerce Brands (<?php esc_html_e( 'official WooCommerce extension', 'mloun-bulk-category-creator-for-woocommerce' ); ?>)</li>
			</ul>
			<p><?php esc_html_e( 'Install and activate one of them, then refresh this page.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
		</div>
	<?php else : ?>
		<div class="notice notice-success">
			<p>
				<strong><?php esc_html_e( 'Detected brand plugins:', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong>
				<?php echo esc_html( implode( ', ', $brand_taxoms ) ); ?>
			</p>
		</div>
	<?php endif; ?>

	<div class="bac-two-col">
		<div class="bac-two-col-main">

			<?php if ( ! empty( $brand_taxoms ) ) : ?>

			<div class="bac-form-section">
				<h3><?php esc_html_e( 'Brand plugin &amp; parent brand', 'mloun-bulk-category-creator-for-woocommerce' ); ?></h3>

				<div class="bac-form-group">
					<label for="brand_taxonomy"><?php esc_html_e( 'Brand taxonomy (plugin)', 'mloun-bulk-category-creator-for-woocommerce' ); ?></label>
					<select name="brand_taxonomy" id="brand_taxonomy">
						<?php foreach ( $brand_taxoms as $taxonomy => $label ) : ?>
							<option value="<?php echo esc_attr( $taxonomy ); ?>" <?php selected( $default_taxonomy, $taxonomy ); ?>>
								<?php echo esc_html( $label ); ?> (<?php echo esc_html( $taxonomy ); ?>)
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<?php foreach ( $parent_options as $taxonomy => $options ) : ?>
					<div class="bac-brand-parent-group" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>">
						<div class="bac-form-group">
							<label for="parent_brand_<?php echo esc_attr( $taxonomy ); ?>"><?php echo esc_html( sprintf( /* translators: %s: brand plugin label. */ __( 'Parent %s brand', 'mloun-bulk-category-creator-for-woocommerce' ), $brand_taxoms[ $taxonomy ] ?? $taxonomy ) ); ?></label>
							<select name="parent_brand_<?php echo esc_attr( $taxonomy ); ?>" id="parent_brand_<?php echo esc_attr( $taxonomy ); ?>">
								<option value="0">-- <?php esc_html_e( 'Top level (no parent)', 'mloun-bulk-category-creator-for-woocommerce' ); ?> --</option>
								<?php foreach ( (array) $options as $brand ) : ?>
									<option value="<?php echo esc_attr( $brand['id'] ); ?>">
										<?php
										$pad  = str_repeat( "\u{00A0}", 4 * (int) $brand['level'] );
										$mark = (int) $brand['level'] > 0 ? '└ ' : '';
										echo esc_html( $pad . $mark . $brand['name'] . ' (ID: ' . $brand['id'] . ')' );
										?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php endif; ?>

			<div class="bac-form-section">
				<h2><?php esc_html_e( 'Bulk Add Brands', 'mloun-bulk-category-creator-for-woocommerce' ); ?></h2>

				<form class="bac-form" data-type="brands">
					<div class="bac-form-group">
						<label for="bulk_data"><?php esc_html_e( 'Brand data', 'mloun-bulk-category-creator-for-woocommerce' ); ?></label>
						<textarea
							name="bulk_data"
							id="bulk_data"
							rows="15"
							placeholder="<?php echo esc_attr( __( 'Enter brand data here, one per line...', 'mloun-bulk-category-creator-for-woocommerce' ) ); ?>"
							<?php echo empty( $brand_taxoms ) ? 'disabled' : ''; ?>
						></textarea>
						<p class="description"><?php esc_html_e( 'Lines starting with # or // are comments; blank lines are skipped.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
					</div>

					<div class="bac-button-group">
						<button type="submit" class="button button-primary button-hero" <?php echo empty( $brand_taxoms ) ? 'disabled' : ''; ?>><?php esc_html_e( 'Start Bulk Add', 'mloun-bulk-category-creator-for-woocommerce' ); ?></button>
						<button type="button" class="button button-secondary bac-clear-btn" <?php echo empty( $brand_taxoms ) ? 'disabled' : ''; ?>><?php esc_html_e( 'Clear Content', 'mloun-bulk-category-creator-for-woocommerce' ); ?></button>
						<button type="button" class="button button-secondary bac-load-example" data-type="brands" <?php echo empty( $brand_taxoms ) ? 'disabled' : ''; ?>><?php esc_html_e( 'Load Example', 'mloun-bulk-category-creator-for-woocommerce' ); ?></button>
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
				<p><?php esc_html_e( 'One brand per line, fields separated by the pipe character:', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
				<pre><code><?php esc_html_e( 'Name | Description | Slug', 'mloun-bulk-category-creator-for-woocommerce' ); ?></code></pre>
				<ul>
					<li><strong><?php esc_html_e( 'Name', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong> - <?php esc_html_e( 'required, display name of the brand', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
					<li><strong><?php esc_html_e( 'Description', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong> - <?php esc_html_e( 'optional', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
					<li><strong><?php esc_html_e( 'Slug', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong> - <?php esc_html_e( 'optional, URL-friendly identifier; auto-generated when empty', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
				</ul>
				<p><strong><?php esc_html_e( 'Example (with a parent selected in the dropdown, only names are needed):', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong></p>
				<pre><code>iPhone
iPad
MacBook
AirPods</code></pre>
			</div>
		</div><!-- .bac-two-col-side -->
	</div><!-- .bac-two-col -->

	<p style="margin-top: 20px;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulk-add-categories' ) ); ?>" class="button">
			<span class="dashicons dashicons-arrow-left-alt" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;"></span>
			<?php esc_html_e( 'Back to Dashboard', 'mloun-bulk-category-creator-for-woocommerce' ); ?>
		</a>
		<?php if ( $default_taxonomy ) : ?>
			<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . $default_taxonomy . '&post_type=product' ) ); ?>" class="button" target="_blank" rel="noopener">
				<span class="dashicons dashicons-external" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;"></span>
				<?php esc_html_e( 'View All Brands', 'mloun-bulk-category-creator-for-woocommerce' ); ?>
			</a>
		<?php endif; ?>
	</p>
</div>