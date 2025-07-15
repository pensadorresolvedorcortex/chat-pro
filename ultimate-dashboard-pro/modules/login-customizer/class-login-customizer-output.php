<?php
/**
 * Login customizer output.
 *
 * @package Ultimate_Dashboard
 */

namespace UdbPro\LoginCustomizer;

defined( 'ABSPATH' ) || die( "Can't access directly" );

use Udb\Base\Base_Output;
use Udb\LoginCustomizer\Login_Customizer_Output as Free_Login_Customizer_Output;

/**
 * Class to setup login customizer output.
 */
class Login_Customizer_Output extends Base_Output {

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
	 * Module constructor.
	 */
	public function __construct() {

		$this->url = ULTIMATE_DASHBOARD_PRO_PLUGIN_URL . '/modules/login-customizer';

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
	 * Init the class setup.
	 */
	public static function init() {

		$class = new self();
		$class->setup();

	}

	/**
	 * Setup login customizer output.
	 */
	public function setup() {

		remove_action( 'login_head', array( Free_Login_Customizer_Output::get_instance(), 'print_login_styles' ), 20 );
		add_action( 'login_head', array( self::get_instance(), 'print_login_styles' ), 20 );

		add_action( 'udb_login_customizer_live_styles', array( self::get_instance(), 'print_login_live_styles' ) );

	}

	/**
	 * Print login styles.
	 *
	 * @param bool $is_subsite Whether or not to print the styles as subsite styles.
	 *                         If set to true, login-subsite.css.php will be printed. Otherwise, login.css.php will be printed.
	 */
	public function print_login_styles( $is_subsite = false ) {

		$file = $is_subsite ? 'login-subsite.css.php' : 'login.css.php';

		echo '<style>';
		ob_start();
		require __DIR__ . '/inc/' . $file;

		$css = ob_get_clean();

		$login      = get_option( 'udb_login', array() );
		$custom_css = isset( $login['custom_css'] ) ? $login['custom_css'] : '';

		$css .= $custom_css;

		echo apply_filters( 'udb_login_styles', $css );
		// ! Deprecated: please use "udb_login_styles".
		echo apply_filters( 'udb_pro_login_styles', $css );
		echo '</style>';

	}

	/**
	 * Print login live styles.
	 */
	public function print_login_live_styles() {

		// Currently, we don't have any custom style tag for the pro version.

	}

}
