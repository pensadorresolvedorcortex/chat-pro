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

/**
 * Fallback for property price retrieval.
 *
 * Provides a safe default implementation when the framework's helper
 * functions are unavailable, preventing fatal errors in templates like the
 * slider that expect this utility.
 *
 * @param int|false $property_id Optional property ID. Defaults to the current
 *                               post inside the Loop.
 * @return string The property price or an empty string if none is found.
 */
if ( ! function_exists( 'get_property_price' ) ) {
    function get_property_price( $property_id = false ) {
        $property_id = $property_id ?: get_the_ID();
        if ( ! $property_id ) {
            return '';
        }

        $price = get_post_meta( $property_id, 'REAL_HOMES_property_price', true );

        return $price ? $price : '';
    }
}

/**
 * Fallback wrapper that echoes a property's price.
 *
 * The original theme provides a `property_price()` helper which prints the
 * property price. Some templates call this function directly, so we supply a
 * minimal implementation that simply echoes the result of
 * `get_property_price()` when the original helper is missing.
 *
 * @param int|false $property_id Optional property ID. Defaults to the current
 *                               post inside the Loop.
 */
if ( ! function_exists( 'property_price' ) ) {
    function property_price( $property_id = false ) {
        echo esc_html( get_property_price( $property_id ) );
    }
}

/**
 * Fallback for figure captions when image utilities are missing.
 *
 * Outputs the featured image caption for a given post if available. This keeps
 * templates like `property-for-home.php` from triggering fatal errors when the
 * original helper isn't bundled with the theme.
 *
 * @param int $post_id Optional. Post ID to retrieve the caption for.
 */
if ( ! function_exists( 'display_figcaption' ) ) {
    function display_figcaption( $post_id = 0 ) {
        $post_id      = $post_id ?: get_the_ID();
        $attachment_id = get_post_thumbnail_id( $post_id );
        if ( ! $attachment_id ) {
            return;
        }

        $caption = wp_get_attachment_caption( $attachment_id );
        if ( $caption ) {
            echo '<figcaption>' . esc_html( $caption ) . '</figcaption>';
        }
    }
}

/**
 * Fallback pagination renderer.
 *
 * Generates a basic numeric pagination using WordPress core functions when the
 * original theme helper is unavailable, preventing template errors.
 *
 * @param int $max_num_pages Optional. Total number of pages. Defaults to the
 *                           current global query's max pages.
 */
if ( ! function_exists( 'theme_pagination' ) ) {
    function theme_pagination( $max_num_pages = 0 ) {
        $total = $max_num_pages;
        if ( ! $total && isset( $GLOBALS['wp_query'] ) ) {
            $total = (int) $GLOBALS['wp_query']->max_num_pages;
        }

        if ( $total > 1 && function_exists( 'paginate_links' ) ) {
            echo '<nav class="pagination">' . paginate_links( [ 'total' => $total ] ) . '</nav>';
        }
    }
}
