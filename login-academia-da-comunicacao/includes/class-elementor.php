<?php
/**
 * Elementor integration layer for the plugin.
 *
 * @package ADC\Login\Elementor
 */

namespace ADC\Login\Elementor;

use ADC\Login\Elementor\Widgets\Forgot;
use ADC\Login\Elementor\Widgets\Login;
use ADC\Login\Elementor\Widgets\Onboarding;
use ADC\Login\Elementor\Widgets\Signup;
use ADC\Login\Elementor\Widgets\TwoFA;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Wires Elementor specific hooks and widgets.
 */
class Integration {

    /**
     * Bootstrap Elementor hooks.
     */
    public function init() {
        if ( did_action( 'elementor/loaded' ) ) {
            $this->boot();
            return;
        }

        add_action( 'elementor/loaded', array( $this, 'boot' ) );
    }

    /**
     * Register widgets, categories and ensure assets are available when Elementor is active.
     */
    public function boot() {
        if ( ! did_action( 'elementor/init' ) ) {
            // Elementor will fire elementor/init right before widgets are registered.
            add_action( 'elementor/init', array( $this, 'boot' ) );
            return;
        }

        add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
        add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
        add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'enqueue_editor_assets' ) );
        add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'elementor/frontend/after_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
    }

    /**
     * Register the custom category used by the widgets.
     *
     * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager instance.
     */
    public function register_category( $elements_manager ) {
        if ( ! $elements_manager || ! method_exists( $elements_manager, 'add_category' ) ) {
            return;
        }

        $elements_manager->add_category(
            'adc-login',
            array(
                'title' => __( 'Login Academia da Comunicação', 'login-academia-da-comunicacao' ),
                'icon'  => 'fa fa-shield',
            )
        );
    }

    /**
     * Register the Elementor widgets.
     *
     * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
     */
    public function register_widgets( $widgets_manager ) {
        if ( ! $widgets_manager || ! class_exists( '\\Elementor\\Widget_Base' ) ) {
            return;
        }

        $widgets = array(
            new Onboarding(),
            new Login(),
            new Signup(),
            new Forgot(),
            new TwoFA(),
        );

        foreach ( $widgets as $widget ) {
            $widgets_manager->register( $widget );
        }
    }

    /**
     * Ensure frontend styles are enqueued when Elementor renders the widgets.
     */
    public function enqueue_frontend_assets() {
        if ( ! wp_style_is( 'adc-login-frontend', 'registered' ) ) {
            wp_register_style( 'adc-login-frontend', ADC_LOGIN_PLUGIN_URL . 'assets/css/frontend.css', array(), '1.0.0' );
        }

        wp_enqueue_style( 'adc-login-frontend' );
    }

    /**
     * Ensure frontend scripts are available in Elementor previews.
     */
    public function enqueue_frontend_scripts() {
        if ( ! wp_script_is( 'adc-login-frontend', 'registered' ) ) {
            wp_register_script( 'adc-login-frontend', ADC_LOGIN_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), '1.0.0', true );
        }

        wp_enqueue_script( 'adc-login-frontend' );
    }

    /**
     * Mirror frontend styles inside the Elementor editor for accurate previews.
     */
    public function enqueue_editor_assets() {
        $this->enqueue_frontend_assets();
    }
}
