<?php
/**
 * Plugin Name: Login Academia da Educação
 * Description: Exibe um menu personalizado do usuário com páginas dedicadas para teoria, prática, conhecimentos e mais.
 * Version: 1.1.0
 * Author: ChatGPT Codex
 * Text Domain: login-academia-da-educacao
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LAE_PLUGIN_VERSION', '1.1.0' );
define( 'LAE_PLUGIN_FILE', __FILE__ );
define( 'LAE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LAE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once LAE_PLUGIN_DIR . 'includes/class-lae-plugin.php';
require_once LAE_PLUGIN_DIR . 'includes/class-lae-pages.php';
require_once LAE_PLUGIN_DIR . 'includes/class-lae-shortcodes.php';

/**
 * Inicializa o plugin.
 */
function lae_init_plugin() {
    $plugin = LAE_Plugin::get_instance();
    $plugin->init();
}
add_action( 'plugins_loaded', 'lae_init_plugin' );

register_activation_hook( __FILE__, array( 'LAE_Pages', 'activate' ) );
