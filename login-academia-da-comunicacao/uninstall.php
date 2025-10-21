<?php
/**
 * Fired when the plugin is uninstalled.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$option_key = 'adc_login_options';
delete_option( $option_key );
delete_site_option( $option_key );

$meta_keys = array(
    'adc_twofa_pending',
    'adc_twofa_code_hash',
    'adc_twofa_expires',
    'adc_twofa_last_sent',
    'adc_gender',
);

if ( function_exists( 'get_users' ) ) {
    $users = get_users( array( 'fields' => 'ID' ) );

    foreach ( $users as $user_id ) {
        foreach ( $meta_keys as $meta_key ) {
            delete_user_meta( $user_id, $meta_key );
        }
    }
}
