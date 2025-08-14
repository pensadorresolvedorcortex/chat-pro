<?php

/**
 * Fired during plugin activation
 *
 * @link       http://meydjer.com
 * @since      1.0.0
 *
 * @package    Lastform
 * @subpackage Lastform/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Lastform
 * @subpackage Lastform/includes
 * @author     Meydjer Windmüller <meydjer@gmail.com>
 */
class Lastform_Activator {

	public static function activate() {

		if (!class_exists('GFAddOn')) {
			self::br_trigger_error(esc_attr__('You need to install and activate Gravity Form first.', 'lastform'), E_USER_ERROR);
		}


		/**
		 * Add route on activation
		 */
		$public = new Lastform_Public(null,null);
		$public->add_route();

		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	public static function br_trigger_error($message, $errno) {

	    if(isset($_GET['action']) && $_GET['action'] == 'error_scrape') {

	        echo '<strong>' . $message . '</strong>';

	        exit;

	    } else {

	        trigger_error($message, $errno);

	    }

	}

}
