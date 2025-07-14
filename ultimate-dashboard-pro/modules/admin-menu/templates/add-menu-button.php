<?php
/**
 * Add menu button template.
 *
 * @package Ultimate_Dashboard_Pro
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );

return function () {
	?>

	<button type="button" class="udb-menu-builder--add-item udb-menu-builder--add-new-menu">
		<i class="dashicons dashicons-plus"></i>
		<?php _e( 'Add Menu Item', 'ultimatedashboard' ); ?>
	</button>

	<?php
};
