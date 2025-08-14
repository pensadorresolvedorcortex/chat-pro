<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://meydjer.com
 * @since      1.0.0
 *
 * @package    Lastform
 * @subpackage Lastform/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Lastform
 * @subpackage Lastform/includes
 * @author     Meydjer Windmüller <meydjer@gmail.com>
 */

class Lastform extends GFAddOn {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Lastform_Loader    $_loader    Maintains and registers all hooks for the plugin.
	 */
	protected $_loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $_slug    The string used to uniquely identify this plugin.
	 */
	protected $_slug;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $_version    The current version of the plugin.
	 */
	protected $_version;

	/**
	 * The minimun required version of Gravity Forms plugin
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $_min_gravityforms_version    The minimun required version of Gravity Forms plugin
	 */
	protected $_min_gravityforms_version;

	/**
	 * The plugin main file relative path
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $_path    The plugin main file relative path
	 */
	protected $_path;

	/**
	 * The plugin main file full path
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $_full_path    The plugin main file full path
	 */
	protected $_full_path;

	/**
	 * The plugin full title
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $_title    The plugin full title
	 */
	protected $_title;

	/**
	 * The plugin short title
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $_short_title    The plugin short title
	 */
	protected $_short_title;

	/**
	 * Instance of this object
	 * @var    null
	 */
	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return Lastform
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new Lastform();
		}

		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {
		parent::init();
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		parent::__construct();

		$this->_slug = 'lastform';
		$this->_version = '2.1.1.6';
		$this->_min_gravityforms_version = '2.0';
		$this->_path = 'lastform/lastform.php';
		$this->_full_path = trailingslashit( plugins_url() ) . $this->_path;
		$this->_title = 'Lastform - Gravity Forms Add-On';
		$this->_short_title = 'Lastform';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_shared_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Get the plugin url
	 * @return string
	 */
	public static function plugin_url() {
		return trailingslashit( plugins_url() ) . 'lastform/';
	}

	/**
	 * Convert dash-case type array's keys to camelCase type array's keys
	 * https://gist.github.com/goldsky/3372487
	 * @param   array   $array          array to convert
	 * @param   array   $arrayHolder    parent array holder for recursive array
	 * @return  array   camelCase array
	 */
	public static function camel_case_keys($array, $array_holder = array()) {
		$camel_case_array = !empty($array_holder) ? $array_holder : array();
		foreach ($array as $key => $val) {
			$new_key = @explode('-', $key);
                       array_walk($new_key, function (&$v) {
                               $v = ucwords($v);
                       });
                       $new_key = @implode('', $new_key);
                       $new_key = lcfirst($new_key);
			if (!is_array($val)) {
				$camel_case_array[$new_key] = $val;
			} else {
				@$camel_case_array[$new_key] = self::camel_case_keys($val, $camel_case_array[$new_key]);
			}
		}
		return $camel_case_array;
	}


	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Lastform_Loader. Orchestrates the hooks of the plugin.
	 * - Lastform_i18n. Defines internationalization functionality.
	 * - Lastform_Admin. Defines all hooks for the admin area.
	 * - Lastform_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		$this->_loader = new Lastform_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Lastform_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Lastform_i18n();

		$this->_loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Lastform_Admin( $this->get_slug(), $this->get_version() );

		$this->_loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->_loader->add_filter( 'gform_tooltips', $plugin_admin, 'custom_gf_tooltips' );
		$this->_loader->add_action( 'gform_editor_js', $plugin_admin, 'image_editor_field' );

	}

	/**
	 * Register all of the hooks shared between public-facing and admin functionality
	 * of the plugin.
	 *
	 * @since 		1.0.0
	 * @access 		private
	 */
	private function define_shared_hooks() {

		$this->_loader->add_filter( 'gform_toolbar_menu', $this, 'add_gform_lastform_item', 10, 2 );
		$this->_loader->add_filter( 'gform_form_actions', $this, 'add_gform_lastform_item', 10, 2 );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Lastform_Public( $this->get_slug(), $this->get_version() );

		$this->_loader->add_filter( 'query_vars', $plugin_public, 'query_vars' );

		$this->_loader->add_action( 'init', $plugin_public, 'add_route' );
		$this->_loader->add_action( 'wp', $plugin_public, 'register_styles' );
		$this->_loader->add_action( 'wp', $plugin_public, 'enqueue_scripts' );
		$this->_loader->add_action( 'wp', $plugin_public, 'process_form_page' );
		$this->_loader->add_action( 'wp_ajax_lastform_is_duplicate',        $plugin_public, 'is_duplicate' );
		$this->_loader->add_action( 'wp_ajax_nopriv_lastform_is_duplicate', $plugin_public, 'is_duplicate' );
		$this->_loader->add_action( 'gform_post_process', $plugin_public, 'gform_processed' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->_loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_slug() {
		return $this->_slug;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Lastform_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->_loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->_version;
	}

	/**
	 * Gravity Forms Add-On Settings
	 * @since     1.0.0
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				// 'title'  => esc_html__( 'Lastform', 'lastform' ),
				'fields' => array(
					array(
						'name'          => 'form_url_slug',
						'default_value' => 'lastform',
						'tooltip'       => esc_html__( 'The slug between your site URL and the form id. E.g.: ', 'lastform' ) . trailingslashit(get_site_url()) . '<span style="text-decoration:underline">lastform</span>/4<br><br><strong>' . esc_attr__('WARNING:', 'lastform') . '</strong> ' . esc_attr__('You need to flush your Rewrite Rules after changing it. Go to the', 'lastform') . ' <a href="' . admin_url( 'options-permalink.php' ) .'">' . esc_attr__('Permalinks page', 'lastform') . '</a> ' . esc_attr__('and click "Save Changes" to flush the rules.', 'lastform'),
						'label'         => esc_html__( 'Form URL Slug', 'lastform' ),
						'type'          => 'text',
						'class'         => 'small'
					)
				)
			)
		);
	}

	public function form_settings_fields( $form ) {
		return array(
			array(
				'title'  => esc_html__( 'Lastform settings', 'lastform' ),
				'fields' => array()
			),
			array(
				'title'  => esc_html__( 'Welcome screen - W  P  L  O C  K  E R  . C  O M ', 'lastform' ),
				'fields' => array(
					array(
						'label'         => esc_html__( 'Enable Welcome Screen?', 'lastform' ),
						'type'          => 'checkbox',
						'name'          => 'welcome-enabled',
						'choices'       => array(
							array(
								'label'         => esc_attr__('Enabled', 'lastform'),
								'name'          => 'welcome-enabled',
								'default_value' => 1
							)
						)
					),
					array(
						'label'         => esc_html__( 'Image URL', 'lastform' ),
						'type'          => 'text',
						'name'          => 'welcome-image-url',
						'tooltip'       => esc_html__( 'Paste the FULL image URL', 'lastform' ),
						'class'         => 'large',
						'placeholder'   => 'http://',
					),
					array(
						'label'         => esc_html__( 'Text', 'lastform' ),
						'type'          => 'textarea',
						'name'          => 'welcome-text',
						'class'         => 'medium'
					),
					array(
						'label'         => esc_html__( 'Start button text', 'lastform' ),
						'type'          => 'text',
						'name'          => 'welcome-start-button-text',
					),
				),
			),
			array(
				'title'  => esc_html__( 'Colors', 'lastform' ),
				'fields' => array(
					array(
						'label'         => esc_html__( 'Question color', 'lastform' ),
						'type'          => 'text',
						'name'          => 'question-color',
						'tooltip'       => esc_html__( 'HEX color code', 'lastform' ),
						'default_value' => '#3D3D3D',
						'placeholder'   => '#c0ffee',
					),
					array(
						'label'         => esc_html__( 'Answer color', 'lastform' ),
						'type'          => 'text',
						'name'          => 'answer-color',
						'tooltip'       => esc_html__( 'HEX color code', 'lastform' ),
						'default_value' => '#41B3FF',
						'placeholder'   => '#c0ffee',
					),
					array(
						'label'         => esc_html__( 'Button color', 'lastform' ),
						'type'          => 'text',
						'name'          => 'button-color',
						'tooltip'       => esc_html__( 'HEX color code', 'lastform' ),
						'default_value' => '#41B3FF',
						'placeholder'   => '#c0ffee',
					),
					array(
						'label'         => esc_html__( 'Background color', 'lastform' ),
						'type'          => 'text',
						'name'          => 'bg-color',
						'tooltip'       => esc_html__( 'HEX color code', 'lastform' ),
						'default_value' => '#FFFFFF',
						'placeholder'   => '#c0ffee',
					),
					array(
						'label'         => esc_html__( 'Warning color', 'lastform' ),
						'type'          => 'text',
						'name'          => 'warning-color',
						'tooltip'       => esc_html__( 'HEX color code', 'lastform' ),
						'default_value' => '#c64145',
						'placeholder'   => '#c0ffee',
					),
				),
			),
			array(
				'title'  => esc_html__( 'Background image', 'lastform' ),
				'fields' => array(
					array(
						'label'         => esc_html__( 'Image URL', 'lastform' ),
						'type'          => 'text',
						'name'          => 'bg-image-url',
						'tooltip'       => esc_html__( 'Paste the FULL image URL', 'lastform' ),
						'class'         => 'large',
						'placeholder'   => 'http://',
					),
					array(
						'label'         => esc_html__( 'Scaling', 'lastform' ),
						'type'          => 'radio',
						'name'          => 'bg-image-scaling',
						'tooltip'       => esc_html__( 'How the image will be displayed in your form background.', 'lastform' ),
						'default_value' => 'fullscreen',
						'horizontal'    => true,
						'choices' => array(
							array(
								'label' => esc_html__( 'Fullscreen', 'lastform' ),
								'value' => 'fullscreen',
							),
							array(
								'label' => esc_html__( 'Repeat', 'lastform' ),
								'value' => 'repeat'
							),
							array(
								'label' => esc_html__( 'No repeat', 'lastform' ),
								'value' => 'no-repeat'
							),
						),
					),
					array(
						'label'         => esc_html__( 'Luminosity', 'lastform' ),
						'type'          => 'radio',
						'name'          => 'bg-luminosity',
						'tooltip'       => esc_html__( 'Depending of your image, you will need to configure this to improve readability.', 'lastform' ),
						'default_value' => 'darker',
						'horizontal'    => true,
						'choices' => array(
							array(
								'label' => esc_html__( 'Darker', 'lastform' ),
								'value' => 'darker',
							),
							array(
								'label' => esc_html__( 'Lighter', 'lastform' ),
								'value' => 'lighter',
							),
						),
					),
					array(
						'label'         => esc_html__( 'Luminosity level %', 'lastform' ),
						'type'          => 'text',
						'name'          => 'bg-luminosity-level',
						'tooltip'       => esc_html__( 'The amount of luminosity to apply over your image. From 0 to 100.', 'lastform' ),
						'placeholder'   => esc_attr__('0 to 100', 'lastform')
					),
				),
			),
			array(
				'title'  => esc_html__( 'Fonts', 'lastform' ),
				'fields' => array(
					array(
						'label'         => esc_html__( 'Google Font Code', 'lastform' ),
						'type'          => 'text',
						'name'          => 'google-font-code',
						'tooltip'       => esc_attr__('Get your here: ', 'lastform') . '<a href="https://fonts.google.com/" target="_blank">fonts.google.com</a><br><br>&#8226; <a href="https://cl.ly/0H2i2B0t1a3P" target="_blank">'.esc_attr__('How to find the code?', 'lastform').'</a>',
						'default_value' => 'Source+Sans+Pro:300,300i,600,600i',
						'class'         => 'medium',
						'placeholder'   => esc_attr__('E.g.: Some+Font+Name:300,300i,600,600i', 'lastform'),
					),
					array(
						'label'         => esc_html__( 'Font Family Name', 'lastform' ),
						'type'          => 'text',
						'name'          => 'font-family-name',
						'tooltip'       => esc_attr__('Get your here: ', 'lastform') . '<a href="https://fonts.google.com/" target="_blank">fonts.google.com</a><br><br>&#8226; <a href="https://cl.ly/0H2i2B0t1a3P" target="_blank">'.esc_attr__('How to find the font family name?', 'lastform').'</a>',
						'default_value' => 'Source Sans Pro',
						'placeholder'   => esc_attr__('E.g.: Source Sans Pro', 'lastform'),
					),
				),
			),
			array(
				'title'  => esc_html__( 'Sound', 'lastform' ),
				'fields' => array(
					array(
						'label'         => esc_html__( 'Enable Sound Effects?', 'lastform' ),
						'type'          => 'checkbox',
						'name'          => 'sound-effects-enabled',
						'choices'       => array(
							array(
								'label'         => esc_attr__('Enabled', 'lastform'),
								'name'          => 'sound-effects-enabled',
								'default_value' => 0
							)
						)
					),
				),
			),
			array(
				'title'  => esc_html__( 'General', 'lastform' ),
				'fields' => array(
					array(
						'label'         => esc_html__( 'Progress box', 'lastform' ),
						'type'          => 'radio',
						'name'          => 'progress-box-type',
						'default_value' => 'percentage',
						'choices' => array(
							array(
								'label' => esc_html__( 'Percentage % (E.g.: 66% completed)', 'lastform' ),
								'value' => 'percentage',
							),
							array(
								'label' => esc_html__( 'Proportional (4 of 6 answered)', 'lastform' ),
								'value' => 'proportional'
							),
							array(
								'label' => esc_html__( 'None', 'lastform' ),
								'value' => 'none'
							),
						),

					),
					array(
						'label'         => esc_html__( 'Unfocused fields transparency level %', 'lastform' ),
						'type'          => 'text',
						'name'          => 'uncofused-fields-transparency-level',
						'tooltip'       => esc_html__( 'The amount of transparency to apply over unfocused fields. From 0 to 100.', 'lastform' ),
						'default_value' => 80,
						'placeholder'   => esc_attr__('0 to 100', 'lastform')
					),
					array(
						'label'         => esc_html__( 'Favicon', 'lastform' ),
						'type'          => 'text',
						'name'          => 'favicon-url',
						'tooltip'       => esc_html__( 'Your favicon URL', 'lastform' ),
						'class'         => 'large',
						'placeholder'   => esc_attr__('http://some-site.com/favicon.png', 'lastform')
					),
				),
			),
			array(
				'title'  => esc_html__( 'Custom code', 'lastform' ),
				'fields' => array(
					array(
						'label'             => esc_html__( 'Analytics, Pixel Code etc...', 'lastform' ),
						'type'              => 'textarea',
						'name'              => 'custom-html-code',
						'tooltip'           => esc_attr__('Paste here your custom HTML code.', 'lastform'),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'allow_all' ),
						'placeholder'       => esc_attr__('Paste here your FB Pixel Code, Google Analytcs code or any other code snippet you want.', 'lastform'),
					),
					array(
						'label'         => esc_html__( 'CSS code', 'lastform' ),
						'type'          => 'textarea',
						'name'          => 'custom-css-code',
						'tooltip'       => esc_attr__('Paste here your custom CSS code.', 'lastform').'<br><br><strong>' . esc_attr__('WARNING:', 'lastform') . '</strong> <em>' . esc_html__('Without ', 'lastform') . ' <span style="letter-spacing:-.1375em">< s t y l e ></span></em>',
						'class'         => 'medium',
						'placeholder'   => esc_attr__('E.g.: body { background-color: #c0ffee; }', 'lastform'),
					),
					array(
						'label'         => esc_html__( 'JavaScript code', 'lastform' ),
						'type'          => 'textarea',
						'name'          => 'custom-js-code',
						'tooltip'       => esc_attr__('Paste here your custom JavaScript code.', 'lastform').'<br><br><strong>' . esc_attr__('WARNING:', 'lastform') . '</strong> <em>' . esc_html__('Without ', 'lastform') . ' <span style="letter-spacing:-.1375em">< s c r i p t ></span></em>',
						'class'         => 'medium',
						'placeholder'   => esc_attr__('E.g.: body { background-color: #c0ffee; }', 'lastform'),
					),
				),
			),
		);
	}



	public function allow_all($value) {
		return true;
	}



	public function add_gform_lastform_item($menu_items, $id) {

		$setting = lastform_addon()->get_plugin_setting('form_url_slug');
		$slug = (!empty($setting)) ? $setting : 'lastform';

		$menu_items['lastform'] = array(
			'label'        => esc_attr__( 'Open Lastform', 'lastform' ),
			'icon'         => '<i class="fa fa-external-link fa-lg"></i>',
			'title'        => esc_attr__( 'View this form in Lastform', 'lastform' ),
			'url'          => home_url() . '/' . $slug . '/' . $id,
			'menu_class'   => 'gf_form_toolbar_lastform',
			'capabilities' => 'gravityforms_view_forms',
			'priority'     => 601,
			'target'       => '_blank',
		);

		return $menu_items;
	}

}
