<?php
/**
 * Plugin Name: Dashboard Agência Privilege
 * Description: Substitui o visual do WordPress pelo tema Ubold e renderiza um Dashboard moderno com KPIs e gráficos.
 * Version: 1.0.0
 * Author: Agência Privilege
 * Text Domain: dap
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'DAP_VERSION', '1.0.0' );
define( 'DAP_URL', plugin_dir_url( __FILE__ ) );
define( 'DAP_PATH', plugin_dir_path( __FILE__ ) );
define( 'DAP_BASENAME', plugin_basename( __FILE__ ) );

require_once DAP_PATH . 'includes/helpers.php';
require_once DAP_PATH . 'includes/class-dap-admin.php';
require_once DAP_PATH . 'includes/class-dap-dashboard.php';
require_once DAP_PATH . 'includes/class-dap-widget-area.php';
require_once DAP_PATH . 'includes/class-dap-settings.php';

function dap_bootstrap_plugin() {
    new DAP_Admin();
    new DAP_Widget_Area();

    if ( is_admin() ) {
        new DAP_Dashboard();
        new DAP_Settings();
    }
}
add_action( 'plugins_loaded', 'dap_bootstrap_plugin' );

register_activation_hook( __FILE__, [ 'DAP_Widget_Area', 'activate' ] );
