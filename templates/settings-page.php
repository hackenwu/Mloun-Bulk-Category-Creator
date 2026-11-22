<?php
/**
 * Settings page template.
 *
 * @package Bulk_Add_Cat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap bulk-add-cat-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<p><?php esc_html_e( 'Tune how bulk batches are submitted. Changes apply to the next bulk add run.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>

	<form method="post" action="options.php">
		<?php settings_fields( Bulk_Add_Cat_Settings::OPTION_GROUP ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="bulk_add_cat_batch_size"><?php esc_html_e( 'Batch Size', 'mloun-bulk-category-creator-for-woocommerce' ); ?></label></th>
				<td>
					<input type="number" id="bulk_add_cat_batch_size" name="bulk_add_cat_batch_size" value="<?php echo esc_attr( get_option( 'bulk_add_cat_batch_size', 50 ) ); ?>" min="1" max="500" class="small-text">
					<p class="description"><?php esc_html_e( 'Items per AJAX request (1-500). Lower values keep requests fast on large catalogs.', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="bulk_add_cat_batch_delay"><?php esc_html_e( 'Batch Delay', 'mloun-bulk-category-creator-for-woocommerce' ); ?></label></th>
				<td>
					<input type="number" id="bulk_add_cat_batch_delay" name="bulk_add_cat_batch_delay" value="<?php echo esc_attr( get_option( 'bulk_add_cat_batch_delay', 1000 ) ); ?>" min="0" max="30000" class="small-text">
					<p class="description"><?php esc_html_e( 'Milliseconds to wait between batches (0-30000).', 'mloun-bulk-category-creator-for-woocommerce' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Logging', 'mloun-bulk-category-creator-for-woocommerce' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="bulk_add_cat_logging" value="1" <?php checked( (int) get_option( 'bulk_add_cat_logging', 0 ), 1 ); ?>>
						<?php esc_html_e( 'Write operation logs to wp-content/uploads/bulk-add-categories/logs/ (off by default)', 'mloun-bulk-category-creator-for-woocommerce' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
