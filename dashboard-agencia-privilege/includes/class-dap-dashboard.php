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
        remove_action( 'welcome_panel', 'wp_welcome_panel' );
    }

    /**
     * Removes existing widgets and adds the custom dashboard widget.
     */
    public function setup_dashboard() {
        $this->remove_default_widgets();

        wp_add_dashboard_widget( 'dap_dashboard_widget', '', [ $this, 'render_dashboard' ] );
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

        $data = [
            'cta_url'      => esc_url( $hero_url ),
            'widget_area'  => dap_get_widget_area_markup(),
            'elementor_cta' => admin_url( 'index.php?page=dap-dashboard-widgets' ),
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
