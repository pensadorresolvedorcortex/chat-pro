<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
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
