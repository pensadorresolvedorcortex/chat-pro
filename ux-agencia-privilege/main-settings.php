<?php
/*
 * UX Agência Privilege
 * @author   Agência Privilége
 * @url     https://studioprivilege.com.br
*/

defined('ABSPATH') || die;

class WPS_Initiate_Options {
    private $options = null;

    public function __construct() {
        // Hook into WordPress
        add_action('admin_menu', [$this, 'wps_createOptionsmenu']);
        add_action( 'admin_init', [$this, 'save_options'] );
        add_action( 'admin_enqueue_scripts', [$this, 'wps_aofAssets'], 99 );
    }

    public function wps_aofAssets($page) {
      global $wps_pages_slugs;
      if( $page == "toplevel_page_wpshapere-options" || in_array($page, $wps_pages_slugs) ) {
          wp_enqueue_script( 'jquery' );
          wp_enqueue_script( 'jquery-ui-core' );
          wp_enqueue_script( 'jquery-ui-sortable' );
          wp_enqueue_script( 'jquery-ui-slider' );
          wp_enqueue_style('aofOptions-css', AOF_DIR_URI . 'assets/css/aof-framework.min.css');
          wp_enqueue_style('aof-ui-css', AOF_DIR_URI . 'assets/css/jquery-ui.css');
          wp_enqueue_script( 'aofresposivetabs', AOF_DIR_URI . 'assets/js/aof-options-tab.js', array( 'jquery' ), '', true );
          wp_enqueue_script( 'aofimageselect', AOF_DIR_URI . 'assets/image-picker/image-picker.min.js', array( 'jquery' ), '', true );
          wp_enqueue_style( 'wp-color-picker' );
          wp_enqueue_style( 'aof-imageselect', AOF_DIR_URI . 'assets/image-picker/image-picker.css');
          wp_enqueue_script( 'aof-scriptjs', AOF_DIR_URI . 'assets/js/script.js', array( 'jquery', 'wp-color-picker' ), false, true );
        }
    }

    public function wps_createOptionsmenu() {
        add_menu_page( 'WPShapere', 'WPShapere', 'manage_options', 'wpshapere-options', [$this, 'wps_generateFields'], 'dashicons-art' );
    }

    public function wps_generateFields() {

        $options = $this->get_options_object();
        $wps_fields = get_wps_options();
        $config = array(
          'multi' => true, //default = false
          'wps_fields' => $wps_fields,
        );

        if ( $options ) {
            $options->generateFields($config);
        }

    }

    public function save_options() {

      $options = $this->get_options_object();

        if ( $options && !empty($_POST) && !empty($_POST['aof_options_save']) ) {
          $this->options->SaveSettings($_POST);
        }

    }

    // Getter method if needed in other hooks
    public function get_options_object() {
        if ( ! $this->options ) {
            require_once( WPSHAPERE_PATH . 'includes/acmee-framework/acmee-framework.php' );
            $this->options = new AcmeeFramework();
        }
        return $this->options;
    }
}

$settings_instance = new WPS_Initiate_Options();

// add_action( 'admin_enqueue_scripts', 'wps_aofAssets', 99 );
// function wps_aofAssets($page) {
//   global $wps_pages_slugs;
//   if( $page == "toplevel_page_wpshapere-options" || in_array($page, $wps_pages_slugs) ) {
//       wp_enqueue_script( 'jquery' );
//       wp_enqueue_script( 'jquery-ui-core' );
//       wp_enqueue_script( 'jquery-ui-sortable' );
//       wp_enqueue_script( 'jquery-ui-slider' );
//       wp_enqueue_style('aofOptions-css', AOF_DIR_URI . 'assets/css/aof-framework.min.css');
//       wp_enqueue_style('aof-ui-css', AOF_DIR_URI . 'assets/css/jquery-ui.css');
//       wp_enqueue_script( 'aofresposivetabs', AOF_DIR_URI . 'assets/js/aof-options-tab.js', array( 'jquery' ), '', true );
//       wp_enqueue_script( 'aofimageselect', AOF_DIR_URI . 'assets/image-picker/image-picker.min.js', array( 'jquery' ), '', true );
//       wp_enqueue_style( 'wp-color-picker' );
//       wp_enqueue_style( 'aof-imageselect', AOF_DIR_URI . 'assets/image-picker/image-picker.css');
//       wp_enqueue_script( 'aof-scriptjs', AOF_DIR_URI . 'assets/js/script.js', array( 'jquery', 'wp-color-picker' ), false, true );
//     }
// }

// add_action('admin_menu', 'wps_createOptionsmenu');
// function wps_createOptionsmenu() {
//   $aof_page = add_menu_page( 'WPShapere', 'WPShapere', 'manage_options', 'wpshapere-options', 'wps_generateFields', 'dashicons-art' );
// }

// function wps_generateFields() {
//   global $aof_options;

//   //AOF Framework Implementation
//   require_once( WPSHAPERE_PATH . 'includes/acmee-framework/acmee-framework.php' );
//   $aof_options = new AcmeeFramework();

//   $wps_fields = get_wps_options();
//   $config = array(
//       'multi' => true, //default = false
//       'wps_fields' => $wps_fields,
//     );

//     if ( $aof_options ) {
//         $aof_options->generateFields($config);
//     }
// }

// add_action('admin_init', 'wps_SaveSettings');
// function wps_SaveSettings() {
//   global $aof_options;
//   echo '<pre>'; print_r($aof_options); echo '</pre>'; exit();
//     if ( $aof_options && !empty($_POST) ) {
//         $aof_options->SaveSettings($_POST);
//     }
// }
