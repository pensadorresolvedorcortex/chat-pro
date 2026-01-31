<?php
/* 
 * Plugin Name: Hospa Toolkit
 * Author: HiBootstrap
 * Author URI: hibootstrap.com
 * Description: A Light weight and easy toolkit for Elementor page builder widgets.
 * Version: 2.1
 */

if (!defined('ABSPATH')) {
    exit; //Exit if accessed directly
}

define('HOSPA_TOOLKIT_VERSION', '2.1');

define('HOSPA_ACC_PATH', plugin_dir_path(__FILE__));

require_once(HOSPA_ACC_PATH . 'inc/login-register.php');
require_once(HOSPA_ACC_PATH . 'inc/appointments.php');

function hospa_init() {
    load_plugin_textdomain( 'hospa-toolkit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'hospa_init' );


if( !defined('HOSPA_FRAMEWORK_VAR') ) define('HOSPA_FRAMEWORK_VAR', 'hospa_opt');

// Disable Elementor's Default Colors and Default Fonts
update_option( 'elementor_disable_color_schemes', 'yes' );
update_option( 'elementor_disable_typography_schemes', 'yes' );

require_once(HOSPA_ACC_PATH . 'inc/reusablec-block/reusablec-block.php');
require_once(HOSPA_ACC_PATH . 'inc/demo-importer-ocdi.php');
require_once(HOSPA_ACC_PATH . 'inc/demo-importer.php');

// Select page for link
function hospa_toolkit_get_page_as_list() {
    $args = wp_parse_args(array(
        'post_type' => 'page',
        'numberposts' => -1,
    ));

    $posts = get_posts( $args );
    $post_options = array(esc_html__('--Select Page--', 'hospa-toolkit') => '');

    if ( $posts ) {
        foreach ( $posts as $post ) {
            $post_options[$post->post_title] = $post->ID;
        }
    }
    $flipped = array_flip($post_options);
    return $flipped;
}

// Select page for link
function hospa_toolkit_get_services_as_list() {
    $args = wp_parse_args(array(
        'post_type' => 'services',
        'numberposts' => -1,
    ));

    $posts = get_posts( $args );
    $post_options = array(esc_html__('--Select Post--', 'hospa-toolkit') => '');

    if ( $posts ) {
        foreach ( $posts as $post ) {
            $post_options[$post->post_title] = $post->ID;
        }
    }
    $flipped = array_flip($post_options);
    return $flipped;
}
// Select page for link
function hospa_toolkit_get_doctors_as_list() {
    $args = wp_parse_args(array(
        'post_type' => 'doctors',
        'numberposts' => -1,
    ));

    $posts = get_posts( $args );
    $post_options = array(esc_html__('--Select Post--', 'hospa-toolkit') => '');

    if ( $posts ) {
        foreach ( $posts as $post ) {
            $post_options[$post->post_title] = $post->ID;
        }
    }
    $flipped = array_flip($post_options);
    return $flipped;
}

/**
 * Post category list
 */
function hospa_toolkit_get_post_cat_list() {
	$post_category_id = get_queried_object_id();
	$args = array(
		'parent' => $post_category_id
	);

	$terms = get_terms( 'category', get_the_ID());
	$cat_options = array(esc_html__('', 'hospa-toolkit') => '');

	if ($terms) {
		foreach ($terms as $term) {
			$cat_options[$term->name] = $term->name;
		}
	}
	return $cat_options;
}

function hospa_toolkit_get_page_career_cat_el()
{
    $arg = array(
        'taxonomy' => 'career_cat',
        'orderby' => 'name',
        'order'   => 'ASC'
    );
    $args = get_categories($arg);
    $args_options = array(esc_html__('', 'hospa-toolkit') => '');
    if ($args) {
        foreach ($args as $args) {
            $args_options[$args->name] = $args->slug;
        }
    }
    return $args_options;
}
function hospa_toolkit_get_page_labtest_cat_el()
{
    $arg = array(
        'taxonomy' => 'labtest_cat',
        'orderby' => 'name',
        'order'   => 'ASC'
    );
    $args = get_categories($arg);
    $args_options = array(esc_html__('', 'hospa-toolkit') => '');
    if ($args) {
        foreach ($args as $args) {
            $args_options[$args->name] = $args->slug;
        }
    }
    return $args_options;
}

function hospa_toolkit_get_page_doctors_cat_el()
{
    $arg = array(
        'taxonomy' => 'doctors_cat',
        'orderby' => 'name',
        'order'   => 'ASC'
    );
    $args = get_categories($arg);
    $args_options = array(esc_html__('', 'hospa-toolkit') => '');
    if ($args) {
        foreach ($args as $args) {
            $args_options[$args->name] = $args->slug;
        }
    }
    return $args_options;
}

function hospa_toolkit_get_page_services_cat_el()
{
    $arg = array(
        'taxonomy' => 'services_cat',
        'orderby' => 'name',
        'order'   => 'ASC'
    );
    $args = get_categories($arg);
    $args_options = array(esc_html__('', 'hospa-toolkit') => '');
    if ($args) {
        foreach ($args as $args) {
            $args_options[$args->name] = $args->slug;
        }
    }
    return $args_options;
}

function hospa_toolkit_get_page_event_cat_el()
{
    $arg = array(
        'taxonomy' => 'event_cat',
        'orderby' => 'name',
        'order'   => 'ASC'
    );
    $args = get_categories($arg);
    $args_options = array(esc_html__('', 'hospa-toolkit') => '');
    if ($args) {
        foreach ($args as $args) {
            $args_options[$args->name] = $args->slug;
        }
    }
    return $args_options;
}

// Select Posts for link
function hospa_toolkit_get_labtest_as_list() {
    $args = wp_parse_args(array(
        'post_type' => 'labtest',
        'numberposts' => -1,
    ));

    $posts = get_posts($args);
    $post_options = array(esc_html__('', 'hospa-toolkit') => '');

    if ($posts) {
        foreach ($posts as $post) {
            $post_options[$post->post_title] = $post->ID;
        }
    }
    $flipped = array_flip($post_options);
    return $flipped;
}
// Select Posts for link
function hospa_toolkit_get_career_as_list() {
    $args = wp_parse_args(array(
        'post_type' => 'career',
        'numberposts' => -1,
    ));

    $posts = get_posts($args);
    $post_options = array(esc_html__('', 'hospa-toolkit') => '');

    if ($posts) {
        foreach ($posts as $post) {
            $post_options[$post->post_title] = $post->ID;
        }
    }
    $flipped = array_flip($post_options);
    return $flipped;
}


//Custom Post
function hospa_toolkit_custom_post()
{
	global $hospa_opt;

	if(isset($hospa_opt['labtest_permalink'])) {
		$labtest_post_type = $hospa_opt['labtest_permalink'];
	} else {
		$labtest_post_type = 'labtest-post';
	}
	if(isset($hospa_opt['doctors_permalink'])) {
		$doctors_permalink = $hospa_opt['doctors_permalink'];
	} else {
		$doctors_permalink = 'doctors-post';
	}
	if(isset($hospa_opt['services_permalink'])) {
		$services_permalink = $hospa_opt['services_permalink'];
	} else {
		$services_permalink = 'services-post';
	}
	if(isset($hospa_opt['career_permalink'])) {
		$career_permalink = $hospa_opt['career_permalink'];
	} else {
		$career_permalink = 'career-post';
	}
	if(isset($hospa_opt['event_permalink'])) {
		$event_permalink = $hospa_opt['event_permalink'];
	} else {
		$event_permalink = 'event-post';
	}
	
	$lab_post_title  = !empty( $hospa_opt['lab_post_title'] ) ? $hospa_opt['lab_post_title'] : 'Lab Test';
	$car_post_title  = !empty( $hospa_opt['car_post_title'] ) ? $hospa_opt['car_post_title'] : 'Career';
	$event_post_title  = !empty( $hospa_opt['event_post_title'] ) ? $hospa_opt['event_post_title'] : 'Event';
	$ser_post_title  = !empty( $hospa_opt['ser_post_title'] ) ? $hospa_opt['ser_post_title'] : 'Services';
	$doc_post_title  = !empty( $hospa_opt['doc_post_title'] ) ? $hospa_opt['doc_post_title'] : 'Doctor';

	// labtest Program Custom Post
 	register_post_type('labtest',
        array(
            'labels' => array(
                'name'          => esc_html__($lab_post_title, 'hospa-toolkit'),
                'singular_name' => esc_html__($lab_post_title, 'hospa-toolkit'),
            ),
            'menu_icon'   => 'dashicons-images-alt',
            'supports'    => array('title', 'thumbnail', 'editor', 'page-attributes','excerpt'),
            'has_archive' => true,
			'public'      => true,
			'rewrite'     => array( 'slug' => $labtest_post_type ),
        )
	);

	// Doctors Custom Post
	register_post_type('doctors',
        array(
            'labels'            => array(
                'name'          => esc_html__($doc_post_title, 'hospa-toolkit'),
                'singular_name' => esc_html__($doc_post_title, 'hospa-toolkit'),
            ),
            'menu_icon'   => 'dashicons-images-alt',
            'supports'    => array('title', 'thumbnail', 'editor', 'page-attributes','excerpt'),
            'has_archive' => true,
			'public'      => true,
			'rewrite'     => array( 'slug' => $doctors_permalink ),
        )
	);

	// Services Custom Post
	register_post_type('services',
        array(
            'labels'            => array(
                'name'          => $ser_post_title,
                'singular_name' => $ser_post_title,
            ),
            'menu_icon'   => 'dashicons-images-alt',
            'supports'    => array('title', 'thumbnail', 'editor', 'page-attributes','excerpt'),
            'has_archive' => true,
			'public'      => true,
			'rewrite'     => array( 'slug' => $services_permalink ),
        )
	);
	// Career Custom Post
	register_post_type('career',
        array(
            'labels'            => array(
                'name'          => esc_html__($car_post_title, 'hospa-toolkit'),
                'singular_name' => esc_html__($car_post_title, 'hospa-toolkit'),
            ),
            'menu_icon'   => 'dashicons-images-alt',
            'supports'    => array('title', 'thumbnail', 'editor', 'page-attributes','excerpt'),
            'has_archive' => true,
			'public'      => true,
			'rewrite'     => array( 'slug' => $career_permalink ),
        )
	);
	// Event Custom Post
	register_post_type('event',
        array(
            'labels'            => array(
                'name'          => esc_html__($event_post_title, 'hospa-toolkit'),
                'singular_name' => esc_html__($event_post_title, 'hospa-toolkit'),
            ),
            'menu_icon'   => 'dashicons-images-alt',
            'supports'    => array('title', 'thumbnail', 'editor', 'page-attributes','excerpt'),
            'has_archive' => true,
			'public'      => true,
			'rewrite'     => array( 'slug' => $event_permalink ),
        )
	);
}
add_action('init', 'hospa_toolkit_custom_post', 20);

//Taxonomy Custom Post
function hospa_custom_post_taxonomy(){

	global $hospa_opt;

	if(isset($hospa_opt['labtest_cat_permalink'])) {
		$labtest_cat_permalink = $hospa_opt['labtest_cat_permalink'];
	} else {
		$labtest_cat_permalink = 'labtest-category';
	}
	if(isset($hospa_opt['doctors_cat_permalink'])) {
		$doctors_cat_permalink = $hospa_opt['doctors_cat_permalink'];
	} else {
		$doctors_cat_permalink = 'doctors-category';
	}
	if(isset($hospa_opt['services_cat_permalink'])) {
		$services_cat_permalink = $hospa_opt['services_cat_permalink'];
	} else {
		$services_cat_permalink = 'services-category';
	}
	if(isset($hospa_opt['career_cat_permalink'])) {
		$career_cat_permalink = $hospa_opt['career_cat_permalink'];
	} else {
		$career_cat_permalink = 'career-category';
	}
	if(isset($hospa_opt['event_cat_permalink'])) {
		$event_cat_permalink = $hospa_opt['event_cat_permalink'];
	} else {
		$event_cat_permalink = 'event-category';
	}

    register_taxonomy(
        'labtest_cat',
        'labtest',
            array(
            'hierarchical'      => true,
            'label'             => esc_html__('Lab Test Category', 'hospa-toolkit' ),
            'query_var'         => true,
            'show_admin_column' => true,
                'rewrite'       => array(
                'slug'          => $labtest_cat_permalink,
                'with_front'    => true
            )
        )
    );
	register_taxonomy(
        'doctors_cat',
        'doctors',
            array(
            'hierarchical'      => true,
            'label'             => esc_html__('Doctors Category', 'hospa-toolkit' ),
            'query_var'         => true,
            'show_admin_column' => true,
                'rewrite'       => array(
                'slug'          => $doctors_cat_permalink,
                'with_front'    => true
            )
        )
    );
	register_taxonomy(
        'doctors_facility',
        'doctors',
            array(
            'hierarchical'      => true,
            'label'             => esc_html__('Doctors Facility', 'hospa-toolkit' ),
            'query_var'         => true,
            'show_admin_column' => true,
                'rewrite'       => array(
                'slug'          => 'doctors-facility',
                'with_front'    => true
            )
        )
    );
	register_taxonomy(
        'services_cat',
        'services',
            array(
            'hierarchical'      => true,
            'label'             => esc_html__('Services Category', 'hospa-toolkit' ),
            'query_var'         => true,
            'show_admin_column' => true,
                'rewrite'       => array(
                'slug'          => $services_cat_permalink,
                'with_front'    => true
            )
        )
    );
	register_taxonomy(
        'career_cat',
        'career',
            array(
            'hierarchical'      => true,
            'label'             => esc_html__('Career Category', 'hospa-toolkit' ),
            'query_var'         => true,
            'show_admin_column' => true,
                'rewrite'       => array(
                'slug'          => $career_cat_permalink,
                'with_front'    => true
            )
        )
    );
	register_taxonomy(
        'event_cat',
        'event',
            array(
            'hierarchical'      => true,
            'label'             => esc_html__('Event Category', 'hospa-toolkit' ),
            'query_var'         => true,
            'show_admin_column' => true,
                'rewrite'       => array(
                'slug'          => $event_cat_permalink,
                'with_front'    => true
            )
        )
    );
}
add_action('init', 'hospa_custom_post_taxonomy', 20);

// Map CSS, JS
function hospa_scripts2() {
	wp_enqueue_style( 'leaflet', 	    'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css', null, HOSPA_VERSION );
	wp_enqueue_script( 'leaflet', 	    'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', array('jquery'),HOSPA_JS );
}

add_action( 'wp_enqueue_scripts', 'hospa_scripts2' );

// Add this to your theme's functions.php
function hospa_add_script_to_footer(){
    if( ! is_admin() ) { 
		global $hospa_opt;
		$action_url  = isset( $hospa_opt['mail_action_url']) ? $hospa_opt['mail_action_url'] : '';
		?>

		<script>
			<?php if( !empty($action_url) ) : ?>
				;(function($){
					"use strict";
					$(document).ready(function () {
						// MAILCHIMP
						if ($(".mailchimp").length > 0) {
							$(".mailchimp").ajaxChimp({
								callback: mailchimpCallback,
								url: "<?php echo esc_js($action_url) ?>"
							});
						}
						$(".memail").on("focus", function () {
							$(".mchimp-errmessage").fadeOut();
							$(".mchimp-sucmessage").fadeOut();
						});
						$(".memail").on("keydown", function () {
							$(".mchimp-errmessage").fadeOut();
							$(".mchimp-sucmessage").fadeOut();
						});
						$(".memail").on("click", function () {
							$(".memail").val("");
						});

						function mailchimpCallback(resp) {
							if (resp.result === "success") {
								$(".mchimp-sucmessage").html(resp.msg).fadeIn(1000);
								$(".mchimp-sucmessage").fadeOut(500);
							} else if (resp.result === "error") {
								$(".mchimp-errmessage").html(resp.msg).fadeIn(1000);
							}
						}
					});
				})(jQuery)
			<?php endif; ?>

			// Quantity
			jQuery(document).ready(function($){
				$(document).on('click', '.plus', function(e) { // replace '.quantity' with document (without single quote)
					$input = $(this).prev('input.qty');
					var val = parseInt($input.val());
					var step = $input.attr('step');
					step = 'undefined' !== typeof(step) ? parseInt(step) : 1;
					$input.val( val + step ).change();
				});
				$(document).on('click', '.minus',  // replace '.quantity' with document (without single quote)
					function(e) {
					$input = $(this).next('input.qty');
					var val = parseInt($input.val());
					var step = $input.attr('step');
					step = 'undefined' !== typeof(step) ? parseInt(step) : 1;
					if (val > 0) {
						$input.val( val - step ).change();
					}
				});
			});
		</script>

	<?php 
	}
}
add_action( 'wp_footer', 'hospa_add_script_to_footer' );

// Main hospa Toolkit Class
final class Elementor_Hospa_Extension {

	const VERSION = '1.0.0';
	const MINIMUM_ELEMENTOR_VERSION = '2.0.0';
	const MINIMUM_PHP_VERSION = '7.0';

	// Instance
    private static $_instance = null;
    
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;

	}

	// Constructor
	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init' ] );

		// Add shortcode column to block list
		add_filter( 'manage_edit-covidform_columns', array( $this, 'edit_covidform_columns' ) );
		add_action( 'manage_covidform_posts_custom_column', array( $this, 'manage_covidform_columns' ), 10, 2 );

	}

	// init
	public function init() {

		// Check if Elementor installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_missing_main_plugin' ] );
			return;
		}

		// Check for required Elementor version
		if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_minimum_elementor_version' ] );
			return;
		}

		// Check for required PHP version
		if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_minimum_php_version' ] );
			return;
		}

		// Add Plugin actions
		add_action( 'elementor/widgets/register', [ $this, 'init_widgets' ] );
        
        add_action('elementor/elements/categories_registered',[ $this, 'register_new_category'] );
        
    }

	public function register_new_category($manager){

        $manager->add_category('hospacategory',[
            'title'  => esc_html__('Hospa Category','hospa-toolkit'),
            'icon'   => 'fa fa-image'
        ]);
    }

	//Admin notice
	public function admin_notice_missing_main_plugin() {

		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'hospa-toolkit' ),
			'<strong>' . esc_html__( 'Hospa Toolkit', 'hospa-toolkit' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'hospa-toolkit' ) . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );

	}
	public function admin_notice_minimum_elementor_version() {

		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'hospa-toolkit' ),
			'<strong>' . esc_html__( 'Hospa Toolkit', 'hospa-toolkit' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'hospa-toolkit' ) . '</strong>',
			 self::MINIMUM_ELEMENTOR_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );

	}
	public function admin_notice_minimum_php_version() {

		if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );

		$message = sprintf(
			/* translators: 1: Plugin name 2: PHP 3: Required PHP version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'hospa-toolkit' ),
			'<strong>' . esc_html__( 'Hospa Toolkit', 'hospa-toolkit' ) . '</strong>',
			'<strong>' . esc_html__( 'PHP', 'hospa-toolkit' ) . '</strong>',
			 self::MINIMUM_PHP_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );

	}

	// Toolkit Widgets
	public function init_widgets() {
		// Include Widget files
		require_once( __DIR__ . '/widgets/banner-one.php' );
		require_once( __DIR__ . '/widgets/features-area.php' );
		require_once( __DIR__ . '/widgets/about-area.php' );
		require_once( __DIR__ . '/widgets/services-post.php' );
		require_once( __DIR__ . '/widgets/feedback.php' );
		require_once( __DIR__ . '/widgets/choose-area.php' );
		require_once( __DIR__ . '/widgets/labtest-post.php' );
		require_once( __DIR__ . '/widgets/labtest-area.php' );
		require_once( __DIR__ . '/widgets/labtest-details.php' );
		require_once( __DIR__ . '/widgets/doctors-tab.php' );
		require_once( __DIR__ . '/widgets/apps-area.php' );
		require_once( __DIR__ . '/widgets/blog-posts.php' );
		require_once( __DIR__ . '/widgets/banner-two.php' );
		require_once( __DIR__ . '/widgets/about-two.php' );
		require_once( __DIR__ . '/widgets/services-two.php' );
		require_once( __DIR__ . '/widgets/animated-text.php' );
		require_once( __DIR__ . '/widgets/features-three.php' );
		require_once( __DIR__ . '/widgets/appointment-area.php' );
		require_once( __DIR__ . '/widgets/faq-area.php' );
		require_once( __DIR__ . '/widgets/blog-two.php' );
		require_once( __DIR__ . '/widgets/banner-three.php' );

		require_once( __DIR__ . '/widgets/partner-area.php' );
		require_once( __DIR__ . '/widgets/solution-area.php' );
		require_once( __DIR__ . '/widgets/features-overview.php' );
		require_once( __DIR__ . '/widgets/visitor-information.php' );
		require_once( __DIR__ . '/widgets/hospital-stay.php' );
		require_once( __DIR__ . '/widgets/health-information.php' );
		require_once( __DIR__ . '/widgets/find-location-area.php' );
		require_once( __DIR__ . '/widgets/emergency-care.php' );
		require_once( __DIR__ . '/widgets/service-details.php' );
		require_once( __DIR__ . '/widgets/doctors-post.php' );
		require_once( __DIR__ . '/widgets/professional-post.php' );
		require_once( __DIR__ . '/widgets/doctor-details.php' );
		require_once( __DIR__ . '/widgets/pricing-plan.php' );
		require_once( __DIR__ . '/widgets/partner-two.php' );
		require_once( __DIR__ . '/widgets/pay-online.php' );
		require_once( __DIR__ . '/widgets/education-info.php' );
		require_once( __DIR__ . '/widgets/annual-wellness-exam.php' );
		require_once( __DIR__ . '/widgets/career-area.php' );
		require_once( __DIR__ . '/widgets/career-post.php' );
		require_once( __DIR__ . '/widgets/career-details.php' );
		require_once( __DIR__ . '/widgets/volunteer-area.php' );
		require_once( __DIR__ . '/widgets/process-area.php' );
		require_once( __DIR__ . '/widgets/working-hours-tab.php' );
		require_once( __DIR__ . '/widgets/gallery-area.php' );
		require_once( __DIR__ . '/widgets/faq-two.php' );
		require_once( __DIR__ . '/widgets/faq-form.php' );
		require_once( __DIR__ . '/widgets/feedback-two.php' );
		require_once( __DIR__ . '/widgets/contact-area.php' );
		require_once( __DIR__ . '/widgets/about-three.php' );
		require_once( __DIR__ . '/widgets/features-four.php' );
		require_once( __DIR__ . '/widgets/strategy-area.php' );
		require_once( __DIR__ . '/widgets/purpose-faq-area.php' );
		require_once( __DIR__ . '/widgets/book-appointment.php' ); //
		require_once( __DIR__ . '/widgets/donate-area.php' ); //
		require_once( __DIR__ . '/widgets/coming-soon.php' );
		require_once( __DIR__ . '/widgets/header-service-area.php' );
		require_once( __DIR__ . '/widgets/footer-area.php' );

		// V1.4
		require_once( __DIR__ . '/widgets/doctors-post-query.php' );
		require_once( __DIR__ . '/widgets/services-post-query.php' );

		// Parts
		require_once( __DIR__ . '/widgets/parts/button.php' );
		require_once( __DIR__ . '/widgets/parts/section-title.php' );
		require_once( __DIR__ . '/widgets/parts/lists.php' );
		require_once( __DIR__ . '/widgets/parts/doctor-contact-info.php' );
		require_once( __DIR__ . '/widgets/parts/contact-lists.php' );
		require_once( __DIR__ . '/widgets/parts/social-link-card.php' );
		require_once( __DIR__ . '/widgets/parts/faq-form-two.php' );
		require_once( __DIR__ . '/widgets/parts/get-direction-card.php' );
		require_once( __DIR__ . '/widgets/parts/qualification-lists.php' );
		require_once( __DIR__ . '/widgets/parts/post-img.php' );
		require_once( __DIR__ . '/widgets/parts/opening-lists.php' );
		require_once( __DIR__ . '/widgets/parts/service-posts-card.php' );
		require_once( __DIR__ . '/widgets/parts/apps-card.php' );
		require_once( __DIR__ . '/widgets/ech/banner-four.php' );
		require_once( __DIR__ . '/widgets/ech/features-area.php' );
		require_once( __DIR__ . '/widgets/ech/about-image.php' );
		require_once( __DIR__ . '/widgets/ech/wrap-quote.php' );
		require_once( __DIR__ . '/widgets/ech/services.php' );
		require_once( __DIR__ . '/widgets/ech/text-with-link.php' );
		require_once( __DIR__ . '/widgets/ech/info-lists.php' );
		require_once( __DIR__ . '/widgets/ech/video.php' );
		require_once( __DIR__ . '/widgets/ech/funfact-card.php' );
		require_once( __DIR__ . '/widgets/ech/choose-counter-wrap.php' );
		require_once( __DIR__ . '/widgets/ech/inner-items.php' );
		require_once( __DIR__ . '/widgets/ech/review-wrap.php' );
		require_once( __DIR__ . '/widgets/ech/feedback.php' );
		require_once( __DIR__ . '/widgets/ech/contact-card.php' );
		require_once( __DIR__ . '/widgets/ech/contact-form.php' );
		require_once( __DIR__ . '/widgets/ech/blog-slider.php' );
		require_once( __DIR__ . '/widgets/ech/cta.php' );
		require_once( __DIR__ . '/widgets/cch/banner-bg.php' );
		require_once( __DIR__ . '/widgets/cch/link-info.php' );
		require_once( __DIR__ . '/widgets/cch/about-ff-card.php' );
		require_once( __DIR__ . '/widgets/cch/icon-img.php' );
		require_once( __DIR__ . '/widgets/cch/cch-feedback.php' );
		require_once( __DIR__ . '/widgets/cch/rating-google.php' );
		require_once( __DIR__ . '/widgets/cch/feed-info-box.php' );
		require_once( __DIR__ . '/widgets/cch/doctors-tab.php' );
		require_once( __DIR__ . '/widgets/cch/services.php' );
		require_once( __DIR__ . '/widgets/cch/help-card.php' );
		require_once( __DIR__ . '/widgets/cch/blog-slider.php' );
		require_once( __DIR__ . '/widgets/cch/section-title-btn.php' );
		require_once( __DIR__ . '/widgets/cch/faq.php' );
		require_once( __DIR__ . '/widgets/cch/cta.php' );

		// v1.5
		require_once( __DIR__ . '/widgets/doctor-slider.php' );
		require_once( __DIR__ . '/widgets/service-details-part/service-sidebar-posts.php' );
		require_once( __DIR__ . '/widgets/parts/social-img-icon.php' );
		require_once( __DIR__ . '/widgets/navbar-one.php' );
		require_once( __DIR__ . '/widgets/career-details-card.php' );
		require_once( __DIR__ . '/widgets/labtest-details-card.php' );

		// v1.7
		require_once( __DIR__ . '/widgets/banner-dental.php' );
		require_once( __DIR__ . '/widgets/about-img-dental.php' );
		require_once( __DIR__ . '/widgets/about-content-dental.php' );
		require_once( __DIR__ . '/widgets/choose-content-dental.php' );
		require_once( __DIR__ . '/widgets/dental-testimonials.php' );
		require_once( __DIR__ . '/widgets/dental-doctor.php' );
		require_once( __DIR__ . '/widgets/dental-treatment.php' );
		require_once( __DIR__ . '/widgets/contact-form-two.php' );
		require_once( __DIR__ . '/widgets/blog-dental.php' );
		require_once( __DIR__ . '/widgets/banner-cancer.php' );
		require_once( __DIR__ . '/widgets/cancer-choose.php' );
		require_once( __DIR__ . '/widgets/about-content-cancer.php' );
		require_once( __DIR__ . '/widgets/cancer-testimonials.php' );
		require_once( __DIR__ . '/widgets/cancer-faq.php' );
		require_once( __DIR__ . '/widgets/blog-cancer.php' );
		require_once( __DIR__ . '/widgets/contact-form-three.php' );
		require_once( __DIR__ . '/widgets/cancer-choose-two.php' );
		require_once( __DIR__ . '/widgets/banner-skin.php' );
		require_once( __DIR__ . '/widgets/appoinment-skin.php' );
		require_once( __DIR__ . '/widgets/appoinment-card.php' );
		require_once( __DIR__ . '/widgets/about-title-skin.php' );
		require_once( __DIR__ . '/widgets/about-content-skin.php' );
		require_once( __DIR__ . '/widgets/skin-features-card.php' );
		require_once( __DIR__ . '/widgets/skin-text-slide.php' );
		require_once( __DIR__ . '/widgets/blog-skin.php' );
		require_once( __DIR__ . '/widgets/skin-gallery-card.php' );
		require_once( __DIR__ . '/widgets/about-download-skin.php' );
		require_once( __DIR__ . '/widgets/skin-treatment.php' );
		require_once( __DIR__ . '/widgets/banner-psychiatric.php' );
		require_once( __DIR__ . '/widgets/about-title-psychiatric.php' );
		require_once( __DIR__ . '/widgets/psychiatric-mission-card.php' );
		require_once( __DIR__ . '/widgets/psychiatric-conditions.php' );
		require_once( __DIR__ . '/widgets/info-title-psychiatric.php' );
		require_once( __DIR__ . '/widgets/psyphiatric-faq.php' );
		require_once( __DIR__ . '/widgets/blog-psyphiatric.php' );
		require_once( __DIR__ . '/widgets/ph-appointment.php' );
		require_once( __DIR__ . '/widgets/banner-maternity.php' );
		require_once( __DIR__ . '/widgets/about-content-maternity.php' );
		require_once( __DIR__ . '/widgets/partner-three.php' );
		require_once( __DIR__ . '/widgets/maternity-treatment.php' );
		require_once( __DIR__ . '/widgets/maternity-overview-card.php' );
		require_once( __DIR__ . '/widgets/maternity-testimonials.php' );
		require_once( __DIR__ . '/widgets/appoinment-maternity.php' );
		require_once( __DIR__ . '/widgets/maternity-faq.php' );
		require_once( __DIR__ . '/widgets/blog-maternity.php' );
		require_once( __DIR__ . '/widgets/maternity-feature-card.php' );
		require_once( __DIR__ . '/widgets/cancer-doctor.php' );
		require_once( __DIR__ . '/widgets/cancer-doctor-two.php' );
		require_once( __DIR__ . '/widgets/cancer-section-title.php' );
		require_once( __DIR__ . '/widgets/doctor-tab-two.php' );
		require_once( __DIR__ . '/widgets/psychiatric-doctor.php' );
		require_once( __DIR__ . '/widgets/maternity-doctor.php' );
		require_once( __DIR__ . '/widgets/services-post-dental.php' );
		require_once( __DIR__ . '/widgets/services-post-dental-two.php' );
		require_once( __DIR__ . '/widgets/services-post-cancer.php' );
		require_once( __DIR__ . '/widgets/appointment-area2.php' );
		require_once( __DIR__ . '/widgets/services-post-skin.php' );
		require_once( __DIR__ . '/widgets/services-post-psychiatric.php' );
		require_once( __DIR__ . '/widgets/services-post-maternity.php' );
		require_once( __DIR__ . '/widgets/service-details-part/service-sidebar-posts-two.php' );
		require_once( __DIR__ . '/widgets/doctor-service.php' );
		require_once( __DIR__ . '/widgets/service-faq-area.php' );
		require_once( __DIR__ . '/widgets/event-post.php' );
		require_once( __DIR__ . '/widgets/event-post-two.php' );
		require_once( __DIR__ . '/widgets/navbar-two.php' );
		require_once( __DIR__ . '/widgets/navbar-three.php' );
		require_once( __DIR__ . '/widgets/navbar-four.php' );
		require_once( __DIR__ . '/widgets/navbar-five.php' );
		require_once( __DIR__ . '/widgets/navbar-six.php' );

		// V1.9
		require_once( __DIR__ . '/widgets/working-hours-tab-two.php' );

		// Appointment
		require_once( __DIR__ . '/widgets/appointment/book-appointment.php' );
		require_once( __DIR__ . '/widgets/appointment/login.php' );
		require_once( __DIR__ . '/widgets/appointment/profile.php' );
		require_once( __DIR__ . '/widgets/appointment/register.php' );
	}
}
Elementor_Hospa_Extension::instance();

require_once(HOSPA_ACC_PATH . 'inc/widgets.php');
add_action('after_setup_theme', function() {
	require_once(HOSPA_ACC_PATH . 'ReduxCore/framework.php');
	require_once(HOSPA_ACC_PATH . 'ReduxCore/redux-sample-config.php');
	require_once(HOSPA_ACC_PATH . 'inc/acf.php');
});
require_once(HOSPA_ACC_PATH . 'inc/header-post.php');

//Registering crazy toolkit files
function hospa_toolkit_files()
{
    wp_enqueue_style('font-awesome-4.7', plugin_dir_url(__FILE__) . 'assets/css/font-awesome.min.css');
}

add_action('wp_enqueue_scripts', 'hospa_toolkit_files');

// Extra P tag from widget
remove_filter('widget_text_content', 'wpautop');

add_filter('script_loader_tag', 'hospa_clean_script_tag');
function hospa_clean_script_tag($input) {
	$input = str_replace( array( 'type="text/javascript"', "type='text/javascript'" ), '', $input );
	return $input;
}

function hospa_admin_css() {
	echo '<style>.#fw-ext-brizy,#fw-extensions-list-wrapper .toggle-not-compat-ext-btn-wrapper,.fw-brz-dismiss{display:none}.fw-brz-dismiss{display:none}.fw-extensions-list-item{display:none!important}#fw-ext-backups{display:block!important}#update-nag,.update-nag{display:block!important} .fw-sole-modal-content.fw-text-center .fw-text-danger.dashicons.dashicons-warning:before { content: "Almost finished! Please check with a reload." !important;}.fw-sole-modal-content.fw-text-center .fw-text-danger.dashicons.dashicons-warning {color: green !important; width:100%} .fw-modal.fw-modal-open > .media-modal-backdrop {width: 100% !important;}</style>';
	
}
add_action('admin_head', 'hospa_admin_css');

// Pass placeholder to Comments Form
add_filter( 'comment_form_default_fields', 'Hospa_comment_placeholders' );
function Hospa_comment_placeholders( $fields )
{

	global $hospa_opt;
	$name_place   = isset( $hospa_opt['form_name_place']) ? $hospa_opt['form_name_place'] : '';
	$email_place  = isset( $hospa_opt['form_email_place']) ? $hospa_opt['form_email_place'] : '';
	$web_place    = isset( $hospa_opt['form_web_place']) ? $hospa_opt['form_web_place'] : '';

	$fields['author'] = str_replace(
        '<input',
        '<input placeholder="' . $name_place . '"',
        $fields['author']
    );
    $fields['email'] = str_replace(
        '<input',
        '<input placeholder="' . $email_place . '"',
        $fields['email']
    );
    $fields['url'] = str_replace(
        '<input',
        '<input placeholder="' . $web_place . '"',
        $fields['url']
    );
    return $fields;
}

/* Add Placehoder in comment Form Field (Comment) */
add_filter( 'comment_form_defaults', 'hospa_textarea_placeholder' );

function hospa_textarea_placeholder( $fields ) {
	global $hospa_opt;
	$comment_place  = isset( $hospa_opt['form_comment_ph']) ? $hospa_opt['form_comment_ph'] : '';

    $fields['comment_field'] = str_replace(
        '<textarea',
        '<textarea placeholder="' . $comment_place . '"',
        $fields['comment_field']
    );

	return $fields;
}

// Advanced search functionality
if ( ! function_exists( 'hospa_advanced_search_query' ) ) :
    function hospa_advanced_search_query($query) {
        if( $query->is_search() ) {
            // category terms search.
            if (isset($_GET['category']) && !empty($_GET['category'])) {
                $query->set('tax_query', array(array( 
                    'taxonomy' => 'product_cat', 
                    'field' => 'slug', 'terms' => array($_GET['category']) )
                ));
            } else {
                if( is_admin() || ! $query->is_main_query() ) {
                    return;
                }
                // Make sure this isn't the WooCommerce product search form 
                if( isset($_GET['post_type']) && ($_GET['post_type'] == 'product') ) {
                    return;
                }
                if( isset($_GET['post_type']) && ($_GET['post_type'] == 'services') ) {
                    return;
                } 
                if( isset($_GET['post_type']) && ($_GET['post_type'] == 'labtest') ) {
                    return;
                } 
                if( isset($_GET['post_type']) && ($_GET['post_type'] == 'doctors') ) {
                    return;
                } 
                if( isset($_GET['post_type']) && ($_GET['post_type'] == 'careers') ) {
                    return;
                } 
                if( isset($_GET['post_type']) && ($_GET['post_type'] == 'events') ) {
                    return;
                } 
                $in_search_post_types = get_post_types( array( 'exclude_from_search' => false ) );
                // The post types you're removing (example: 'product', 'custom post types' and 'page')
                $post_types_to_remove = array( 'product', 'page', 'services', 'labtest', 'doctors', 'events' ); // Add here your custom posts name instead of custompost1, custompost2
                foreach( $post_types_to_remove as $post_type_to_remove ) {
                    if( is_array( $in_search_post_types ) && in_array(
                        $post_type_to_remove, $in_search_post_types ) ) {
                        unset( $in_search_post_types[ $post_type_to_remove ] );
                        $query->set( 'post_type', $in_search_post_types );
                    }
                }
            }
        }
        return $query;
    }
endif;
add_action('pre_get_posts', 'hospa_advanced_search_query');

function hospa_toolkit_enable_svg_upload( $upload_mimes ) {
    $upload_mimes['svg'] = 'image/svg+xml';
    $upload_mimes['svgz'] = 'image/svg+xml';
    return $upload_mimes;
}
add_filter( 'upload_mimes', 'hospa_toolkit_enable_svg_upload', 10, 1 );


function hospa_toolkit_get_courses_cat_list() {
	$courses_category_id = get_queried_object_id();
	$args = array(
		'parent' => $courses_category_id
	);

	if ( function_exists('tutor') ) {
		$terms = get_terms( array(
			'taxonomy' => 'course-category',
			'hide_empty' => false,
		) );
	}

	$cat_options = array('' => '');

	if ($terms) {
		foreach ($terms as $term) {
			$cat_options[$term->name] = $term->name;
		}
	}
	return $cat_options;
}

/**
 * Check a plugin activate
 */
function hospa_plugin_active( $plugin ) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	if ( is_plugin_active( $plugin ) ) {
		return true;
	}
	return false;
}

function hospa_current_url() {
    return wp_doing_ajax() ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ?? '' ) ) : tutor()->current_url; //phpcs:disable WordPress.Security.NonceVerification.Missing
}
$opt_name = HOSPA_FRAMEWORK_VAR;

add_filter( 'body_class', function( $classes ) {
    return array_merge( $classes, array( 'hospa-toolkit-activate' ) );
} );

/**
 * Menu Registration
*/
if ( ! function_exists( 'hospa_register_top_menus' ) ) :
	function hospa_register_top_menus(){
		register_nav_menus(
			array(
				'top-menu'   => esc_html__('Top Header Menu', 'hospa-toolkit'),
				'mobile-menu'   => esc_html__('Mobile Menu', 'hospa-toolkit'),
			)
		);
	}
endif;
add_action('init', 'hospa_register_top_menus');

/**
 * Get the existing menus in array format
 * @return array
 */
function hospa_get_menu_array() {
    $menus = wp_get_nav_menus();
    $menu_array = [];
    foreach ( $menus as $menu ) {
        $menu_array[$menu->slug] = $menu->name;
    }
    return $menu_array;
}


// Remove custom posts type URL
// 1. services
function custom_service_rewrite_rules() {

	global $hospa_opt;
	$remove_url_option       = !empty($hospa_opt['hospa_disable_cp_url']) ? $hospa_opt['hospa_disable_cp_url'] : '';

	if(	$remove_url_option == '1'):
		$posts = get_posts(array(
			'post_type' => 'services',
			'posts_per_page' => -1,
			'post_status' => 'publish',
		));

		foreach ($posts as $post) {
			$slug = $post->post_name;
			add_rewrite_rule(
				'^' . $slug . '/?$',
				'index.php?post_type=services&name=' . $slug,
				'top'
			);
		}
	endif;

}
add_action('init', 'custom_service_rewrite_rules');

function custom_service_post_type_link($post_link, $post) {

	global $hospa_opt;
	$remove_url_option       = !empty($hospa_opt['hospa_disable_cp_url']) ? $hospa_opt['hospa_disable_cp_url'] : '';
		if(	$remove_url_option == '1'):
			if ($post->post_type === 'services') {
				return home_url('/' . $post->post_name . '/');
			}
		endif;
		return $post_link;
	
}
add_filter('post_type_link', 'custom_service_post_type_link', 10, 2);



// 2. labtest
function custom_labtest_rewrite_rules() {

	global $hospa_opt;
	$remove_url_option       = !empty($hospa_opt['hospa_disable_cp_url']) ? $hospa_opt['hospa_disable_cp_url'] : '';

	if(	$remove_url_option == '1'):
		$posts = get_posts(array(
			'post_type' => 'labtest',
			'posts_per_page' => -1,
			'post_status' => 'publish',
		));

		foreach ($posts as $post) {
			$slug = $post->post_name;
			add_rewrite_rule(
				'^' . $slug . '/?$',
				'index.php?post_type=labtest&name=' . $slug,
				'top'
			);
		}
	endif;
}
add_action('init', 'custom_labtest_rewrite_rules');

function custom_labtest_post_type_link($post_link, $post) {
	global $hospa_opt;
	$remove_url_option       = !empty($hospa_opt['hospa_disable_cp_url']) ? $hospa_opt['hospa_disable_cp_url'] : '';

	if(	$remove_url_option == '1'):
		if ($post->post_type === 'labtest') {
			return home_url('/' . $post->post_name . '/');
		}
	endif;

	return $post_link;
}
add_filter('post_type_link', 'custom_labtest_post_type_link', 10, 2);

// 3. doctors
function custom_doctors_rewrite_rules() {

	global $hospa_opt;
	$remove_url_option       = !empty($hospa_opt['hospa_disable_cp_url']) ? $hospa_opt['hospa_disable_cp_url'] : '';

	if(	$remove_url_option == '1'):
		$posts = get_posts(array(
			'post_type' => 'doctors',
			'posts_per_page' => -1,
			'post_status' => 'publish',
		));

		foreach ($posts as $post) {
			$slug = $post->post_name;
			add_rewrite_rule(
				'^' . $slug . '/?$',
				'index.php?post_type=doctors&name=' . $slug,
				'top'
			);
		}
	endif;
}
add_action('init', 'custom_doctors_rewrite_rules');

function custom_doctors_post_type_link($post_link, $post) {
	global $hospa_opt;
	$remove_url_option       = !empty($hospa_opt['hospa_disable_cp_url']) ? $hospa_opt['hospa_disable_cp_url'] : '';

	if(	$remove_url_option == '1'):
		if ($post->post_type === 'doctors') {
			return home_url('/' . $post->post_name . '/');
		}
	endif;
	
	return $post_link;
}
add_filter('post_type_link', 'custom_doctors_post_type_link', 10, 2);

// 4. career
function custom_career_rewrite_rules() {

	global $hospa_opt;
	$remove_url_option       = !empty($hospa_opt['hospa_disable_cp_url']) ? $hospa_opt['hospa_disable_cp_url'] : '';

	if(	$remove_url_option == '1'):
		$posts = get_posts(array(
			'post_type' => 'career',
			'posts_per_page' => -1,
			'post_status' => 'publish',
		));

		foreach ($posts as $post) {
			$slug = $post->post_name;
			add_rewrite_rule(
				'^' . $slug . '/?$',
				'index.php?post_type=career&name=' . $slug,
				'top'
			);
		}
	endif;
}
add_action('init', 'custom_career_rewrite_rules');

function custom_career_post_type_link($post_link, $post) {
	global $hospa_opt;
	$remove_url_option       = !empty($hospa_opt['hospa_disable_cp_url']) ? $hospa_opt['hospa_disable_cp_url'] : '';

	if(	$remove_url_option == '1'):
		if ($post->post_type === 'career') {
			return home_url('/' . $post->post_name . '/');
		}
	endif;
	
	return $post_link;
}
add_filter('post_type_link', 'custom_career_post_type_link', 10, 2);

// 5. event
function custom_event_rewrite_rules() {

	global $hospa_opt;
	$remove_url_option       = !empty($hospa_opt['hospa_disable_cp_url']) ? $hospa_opt['hospa_disable_cp_url'] : '';

	if(	$remove_url_option == '1'):
		$posts = get_posts(array(
			'post_type' => 'event',
			'posts_per_page' => -1,
			'post_status' => 'publish',
		));

		foreach ($posts as $post) {
			$slug = $post->post_name;
			add_rewrite_rule(
				'^' . $slug . '/?$',
				'index.php?post_type=event&name=' . $slug,
				'top'
			);
		}
	endif;
}
add_action('init', 'custom_event_rewrite_rules');

function custom_event_post_type_link($post_link, $post) {
	global $hospa_opt;
	$remove_url_option       = !empty($hospa_opt['hospa_disable_cp_url']) ? $hospa_opt['hospa_disable_cp_url'] : '';

	if(	$remove_url_option == '1'):
		if ($post->post_type === 'event') {
			return home_url('/' . $post->post_name . '/');
		}
	endif;
	
	return $post_link;
}
add_filter('post_type_link', 'custom_event_post_type_link', 10, 2);
