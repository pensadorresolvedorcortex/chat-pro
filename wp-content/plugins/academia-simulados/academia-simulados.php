<?php
/**
 * Plugin Name: Academia da Comunicação Simulados
 * Description: Plugin de simulados com perguntas e respostas para a Academia da Comunicação.
 * Version: 1.0.0
 * Author: Academia da Comunicação
 * Text Domain: academia-simulados
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'ACADEMIA_SIMULADOS_VERSION' ) ) {
    define( 'ACADEMIA_SIMULADOS_VERSION', '1.0.0' );
}

define( 'ACADEMIA_SIMULADOS_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACADEMIA_SIMULADOS_URL', plugin_dir_url( __FILE__ ) );

academia_simulados_autoload();
register_activation_hook( __FILE__, 'academia_simulados_activate' );

add_action( 'plugins_loaded', 'academia_simulados_init' );

/**
 * Simple autoloader for plugin classes.
 */
function academia_simulados_autoload() {
    require_once ACADEMIA_SIMULADOS_PATH . 'includes/class-simulado-post-type.php';
    require_once ACADEMIA_SIMULADOS_PATH . 'includes/class-simulado-meta-box.php';
    require_once ACADEMIA_SIMULADOS_PATH . 'includes/class-simulado-assets.php';
    require_once ACADEMIA_SIMULADOS_PATH . 'includes/class-simulado-shortcode.php';
    require_once ACADEMIA_SIMULADOS_PATH . 'includes/class-simulado-comments.php';
    require_once ACADEMIA_SIMULADOS_PATH . 'includes/sample-data.php';
}

/**
 * Initialise plugin services.
 */
function academia_simulados_init() {
    load_plugin_textdomain( 'academia-simulados', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    Academia_Simulados_Post_Type::init();
    Academia_Simulados_Meta_Box::init();
    Academia_Simulados_Assets::init();
    Academia_Simulados_Comments::init();
    Academia_Simulados_Shortcode::init();
}

/**
 * Plugin activation routine.
 */
function academia_simulados_activate() {
    Academia_Simulados_Post_Type::register_post_type();
    flush_rewrite_rules();
    Academia_Simulados_Sample_Data::seed();
}
