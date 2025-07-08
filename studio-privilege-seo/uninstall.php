<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'sp_seo_title' );
delete_option( 'sp_seo_meta_tags' );
delete_option( 'sp_seo_jsonld' );
delete_option( 'sp_seo_canonical' );
delete_option( 'sp_seo_last_fetch' );
delete_option( 'sp_seo_etag' );
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . SP_SEO_CLICK_TABLE);
delete_option( 'sp_seo_last_modified' );
?>
