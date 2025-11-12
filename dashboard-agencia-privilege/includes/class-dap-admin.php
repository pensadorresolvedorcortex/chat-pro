<?php
/**
 * Handles admin hooks, assets and REST endpoints.
 *
 * @package DashboardAgenciaPrivilege
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DAP_Admin {
    /**
     * Whether the bundled Ubold assets are available.
     *
     * @var bool
     */
    protected $has_ubold_assets = false;

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'init', [ $this, 'load_textdomain' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_filter( 'admin_body_class', [ $this, 'filter_admin_body_class' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
    }

    /**
     * Loads plugin text domain.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'dap', false, dirname( DAP_BASENAME ) . '/languages' );
    }

    /**
     * Enqueues scripts and styles for the admin.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( $hook ) {
        $settings              = dap_get_settings();
        $should_globalize_skin = ! empty( $settings['enable_global_skin'] );
        $is_dashboard          = ( 'index.php' === $hook );

        if ( ! $is_dashboard && ! $should_globalize_skin ) {
            return;
        }

        $this->has_ubold_assets = $this->determine_ubold_assets();

        if ( $is_dashboard ) {
            wp_register_script(
                'dap-admin',
                DAP_URL . 'assets/js/admin.js',
                [ 'dap-apexcharts' ],
                DAP_VERSION,
                true
            );

            $this->enqueue_dashboard_assets();

            wp_enqueue_style(
                'dap-admin',
                DAP_URL . 'assets/css/admin.css',
                $this->has_ubold_assets ? [ 'dap-ubold-app' ] : [],
                DAP_VERSION
            );

            wp_localize_script( 'dap-admin', 'dapDashboard', $this->get_dashboard_localized_data() );
            wp_enqueue_script( 'dap-admin' );
        } elseif ( $should_globalize_skin ) {
            wp_enqueue_style(
                'dap-admin',
                DAP_URL . 'assets/css/admin.css',
                [],
                DAP_VERSION
            );
        }
    }

    /**
     * Determines whether local Ubold assets exist and enqueues them if available.
     */
    protected function enqueue_dashboard_assets() {
        if ( $this->has_ubold_assets ) {
            wp_enqueue_style( 'dap-ubold-icons', DAP_URL . 'assets/ubold/assets/css/icons.min.css', [], DAP_VERSION );
            wp_enqueue_style( 'dap-ubold-app', DAP_URL . 'assets/ubold/assets/css/app.min.css', [ 'dap-ubold-icons' ], DAP_VERSION );

            wp_enqueue_script( 'dap-ubold-vendor', DAP_URL . 'assets/ubold/assets/js/vendor.min.js', [], DAP_VERSION, true );
            wp_enqueue_script( 'dap-ubold-app', DAP_URL . 'assets/ubold/assets/js/app.min.js', [ 'dap-ubold-vendor' ], DAP_VERSION, true );
        }

        if ( dap_asset_exists( 'assets/ubold/assets/vendor/apexcharts/apexcharts.min.js' ) ) {
            wp_enqueue_script( 'dap-apexcharts', DAP_URL . 'assets/ubold/assets/vendor/apexcharts/apexcharts.min.js', [], DAP_VERSION, true );
        } else {
            wp_enqueue_script( 'dap-apexcharts', 'https://cdn.jsdelivr.net/npm/apexcharts@3.49.1/dist/apexcharts.min.js', [], DAP_VERSION, true );
        }

        $inline_settings = 'window.dapDashboardAssets = ' . wp_json_encode(
            [
                'hasUboldAssets' => $this->has_ubold_assets,
            ]
        ) . ';';
        wp_add_inline_script( 'dap-admin', $inline_settings, 'before' );
    }

    /**
     * Checks if the user supplied Ubold assets exist.
     *
     * @return bool
     */
    protected function determine_ubold_assets() {
        $required = [
            'assets/ubold/assets/css/app.min.css',
            'assets/ubold/assets/css/icons.min.css',
            'assets/ubold/assets/js/vendor.min.js',
            'assets/ubold/assets/js/app.min.js',
        ];

        foreach ( $required as $relative ) {
            if ( ! dap_asset_exists( $relative ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Adds contextual admin body classes.
     *
     * @param string $classes Body classes.
     *
     * @return string
     */
    public function filter_admin_body_class( $classes ) {
        $screen   = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $settings = dap_get_settings();

        if ( $screen && 'dashboard' === $screen->id ) {
            $classes .= ' dap-dashboard-screen';
        }

        if ( ! empty( $settings['enable_global_skin'] ) ) {
            $classes .= ' dap-global-skin';
        }

        return $classes;
    }

    /**
     * Returns localized data for the dashboard scripts.
     *
     * @return array
     */
    protected function get_dashboard_localized_data() {
        $hero_slug = dap_get_option( 'hero_button_slug', 'admin.php?page=juntaplay-groups' );
        $hero_slug = ltrim( $hero_slug, '/' );

        return [
            'hasUboldAssets' => $this->has_ubold_assets,
            'heroButtonUrl'  => esc_url( admin_url( $hero_slug ) ),
            'restEndpoint'   => esc_url_raw( rest_url( 'dap/v1/stats' ) ),
            'nonce'          => wp_create_nonce( 'wp_rest' ),
            'strings'        => [
                'monthly' => esc_html__( 'Monthly', 'dap' ),
                'weekly'  => esc_html__( 'Weekly', 'dap' ),
                'today'   => esc_html__( 'Today', 'dap' ),
                'projects' => esc_html__( 'Projects', 'dap' ),
                'onProgress' => esc_html__( 'On Progress', 'dap' ),
            ],
        ];
    }

    /**
     * Registers REST API routes for future extensibility.
     */
    public function register_rest_routes() {
        register_rest_route(
            'dap/v1',
            '/stats',
            [
                'methods'             => 'GET',
                'permission_callback' => function() {
                    return current_user_can( 'read' );
                },
                'callback'            => function( \WP_REST_Request $request ) {
                    return rest_ensure_response(
                        [
                            'projects' => [
                                'total'       => 42,
                                'new'         => 8,
                                'progress'    => 72,
                                'unfinished'  => 5,
                                'time_series' => [ 15, 22, 10, 28, 19, 33, 25 ],
                            ],
                        ]
                    );
                },
            ]
        );
    }
}
