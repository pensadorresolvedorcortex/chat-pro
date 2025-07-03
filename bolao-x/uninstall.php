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
delete_option( 'bolaox_mp_prod_public' );
delete_option( 'bolaox_mp_prod_token' );
delete_option( 'bolaox_mp_test_public' );
delete_option( 'bolaox_mp_test_token' );
delete_option( 'bolaox_mp_mode' );
delete_option( 'bolaox_price' );
delete_option( 'bolaox_pix_key' );

$upload = wp_upload_dir();
$dir    = trailingslashit( $upload['basedir'] ) . 'bolao-x';
if ( file_exists( $dir . '/mp-error.log' ) ) {
    unlink( $dir . '/mp-error.log' );
}
if ( file_exists( $dir . '/general.log' ) ) {
    unlink( $dir . '/general.log' );
}
if ( is_dir( $dir ) ) {
    rmdir( $dir );
}

