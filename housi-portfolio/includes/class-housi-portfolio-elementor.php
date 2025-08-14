<?php
/**
 * Elementor integration for Housi Portfolio.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Housi_Portfolio_Elementor {

    public function __construct() {
        add_action( 'elementor/widgets/register', [ $this, 'register_widget' ] );
    }

    /**
     * Register the portfolio widget.
     */
    public function register_widget( $widgets_manager ) {
        require_once HOUSI_PORTFOLIO_PATH . 'includes/widgets/class-housi-portfolio-widget.php';
        $widgets_manager->register( new \Housi_Portfolio_Widget() );
    }
}

// Only load if Elementor is active.
if ( did_action( 'elementor/loaded' ) ) {
    new Housi_Portfolio_Elementor();
}

