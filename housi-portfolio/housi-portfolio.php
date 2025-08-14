<?php
/**
 * Plugin Name: Housi Portfólio
 * Description: Portfolio plugin inspired by Woodmart theme, integrated with Elementor.
 * Version: 0.1.0
 * Author: ChatGPT
 * Text Domain: housi-portfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'HOUSI_PORTFOLIO_VERSION', '0.1.0' );
define( 'HOUSI_PORTFOLIO_PATH', plugin_dir_path( __FILE__ ) );
define( 'HOUSI_PORTFOLIO_URL', plugin_dir_url( __FILE__ ) );

require_once HOUSI_PORTFOLIO_PATH . 'includes/class-housi-portfolio-cpt.php';
require_once HOUSI_PORTFOLIO_PATH . 'includes/class-housi-portfolio-elementor.php';

// Register frontend styles.
add_action( 'wp_enqueue_scripts', function() {
    wp_register_style( 'housi-portfolio', HOUSI_PORTFOLIO_URL . 'assets/css/portfolio.css', [], HOUSI_PORTFOLIO_VERSION );
} );

// Load plugin textdomain.
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'housi-portfolio', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// Flush rewrite rules on activation/deactivation.
function housi_portfolio_activate() {
    Housi_Portfolio_CPT::register_cpt();
    Housi_Portfolio_CPT::register_taxonomy();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'housi_portfolio_activate' );

function housi_portfolio_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'housi_portfolio_deactivate' );

// Template loader for archive and single portfolio pages.
add_filter( 'template_include', function( $template ) {
    if ( is_post_type_archive( 'portfolio' ) && file_exists( HOUSI_PORTFOLIO_PATH . 'templates/archive-portfolio.php' ) ) {
        return HOUSI_PORTFOLIO_PATH . 'templates/archive-portfolio.php';
    }
    if ( is_singular( 'portfolio' ) && file_exists( HOUSI_PORTFOLIO_PATH . 'templates/single-portfolio.php' ) ) {
        return HOUSI_PORTFOLIO_PATH . 'templates/single-portfolio.php';
    }
    return $template;
} );

