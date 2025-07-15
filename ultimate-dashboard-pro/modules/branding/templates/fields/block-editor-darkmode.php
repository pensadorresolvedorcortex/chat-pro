<?php
/**
 * The "Enable block editor darkmode" field.
 *
 * @package Ultimate_Dashboard_PRO
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );

/**
 * Outputting "enable block editor darkmode" field.
 */
return function () {

	$settings   = get_option( 'udb_branding' );
	$is_checked = isset( $settings['block_editor_darkmode'] ) && $settings['block_editor_darkmode'];

	$field_description = __(
		'Enable dark mode for the block editor (Gutenberg).',
		'ultimatedashboard'
	);
	?>

	<label for="udb_branding--block_editor_darkmode" class="toggle-switch">
		<input
			type="checkbox"
			name="udb_branding[block_editor_darkmode]"
			id="udb_branding--block_editor_darkmode"
			value="1"
			<?php checked( $is_checked, true ); ?>
		/>
		<div class="switch-track">
			<div class="switch-thumb"></div>
		</div>
	</label>

	<p class="description">
		<?php echo esc_html( $field_description ); ?>
	</p>

	<?php

};
