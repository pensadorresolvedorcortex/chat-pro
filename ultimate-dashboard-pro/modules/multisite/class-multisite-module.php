<?php
/**
 * Multisite module.
 *
 * @package Ultimate_Dashboard_Pro
 */

namespace UdbPro\Multisite;

defined( 'ABSPATH' ) || die( "Can't access directly" );

use UdbPro\Helpers\Multisite_Helper;
use Udb\Base\Base_Module;

use Udb\Feature\Feature_Module as Free_Feature_Module;
use Udb\LoginRedirect\Login_Redirect_Module as Free_Login_Redirect_Module;
use Udb\Tool\Tool_Module as Free_Tool_Module;

/**
 * Class to setup multisite module.
 */
class Multisite_Module extends Base_Module {

	/**
	 * The class instance.
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * The current module url.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * The multisite helper.
	 *
	 * @var Multisite_Helper
	 */
	public $ms_helper;

	/**
	 * Module constructor.
	 */
	public function __construct() {

		$this->url = ULTIMATE_DASHBOARD_PRO_PLUGIN_URL . '/modules/multisite';

		$this->ms_helper = new Multisite_Helper();

	}

	/**
	 * Get instance of the class.
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Setup tools module.
	 */
	public function setup() {

		add_action( 'network_admin_menu', array( self::get_instance(), 'submenu_page' ) );
		add_action( 'admin_init', array( self::get_instance(), 'add_settings' ) );

		add_filter( 'udb_ms_supported', array( $this->ms_helper, 'multisite_supported' ) );
		add_filter( 'udb_ms_is_blueprint', array( $this->ms_helper, 'is_blueprint_site' ) );
		add_filter( 'udb_ms_switch_blog', array( self::get_instance(), 'switch_blog' ) );
		add_filter( 'udb_ms_restore_blog', array( self::get_instance(), 'restore_blog' ) );

		add_action( 'init', array( self::get_instance(), 'remove_unnecessary_functionality' ) );
		add_filter( 'udb_login_customizer_control_file_paths', array( self::get_instance(), 'remove_login_customizer_controls' ) );

		add_action( 'udb_export_fields', array( self::get_instance(), 'add_export_fields' ) );

		add_filter( 'udb_admin_menu_placeholder_tags', array( self::get_instance(), 'admin_menu_multisite_placeholder_tags' ) );
		add_filter( 'udb_widgets_placeholder_tags', array( self::get_instance(), 'widgets_multisite_placeholder_tags' ) );

		require __DIR__ . '/class-multisite-output.php';
		Multisite_Output::init();

	}

	/**
	 * Add submenu page.
	 */
	public function submenu_page() {

		add_submenu_page( 'settings.php', 'Ultimate Dashboard', 'Ultimate Dashboard', 'manage_options', 'ultimate-dashboard-multisite', array( $this, 'submenu_page_content' ) );

	}

	/**
	 * Submenu page content.
	 */
	public function submenu_page_content() {

		require __DIR__ . '/templates/multisite-template.php';

	}

	/**
	 * Add settings.
	 */
	public function add_settings() {

		// Register settings.
		register_setting( 'udb-multisite-settings-group', 'udb_multisite_blueprint' );
		register_setting( 'udb-multisite-settings-group', 'udb_multisite_exclude' );
		register_setting( 'udb-multisite-settings-group', 'udb_multisite_widget_order' );
		register_setting( 'udb-multisite-settings-group', 'udb_multisite_capability' );

		// Settings sections.
		add_settings_section( 'udb-multisite-blueprint-settings', '', '', 'ultimate-dashboard-multisite' );
		add_settings_section( 'udb-multisite-widget-order-settings', '', '', 'ultimate-dashboard-multisite' );
		add_settings_section( 'udb-multisite-capability-settings', '', '', 'ultimate-dashboard-multisite' );

		// Settings fields.
		add_settings_field( 'multisite-blueprint-settings', __( 'Blueprint Site', 'ultimatedashboard' ), array( $this, 'blueprint_field' ), 'ultimate-dashboard-multisite', 'udb-multisite-blueprint-settings' );
		add_settings_field( 'multisite-exclude-settings', __( 'Exclude', 'ultimatedashboard' ), array( $this, 'exclude_sites_field' ), 'ultimate-dashboard-multisite', 'udb-multisite-blueprint-settings' );
		add_settings_field( 'multisite-widget-order-settings', __( 'Order Widgets by', 'ultimatedashboard' ), array( $this, 'widgets_order_field' ), 'ultimate-dashboard-multisite', 'udb-multisite-widget-order-settings' );
		add_settings_field( 'multisite-capability-settings', __( 'Capability', 'ultimatedashboard' ), array( $this, 'capability_field' ), 'ultimate-dashboard-multisite', 'udb-multisite-capability-settings' );

	}

	/**
	 * Blueprint field.
	 */
	public function blueprint_field() {

		$field = require __DIR__ . '/templates/fields/blueprint.php';
		$field();

	}

	/**
	 * Exclude sites field.
	 */
	public function exclude_sites_field() {

		$field = require __DIR__ . '/templates/fields/exclude-sites.php';
		$field();

	}

	/**
	 * Widgets order field.
	 */
	public function widgets_order_field() {

		$field = require __DIR__ . '/templates/fields/widgets-order.php';
		$field();

	}

	/**
	 * Capability field.
	 */
	public function capability_field() {

		$field = require __DIR__ . '/templates/fields/capability.php';
		$field();

	}

	/**
	 * Switch blog when necessary.
	 */
	public function switch_blog() {

		if ( $this->ms_helper->needs_to_switch_blog( false ) ) {

			global $blueprint;

			switch_to_blog( $blueprint );

		}

	}

	/**
	 * Restore blog when necessary.
	 */
	public function restore_blog() {

		restore_current_blog();

	}

	/**
	 * Remove unnecessary functionality on non-blueprint sites.
	 */
	public function remove_unnecessary_functionality() {

		if ( ! is_admin() ) {
			return;
		}

		global $blueprint;

		if ( get_current_blog_id() === $blueprint ) {
			return;
		}

		// Feature module.
		if ( class_exists( 'Udb\Feature\Feature_Module' ) ) {
			// Remove features submenu page.
			remove_action( 'admin_menu', array( Free_Feature_Module::get_instance(), 'submenu_page' ) );
			remove_action( 'admin_enqueue_scripts', array( Free_Feature_Module::get_instance(), 'admin_styles' ) );
			remove_action( 'admin_enqueue_scripts', array( Free_Feature_Module::get_instance(), 'admin_scripts' ) );
			remove_action( 'wp_ajax_udb_handle_module_actions', array( Free_Feature_Module::get_instance(), 'handle_module_actions' ) );
		}

		// Login redirect module.
		if ( class_exists( 'Udb\LoginRedirect\Login_Redirect_Module' ) ) {
			// Remove login redirect submenu page.
			remove_action( 'admin_menu', array( Free_Login_Redirect_Module::get_instance(), 'submenu_page' ) );
			remove_action( 'admin_init', array( Free_Login_Redirect_Module::get_instance(), 'add_settings' ) );
			remove_action( 'admin_enqueue_scripts', array( Free_Login_Redirect_Module::get_instance(), 'admin_styles' ) );
			remove_action( 'admin_enqueue_scripts', array( Free_Login_Redirect_Module::get_instance(), 'admin_scripts' ) );
		}

		// Remove tools submenu page.
		remove_action( 'admin_menu', array( Free_Tool_Module::get_instance(), 'submenu_page' ), 20 );
		remove_action( 'admin_enqueue_scripts', array( Free_Tool_Module::get_instance(), 'admin_styles' ) );
		// remove_action( 'admin_enqueue_scripts', array( Free_Tool_Module::get_instance(), 'admin_scripts' ) );
		remove_action( 'admin_init', array( Free_Tool_Module::get_instance(), 'add_settings' ) );

	}

	/**
	 * Remove unnecessary controls from login customizer.
	 *
	 * @param array $control_files List of control files.
	 * @return array
	 */
	public function remove_login_customizer_controls( $control_files ) {

		global $blueprint;

		if ( get_current_blog_id() === $blueprint ) {
			return $control_files;
		}

		if ( ! isset( $control_files['logo'] ) ) {
			return array();
		}

		return array(
			'logo' => $control_files['logo'],
		);

	}

	/**
	 * Add multisite checkbox to export fields.
	 */
	public function add_export_fields() {

		?>

		<p>
			<label>
				<input type="checkbox" name="udb_export_modules[]" class="udb-module-checkbox" value="multisite" checked />
				<?php _e( 'Multisite Settings', 'ultimatedashboard' ); ?>
			</label>
		</p>

		<?php

	}

	/**
	 * Add multisite related placeholder tags for admmin menu & admin bar.
	 * 
	 * @param array $tags The existing tags.
	 * @return array The modified tags.
	 */
	public function admin_menu_multisite_placeholder_tags( $tags = [] ) {
		
		array_push( $tags, '{blueprint_name}', '{blueprint_url}' );

		return $tags;

	}

	/**
	 * Add multisite related placeholder tags for widgets & welcome panel.
	 * 
	 * @param array $tags The existing tags.
	 * @return array The modified tags.
	 */
	public function widgets_multisite_placeholder_tags( $tags = [] ) {
		
		array_push( $tags, '{network_url}', '{blueprint_name}', '{blueprint_url}' );

		return $tags;

	}

}
