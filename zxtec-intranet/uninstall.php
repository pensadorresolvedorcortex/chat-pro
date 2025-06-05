<?php
// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove custom role
remove_role( 'zxtec_colaborador' );

// Delete user meta
$meta_keys = array( 'zxtec_lat', 'zxtec_lng', 'zxtec_specialty', 'zxtec_cost_km', 'zxtec_notifications' );
$users = get_users();
foreach ( $users as $user ) {
    foreach ( $meta_keys as $key ) {
        delete_user_meta( $user->ID, $key );
    }
}

// Delete custom posts
$post_types = array( 'zxtec_client', 'zxtec_service', 'zxtec_order', 'zxtec_contract', 'zxtec_expense' );
foreach ( $post_types as $pt ) {
    $posts = get_posts( array( 'post_type' => $pt, 'numberposts' => -1, 'post_status' => 'any' ) );
    foreach ( $posts as $p ) {
        wp_delete_post( $p->ID, true );
    }
}

// Remove options
delete_option( 'zxtec_commission' );
