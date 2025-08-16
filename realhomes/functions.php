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
