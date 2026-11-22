<?php
/**
 * Product categories page template.
 *
 * Expects $categories (array) and $top_terms (WP list of terms or WP_Error)
 * to be set by Bulk_Add_Cat_Admin::render_categories_page().
 *
 * @package Bulk_Add_Cat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound */
$categories = isset( $categories ) ? (array) $categories : array();
$top_terms  = isset( $top_terms ) ? $top_terms : array();
?>

<div class="wrap bulk-add-cat-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="bac-two-col">
		<div class="bac-two-col-main">

			<div class="bac-form-section">
				<h3><?php esc_html_e( 'Parent category', 'mloun-bulk-category-creator-for-woocommerce' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Choose the parent for all new categories (top level when left empty).', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
				<div class="bac-form-group">
					<select name="parent_category" id="parent_category">
						<option value="0">-- <?php esc_html_e( 'Top level (no parent)', 'mloun-bulk-category-creator-for-woocommerce' ); ?> --</option>
						<?php foreach ( $categories as $cat ) : ?>
							<option value="<?php echo esc_attr( $cat['id'] ); ?>">
								<?php
								$pad   = str_repeat( "\u{00A0}", 4 * (int) $cat['level'] );
								$mark  = (int) $cat['level'] > 0 ? '└ ' : '';
								echo esc_html( $pad . $mark . $cat['name'] . ' (ID: ' . $cat['id'] . ')' );
								?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<?php if ( ! empty( $top_terms ) && ! is_wp_error( $top_terms ) ) : ?>
			<div class="bac-form-section">
				<h3><?php esc_html_e( 'Existing top-level categories', 'mloun-bulk-category-creator-for-woocommerce' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Already present in your store:', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
				<div>
					<?php foreach ( $top_terms as $cat ) : ?>
						<code style="margin-right: 10px; margin-bottom: 5px; display: inline-block;"><?php echo esc_html( $cat->name ); ?></code>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<div class="bac-form-section">
				<h2><?php esc_html_e( 'Bulk Add Categories', 'mloun-bulk-category-creator-for-woocommerce' ); ?></h2>

				<form class="bac-form" data-type="categories">
					<div class="bac-form-group">
						<label for="bulk_data"><?php esc_html_e( 'Category data', 'mloun-bulk-category-creator-for-woocommerce' ); ?></label>
						<textarea
							name="bulk_data"
							id="bulk_data"
							rows="15"
							placeholder="<?php echo esc_attr( __( 'Enter category data here, one per line...', 'mloun-bulk-category-creator-for-woocommerce' ) ); ?>"
						></textarea>
						<p class="description"><?php esc_html_e( 'Lines starting with # or // are comments; blank lines are skipped.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
					</div>

					<div class="bac-button-group">
						<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Start Bulk Add', 'mloun-bulk-category-creator-for-woocommerce' ); ?></button>
						<button type="button" class="button button-secondary bac-clear-btn"><?php esc_html_e( 'Clear Content', 'mloun-bulk-category-creator-for-woocommerce' ); ?></button>
						<button type="button" class="button button-secondary bac-load-example" data-type="categories"><?php esc_html_e( 'Load Example', 'mloun-bulk-category-creator-for-woocommerce' ); ?></button>
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
				<p><?php esc_html_e( 'One category per line, fields separated by the pipe character:', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
				<pre><code><?php esc_html_e( 'Name | Parent | Description | Slug', 'mloun-bulk-category-creator-for-woocommerce' ); ?></code></pre>
				<ul>
					<li><strong><?php esc_html_e( 'Name', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong> - <?php esc_html_e( 'required, display name of the category', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
					<li><strong><?php esc_html_e( 'Parent', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong> - <?php esc_html_e( 'optional, name of an existing parent category; empty for top level', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
					<li><strong><?php esc_html_e( 'Description', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong> - <?php esc_html_e( 'optional', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
					<li><strong><?php esc_html_e( 'Slug', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong> - <?php esc_html_e( 'optional, URL-friendly identifier; auto-generated when empty', 'mloun-bulk-category-creator-for-woocommerce' ); ?></li>
				</ul>
				<p><strong><?php esc_html_e( 'Example (with a parent selected in the dropdown, only names are needed):', 'mloun-bulk-category-creator-for-woocommerce' ); ?></strong></p>
				<pre><code>Mobile Phones
Tablets
Smart Watches
Headphones</code></pre>
				<p class="description"><?php esc_html_e( 'Tip: with a parent selected in the dropdown below, every new category is created under it. Items whose parent cannot be found are reported as failures.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
			</div>
		</div><!-- .bac-two-col-side -->
	</div><!-- .bac-two-col -->

	<p style="margin-top: 20px;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bulk-add-categories' ) ); ?>" class="button">
			<span class="dashicons dashicons-arrow-left-alt" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;"></span>
			<?php esc_html_e( 'Back to Dashboard', 'mloun-bulk-category-creator-for-woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' ) ); ?>" class="button" target="_blank" rel="noopener">
			<span class="dashicons dashicons-external" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;"></span>
			<?php esc_html_e( 'View All Categories', 'mloun-bulk-category-creator-for-woocommerce' ); ?>
		</a>
	</p>
</div>