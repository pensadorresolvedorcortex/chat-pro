<?php
/**
 * Plugin Name: Introdução Academia da Educação
 * Description: Exibe um menu personalizado do usuário com páginas dedicadas para teoria, prática, conhecimentos e mais.
 * Version: 1.4.0
 * Author: ChatGPT Codex
 * Text Domain: introducao
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'INTRODUCAO_PLUGIN_VERSION', '1.4.0' );
define( 'INTRODUCAO_PLUGIN_FILE', __FILE__ );
define( 'INTRODUCAO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'INTRODUCAO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once INTRODUCAO_PLUGIN_DIR . 'includes/class-lae-plugin.php';
require_once INTRODUCAO_PLUGIN_DIR . 'includes/class-lae-pages.php';
require_once INTRODUCAO_PLUGIN_DIR . 'includes/class-lae-shortcodes.php';
require_once INTRODUCAO_PLUGIN_DIR . 'includes/class-lae-auth.php';

/**
 * Inicializa o plugin.
 */
function introducao_init_plugin() {
    $plugin = Introducao_Plugin::get_instance();
    $plugin->init();
}
add_action( 'plugins_loaded', 'introducao_init_plugin' );

register_activation_hook( __FILE__, array( 'Introducao_Pages', 'activate' ) );
