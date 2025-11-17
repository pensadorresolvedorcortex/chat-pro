<?php
/*
Plugin Name: Ux Agência Privilege
Plugin URI: https://www.studioprivilege.com.br
Description: Visual para CMS
Version: 2025
Author: Agência Privilege
Author URI: https://www.studioprivilege.com.br
License:     GPLv2 or later
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Text Domain: AP
Domain Path: /languages
 *
*/

/*
*   WPSHAPERE Version
*/

define( 'WPSHAPERE_VERSION' , '8.0.0' );

update_option( 'wps_purchase_data', [
    'license_key' => 'AAAA-BBBB-CCCC-DDDD-123456789012',
    'product_code' => '8183353',
    'license_type' => 'regular'
] );


/*
*   WPSHAPERE Path Constant
*/
define( 'WPSHAPERE_PATH' , dirname(__FILE__) . "/");

/*
*   WPSHAPERE URI Constant
*/
define( 'WPSHAPERE_DIR_URI' , plugin_dir_url(__FILE__) );

/*
*   WPSHAPERE Options slug Constant
*/
define( 'WPSHAPERE_OPTIONS_SLUG' , 'wpshapere_options' );

/*
*   WPSHAPERE menu slug Constant
*/
define( 'WPSHAPERE_MENU_SLUG' , 'wpshapere-options' );

/*
*   WPSHAPERE users list slug Constant
*/
define( 'WPS_ADMIN_USERS_SLUG' , 'wps_admin_users' );

/*
*   WPSHAPERE admin bar items list Constant
*/
define( 'WPS_ADMINBAR_LIST_SLUG' , 'wps_adminbar_list' );

/*
* AOF Constants
*/
define( 'AOF_VERSION' , '1.2.1' );
define( 'AOF_PATH' , dirname(__FILE__) . "/includes/acmee-framework/");
define( 'AOF_DIR_URI' , plugin_dir_url(__FILE__) . '/includes/acmee-framework/' );

/*
* Enabling Global Customization for Multi-site installation.
* Delete below two lines if you want to give access to all blog admins to customizing their own blog individually.
* Works only for multi-site installation
*/
if(is_multisite())
    define('NETWORK_ADMIN_CONTROL', true);
// Delete the above two lines to enable customization per blog

global $wps_pages_slugs;

$wps_pages_slugs = [
  'wpshapere-options',
  'toplevel_page_wpshapere-options',
  'wpshapere_page_admin_menu_management',
  'wpshapere_page_wps_themes',
  'wpshapere_page_wps_import_login_theme',
  'wpshapere_page_wps_impexp_settings',
  'wpshapere_page_wpshapere_help',
  'wpshapere_page_wps_addons_adv',
  'wpshapere_page_powerbox_hide_meta_boxes',
  'wpshapere_page_wpspb_impexport_sidebar',
  'wpshapere_page_powerbox_font_options',
  'wpshapere_page_powerbox_custom_menu_set',
  'wpshapere_page_powerbox_user_options'
];

function wps_load_textdomain()
{
   load_plugin_textdomain('wps', false, dirname( plugin_basename( __FILE__ ) )  . '/languages' );
}
add_action('init', 'wps_load_textdomain');

// Run on activation
function wps_plugin_activate() {
    // Set a transient flag so we know activation just happened
    set_transient( 'wps_activation_redirect', true, 30 );
}
register_activation_hook( __FILE__, 'wps_plugin_activate' );

require_once( WPSHAPERE_PATH . 'includes/wps.class.php' );
require_once( WPSHAPERE_PATH . 'includes/init.php' );

