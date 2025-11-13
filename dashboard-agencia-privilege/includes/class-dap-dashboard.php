<?php
/**
 * Overrides the default WordPress dashboard with the Ubold layout.
 *
 * @package DashboardAgenciaPrivilege
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DAP_Dashboard {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'wp_dashboard_setup', [ $this, 'setup_dashboard' ], 999 );
        add_filter( 'screen_layout_columns', [ $this, 'force_single_column' ], 10, 2 );
        add_filter( 'get_user_option_screen_layout_dashboard', [ $this, 'user_option_screen_layout' ] );
        add_filter( 'get_user_option_meta-box-order_dashboard', [ $this, 'force_widget_order' ], 10, 2 );
        add_filter( 'get_user_option_metaboxhidden_dashboard', [ $this, 'ensure_widget_visible' ] );
        remove_action( 'welcome_panel', 'wp_welcome_panel' );
    }

    /**
     * Removes existing widgets and adds the custom dashboard widget.
     */
    public function setup_dashboard() {
        $this->remove_default_widgets();

        wp_add_dashboard_widget(
            'dap_dashboard_widget',
            esc_html__( 'Dashboard Agência Privilege', 'dap' ),
            [ $this, 'render_dashboard' ]
        );
    }

    /**
     * Renders the dashboard view.
     *
     * @param mixed $object        Dashboard object placeholder (unused).
     * @param array $callback_args Optional callback arguments.
     */
    public function render_dashboard( $object = null, $callback_args = [] ) {
        $hero_slug = dap_get_option( 'hero_button_slug', 'admin.php?page=juntaplay-groups' );
        $hero_slug = ltrim( $hero_slug, '/' );
        $hero_url  = admin_url( $hero_slug );
        $dashboard = dap_get_dashboard_data();

        $data = [
            'cta_url'              => esc_url( $hero_url ),
            'widget_area'          => dap_get_widget_area_markup(),
            'elementor_cta'        => admin_url( 'index.php?page=dap-dashboard-widgets' ),
            'error_logs'           => dap_get_error_logs(),
            'clear_logs_url'       => wp_nonce_url( admin_url( 'admin-post.php?action=dap_clear_logs' ), 'dap_clear_logs' ),
            'refresh_url'          => wp_nonce_url( admin_url( 'admin-post.php?action=dap_refresh_dashboard' ), 'dap_refresh_dashboard' ),
            'logs_recently_cleared' => isset( $_GET['dap_logs_cleared'] ) && (int) $_GET['dap_logs_cleared'] === 1,
            'dashboard_recently_refreshed' => isset( $_GET['dap_dashboard_refreshed'] ) && (int) $_GET['dap_dashboard_refreshed'] === 1,
            'dashboard'            => $dashboard,
            'diagnostics'          => dap_get_dashboard_diagnostics( $dashboard ),
            'topbar'               => dap_get_dashboard_topbar_data(),
        ];

        include DAP_PATH . 'includes/views/dashboard.php';
    }

    /**
     * Forces a single column layout.
     *
     * @param array  $columns Columns configuration.
     * @param string $screen  Current screen id.
     *
     * @return array
     */
    public function force_single_column( $columns, $screen ) {
        if ( 'dashboard' === $screen ) {
            $columns['dashboard'] = 1;
        }

        return $columns;
    }

    /**
     * Forces user option for single column dashboard.
     *
     * @return int
     */
    public function user_option_screen_layout() {
        return 1;
    }

    /**
     * Forces the dashboard widget order so our layout always renders.
     *
     * @param array $order Existing order.
     * @param mixed $user  User id or object.
     *
     * @return array
     */
    public function force_widget_order( $order, $user ) {
        if ( ! is_array( $order ) ) {
            $order = maybe_unserialize( $order );
        }

        if ( ! is_array( $order ) ) {
            $order = [];
        }

        $order['normal']  = 'dap_dashboard_widget';
        $order['side']    = '';
        $order['column3'] = '';
        $order['column4'] = '';

        return $order;
    }

    /**
     * Ensures the main dashboard widget cannot be permanently hidden.
     *
     * @param array $hidden List of hidden widget ids.
     *
     * @return array
     */
    public function ensure_widget_visible( $hidden ) {
        if ( empty( $hidden ) ) {
            return $hidden;
        }

        if ( ! is_array( $hidden ) ) {
            $hidden = maybe_unserialize( $hidden );
        }

        if ( ! is_array( $hidden ) ) {
            return $hidden;
        }

        return array_values( array_diff( $hidden, [ 'dap_dashboard_widget' ] ) );
    }

    /**
     * Removes default WordPress dashboard widgets.
     */
    protected function remove_default_widgets() {
        global $wp_meta_boxes;

        $contexts = [ 'normal', 'side', 'column3', 'column4' ];
        $priorities = [ 'core', 'high', 'sorted', 'default', 'low' ];

        foreach ( $contexts as $context ) {
            foreach ( $priorities as $priority ) {
                if ( isset( $wp_meta_boxes['dashboard'][ $context ][ $priority ] ) ) {
                    $wp_meta_boxes['dashboard'][ $context ][ $priority ] = [];
                }
            }
        }
    }
}
