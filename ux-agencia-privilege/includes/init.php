<?php
/*
 * WPSHAPERE
 * @author   AcmeeDesign
 * @url     https://acmeedesign.com
*/

defined('ABSPATH') || die;
function wps_load_files() {

  //load wps options
  require_once( WPSHAPERE_PATH . 'includes/wps-options.php' );
  //Implement main settings
  require_once( WPSHAPERE_PATH . 'main-settings.php' );
  include_once WPSHAPERE_PATH . 'includes/dash-icons.class.php';
  include_once WPSHAPERE_PATH . 'includes/fa-icons.class.php';
  include_once WPSHAPERE_PATH . 'includes/line-icons.class.php';
  include_once WPSHAPERE_PATH . 'includes/wpshapere.class.php';
  include_once WPSHAPERE_PATH . 'includes/wps-login-presets.php';
  include_once WPSHAPERE_PATH . 'includes/wpsthemes.class.php';
  include_once WPSHAPERE_PATH . 'includes/wpsmenu.class.php';
  include_once WPSHAPERE_PATH . 'includes/wps-impexp.class.php';
  include_once WPSHAPERE_PATH . 'includes/wps-notices.class.php';
  include_once WPSHAPERE_PATH . 'includes/wps.help.php';
  include_once WPSHAPERE_PATH . 'includes/wps-addons.php';
  include_once WPSHAPERE_PATH . 'includes/elementor/elementor-widgets.php';
}
add_action( 'init', 'wps_load_files', 1 );

function wps_load_elementor_widgets() {
  include_once WPSHAPERE_PATH . 'includes/wps-elementor-dashboard.php';
}
add_action( 'init', 'wps_load_elementor_widgets', 99 );