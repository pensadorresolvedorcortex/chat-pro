<?php
/**
 * Plugin Name: Login Academia da Comunicação
 * Description: Força onboarding, cadastro, login com 2FA e e-mails personalizados para a Academia da Comunicação.
 * Version: 1.0.0
 * Author: Academia da Comunicação
 * Text Domain: login-academia-da-comunicacao
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ADC_LOGIN_PLUGIN_FILE', __FILE__ );
define( 'ADC_LOGIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADC_LOGIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Simple autoloader for plugin classes following the ADC\Login namespace.
 *
 * @param string $class Class name.
 */
function adc_login_autoload( $class ) {
    if ( strpos( $class, 'ADC\\Login\\' ) !== 0 ) {
        return;
    }

    $relative = strtolower( str_replace( array( 'ADC\\Login\\', '\\' ), array( '', '-' ), $class ) );
    $path     = ADC_LOGIN_PLUGIN_DIR . 'includes/class-' . $relative . '.php';

    if ( file_exists( $path ) ) {
        require_once $path;
    }
}
spl_autoload_register( 'adc_login_autoload' );

require_once ADC_LOGIN_PLUGIN_DIR . 'includes/helpers.php';

/**
 * Boot the plugin.
 */
function adc_login_bootstrap() {
    $plugin = \ADC\Login\Plugin::instance();
    $plugin->init();
}
adc_login_bootstrap();

register_activation_hook( __FILE__, array( '\\ADC\\Login\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\\ADC\\Login\\Plugin', 'deactivate' ) );
