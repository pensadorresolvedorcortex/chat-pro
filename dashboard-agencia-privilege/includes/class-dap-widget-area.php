<?php
/**
 * Provides an Elementor-editable canvas for legacy dashboard widgets.
 *
 * @package DashboardAgenciaPrivilege
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DAP_Widget_Area {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'init', [ __CLASS__, 'register_post_type' ] );
        add_action( 'admin_init', [ $this, 'maybe_bootstrap_area' ] );
        add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
        add_filter( 'elementor_cpt_support', [ $this, 'add_elementor_support' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_elementor_frontend_assets' ] );
    }

    /**
     * Registers the widget area custom post type.
     */
    public static function register_post_type() {
        $labels = [
            'name'          => _x( 'Dashboard Widget Areas', 'post type general name', 'dap' ),
            'singular_name' => _x( 'Dashboard Widget Area', 'post type singular name', 'dap' ),
        ];

        register_post_type(
            'dap_widget_area',
            [
                'labels'              => $labels,
                'public'              => false,
                'show_ui'             => true,
                'show_in_menu'        => false,
                'show_in_rest'        => true,
                'supports'            => [ 'title', 'editor', 'revisions' ],
                'capability_type'     => 'page',
                'map_meta_cap'        => true,
                'rewrite'             => false,
                'exclude_from_search' => true,
            ]
        );
    }

    /**
     * Runs during activation.
     */
    public static function activate() {
        self::register_post_type();
        self::ensure_widget_area_exists();
    }

    /**
     * Ensures the Elementor canvas exists.
     */
    public static function ensure_widget_area_exists() {
        $post_id = dap_get_widget_area_post_id();
        if ( $post_id ) {
            return;
        }

        $post_id = wp_insert_post(
            [
                'post_type'   => 'dap_widget_area',
                'post_title'  => esc_html__( 'Dashboard Widgets Canvas', 'dap' ),
                'post_status' => 'publish',
            ]
        );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_option( 'dap_widget_area_id', (int) $post_id );
        }
    }

    /**
     * Boots the area on admin requests.
     */
    public function maybe_bootstrap_area() {
        self::ensure_widget_area_exists();
    }

    /**
     * Adds submenu page for managing widgets.
     */
    public function register_admin_page() {
        add_dashboard_page(
            esc_html__( 'Dashboard Widgets Canvas', 'dap' ),
            esc_html__( 'Widgets Elementor', 'dap' ),
            'manage_options',
            'dap-dashboard-widgets',
            [ $this, 'render_admin_page' ]
        );
    }

    /**
     * Renders the dashboard widgets management screen.
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $post_id      = dap_get_widget_area_post_id();
        $edit_link    = $post_id ? get_edit_post_link( $post_id, '' ) : '';
        $elementor_url = '';

        if ( $post_id && did_action( 'elementor/loaded' ) ) {
            $elementor_url = add_query_arg(
                [
                    'post'   => $post_id,
                    'action' => 'elementor',
                ],
                admin_url( 'post.php' )
            );
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Widgets Elementor', 'dap' ); ?></h1>
            <p class="description">
                <?php echo esc_html__( 'Customize the introductory dashboard widgets using Elementor. The content saved here appears above the Ubold analytics layout.', 'dap' ); ?>
            </p>

            <?php if ( $post_id ) : ?>
                <p>
                    <?php if ( $elementor_url ) : ?>
                        <a class="button button-primary" href="<?php echo esc_url( $elementor_url ); ?>">
                            <?php echo esc_html__( 'Edit with Elementor', 'dap' ); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ( $edit_link ) : ?>
                        <a class="button" href="<?php echo esc_url( $edit_link ); ?>">
                            <?php echo esc_html__( 'Edit in WordPress', 'dap' ); ?>
                        </a>
                    <?php endif; ?>
                </p>
            <?php else : ?>
                <p><?php echo esc_html__( 'The widget canvas will be created automatically on save.', 'dap' ); ?></p>
            <?php endif; ?>

            <p>
                <?php
                echo esc_html__( 'Tip: Use full-width sections inside Elementor for edge-to-edge layouts. Keep typography balanced with the rest of the dashboard.', 'dap' );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Adds Elementor support for the custom post type.
     *
     * @param array $post_types Supported post types.
     *
     * @return array
     */
    public function add_elementor_support( $post_types ) {
        if ( ! in_array( 'dap_widget_area', $post_types, true ) ) {
            $post_types[] = 'dap_widget_area';
        }

        return $post_types;
    }

    /**
     * Ensures Elementor front-end assets load when needed.
     *
     * @param string $hook Current admin hook.
     */
    public function enqueue_elementor_frontend_assets( $hook ) {
        if ( 'index.php' !== $hook ) {
            return;
        }

        if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\\Elementor\\Plugin' ) ) {
            return;
        }

        $post_id = dap_get_widget_area_post_id();
        if ( ! $post_id ) {
            return;
        }

        $plugin = \Elementor\Plugin::$instance;

        if ( isset( $plugin->frontend ) ) {
            if ( method_exists( $plugin->frontend, 'enqueue_styles' ) ) {
                $plugin->frontend->enqueue_styles();
            }

            if ( method_exists( $plugin->frontend, 'enqueue_scripts' ) ) {
                $plugin->frontend->enqueue_scripts();
            }

            if ( method_exists( $plugin->frontend, 'enqueue_fontawesome' ) ) {
                $plugin->frontend->enqueue_fontawesome();
            }
        }
    }
}
