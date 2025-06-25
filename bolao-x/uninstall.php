<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

$types = array( 'bolaox_aposta', 'bolaox_result' );
$posts = get_posts( array(
    'post_type'   => $types,
    'numberposts' => -1,
    'post_status' => 'any',
) );
foreach ( $posts as $post ) {
    wp_delete_post( $post->ID, true );
}

delete_option( 'bolaox_result' );
delete_option( 'bolaox_cutoffs' );
delete_option( 'bolaox_pix_key' );

