<?php
/**
 * Block editor logo field.
 *
 * @package Ultimate_Dashboard_PRO
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );

return function () {

	$branding           = get_option( 'udb_branding' );
	$block_editor_logo = isset( $branding['block_editor_logo_image'] ) ? $branding['block_editor_logo_image'] : false;

	if ( function_exists( 'wp_enqueue_media' ) ) {
		wp_enqueue_media();
	} else {
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );
	}

	?>

	<input type="text" name="udb_branding[block_editor_logo_image]" value="<?php echo esc_url( $block_editor_logo ); ?>" class="all-options udb-branding-upload-image">
	<button type="button" class="udb-branding-admin-bar-logo-upload button-secondary" data-media-library-title="Block Editor Logo">
		<?php _e( 'Add or Upload File', 'ultimatedashboard' ); ?>
	</button>
	<a href="#" class="udb-branding-clear-upload button-secondary">x</a>

	<p class="description">
		<?php _e( 'Replace the logo on the top-left inside the WordPress block editor.', 'ultimatedashboard' ); ?><br>
		<?php _e( '<strong>Recommended image size:</strong> 512px x 512px.', 'ultimatedashboard' ); ?>
	</p>

	<?php

};
