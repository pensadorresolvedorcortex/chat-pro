<?php
/**
 * The current version of the theme.
 *
 * @package realhomes
 */
update_option( 'inspiry_realhomes_registered', 'yes' );
// Framework Path.
define( 'INSPIRY_FRAMEWORK', get_template_directory() . '/framework/' );

// Text domain for the theme.
define( 'RH_TEXT_DOMAIN', 'framework' );

// Default Design Variation
if ( ! defined( 'REALHOMES_DESIGN_VARIATION' ) ) {

	// Define the latest default design variation of Realhomes theme.
	define( 'REALHOMES_DESIGN_VARIATION', 'ultra' );

	/**
	 * Verify whether the user has saved their preference for the Classic, Modern or Ultra design; if not,
	 * set the latest design as the default.
	 */
	if ( ! in_array( get_option( 'inspiry_design_variation' ), array( 'classic', 'modern', 'ultra' ) ) ) {
		update_option( 'inspiry_design_variation', REALHOMES_DESIGN_VARIATION );
	}
}

// Design Variation
if ( ! defined( 'INSPIRY_DESIGN_VARIATION' ) ) {
	define( 'INSPIRY_DESIGN_VARIATION', get_option( 'inspiry_design_variation', REALHOMES_DESIGN_VARIATION ) );
}

// Theme assets.
if ( ! defined( 'INSPIRY_THEME_ASSETS' ) ) {
	define( 'INSPIRY_THEME_ASSETS', '/assets/' . INSPIRY_DESIGN_VARIATION );
}

// Theme directory.
if ( ! defined( 'INSPIRY_THEME_DIR' ) ) {
	define( 'INSPIRY_THEME_DIR', get_template_directory() . INSPIRY_THEME_ASSETS );
}

// Theme directory URI.
if ( ! defined( 'INSPIRY_DIR_URI' ) ) {
	define( 'INSPIRY_DIR_URI', get_template_directory_uri() . INSPIRY_THEME_ASSETS );
}

// Theme common directory.
if ( ! defined( 'INSPIRY_COMMON_DIR' ) ) {
	define( 'INSPIRY_COMMON_DIR', get_template_directory() . '/common/' );
}

// Theme common directory URI.
if ( ! defined( 'INSPIRY_COMMON_URI' ) ) {
	define( 'INSPIRY_COMMON_URI', get_template_directory_uri() . '/common/' );
}

/**
 * Disable the auto registration of the RH_TEXT_DOMAIN text domain and load the theme framework.
 */
realhomes_disable_textdomain_autoregistration();
require_once INSPIRY_FRAMEWORK . '/load.php';

/**
 * Disable the auto registration of the RH_TEXT_DOMAIN text domain.
 *
 * @since 4.4.0
 */
function realhomes_disable_textdomain_autoregistration() {
	if ( isset( $GLOBALS['wp_textdomain_registry'] ) ) {
		try {
			$registry   = $GLOBALS['wp_textdomain_registry'];
			$reflection = new ReflectionClass( $registry );
			if ( $reflection->hasProperty( 'custom_paths' ) ) {
				$prop = $reflection->getProperty( 'custom_paths' );
				$prop->setAccessible( true );
				$custom_paths = $prop->getValue( $registry );
				if ( isset( $custom_paths[ RH_TEXT_DOMAIN ] ) ) {
					unset( $custom_paths[ RH_TEXT_DOMAIN ] );
					$prop->setValue( $registry, $custom_paths );
				}
				$prop->setAccessible( false );
			}
		} catch ( Exception $e ) {
			// Optional: log or fail silently
			error_log( 'Textdomain registry reflection error: ' . $e->getMessage() );
		}
	}
}

if ( ! function_exists( 'inspiry_theme_setup' ) ) {
	/**
	 * 1. Load text domain
	 * 2. Add custom background support
	 * 3. Add automatic feed links support
	 * 4. Add specific post formats support
	 * 5. Add custom menu support and register a custom menu
	 * 6. Register required image sizes
	 * 7. Add title tag support
	 */
	function inspiry_theme_setup() {

		/**
		 * Load text domain for translation purposes
		 */
		$languages_dir = get_template_directory() . '/languages';
		load_theme_textdomain( RH_TEXT_DOMAIN, $languages_dir );

		// Set the default content width.
		$GLOBALS['content_width'] = 828;

		/**
		 * Add Theme Support - Custom background
		 */
		add_theme_support( 'custom-background' );

		/**
		 * Add Automatic Feed Links Support
		 */
		add_theme_support( 'automatic-feed-links' );

		/**
		 * Add Post Formats Support
		 */
		add_theme_support( 'post-formats', array( 'image', 'video', 'gallery' ) );

		/**
		 * Register custom menus
		 */
		$nav_menus = array(
			'main-menu'       => esc_html__( 'Main Menu', RH_TEXT_DOMAIN ),
			'responsive-menu' => esc_html__( 'Responsive Menu', RH_TEXT_DOMAIN ),
		);
		register_nav_menus( apply_filters( 'inspiry_nav_menus', $nav_menus ) );

		/**
		 * Add Post Thumbnails Support and Related Image Sizes
		 */
		add_theme_support( 'post-thumbnails' );
		set_post_thumbnail_size( 150, 150 );                                               // Default Post Thumbnail dimensions.
		add_image_size( 'modern-property-child-slider', 680, 510, true );       // Moved to common for Dashboard needs. For Gallery, Child Property, Property Card, Property Grid Card, Similar Property.
		add_image_size( 'property-thumb-image', 488, 326, true );               // For Home page posts thumbnails/Featured Properties carousels thumb.
		add_image_size( 'property-detail-video-image', 818, 417, true );        // For Property detail page video image.
		add_image_size( 'agent-image', 210, 210, true );                        // For Agent Picture.
		add_image_size( 'partners-logo', 600, 9999, true );                     // For partner carousel logos

		if ( 'modern' === INSPIRY_DESIGN_VARIATION || 'ultra' === INSPIRY_DESIGN_VARIATION ) {
			/**
			 * Modern Design Image Sizes
			 */
			add_image_size( 'post-featured-image', 1240, 720, true );               // For Blog featured image.
		} else if ( 'classic' === INSPIRY_DESIGN_VARIATION ) {
			/**
			 * Classic Design Image Sizes
			 */
			add_image_size( 'gallery-two-column-image', 536, 269, true );           // For Gallery Two Column property Thumbnails.
			add_image_size( 'property-detail-slider-image-two', 1170, 648, true );  // For Property detail page slider image.
			add_image_size( 'property-detail-slider-thumb', 82, 60, true );         // For Property detail page slider thumb.
		}

		add_theme_support( 'title-tag' );

		if ( realhomes_is_woocommerce_activated() ) {
			/**
			 * Registers support for various WooCommerce features.
			 *
			 * @since  3.13.0
			 */
			add_theme_support( 'woocommerce' );
			add_theme_support( 'wc-product-gallery-zoom' );
			add_theme_support( 'wc-product-gallery-lightbox' );
			add_theme_support( 'wc-product-gallery-slider' );

			/**
			 * Add 'realhomes_woocommerce_setup' action.
			 *
			 * @since  3.13.0
			 */
			do_action( 'realhomes_woocommerce_setup' );
		}

		/**
		 * Add theme support for selective refresh
		 * of widgets in customizer.
		 */
		add_theme_support( 'customize-selective-refresh-widgets' );

		// Add support for Block Styles.
		add_theme_support( 'wp-block-styles' );

		// Add support for full and wide align images.
		add_theme_support( 'align-wide' );

		// Add support for editor styles.
		add_theme_support( 'editor-styles' );

		if ( class_exists( 'Header_Footer_Elementor' ) ) {
			add_theme_support( 'header-footer-elementor' );
		}

		// Add custom editor font sizes.
		add_theme_support( 'editor-font-sizes', array(
			array(
				'name'      => esc_html__( 'Small', RH_TEXT_DOMAIN ),
				'shortName' => esc_html__( 'S', RH_TEXT_DOMAIN ),
				'size'      => 14,
				'slug'      => 'small',
			),
			array(
				'name'      => esc_html__( 'Normal', RH_TEXT_DOMAIN ),
				'shortName' => esc_html__( 'M', RH_TEXT_DOMAIN ),
				'size'      => 16,
				'slug'      => 'normal',
			),
			array(
				'name'      => esc_html__( 'Large', RH_TEXT_DOMAIN ),
				'shortName' => esc_html__( 'L', RH_TEXT_DOMAIN ),
				'size'      => 28,
				'slug'      => 'large',
			),
			array(
				'name'      => esc_html__( 'Huge', RH_TEXT_DOMAIN ),
				'shortName' => esc_html__( 'XL', RH_TEXT_DOMAIN ),
				'size'      => 36,
				'slug'      => 'huge',
			),
		) );

		$editor_color_palette = array(
			array(
				'name'  => esc_html__( 'Primary', RH_TEXT_DOMAIN ),
				'slug'  => 'primary',
				'color' => '#ec894d',
			),
			array(
				'name'  => esc_html__( 'Orange Dark', RH_TEXT_DOMAIN ),
				'slug'  => 'orange-dark',
				'color' => '#dc7d44',
			),
			array(
				'name'  => esc_html__( 'Secondary', RH_TEXT_DOMAIN ),
				'slug'  => 'secondary',
				'color' => '#4dc7ec',
			),
			array(
				'name'  => esc_html__( 'Blue Dark', RH_TEXT_DOMAIN ),
				'slug'  => 'blue-dark',
				'color' => '#37b3d9',
			),
		);

		if ( 'modern' === INSPIRY_DESIGN_VARIATION ) {
			$editor_color_palette = array(
				array(
					'name'  => esc_html__( 'Primary', RH_TEXT_DOMAIN ),
					'slug'  => 'primary',
					'color' => '#ea723d',
				),
				array(
					'name'  => esc_html__( 'Orange Dark', RH_TEXT_DOMAIN ),
					'slug'  => 'orange-dark',
					'color' => '#e0652e',
				),
				array(
					'name'  => esc_html__( 'Secondary', RH_TEXT_DOMAIN ),
					'slug'  => 'secondary',
					'color' => '#1ea69a',
				),
				array(
					'name'  => esc_html__( 'Green Dark', RH_TEXT_DOMAIN ),
					'slug'  => 'blue-dark',
					'color' => '#0b8278',
				),
			);
		} else if ( 'ultra' === INSPIRY_DESIGN_VARIATION ) {
			$editor_color_palette = array(
				array(
					'name'  => esc_html__( 'Primary', RH_TEXT_DOMAIN ),
					'slug'  => 'primary',
					'color' => '#1db2ff',
				),
				array(
					'name'  => esc_html__( 'Primary Light', RH_TEXT_DOMAIN ),
					'slug'  => 'primary-light',
					'color' => '#e7f6fd',
				),
				array(
					'name'  => esc_html__( 'Primary Dark', RH_TEXT_DOMAIN ),
					'slug'  => 'primary-dark',
					'color' => '#dbf0fa',
				),
				array(
					'name'  => esc_html__( 'Secondary', RH_TEXT_DOMAIN ),
					'slug'  => 'secondary',
					'color' => '#f58220',
				),
			);
		}

		$editor_color_palette[] = array(
			'name'  => esc_html__( 'Black', RH_TEXT_DOMAIN ),
			'slug'  => 'black',
			'color' => '#394041',
		);

		$editor_color_palette[] = array(
			'name'  => esc_html__( 'White', RH_TEXT_DOMAIN ),
			'slug'  => 'white',
			'color' => '#fff',
		);

		// Editor color palette.
		add_theme_support( 'editor-color-palette', $editor_color_palette );

		// Add support for responsive embedded content.
		add_theme_support( 'responsive-embeds' );

		// Disables the Widget Block Editor
		remove_theme_support( 'widgets-block-editor' );

		global $pagenow;
		if ( is_admin() && 'themes.php' == $pagenow && isset( $_GET['activated'] ) ) {
			wp_redirect( admin_url( "admin.php?page=realhomes-design" ) );
		}
	}

	add_action( 'after_setup_theme', 'inspiry_theme_setup' );
}

if ( ! function_exists( 'inspiry_content_width' ) ) {
	/**
	 * Set the content width in pixels, based on the theme's design and stylesheet.
	 *
	 * Priority 0 to make it available to lower priority callbacks.
	 *
	 * @global int $content_width
	 */
	function inspiry_content_width() {

		$content_width = $GLOBALS['content_width'];

		if ( 'modern' === INSPIRY_DESIGN_VARIATION ) {
			if ( is_page_template( 'templates/full-width.php' ) ) {
				$content_width = 1140;
			} else if ( is_singular( 'property' ) || is_singular( 'agent' ) || is_singular( 'agency' ) ) {
				$content_width = 778;
			} else if ( is_singular( 'post' ) || is_page() ) {
				$content_width = 738;
			}
		} else {
			if ( is_page_template( 'templates/full-width.php' ) ) {
				$content_width = 1128;
			} else if ( is_singular( 'agent' ) || is_singular( 'agency' ) ) {
				$content_width = 578;
			} else if ( is_singular( 'post' ) ) {
				$content_width = 708;
			}
		}

		/**
		 * Filter RealHomes content width of the theme.
		 *
		 * @since RealHomes 3.6.1
		 *
		 * @param int $content_width Content width in pixels.
		 *
		 */
		$GLOBALS['content_width'] = apply_filters( 'inspiry_content_width', $content_width );
	}

	add_action( 'template_redirect', 'inspiry_content_width', 0 );
}

if ( ! function_exists( 'inspiry_add_editor_style' ) ) :
	/**
	 * Add editor styles and fonts
	 */
	function inspiry_add_editor_style() {

		wp_enqueue_style(
			'rh-font-awesome',
			get_theme_file_uri( 'common/font-awesome/css/all.min.css' ),
			array(),
			'5.13.1',
			'all'
		);

		wp_enqueue_style(
			'inspiry-google-fonts',
			inspiry_google_fonts(),
			array(),
			INSPIRY_THEME_VERSION
		);

		wp_enqueue_style(
			'inspiry-gutenberg-editor-style',
			get_theme_file_uri( INSPIRY_THEME_ASSETS . '/styles/css/editor-style.css' ),
			array(),
			INSPIRY_THEME_VERSION
		);
	}

	add_action( 'enqueue_block_editor_assets', 'inspiry_add_editor_style' );
endif;

if ( ! function_exists( 'inspiry_safe_include_svg' ) ) {
	/**
	 * Includes svg file in the theme.
	 *
	 * @since 3.10.2
	 *
	 * @param string $path
	 *
	 * @param string $file
	 */
	function inspiry_safe_include_svg( $file, $path = INSPIRY_THEME_ASSETS ) {
		$file = get_theme_file_path( $path . $file );
		if ( file_exists( $file ) ) {
			include( $file );
		}
	}
}



if ( ! function_exists( 'inspiry_theme_sidebars' ) ) {
	/**
	 * Sidebars, Footer and other Widget areas
	 */
	function inspiry_theme_sidebars() {

		// Location: Default Sidebar.
		register_sidebar( array(
			'name'          => esc_html__( 'Default Sidebar', RH_TEXT_DOMAIN ),
			'id'            => 'default-sidebar',
			'description'   => esc_html__( 'Widget area for default sidebar on news and post pages.', RH_TEXT_DOMAIN ),
			'before_widget' => '<section id="%1$s" class="widget clearfix %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="title">',
			'after_title'   => '</h3>',
		) );

		// Location: Sidebar Pages.
		register_sidebar( array(
			'name'          => esc_html__( 'Pages Sidebar', RH_TEXT_DOMAIN ),
			'id'            => 'default-page-sidebar',
			'description'   => esc_html__( 'Widget area for default page template sidebar.', RH_TEXT_DOMAIN ),
			'before_widget' => '<section id="%1$s" class="widget clearfix %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="title">',
			'after_title'   => '</h3>',
		) );

		// Location: Sidebar for contact page.
		if ( 'classic' === INSPIRY_DESIGN_VARIATION ) {
			register_sidebar( array(
				'name'          => esc_html__( 'Contact Sidebar', RH_TEXT_DOMAIN ),
				'id'            => 'contact-sidebar',
				'description'   => esc_html__( 'Widget area for contact page sidebar.', RH_TEXT_DOMAIN ),
				'before_widget' => '<section class="widget clearfix %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3 class="title">',
				'after_title'   => '</h3>',
			) );
		}

		// Location: Sidebar Property.
		register_sidebar( array(
			'name'          => esc_html__( 'Property Sidebar', RH_TEXT_DOMAIN ),
			'id'            => 'property-sidebar',
			'description'   => esc_html__( 'Widget area for property detail page sidebar.', RH_TEXT_DOMAIN ),
			'before_widget' => '<section id="%1$s" class="widget clearfix %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="title">',
			'after_title'   => '</h3>',
		) );

		// Location: Sidebar Properties List.
		register_sidebar( array(
			'name'          => esc_html__( 'Properties Pages Sidebar', RH_TEXT_DOMAIN ),
			'id'            => 'property-listing-sidebar',
			'description'   => esc_html__( 'Widget area for sidebar in properties list, grid and archive pages.', RH_TEXT_DOMAIN ),
			'before_widget' => '<section id="%1$s" class="widget clearfix %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="title">',
			'after_title'   => '</h3>',
		) );

		// Location: Footer First Column.
		register_sidebar( array(
			'name'          => esc_html__( 'Footer First Column', RH_TEXT_DOMAIN ),
			'id'            => 'footer-first-column',
			'description'   => esc_html__( 'Widget area for first column in footer.', RH_TEXT_DOMAIN ),
			'before_widget' => '<section id="%1$s" class="widget clearfix %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="title">',
			'after_title'   => '</h3>',
		) );

		// Location: Footer Second Column.
		register_sidebar( array(
			'name'          => esc_html__( 'Footer Second Column', RH_TEXT_DOMAIN ),
			'id'            => 'footer-second-column',
			'description'   => esc_html__( 'Widget area for second column in footer.', RH_TEXT_DOMAIN ),
			'before_widget' => '<section id="%1$s" class="widget clearfix %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="title">',
			'after_title'   => '</h3>',
		) );

		// Location: Footer Third Column.
		register_sidebar( array(
			'name'          => esc_html__( 'Footer Third Column', RH_TEXT_DOMAIN ),
			'id'            => 'footer-third-column',
			'description'   => esc_html__( 'Widget area for third column in footer.', RH_TEXT_DOMAIN ),
			'before_widget' => '<section id="%1$s" class="widget clearfix %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="title">',
			'after_title'   => '</h3>',
		) );

		// Location: Footer Fourth Column.
		register_sidebar( array(
			'name'          => esc_html__( 'Footer Fourth Column', RH_TEXT_DOMAIN ),
			'id'            => 'footer-fourth-column',
			'description'   => esc_html__( 'Widget area for fourth column in footer.', RH_TEXT_DOMAIN ),
			'before_widget' => '<section id="%1$s" class="widget clearfix %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="title">',
			'after_title'   => '</h3>',
		) );

		if ( post_type_exists( 'agent' ) ) {
			// Location: Sidebar Agent.
			register_sidebar( array(
				'name'          => esc_html__( 'Agent Sidebar', RH_TEXT_DOMAIN ),
				'id'            => 'agent-sidebar',
				'description'   => esc_html__( 'Sidebar widget area for agent detail page.', RH_TEXT_DOMAIN ),
				'before_widget' => '<section id="%1$s" class="widget clearfix %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3 class="title">',
				'after_title'   => '</h3>',
			) );
		}

		if ( post_type_exists( 'agency' ) ) {
			// Location: Sidebar Agency.
			register_sidebar( array(
				'name'          => esc_html__( 'Agency Sidebar', RH_TEXT_DOMAIN ),
				'id'            => 'agency-sidebar',
				'description'   => esc_html__( 'Sidebar widget area for agency detail page.', RH_TEXT_DOMAIN ),
				'before_widget' => '<section id="%1$s" class="widget clearfix %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3 class="title">',
				'after_title'   => '</h3>',
			) );
		}

		// Location: Property Search Template.
		register_sidebar( array(
			'name'          => esc_html__( 'Property Search Sidebar', RH_TEXT_DOMAIN ),
			'id'            => 'property-search-sidebar',
			'description'   => esc_html__( 'Widget area for property search template with sidebar.', RH_TEXT_DOMAIN ),
			'before_widget' => '<section id="%1$s" class="widget clearfix %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="title">',
			'after_title'   => '</h3>',
		) );

		if ( 'classic' !== INSPIRY_DESIGN_VARIATION ) {
			// Location: Property Search Template.
			register_sidebar( array(
				'name'          => esc_html__( '404 Page Widgets', RH_TEXT_DOMAIN ),
				'id'            => '404-sidebar',
				'description'   => esc_html__( 'Widget area for 404 template content area.', RH_TEXT_DOMAIN ),
				'before_widget' => '<section id="%1$s" class="widget clearfix %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3 class="title">',
				'after_title'   => '</h3>',
			) );
		}

		// Create additional sidebar to use with visual composer if needed.
		if ( class_exists( 'Vc_Manager' ) ) {

			// Additional Sidebars.
			register_sidebars( 4, array(
				'name'          => esc_html__( 'Additional Sidebar %d', RH_TEXT_DOMAIN ),
				'description'   => esc_html__( 'An extra sidebar to use with Visual Composer if needed.', RH_TEXT_DOMAIN ),
				'before_widget' => '<section id="%1$s" class="widget clearfix %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3 class="title">',
				'after_title'   => '</h3>',
			) );

		}

		// Create additional sidebar to use with Optima Express if needed.
		if ( class_exists( 'iHomefinderAdmin' ) ) {

			// Location: Home Search Area.
			register_sidebar( array(
				'name'          => esc_html__( 'Home Search Area', RH_TEXT_DOMAIN ),
				'id'            => 'home-search-area',
				'description'   => esc_html__( 'Widget area for only IDX Search Widget. Using this area means you want to display IDX search form instead of default search form.', RH_TEXT_DOMAIN ),
				'before_widget' => '<section id="home-idx-search" class="clearfix %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3 class="home-widget-label">',
				'after_title'   => '</h3>',
			) );

			// Additional Sidebars.
			register_sidebar( array(
				'name'          => esc_html__( 'Optima Express Sidebar', RH_TEXT_DOMAIN ),
				'id'            => 'optima-express-page-sidebar',
				'description'   => esc_html__( 'An extra sidebar to use with Optima Express if needed.', RH_TEXT_DOMAIN ),
				'before_widget' => '<section id="%1$s" class="widget clearfix %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3 class="title">',
				'after_title'   => '</h3>',
			) );
		}

		// Creates additional sidebar to use with WooCommerce page if needed.
		if ( realhomes_is_woocommerce_activated() ) {

			// Shop Sidebar.
			register_sidebar( array(
				'name'          => esc_html__( 'Shop Sidebar', RH_TEXT_DOMAIN ),
				'id'            => 'shop-page-sidebar',
				'description'   => esc_html__( 'An extra sidebar to use with WooCommerce pages if needed.', RH_TEXT_DOMAIN ),
				'before_widget' => '<section id="%1$s" class="widget clearfix %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3 class="title">',
				'after_title'   => '</h3>',
			) );
		}

	}

	add_action( 'widgets_init', 'inspiry_theme_sidebars' );
}

if ( ! function_exists( 'inspiry_google_fonts' ) ) {
	/**
	 * Google fonts enqueue url
	 */
	function inspiry_google_fonts() {

		$font_families = array();
		$fonts_url     = '';
		$subsets       = 'latin,latin-ext';

		// Body Font
		$body_font = get_option( 'inspiry_body_font', 'Default' );
		if ( 'Default' !== $body_font ) {
			$font_families[] = $body_font . ':' . Inspiry_Google_Fonts::get_font_weights( $body_font, false, true );
		} else {
			// Open Sans is theme's default text font.
			$font_families[] = 'Open+Sans:400,400i,600,600i,700,700i';
		}

		// Heading Font
		$heading_font = get_option( 'inspiry_heading_font', 'Default' );
		if ( 'Default' !== $heading_font ) {
			$font_families[] = $heading_font . ':' . Inspiry_Google_Fonts::get_font_weights( $heading_font, false, true );
		} else {
			// Lato is theme's default heading font.
			$font_families[] = 'Lato:400,400i,700,700i';
		}

		// Secondary Font
		$secondary_font = get_option( 'inspiry_secondary_font', 'Default' );
		if ( 'Default' !== $secondary_font ) {
			$font_families[] = $secondary_font . ':' . Inspiry_Google_Fonts::get_font_weights( $secondary_font, false, true );
		} else {
			// Robot is theme's default secondary font.
			$font_families[] = 'Roboto:400,400i,500,500i,700,700i';
		}

		/*
         * This font is used on dashboard membership order completion page and will be included only on dashboard template.
         *
         * Translators: If there are characters in your language that are not
         * supported by Damion, translate this to 'off'. Do not translate into your own language.
         */
		if ( 'off' !== _x( 'on', 'Damion font: on or off', RH_TEXT_DOMAIN ) && is_page_template( 'templates/dashboard.php' ) ) {
			$font_families[] = 'Damion';
		}

		if ( ( 'modern' === INSPIRY_DESIGN_VARIATION ) || is_page_template( 'templates/dashboard.php' ) ) {
			/*
			 * Translators: If there are characters in your language that are not
			 * supported by Rubik, translate this to 'off'. Do not translate into your own language.
			 */
			if ( 'off' !== _x( 'on', 'Rubik font: on or off', RH_TEXT_DOMAIN ) ) {
				$font_families[] = 'Rubik:400,400i,500,500i,700,700i';
			}
		}

		if ( 'ultra' === INSPIRY_DESIGN_VARIATION ) {
			/*
	 * Translators: If there are characters in your language that are not
	 * supported by DM Sans, translate this to 'off'. Do not translate into your own language.
	 */
			if ( 'off' !== _x( 'on', 'DM Sans font: on or off', RH_TEXT_DOMAIN ) ) {
				$font_families[] = 'DM Sans:400,400i,500,500i,700,700i';
			}
		}

		if ( ! empty( $font_families ) ) {
			$query_args = array(
				'family'  => implode( '|', array_unique( $font_families ) ),
				'subset'  => urlencode( $subsets ),
				'display' => urlencode( 'fallback' ),
			);

			$fonts_url = add_query_arg( $query_args, '//fonts.googleapis.com/css' );
		}

		return esc_url_raw( $fonts_url );
	}
}

if ( ! function_exists( 'inspiry_update_page_templates' ) ) {

	/**
	 * Function to update page templates.
	 *
	 * @since 3.0.0
	 */
	function inspiry_update_page_templates() {

		if ( ! is_page_template() ) {
			return;
		}

		$page_id = get_queried_object_id();
		if ( ! empty( $page_id ) ) {
			$page_template = get_post_meta( $page_id, '_wp_page_template', true );
		}

		if ( empty( $page_template ) ) {
			return;
		}

		$latest_templates = array(
			// Updated properties list & grid templates
			'templates/list-layout.php'                     => 'templates/properties.php',
			'templates/list-layout-full-width.php'          => 'templates/properties.php',
			'templates/grid-layout.php'                     => 'templates/properties.php',
			'templates/grid-layout-full-width.php'          => 'templates/properties.php',
			'templates/half-map-layout.php'                 => 'templates/properties.php',
			'template-property-listing.php'                 => 'templates/properties.php',
			'templates/template-property-listing.php'       => 'templates/properties.php',
			'template-property-grid-listing.php'            => 'templates/properties.php',
			'templates/template-property-grid-listing.php'  => 'templates/properties.php',
			'template-map-based-listing.php'                => 'templates/properties.php',
			'templates/template-map-based-listing.php'      => 'templates/properties.php',

			// Updated Full and Fluid width templates
			'template-fullwidth.php'                        => '',
			'templates/template-fullwidth.php'              => '',
			'templates/full-width.php'                      => '',
			'templates/fluid-width.php'                     => '',

			// Gallery template
			'templates/2-columns-gallery.php'               => 'templates/properties-gallery.php',
			'templates/3-columns-gallery.php'               => 'templates/properties-gallery.php',
			'templates/4-columns-gallery.php'               => 'templates/properties-gallery.php',

			// Updated agents list template
			'template-agent-listing.php'                    => 'templates/agents-list.php',
			'templates/template-agent-listing.php'          => 'templates/agents-list.php',

			// Updated compare properties template
			'template-compare.php'                          => 'templates/compare-properties.php',
			'templates/template-compare.php'                => 'templates/compare-properties.php',

			// Updated contact template
			'template-contact.php'                          => 'templates/contact.php',
			'templates/template-contact.php'                => 'templates/contact.php',

			// Updated home template
			'template-home.php'                             => 'templates/home.php',
			'templates/template-home.php'                   => 'templates/home.php',

			// Updated login template
			'template-login.php'                            => 'templates/login-register.php',
			'templates/template-login.php'                  => 'templates/login-register.php',

			// Updated optima express template
			'template-optima-express.php'                   => 'templates/optima-express.php',
			'templates/template-optima-express.php'         => 'templates/optima-express.php',

			// Updated search template
			'template-search.php'                           => 'templates/properties-search.php',
			'templates/template-search.php'                 => 'templates/properties-search.php',
			'templates/properties-search-half-map.php'      => 'templates/properties-search.php',

			// Updated search template with right sidebar
			'templates/properties-search-right-sidebar.php' => 'templates/properties-search.php',
			'template-search-right-sidebar.php'             => 'templates/properties-search.php',
			'templates/template-search-right-sidebar.php'   => 'templates/properties-search.php',

			// Updated search template with left sidebar
			'templates/properties-search-left-sidebar.php'  => 'templates/properties-search.php',
			'template-search-sidebar.php'                   => 'templates/properties-search.php',
			'templates/template-search-sidebar.php'         => 'templates/properties-search.php',

			// Updated users list template
			'template-users-listing.php'                    => 'templates/users-lists.php',
			'templates/template-users-listing.php'          => 'templates/users-lists.php',
		);

		if ( ! empty( $page_template ) && array_key_exists( $page_template, $latest_templates ) ) {

			if ( in_array( $page_template, array( 'template-fullwidth.php', 'templates/template-fullwidth.php', 'templates/full-width.php', ) ) ) {
				update_post_meta( $page_id, 'realhomes_page_layout', 'fullwidth' );

			} else if ( 'templates/fluid-width.php' === $page_template ) {
				update_post_meta( $page_id, 'realhomes_page_layout', 'fluid_width' );

			} else if ( in_array( $page_template, array( 'templates/list-layout.php', 'templates/list-layout-full-width.php', 'templates/grid-layout.php', 'templates/grid-layout-full-width.php', 'templates/half-map-layout.php' ) ) ) {

				if ( in_array( $page_template, array( 'templates/list-layout.php', 'templates/list-layout-full-width.php', 'templates/half-map-layout.php' ) ) ) {
					update_post_meta( $page_id, 'realhomes_property_card', 'list' );

					if ( 'templates/half-map-layout.php' === $page_template ) {
						update_post_meta( $page_id, 'realhomes_property_half_map', '1' );

					} else if ( 'templates/list-layout-full-width.php' === $page_template ) {
						update_post_meta( $page_id, 'realhomes_page_layout', 'fullwidth' );
					}

				} else if ( in_array( $page_template, array( 'templates/grid-layout.php', 'templates/grid-layout-full-width.php' ) ) ) {
					update_post_meta( $page_id, 'realhomes_property_card', 'grid' );

					if ( 'templates/grid-layout-full-width.php' === $page_template ) {
						update_post_meta( $page_id, 'realhomes_page_layout', 'fullwidth' );
						update_post_meta( $page_id, 'realhomes_properties_grid_fullwidth_column', '3' );
					}
				}

			} else if ( in_array( $page_template, array(
				'templates/properties-search-half-map.php',
				'templates/properties-search-right-sidebar.php',
				'template-search-right-sidebar.php',
				'templates/template-search-right-sidebar.php',
				'templates/properties-search-left-sidebar.php',
				'template-search-sidebar.php',
				'templates/template-search-sidebar.php'
			) ) ) {

				if ( in_array( $page_template, array( 'templates/properties-search-right-sidebar.php', 'template-search-right-sidebar.php', 'templates/template-search-right-sidebar.php' ) ) ) {
					update_post_meta( $page_id, 'realhomes_page_layout', 'sidebar_right' );

				} else if ( in_array( $page_template, array( 'templates/properties-search-left-sidebar.php', 'template-search-sidebar.php', 'templates/template-search-sidebar.php' ) ) ) {
					update_post_meta( $page_id, 'realhomes_page_layout', 'sidebar_left' );

				} else if ( in_array( $page_template, array( 'templates/properties-search-half-map.php' ) ) ) {
					update_post_meta( $page_id, 'realhomes_property_half_map', '1' );
				}

			} else if ( in_array( $page_template, array( 'templates/2-columns-gallery.php', 'templates/3-columns-gallery.php', 'templates/4-columns-gallery.php' ) ) ) {
				update_post_meta( $page_id, 'realhomes_properties_gallery_column', preg_replace( '/[^0-9]/', '', $page_template ) );
			}

		}

		if ( ! empty( $page_template ) && array_key_exists( $page_template, $latest_templates ) && ! defined( 'DSIDXPRESS_PLUGIN_VERSION' ) ) {
			$updated_template = $latest_templates[ $page_template ];

			update_post_meta( $page_id, '_wp_page_template', $updated_template );
			echo '<meta HTTP-EQUIV="Refresh" CONTENT="1">';

		} else if ( ! empty( $page_template ) && false !== strpos( $page_template, 'template-' ) && false === strpos( $page_template, 'templates/' ) && ! defined( 'DSIDXPRESS_PLUGIN_VERSION' ) ) {
			update_post_meta( $page_id, '_wp_page_template', 'templates/' . $page_template );
			echo '<meta HTTP-EQUIV="Refresh" CONTENT="1">';
		}
	}

	add_action( 'wp_head', 'inspiry_update_page_templates' );
}

// Enable shortcodes in text widgets.
add_filter( 'widget_text', 'do_shortcode' );

if ( ! function_exists( 'inspiry_header_variation_body_classes' ) ) {
	/**
	 * Header variation body classes.
	 */
	function inspiry_header_variation_body_classes( $classes ) {
		$get_header_variations = apply_filters( 'inspiry_header_variation', get_option( 'inspiry_header_mod_variation_option', 'one' ) );
		$class_name            = 'inspiry_mod_header_variation_' . $get_header_variations;

		if ( inspiry_show_header_search_form() ) {
			$class_name .= ' inspiry_header_search_form_enabled';
		}

		if ( 'search-form-over-image' == get_post_meta( get_the_ID(), 'theme_homepage_module', true ) ) {
			$class_name .= ' inspiry_search_form_over_image_enabled';
		}

		$classes[] = $class_name;

		return $classes;
	}

	if ( 'modern' === INSPIRY_DESIGN_VARIATION ) {
		add_filter( 'body_class', 'inspiry_header_variation_body_classes' );
	}
}

if ( ! function_exists( 'inspiry_search_form_variation_body_classes' ) ) {
	/**
	 * Search form variation body classes.
	 */
	function inspiry_search_form_variation_body_classes( $classes ) {
		$get_header_variations = get_option( 'inspiry_search_form_mod_layout_options', 'default' );
		$get_header_location   = get_option( 'inspiry_show_search_in_header', '1' );

		if ( is_home() ) {
			$page_id = get_queried_object_id();
		} else {
			$page_id = get_the_ID();
		}

		$REAL_HOMES_hide_advance_search = get_post_meta( $page_id, 'REAL_HOMES_hide_advance_search', true );

		if ( '0' === $get_header_location || '1' === $REAL_HOMES_hide_advance_search ) {
			$classes[] = 'inspriry_search_form_hidden_in_header';
		}

		$class_name = 'inspiry_mod_search_form_' . $get_header_variations;
		$classes[]  = $class_name;

		return $classes;
	}

	if ( 'modern' === INSPIRY_DESIGN_VARIATION ) {
		add_filter( 'body_class', 'inspiry_search_form_variation_body_classes' );
	}
}

if ( ! function_exists( 'inspiry_add_meta_based_class' ) ) {
	function inspiry_add_meta_based_class( $class ) {
		if ( 'modern' === INSPIRY_DESIGN_VARIATION ) {
			$REAL_HOMES_page_top_bottom_padding_nil = get_post_meta( get_the_ID(), 'REAL_HOMES_page_top_bottom_padding_nil', true );
			if ( $REAL_HOMES_page_top_bottom_padding_nil == 1 ) {
				$class[] = 'REAL_HOMES_page_top_bottom_padding_nil';
			}
		}
		$REAL_HOMES_content_area_padding_nil = get_post_meta( get_the_ID(), 'REAL_HOMES_content_area_padding_nil', true );
		if ( $REAL_HOMES_content_area_padding_nil == 1 ) {
			$class[] = 'REAL_HOMES_content_area_padding_nil';
		}

		return $class;
	}

	add_filter( 'body_class', 'inspiry_add_meta_based_class' );

}

if ( ! function_exists( 'inspiry_floating_bar_class' ) ) {
	/**
	 * Search form variation body classes.
	 */
	function inspiry_floating_bar_class( $classes ) {
		$inspiry_default_floating_bar_display = get_theme_mod( 'inspiry_default_floating_bar_display', 'show' );
		if ( 'show' == $inspiry_default_floating_bar_display ) {
			$class_name = 'inspiry_body_floating_features_show';
		} else {
			$class_name = 'inspiry_body_floating_features_hide';
		}

		$classes[] = $class_name;

		return $classes;
	}

	add_filter( 'body_class', 'inspiry_floating_bar_class' );
}

if ( ! function_exists( 'inspiry_elementor_styles' ) ) {
	/**
	 * enqueue Elementor styles.
	 */
	function inspiry_elementor_styles() {
		wp_enqueue_style(
			'inspiry-elementor-style',
			get_theme_file_uri( 'common/css/elementor-styles.min.css' ),
			array(),
			INSPIRY_THEME_VERSION
		);
	}

	add_action( 'elementor/frontend/after_enqueue_styles', 'inspiry_elementor_styles' );
}

if ( function_exists( 'realhomes_currency_switcher_enabled' ) && realhomes_currency_switcher_enabled() ) {

	if ( ! function_exists( 'inspiry_currency_switcher_flags' ) ) {
		/**
		 * Enqueue currency switcher flags css.
		 */
		function inspiry_currency_switcher_flags() {
			wp_enqueue_style(
				'inspiry-currency-flags',
				get_theme_file_uri( 'common/css/currency-flags.min.css' ),
				array(),
				INSPIRY_THEME_VERSION
			);
		}

		add_action( 'wp_enqueue_scripts', 'inspiry_currency_switcher_flags' );
	}
}

if ( ! function_exists( 'inspiry_frontend_styles' ) ) {
	/**
	 * enqueue Elementor styles.
	 */
	function inspiry_frontend_styles() {
		wp_enqueue_style(
			'inspiry-frontend-style',
			get_theme_file_uri( 'common/css/frontend-styles.min.css' ),
			array(),
			INSPIRY_THEME_VERSION
		);
	}

	add_action( 'wp_enqueue_scripts', 'inspiry_frontend_styles' );
}

if ( ! function_exists( 'inspiry_sanitize_field' ) ) {
	function inspiry_sanitize_field( $str ) {
		/**
		 * Filters a sanitized textarea field string.
		 */

		$allowed_html = array(
			'a'      => array(
				'href'   => array(),
				'target' => array(),
			),
			'br'     => array(),
			'strong' => array(),
			'i'      => array(),
			'em'     => array(),
		);

		$str = wp_kses( $str, $allowed_html );

		return apply_filters( 'inspiry_sanitize_field', $str );
	}
}

if ( ! function_exists( 'inspiry_kses' ) ) {
	function inspiry_kses( $str ) {
		/**
		 * Filters content and keeps only allowable HTML elements.
		 */
		$allowed_html = array(
			'a'      => array(
				'href'   => array(),
				'target' => array(),
			),
			'br'     => array(),
			'strong' => array(),
			'i'      => array(),
			'em'     => array(),
		);

		$str = wp_kses( $str, $allowed_html );

		return apply_filters( 'inspiry_kses', $str );
	}
}

// Register Theme Locations For Elementor Templates
if ( ! function_exists( 'inspiry_register_elementor_locations' ) ) {

	function inspiry_register_elementor_locations( $elementor_theme_manager ) {

		$elementor_theme_manager->register_location( 'header' );
		$elementor_theme_manager->register_location( 'footer' );
		$elementor_theme_manager->register_location( 'single' );
		$elementor_theme_manager->register_location( 'archive' );
		$elementor_theme_manager->register_location(
			'single-property',
			[
				'label'           => esc_html__( 'Single Property', RH_TEXT_DOMAIN ),
				'multiple'        => true,
				'edit_in_content' => true,
			]
		);
		$elementor_theme_manager->register_location(
			'single-property-fullwidth',
			[
				'label'           => esc_html__( 'Single Property Fullwidth', RH_TEXT_DOMAIN ),
				'multiple'        => true,
				'edit_in_content' => true,
			]
		);
		$elementor_theme_manager->register_location(
			'single-agent',
			[
				'label'           => esc_html__( 'Single Agent', RH_TEXT_DOMAIN ),
				'multiple'        => true,
				'edit_in_content' => true,
			]
		);
		$elementor_theme_manager->register_location(
			'single-agency',
			[
				'label'           => esc_html__( 'Single Agency', RH_TEXT_DOMAIN ),
				'multiple'        => true,
				'edit_in_content' => true,
			]
		);
		$elementor_theme_manager->register_location(
			'properties-listings-grid',
			[
				'label'           => esc_html__( 'Properties Grid Layout', RH_TEXT_DOMAIN ),
				'multiple'        => true,
				'edit_in_content' => true,
			]
		);
		$elementor_theme_manager->register_location(
			'properties-listings-grid-fullwidth',
			[
				'label'           => esc_html__( 'Properties Grid Layout Fullwidth', RH_TEXT_DOMAIN ),
				'multiple'        => true,
				'edit_in_content' => true,
			]
		);
		$elementor_theme_manager->register_location(
			'properties-listings-list',
			[
				'label'           => esc_html__( 'Properties List Layout', RH_TEXT_DOMAIN ),
				'multiple'        => true,
				'edit_in_content' => true,
			]
		);
		$elementor_theme_manager->register_location(
			'properties-listings-list-fullwidth',
			[
				'label'           => esc_html__( 'Properties List Layout Fullwidth', RH_TEXT_DOMAIN ),
				'multiple'        => true,
				'edit_in_content' => true,
			]
		);
		$elementor_theme_manager->register_location(
			'agents-listings',
			[
				'label'           => esc_html__( 'Agents Listings', RH_TEXT_DOMAIN ),
				'multiple'        => true,
				'edit_in_content' => true,
			]
		);
		$elementor_theme_manager->register_location(
			'agency-listings',
			[
				'label'           => esc_html__( 'Agency Listings', RH_TEXT_DOMAIN ),
				'multiple'        => true,
				'edit_in_content' => true,
			]
		);
	}

	add_action( 'elementor/theme/register_locations', 'inspiry_register_elementor_locations' );
}

if ( ! function_exists( 'inspiry_post_classes' ) ) {

	function inspiry_post_classes( $classes ) {

		if ( 'modern' === INSPIRY_DESIGN_VARIATION ) {

			if ( is_home() || is_archive() || is_search() || is_singular( 'post' ) ) {
				$classes[] = 'rh_blog__post';

			} else if ( is_page() ) {
				$classes[] = 'rh_blog__post';

				if ( 'hide' != get_post_meta( get_the_ID(), 'REAL_HOMES_page_title_display', true ) ) {
					$classes[] = 'entry-header-margin-fix';
				}
			}

		} else {
			if ( is_page_template( 'templates/optima-express.php' ) ) {
				$classes[] = 'optima-express clearfix';
			} else if ( is_page() ) {
				$classes[] = 'clearfix';
			}
		}

		return $classes;
	}

	add_filter( 'post_class', 'inspiry_post_classes' );
}

if ( ! function_exists( 'realhomes_content_width' ) ) {
	/**
	 * Adds css class to body when related sidebar is not active
	 */
	function realhomes_content_width( $classes ) {

		if ( 'classic' === INSPIRY_DESIGN_VARIATION ) {
			if ( ! is_active_sidebar( 'contact-sidebar' ) && is_page_template( 'templates/contact.php' ) ) {
				$classes[] = 'realhomes-content-fullwidth contact-sidebar-inactive';
			} else if ( ! is_active_sidebar( 'default-page-sidebar' ) && is_page_template( array(
					'templates/agents-list.php',
					'templates/agencies-list.php'
				) )
			) {
				$classes[] = 'realhomes-content-fullwidth default-page-sidebar-inactive';
			}
		}

		if ( 'modern' === INSPIRY_DESIGN_VARIATION ) {
			if ( ! is_active_sidebar( 'property-listing-sidebar' ) && is_page_template( array(
					'templates/agents-list.php',
					'templates/agencies-list.php',
				) )
			) {
				$classes[] = 'realhomes-content-fullwidth property-listing-sidebar-inactive';
			}
		}

		if ( ! is_active_sidebar( 'property-listing-sidebar' ) && ( is_tax() || is_page_template( array( 'templates/users-lists.php' ) ) )
		) {
			$classes[] = 'realhomes-content-fullwidth property-listing-sidebar-inactive';

		} else if ( ! is_active_sidebar( 'dsidx-sidebar' ) && is_page_template( 'templates/dsIDXpress.php' ) ) {
			$classes[] = 'realhomes-content-fullwidth dsidx-sidebar-inactive';

		} else if ( ! is_active_sidebar( 'agent-sidebar' ) && ( is_singular( 'agent' ) || is_author() ) ) {
			$classes[] = 'realhomes-content-fullwidth agent-sidebar-inactive';

		} else if ( ! is_active_sidebar( 'agency-sidebar' ) && is_singular( 'agency' ) ) {
			$classes[] = 'realhomes-content-fullwidth agency-sidebar-inactive';

		} else if ( ! is_active_sidebar( 'default-page-sidebar' ) && is_page() && ! is_page_template() ) {
			$classes[] = 'realhomes-content-fullwidth default-page-sidebar-inactive';

		} else if ( ! is_active_sidebar( 'default-sidebar' ) && ! is_tax() && ( is_singular( 'post' ) || is_home() || is_archive() || is_search() ) ) {
			$classes[] = 'realhomes-content-fullwidth default-sidebar-inactive';
		} else if ( ! is_active_sidebar( 'shop-page-sidebar' ) && realhomes_is_woocommerce_activated() && ( is_post_type_archive( 'product' ) || is_singular( 'product' ) ) ) {
			$classes[] = 'realhomes-content-fullwidth shop-sidebar-inactive';
		}

		return $classes;
	}

	add_filter( 'body_class', 'realhomes_content_width' );
}

if ( ! function_exists( 'inspiry_half_map_fixed_classes' ) ) {

	function inspiry_half_map_fixed_classes( $classes ) {
		if ( realhomes_is_half_map_template() ) {
			$classes[] = 'inspiry_half_map_fixed';
		}

		return $classes;
	}

	add_filter( 'body_class', 'inspiry_half_map_fixed_classes' );
}

if ( ! function_exists( 'inspiry_home_search_form_class' ) ) {

// Adds css class to body when search form is disable on Home Page


	function inspiry_home_search_form_class( $classes ) {
		if ( 'modern' === INSPIRY_DESIGN_VARIATION && is_page_template( 'templates/home.php' ) ) {
			if ( 'false' == get_post_meta( get_the_ID(), 'theme_show_home_search', true ) ) {
				$classes[] = 'inspiry-home-search-form-hide';
			}
		}

		return $classes;
	}

	add_filter( 'body_class', 'inspiry_home_search_form_class' );
}

if ( ! function_exists( 'inspiry_responsive_header' ) ) {

// Adds Responsive header css class to body
	function inspiry_responsive_header( $classes ) {
		if ( 'modern' === INSPIRY_DESIGN_VARIATION ) {
			$get_responsive_header = get_option( 'inspiry_responsive_header_option', 'solid' );
			$classes[]             = 'inspiry_responsive_header_' . $get_responsive_header;
		}

		return $classes;
	}

	add_filter( 'body_class', 'inspiry_responsive_header' );
}

if ( ! function_exists( 'rh_sfoi_data_fetch' ) ) {

	function rh_sfoi_data_fetch() {
		$the_query = new WP_Query( array(
			'posts_per_page' => 50,
			's'              => esc_attr( $_POST['keyword'] ),
			'post_type'      => 'property',
		) );
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post(); ?>

                <a href="<?php the_permalink(); ?>">
                    <span class="sfoi_ajax_thumb"><?php the_post_thumbnail( 'thumbnail' ) ?></span>
                    <span class="sfoi_ajax_title"><?php the_title(); ?></span>
                    <span class="sfoi_ajax_status"><?php echo esc_html( display_property_status( get_the_ID() ) ); ?></span>
                </a>

				<?php
			}
			wp_reset_postdata();
		}
		die();
	}

	add_action( 'wp_ajax_rh_sfoi_data_fetch', 'rh_sfoi_data_fetch' );
	add_action( 'wp_ajax_nopriv_rh_sfoi_data_fetch', 'rh_sfoi_data_fetch' );
}

// Set theme logo to custom_logo (for Elementor Site Logo widget)
if ( ! function_exists( 'inspiry_theme_logo_to_custom_logo' ) ) {

	function inspiry_theme_logo_to_custom_logo() {
		$get_attachment_id = '';
		$get_site_log_url  = get_option( 'theme_sitelogo' );
		if ( ! empty( $get_site_log_url ) ) {
			$get_attachment_id = attachment_url_to_postid( $get_site_log_url );
		}
		set_theme_mod( 'custom_logo', $get_attachment_id );
	}

	add_action( 'customize_save_after', 'inspiry_theme_logo_to_custom_logo' );
}

// Remove theme mod of core colors after settings them as theme options
if ( ! function_exists( 'inspiry_update_theme_mod_to_options' ) ) {
	function inspiry_update_theme_mod_to_options() {
		$keys = array(
			'inspiry_default_styles',
			'theme_core_mod_color_orange',
			'theme_core_mod_color_green_dark',
			'theme_core_mod_color_green',
			'theme_core_color_orange_light',
			'theme_core_color_orange_dark',
			'theme_core_color_orange_glow',
			'theme_core_color_orange_burnt',
			'theme_core_color_blue_light',
			'theme_core_color_blue_dark',
		);

		foreach ( $keys as $key ) {
			$key_theme_mod = get_theme_mod( $key );
			if ( ! empty( $key_theme_mod ) ) {
				update_option( $key, $key_theme_mod );
				remove_theme_mod( $key );
			}
		}
	}

	add_action( 'upgrader_process_complete', 'inspiry_update_theme_mod_to_options' );
}

// Remove default image sizes here.
if ( ! function_exists( 'inspiry_remove_default_images' ) ) {
	function inspiry_remove_default_images( $sizes ) {
		if ( 'true' == get_option( 'inspiry_unset_default_image_sizes' ) ) {
			unset( $sizes['small'] ); // 150px
			unset( $sizes['medium'] ); // 300px
			unset( $sizes['medium_large'] ); // 768px
			unset( $sizes['1536x1536'] ); // 2x medium_large size.
			unset( $sizes['large'] ); // 1024px
			unset( $sizes['2048x2048'] ); // // 2x large size.
		}

		return $sizes;
	}

	add_filter( 'intermediate_image_sizes_advanced', 'inspiry_remove_default_images' );
}

/**
 * This function runs when WordPress theme completes its upgrade process.
 *
 * @param $upgrader_object Array
 * @param $options         Array
 */
function inspiry_upgrade_function( $upgrader_object, $options ) {

	if ( 'update' === $options['action'] && 'theme' === $options['type'] ) {

		delete_user_meta( get_current_user_id(), 'tgmpa_dismissed_notice_inspiry' );
	}
}

add_action( 'upgrader_process_complete', 'inspiry_upgrade_function', 10, 2 );

if ( ! function_exists( 'inspiry_filesize_formatted' ) ) {
	/**
	 * This function returns the file size of specified path
	 *
	 * @param string $path Path of the file
	 *
	 * @return string File size with units
	 */
	function inspiry_filesize_formatted( $path ) {
		$size  = filesize( $path );
		$units = array( 'b', 'kb', 'mb', 'gb', 'tb' );
		$power = $size > 0 ? floor( log( $size, 1024 ) ) : 0;

		return number_format( $size / pow( 1024, $power ), 2, '.', ',' ) . ' ' . $units[ $power ];
	}
}

if ( ! function_exists( 'inspiry_active_nav_class' ) ) {
	function inspiry_active_nav_class( $classes ) {
		if ( in_array( 'current-menu-ancestor', $classes ) || in_array( 'current-menu-item', $classes ) ) {
			$classes[] = 'rh-active';
		}

		return $classes;
	}

	add_filter( 'nav_menu_css_class', 'inspiry_active_nav_class', 10, 1 );
}

if ( ! function_exists( 'realhomes_unset_page_templates' ) ) {

	/**
	 * This function unset the Page Templates from dropdown
	 *
	 * @since 4.0.0
	 *
	 * @param array $page_templates Page templates.
	 *
	 * @return array modified page templates array
	 */

	function realhomes_unset_page_templates( $page_templates ) {

		if ( 'ultra' === INSPIRY_DESIGN_VARIATION ) {
			unset( $page_templates['templates/optima-express.php'] );
		}

		return $page_templates;
	}

	add_filter( 'theme_page_templates', 'realhomes_unset_page_templates' );
}

if ( ! function_exists( 'realhomes_hide_price_separator_class' ) ) {

	/**
	 * Show or hide price separator "/" between price value and post fix
	 *
	 * @since 4.3.2
	 *
	 */
	function realhomes_hide_price_separator_class() {
		if ( 'hide' === get_option( 'realhomes_property_price_separator', 'hide' ) ) {
			echo esc_attr( 'hide-ultra-price-postfix-separator' );
		}
	}
}

if ( ! function_exists( 'realhomes_fullscreen_search_overlay' ) ) {

/**
 * Output fullscreen search overlay markup in footer.
 */
function realhomes_fullscreen_search_overlay() {
?>
<div id="rh-search-overlay" class="rh-search-overlay">
    <button type="button" class="rh-search-close" aria-label="<?php esc_attr_e( 'Fechar', RH_TEXT_DOMAIN ); ?>"><i class="fa fa-times"></i></button>
    <div class="rh-search-overlay-form">
        <?php get_search_form(); ?>
    </div>
</div>
<?php
}
add_action( 'wp_footer', 'realhomes_fullscreen_search_overlay' );
}

/**
 * Enqueue script for fullscreen search overlay.
 */
function realhomes_enqueue_search_overlay_assets() {
        wp_enqueue_script(
                'realhomes-search-overlay',
                get_template_directory_uri() . '/assets/js/search-overlay.js',
                array(),
                null,
                true
        );
}
add_action( 'wp_enqueue_scripts', 'realhomes_enqueue_search_overlay_assets' );