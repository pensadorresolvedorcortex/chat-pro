<?php
/*
 * WPSHAPERE
 * @author   AcmeeDesign
 * @url     https://acmeedesign.com
*/

defined('ABSPATH') || die;

if( ! class_exists('wps_class_functions') ) {
  class wps_class_functions
  {
    private $wps_options = null;

    public function __construct() {
        $this->wps_options = $this->get_wps_option_data(WPSHAPERE_OPTIONS_SLUG);
        add_action('admin_init', [$this, 'wps_settings_action']);
        add_action( 'admin_menu', [$this, 'wps_load_default'], 5 );
        add_action( 'admin_init', [$this, 'wps_activation_redirect'] );
    }

    private function get_wps_option_data( $option_id ) {
        if($this->is_wps_single_site()) {
            $get_wps_option_data = (is_serialized(get_option($option_id))) ? unserialize(get_option($option_id)) : get_option($option_id);
        }
        else {
            $get_wps_option_data = (is_serialized(get_site_option($option_id))) ? unserialize(get_site_option($option_id)) : get_site_option($option_id);
        }
        return $get_wps_option_data;
	}

    private function is_wps_single_site() {
	    if(!is_multisite())
		    return true;
	    elseif(is_multisite() && !defined('NETWORK_ADMIN_CONTROL'))
		    return true;
	    else return false;
	}

    private function wps_get_user_role() {
        global $current_user;
        $get_user_roles = $current_user->roles;
        $get_user_role = array_shift($get_user_roles);
        return $get_user_role;
    }

    function wps_get_wproles() {
        global $wp_roles;
        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }
        return $wp_roles->get_names();
    }

    /* get roles by blog based @since 6.1.4 */
    function wps_get_wproles_blog( $blog_id ) {

    $switched = is_multisite() ? switch_to_blog( $blog_id ) : false;
    $wp_roles = new WP_Roles();
    $roles = $wp_roles->get_names();
    if ( $switched ) {
        restore_current_blog();
    }
    return $roles;

    }

    //fn to save options
    public function updateOption($option='', $data='') {
        $update = false;
        if(empty($option)) {
            $option = WPSHAPERE_OPTIONS_SLUG;
        }
        if(!empty($data)) {
            if($this->is_wps_single_site())
                $update = update_option($option, $data);
            else
                $update = update_site_option($option, $data);
        }
        return $update;
    }

    //import wpshapere settings
    function wps_settings_action() {
        if(isset($_POST['wps_import_settings_field']) ) {

            if(!wp_verify_nonce( $_POST['wps_import_settings_field'], 'wps_import_settings_nonce' ) )
                exit();

            $import_data = trim($_POST['wps_import_settings_data']);
            if(empty($import_data) || !is_serialized($import_data)) {
                wp_safe_redirect( esc_url(admin_url( 'admin.php?page=wps_impexp_settings&status=dataerror' )) );
                exit();
            }
            else {
                // Remove slashes
                $fixed_data = stripslashes($import_data);

                // Unserialize safely
                $data = @unserialize($fixed_data);

                // Check for error
                if ($data === false && $fixed_data !== 'b:0;') {
                    wp_safe_redirect( esc_url(admin_url( 'admin.php?page=wps_impexp_settings&status=error-corrupted' )) );
                    exit();
                } 

                // finally update
                $update = $this->updateOption(WPSHAPERE_OPTIONS_SLUG, $data);

                if($update) {
                    wp_safe_redirect( esc_url(admin_url( 'admin.php?page=wps_impexp_settings&status=updated') ) );
                    exit();
                }
                else {
                    wp_safe_redirect( esc_url(admin_url( 'admin.php?page=wps_impexp_settings&status=error' )) );
                    exit();
                }
            }

            if(isset($_POST['reset_to_default']) && $_POST['reset_to_default'] == "wps_master_reset") {
                if(!wp_verify_nonce( $_POST['wps_reset_field'], 'wps_reset_nonce' ) )
                    exit();
                $this->wps_load_default(true);
                wp_safe_redirect( esc_url(admin_url( 'admin.php?page='.WPSHAPERE_MENU_SLUG )) );
                exit();
            }

        }

    }
    /**
    *  insert default values
    */
    function wps_load_default($reset=false) {

        $options = $this->get_wps_option_data( WPSHAPERE_OPTIONS_SLUG );
        if ( false === $options || empty($options) || true === $reset ) {
            require_once( WPSHAPERE_PATH . 'includes/acmee-framework/acmee-framework.php' );
            $aofoptions = new AcmeeFramework();
            $default_options = $aofoptions->getDefaultOptions();
            if(!empty($default_options)) {
                if($this->config['multi'] === true) {
                    update_site_option( WPSHAPERE_OPTIONS_SLUG, $default_options );
                }
                else {
                    update_option( WPSHAPERE_OPTIONS_SLUG, $default_options );
                }
            }
        }

    }

    function wps_activation_redirect() {
        // if not in admin
        if ( ! is_admin() ) {
            return;
        }

        // Check our transient
        if ( get_transient( 'wps_activation_redirect' ) ) {
            // Remove it so it only runs once
            delete_transient( 'wps_activation_redirect' );

            // Redirect to options page
            wp_safe_redirect( esc_url(admin_url( 'admin.php?page=wpshapere-options' )) );
            exit;
        }
    }


  }

  new wps_class_functions();

}