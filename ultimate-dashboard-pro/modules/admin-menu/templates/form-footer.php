<?php
/**
 * Admin menu's form footer template.
 *
 * @package Ultimate_Dashboard_Pro
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );

return function () {
	?>

	<div class="heatbox-left-footer">
		<button class="button button-large button-primary udb-menu-builder--button udb-menu-builder--submit-button">
			<i class="dashicons dashicons-yes"></i>
			<?php _e( 'Save Changes', 'ultimatedashboard' ); ?>
		</button>
	</div>
	<div class="heatbox-right-footer">
		<button type="button" class="button button-large button-danger udb-menu-builder--button udb-menu-builder--reset-button udb-menu-builder--reset-all" data-role="all">
			<?php _e( 'Reset All Menus', 'ultimatedashboard' ); ?>
		</button>

		<button type="button" class="button button-large button-danger udb-menu-builder--button udb-menu-builder--reset-button udb-menu-builder--reset-role" data-role="administrator">
			<?php _e( 'Reset Administrator Menu', 'ultimatedashboard' ); ?>
		</button>
	</div>

	<?php
};
