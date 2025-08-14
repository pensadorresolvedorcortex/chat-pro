<?php

/**
 * ﷽‎
 *
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://meydjer.com
 * @since             1.0.0
 * @package           Lastform
 *
 * @wordpress-plugin
 * Plugin Name:       Lastform |
 * Plugin URI:        https://codecanyon.net/item/lastform-affordable-typeform-alternative/17870313?ref=meydjer
 * Description:       Affordable Typeform alternative
 * Version:           2.1.1.6
 * Author:            Meydjer Windmüller
 * Author URI:        http://meydjer.com
 * Text Domain:       lastform
 * Domain Path:       /languages
 *
 * ------------------------------------------------------------------------
 *
 * This plugin is comprised of two parts.
 *
 * (1) The PHP code and integrated HTML are licensed under the General Public
 * License (GPL). Visit https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * (2) All other parts, but not limited to the CSS code, images, and design are
 * licensed according to the license purchased from Envato.
 *
 * Read more about licensing here: http://codecanyon.net/licenses
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Autoload classes
 */
if  ( ! function_exists('lastform_autoload_plugin_classes') ) {
	function lastform_autoload_plugin_classes($class_name) {
		// Get class parts
		$class_name          = strtolower($class_name);
		$exploded_class_name = explode('_', $class_name);

		// If is not a Lastform class, stop the autoload
		$plugin              = $exploded_class_name[0];
		if('lastform' != $plugin) return null;

		// Check the class folder
		$class_name_suffix   = end($exploded_class_name);
		if('admin' == $class_name_suffix) {
			$folder = 'admin';
		} else if('public' == $class_name_suffix) {
			$folder = 'public';
		} else {
			$folder = 'includes';
		}

		// Class name to file name
		$file_name           = 'class-' . str_replace('_', '-', $class_name) . '.php';
		$file_with_path      = plugin_dir_path( __FILE__ ) . $folder . '/' . $file_name;

		require_once $file_with_path;
	}
}
spl_autoload_register('lastform_autoload_plugin_classes');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-lastform-activator.php
 */
function activate_lastform() {
	Lastform_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-lastform-deactivator.php
 */
function deactivate_lastform() {
	Lastform_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_lastform' );
register_deactivation_hook( __FILE__, 'deactivate_lastform' );

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function lastform_bootstrap() {
    if ( method_exists( 'GFForms', 'include_addon_framework' ) ) {
    	GFForms::include_addon_framework();
        GFAddOn::register( 'Lastform' );
        lastform_addon()->run();
    }
}
add_action( 'gform_loaded', 'lastform_bootstrap', 5 );

function lastform_addon() {
    return Lastform::get_instance();
}
