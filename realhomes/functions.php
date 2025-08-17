<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure the theme's text domain constant is available.
if ( ! defined( 'RH_TEXT_DOMAIN' ) ) {
    define( 'RH_TEXT_DOMAIN', 'framework' );
}

// Load theme translations at the proper time to avoid early translation notices.
if ( ! function_exists( 'realhomes_load_textdomain' ) ) {
    function realhomes_load_textdomain() {
        load_theme_textdomain( RH_TEXT_DOMAIN, get_template_directory() . '/languages' );
    }
    add_action( 'init', 'realhomes_load_textdomain' );
}

/**
 * Fallback check for WooCommerce activation.
 *
 * Provides a lightweight implementation when the WooCommerce helper file is
 * missing, avoiding fatal errors during theme setup.
 *
 * @return bool True if WooCommerce is detected, false otherwise.
 */
if ( ! function_exists( 'realhomes_is_woocommerce_activated' ) ) {
    function realhomes_is_woocommerce_activated() {
        return class_exists( 'WooCommerce' );
    }
}

// Define framework path constant for reuse and load the theme framework.
if ( ! defined( 'INSPIRY_FRAMEWORK' ) ) {
    define( 'INSPIRY_FRAMEWORK', get_template_directory() . '/framework/' );
}

$framework = INSPIRY_FRAMEWORK . 'load.php';
if ( file_exists( $framework ) ) {
    require_once $framework;
}
