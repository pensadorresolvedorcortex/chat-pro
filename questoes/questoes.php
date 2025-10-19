<?php
/**
 * Plugin Name: Questões Academia da Comunicação
 * Plugin URI: https://academiadacomunicacao.example
 * Description: Renderiza mapas mentais e organogramas acessíveis com base em dados estruturados para a Academia da Comunicação.
 * Version: 0.11.0
 * Author: Academia da Comunicação
 * Text Domain: questoes
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'QUESTOES_PLUGIN_FILE' ) ) {
    define( 'QUESTOES_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'QUESTOES_PLUGIN_DIR' ) ) {
    define( 'QUESTOES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'QUESTOES_PLUGIN_URL' ) ) {
    define( 'QUESTOES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once QUESTOES_PLUGIN_DIR . 'includes/helpers.php';
require_once QUESTOES_PLUGIN_DIR . 'includes/schema.php';
require_once QUESTOES_PLUGIN_DIR . 'includes/class-settings.php';
require_once QUESTOES_PLUGIN_DIR . 'includes/class-admin.php';
require_once QUESTOES_PLUGIN_DIR . 'includes/class-questions.php';
require_once QUESTOES_PLUGIN_DIR . 'includes/class-renderer.php';
require_once QUESTOES_PLUGIN_DIR . 'includes/class-accessibility.php';
require_once QUESTOES_PLUGIN_DIR . 'includes/class-frontend.php';
require_once QUESTOES_PLUGIN_DIR . 'includes/class-rest.php';
require_once QUESTOES_PLUGIN_DIR . 'includes/class-block.php';

/**
 * Check whether Elementor is loaded and ready for integrations.
 *
 * @return bool
 */
function questoes_is_elementor_ready() {
    return did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Widget_Base' );
}

/**
 * Output an admin notice if Elementor integration is unavailable.
 */
function questoes_elementor_missing_notice() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    if ( questoes_is_elementor_ready() ) {
        return;
    }

    printf(
        '<div class="notice notice-warning"><p>%s</p></div>',
        esc_html__( 'O plugin Questões detectou que o Elementor não está ativo. A integração com widgets do Elementor continuará desativada até que o Elementor seja instalado e ativado.', 'questoes' )
    );
}

/**
 * Bootstrap plugin.
 */
function questoes_bootstrap() {
    $settings      = new Questoes_Settings();
    $questions     = new Questoes_Questions();
    $renderer      = new Questoes_Renderer( $settings );
    $accessibility = new Questoes_Accessibility();

    new Questoes_Admin( $settings, $renderer );
    new Questoes_Frontend( $settings, $renderer, $accessibility, $questions );
    new Questoes_REST( $settings, $renderer, $questions );
    new Questoes_Block( $settings, $renderer, $accessibility );

    $maybe_init_elementor = function() use ( $settings, $renderer, $accessibility ) {
        if ( ! questoes_is_elementor_ready() ) {
            return;
        }

        require_once QUESTOES_PLUGIN_DIR . 'includes/class-elementor.php';
        new Questoes_Elementor( $settings, $renderer, $accessibility );
    };

    if ( questoes_is_elementor_ready() ) {
        $maybe_init_elementor();
    } else {
        add_action( 'elementor/loaded', $maybe_init_elementor );
        add_action( 'admin_notices', 'questoes_elementor_missing_notice' );
    }
}
add_action( 'plugins_loaded', 'questoes_bootstrap', 5 );

/**
 * Load plugin text domain.
 */
function questoes_load_textdomain() {
    load_plugin_textdomain( 'questoes', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'questoes_load_textdomain' );
