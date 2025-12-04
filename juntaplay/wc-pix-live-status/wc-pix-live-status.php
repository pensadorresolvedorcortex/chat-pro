<?php
/**
 * Plugin Name: WC PIX Live Status – Mercado Pago Auto Update
 * Plugin URI: https://example.com/wc-pix-live-status
 * Description: Adiciona polling automático no checkout do WooCommerce para pedidos pagos via PIX do Mercado Pago, redirecionando quando o pagamento é aprovado.
 * Version: 1.0.0
 * Author: ChatGPT
 * Author URI: https://openai.com/
 * License: GPLv2 or later
 * Text Domain: wc-pix-live-status
 * Requires at least: 6.3
 * Requires PHP: 8.2
 * Tested up to: 6.5
 * WC requires at least: 8.0
 * WC tested up to: 8.9
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

if ( ! class_exists( 'WC_Pix_Live_Status' ) ) {
/**
 * Main plugin class.
 */
class WC_Pix_Live_Status {
const VERSION = '1.0.0';

/**
 * Bootstraps the plugin.
 */
public function __construct() {
$this->define_constants();
$this->includes();
$this->init_hooks();
}

private function define_constants() {
define( 'WC_PLS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_PLS_URL', plugin_dir_url( __FILE__ ) );
define( 'WC_PLS_VERSION', self::VERSION );
}

private function includes() {
require_once WC_PLS_PATH . 'includes/class-wc-pix-live-status-loader.php';
require_once WC_PLS_PATH . 'includes/class-wc-pix-live-status-rest.php';
}

private function init_hooks() {
add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
add_action( 'init', array( $this, 'init_components' ) );
}

public function load_textdomain() {
load_plugin_textdomain( 'wc-pix-live-status', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

public function init_components() {
$loader = new WC_Pix_Live_Status_Loader();
$loader->setup();

$rest = new WC_Pix_Live_Status_REST();
$rest->register_routes();
}
}
}

new WC_Pix_Live_Status();
