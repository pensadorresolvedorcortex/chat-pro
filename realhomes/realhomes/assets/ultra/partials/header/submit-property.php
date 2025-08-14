<?php
$show_submit_button = get_option( 'inspiry_show_submit_on_login', 'false' );
$submit_url         = '';

if ( is_user_logged_in() || inspiry_guest_submission_enabled() ) {
	$login_required = '';
} else {
	$login_required = ' inspiry_submit_login_required ';
}

if ( realhomes_get_dashboard_page_url() && realhomes_dashboard_module_enabled( 'inspiry_submit_property_module_display' ) ) {
	$submit_url = realhomes_get_dashboard_page_url( 'properties&submodule=submit-property' );
}

if ( ! empty( $submit_url ) && ( 'hide' !== $show_submit_button ) ) {

	if ( inspiry_no_membership_disable_stuff() ) {

		$theme_submit_button_text = get_option( 'theme_submit_button_text' );
		if ( empty( $theme_submit_button_text ) ) {
			$theme_submit_button_text = esc_html__( 'Submit', RH_TEXT_DOMAIN );
		}

$submit_link_format = '<div class="rh-ultra-submit"><a class="%s" href="%s">%s</a><button aria-label="Buscar" class="Header-module--link--3rH8w Header-module--searchLink--ku0tv rh-search-toggle"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="Header-module--iconSearchDesktop--29aB_"><path fill-rule="evenodd" clip-rule="evenodd" d="M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0zm-1.562 5.645a7.5 7.5 0 11.707-.707l5.209 5.208-.708.708-5.208-5.209z" fill="currentColor"></path></svg></button></div>';
		if ( 'true' === $show_submit_button ) {
			if ( realhomes_get_current_user_role_option( 'property_submit' ) || inspiry_guest_submission_enabled() ) {
				printf( $submit_link_format, esc_attr( $login_required ), esc_url( $submit_url ), esc_html( $theme_submit_button_text ) );
			}
		} else {
			printf( $submit_link_format, esc_attr( $login_required ), esc_url( $submit_url ), esc_html( $theme_submit_button_text ) );
		}
	}
}