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
    protected $has_ubold_assets = null;

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'init', [ $this, 'load_textdomain' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_filter( 'admin_body_class', [ $this, 'filter_admin_body_class' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        add_action( 'admin_post_dap_clear_logs', [ $this, 'handle_clear_logs_request' ] );
        add_action( 'admin_post_dap_refresh_dashboard', [ $this, 'handle_refresh_dashboard_request' ] );
        add_action( 'admin_post_dap_switch_locale', [ $this, 'handle_switch_locale_request' ] );
        add_action( 'admin_notices', [ $this, 'maybe_display_missing_assets_notice' ] );
        add_action( 'admin_notices', [ $this, 'maybe_display_locale_notice' ] );
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

        $style_dependencies = [];

        if ( $is_dashboard ) {
            wp_register_script(
                'dap-admin',
                DAP_URL . 'assets/js/admin.js',
                [ 'dap-apexcharts' ],
                DAP_VERSION,
                true
            );

            $dashboard_assets = $this->enqueue_dashboard_assets();

            if ( ! empty( $dashboard_assets['styles'] ) ) {
                $style_dependencies = array_merge( $style_dependencies, $dashboard_assets['styles'] );
            }

            wp_enqueue_style(
                'dap-admin',
                DAP_URL . 'assets/css/admin.css',
                $style_dependencies,
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
        $style_dependencies = [];

        if ( $this->has_ubold_assets ) {
            $icons_url = $this->get_asset_url( [
                'assets/ubold/assets/css/icons.min.css',
                'assets/ubold/assets/css/icons.css',
                'assets/css/icons.min.css',
                'assets/css/icons.css',
                'assets/css/vendor.min.css',
                'assets/css/vendor.css',
            ] );

            if ( $icons_url ) {
                wp_enqueue_style( 'dap-ubold-icons', $icons_url, [], DAP_VERSION );
                $style_dependencies[] = 'dap-ubold-icons';
            } else {
                wp_enqueue_style( 'dap-remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css', [], '3.5.0' );
                $style_dependencies[] = 'dap-remixicon';
            }

            $vendor_css_url = $this->get_asset_url( [
                'assets/ubold/assets/css/vendor.min.css',
                'assets/ubold/assets/css/vendor.css',
                'assets/css/vendor.min.css',
                'assets/css/vendor.css',
            ] );

            if ( $vendor_css_url ) {
                wp_enqueue_style( 'dap-ubold-vendor', $vendor_css_url, [], DAP_VERSION );
                $style_dependencies[] = 'dap-ubold-vendor';
            }

            $app_css_url = $this->get_asset_url( [
                'assets/ubold/assets/css/app.min.css',
                'assets/ubold/assets/css/app.css',
                'assets/css/app.min.css',
                'assets/css/app.css',
            ] );

            if ( $app_css_url ) {
                wp_enqueue_style( 'dap-ubold-app', $app_css_url, $style_dependencies, DAP_VERSION );
                $style_dependencies = [ 'dap-ubold-app' ];
            }

            $script_dependencies = [];
            $vendor_js_url       = $this->get_asset_url( [
                'assets/ubold/assets/js/vendor.min.js',
                'assets/ubold/assets/js/vendor.js',
                'assets/js/vendor.min.js',
                'assets/js/vendor.js',
            ] );

            if ( $vendor_js_url ) {
                wp_enqueue_script( 'dap-ubold-vendor', $vendor_js_url, [], DAP_VERSION, true );
                $script_dependencies[] = 'dap-ubold-vendor';
            }

            $config_js_url = $this->get_asset_url( [
                'assets/ubold/assets/js/config.js',
                'assets/js/config.js',
            ] );

            if ( $config_js_url ) {
                wp_enqueue_script( 'dap-ubold-config', $config_js_url, $script_dependencies, DAP_VERSION, true );
                $script_dependencies[] = 'dap-ubold-config';
            }

            $app_js_url = $this->get_asset_url( [
                'assets/ubold/assets/js/app.min.js',
                'assets/ubold/assets/js/app.js',
                'assets/js/app.min.js',
                'assets/js/app.js',
            ] );

            if ( $app_js_url && ! empty( $script_dependencies ) ) {
                wp_enqueue_script( 'dap-ubold-app', $app_js_url, $script_dependencies, DAP_VERSION, true );
            }
        } else {
            wp_enqueue_style( 'dap-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', [], '5.3.3' );
            wp_enqueue_style( 'dap-remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css', [], '3.5.0' );
            $style_dependencies = [ 'dap-bootstrap', 'dap-remixicon' ];

            wp_enqueue_script( 'dap-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', [], '5.3.3', true );
        }

        $apexcharts_url = '';
        $apex_candidates = [
            'assets/ubold/assets/vendor/apexcharts/apexcharts.min.js',
            'assets/vendor/apexcharts/apexcharts.min.js',
        ];

        foreach ( $apex_candidates as $candidate ) {
            if ( dap_asset_exists( $candidate ) ) {
                $apexcharts_url = DAP_URL . ltrim( $candidate, '/' );
                break;
            }
        }

        if ( $apexcharts_url ) {
            wp_enqueue_script( 'dap-apexcharts', $apexcharts_url, [], DAP_VERSION, true );
        } else {
            wp_enqueue_script( 'dap-apexcharts', 'https://cdn.jsdelivr.net/npm/apexcharts@3.49.1/dist/apexcharts.min.js', [], DAP_VERSION, true );
        }

        $inline_settings = 'window.dapDashboardAssets = ' . wp_json_encode(
            [
                'hasUboldAssets' => $this->has_ubold_assets,
            ]
        ) . ';';
        wp_add_inline_script( 'dap-admin', $inline_settings, 'before' );

        return [
            'styles' => $style_dependencies,
        ];
    }

    /**
     * Returns the first available asset URL from the provided relative paths.
     *
     * @param array $relative_paths Relative file paths ordered by priority.
     *
     * @return string
     */
    protected function get_asset_url( $relative_paths ) {
        foreach ( (array) $relative_paths as $relative_path ) {
            if ( dap_asset_exists( $relative_path ) ) {
                return DAP_URL . ltrim( $relative_path, '/' );
            }
        }

        return '';
    }

    /**
     * Displays a warning when the bundled Ubold assets are missing.
     */
    public function maybe_display_missing_assets_notice() {
        if ( $this->has_ubold_assets ) {
            return;
        }

        if ( ! $this->has_ubold_assets && $this->determine_ubold_assets() ) {
            $this->has_ubold_assets = true;

            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

        if ( ! $screen || 'dashboard' !== $screen->id ) {
            return;
        }

        $assets_dir   = trailingslashit( DAP_PATH ) . 'assets/ubold/assets';
        $relative_dir = wp_normalize_path( $assets_dir );
        $relative_dir = str_replace( wp_normalize_path( ABSPATH ), '/', $relative_dir );
        ?>
        <div class="notice notice-warning">
            <p>
                <?php
                printf(
                    /* translators: 1: plugin assets path, 2: fallback directories */
                    esc_html__( 'A aparência completa do Dashboard Agência Privilege usa o tema Ubold. Copie a pasta Docs/assets do Ubold para %1$s ou mantenha os arquivos diretamente em %2$s para substituir os arquivos de fallback.', 'dap' ),
                    '<code>' . esc_html( $relative_dir ) . '</code>',
                    '<code>assets/css</code> / <code>assets/js</code>'
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Displays feedback after attempting to switch the dashboard locale.
     */
    public function maybe_display_locale_notice() {
        if ( empty( $_GET['dap_locale_switched'] ) ) {
            return;
        }

        $status = (int) $_GET['dap_locale_switched'];

        if ( 1 === $status ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html__( 'Idioma do painel atualizado com sucesso.', 'dap' ); ?></p>
            </div>
            <?php
        } elseif ( current_user_can( 'edit_user', get_current_user_id() ) ) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html__( 'Não foi possível atualizar o idioma selecionado.', 'dap' ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Checks if the user supplied Ubold assets exist.
     *
     * @return bool
     */
    protected function determine_ubold_assets() {
        return dap_has_local_ubold_assets();
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

        if ( null === $this->has_ubold_assets ) {
            $this->has_ubold_assets = $this->determine_ubold_assets();
        }

        if ( $screen && 'dashboard' === $screen->id ) {
            $classes .= ' dap-dashboard-screen';
        }

        if ( ! empty( $settings['enable_global_skin'] ) ) {
            $classes .= ' dap-global-skin';
        }

        if ( true === $this->has_ubold_assets ) {
            $classes .= ' dap-has-ubold-assets';
        } else {
            $classes .= ' dap-fallback-assets';
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
        $dashboard = dap_get_dashboard_data();
        $charts    = isset( $dashboard['charts'] ) ? $dashboard['charts'] : [];
        $topbar    = dap_get_dashboard_topbar_data();
        $topbar_user = isset( $topbar['user'] ) && is_array( $topbar['user'] ) ? $topbar['user'] : [];

        return [
            'hasUboldAssets' => $this->has_ubold_assets,
            'heroButtonUrl'  => esc_url( admin_url( $hero_slug ) ),
            'restEndpoint'   => esc_url_raw( rest_url( 'dap/v1/stats' ) ),
            'rest'           => [
                'stats' => esc_url_raw( rest_url( 'dap/v1/stats' ) ),
                'theme' => esc_url_raw( rest_url( 'dap/v1/preferences/theme' ) ),
            ],
            'nonce'          => wp_create_nonce( 'wp_rest' ),
            'charts'         => $charts,
            'palette'        => dap_get_dashboard_palette(),
            'layout'         => [
                'maxWidth' => dap_get_dashboard_max_width(),
            ],
            'generatedAt'    => isset( $dashboard['generated_at_local'] ) ? esc_html( $dashboard['generated_at_local'] ) : '',
            'generatedTimestamp' => isset( $dashboard['generated_timestamp'] ) ? (int) $dashboard['generated_timestamp'] : 0,
            'diagnostics'    => dap_get_dashboard_diagnostics( $dashboard ),
            'themeMode'      => dap_get_user_theme_mode(),
            'topbar'         => [
                'user' => [
                    'name'       => isset( $topbar_user['name'] ) ? $topbar_user['name'] : '',
                    'role'       => isset( $topbar_user['role'] ) ? $topbar_user['role'] : '',
                    'avatar'     => isset( $topbar_user['avatar'] ) ? esc_url( $topbar_user['avatar'] ) : '',
                    'profileUrl' => isset( $topbar_user['profile_url'] ) ? esc_url( $topbar_user['profile_url'] ) : '',
                    'logoutUrl'  => isset( $topbar_user['logout_url'] ) ? esc_url( $topbar_user['logout_url'] ) : '',
                    'editUrl'    => isset( $topbar_user['edit_url'] ) ? esc_url( $topbar_user['edit_url'] ) : '',
                ],
            ],
            'strings'        => [
                'monthly'        => esc_html__( 'Mensal', 'dap' ),
                'weekly'         => esc_html__( 'Semanal', 'dap' ),
                'today'          => esc_html__( 'Hoje', 'dap' ),
                'projects'       => esc_html__( 'Projetos', 'dap' ),
                'sales'          => esc_html__( 'Projetos publicados', 'dap' ),
                'engagement'     => esc_html__( 'Interações', 'dap' ),
                'primarySeries'  => esc_html__( 'Projetos publicados', 'dap' ),
                'comparisonSeries' => esc_html__( 'Comentários aprovados', 'dap' ),
                'onProgress'     => esc_html__( 'Em andamento', 'dap' ),
                'orders'         => esc_html__( 'Pedidos', 'dap' ),
                'themeLight'     => esc_html__( 'Modo claro', 'dap' ),
                'themeDark'      => esc_html__( 'Modo escuro', 'dap' ),
                'themeAuto'      => esc_html__( 'Modo automático', 'dap' ),
                'editProfile'    => esc_html__( 'Editar perfil', 'dap' ),
                'logout'         => esc_html__( 'Sair', 'dap' ),
            ],
        ];
    }

    /**
     * Handles requests to clear stored error logs from the dashboard.
     */
    public function handle_clear_logs_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para executar esta ação.', 'dap' ) );
        }

        check_admin_referer( 'dap_clear_logs' );

        dap_clear_error_logs();

        $redirect = wp_get_referer();

        if ( ! $redirect ) {
            $redirect = admin_url( 'index.php' );
        }

        $redirect = add_query_arg( 'dap_logs_cleared', '1', $redirect );

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Handles dashboard refresh requests triggered from the UI.
     */
    public function handle_refresh_dashboard_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para executar esta ação.', 'dap' ) );
        }

        check_admin_referer( 'dap_refresh_dashboard' );

        dap_invalidate_dashboard_cache();
        dap_get_dashboard_data();

        $redirect = wp_get_referer();

        if ( ! $redirect ) {
            $redirect = admin_url( 'index.php' );
        }

        $redirect = add_query_arg( 'dap_dashboard_refreshed', '1', $redirect );

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Handles locale switch requests triggered from the dashboard topbar.
     */
    public function handle_switch_locale_request() {
        if ( ! is_user_logged_in() ) {
            wp_safe_redirect( wp_login_url() );
            exit;
        }

        $user_id = get_current_user_id();

        if ( ! $user_id || ! current_user_can( 'edit_user', $user_id ) ) {
            wp_die( esc_html__( 'Você não tem permissão para alterar o idioma.', 'dap' ) );
        }

        check_admin_referer( 'dap_switch_locale' );

        $requested_locale = isset( $_GET['locale'] ) ? sanitize_text_field( wp_unslash( $_GET['locale'] ) ) : '';
        $available        = get_available_languages();

        if ( empty( $available ) ) {
            $available = [ 'pt_BR', 'en_US', 'es_ES', 'fr_FR' ];
        }

        $site_locale = get_locale();
        $allowed     = array_unique( array_merge( [ $site_locale ], $available ) );
        $success     = false;

        if ( '' === $requested_locale ) {
            delete_user_meta( $user_id, 'locale' );
            $success = true;
        } elseif ( in_array( $requested_locale, $allowed, true ) ) {
            update_user_meta( $user_id, 'locale', $requested_locale );
            $success = true;
        }

        $redirect = wp_get_referer();

        if ( ! $redirect ) {
            $redirect = admin_url( 'index.php' );
        }

        $redirect = add_query_arg( 'dap_locale_switched', $success ? 1 : 0, $redirect );
        wp_safe_redirect( $redirect );
        exit;
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
                'callback'            => [ $this, 'handle_stats_rest_request' ],
            ]
        );

        register_rest_route(
            'dap/v1',
            '/preferences/theme',
            [
                'methods'             => 'POST',
                'permission_callback' => function() {
                    return current_user_can( 'read' );
                },
                'callback'            => [ $this, 'handle_theme_preference_rest_request' ],
                'args'                => [
                    'mode' => [
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_key',
                        'validate_callback' => function( $value ) {
                            return in_array( $value, [ 'light', 'dark', 'auto' ], true );
                        },
                    ],
                ],
            ]
        );
    }

    /**
     * Returns live dashboard data for REST API consumers.
     *
     * @param \WP_REST_Request $request REST request instance.
     *
     * @return \WP_REST_Response|array
     */
    public function handle_stats_rest_request( \WP_REST_Request $request ) {
        $payload = [
            'dashboard'   => dap_get_dashboard_data(),
            'logs'        => dap_get_error_logs( 10 ),
            'generatedAt' => current_time( 'mysql', true ),
        ];

        /**
         * Filters the REST response payload before it is returned.
         *
         * @param array             $payload Response data.
         * @param \WP_REST_Request $request Request object.
         */
        $payload = apply_filters( 'dap_rest_stats_response', $payload, $request );

        return rest_ensure_response( $payload );
    }

    /**
     * Stores the preferred dashboard theme mode for the current user.
     *
     * @param \WP_REST_Request $request REST request instance.
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_theme_preference_rest_request( \WP_REST_Request $request ) {
        $mode = $request->get_param( 'mode' );
        $mode = sanitize_key( $mode );

        if ( ! in_array( $mode, [ 'light', 'dark', 'auto' ], true ) ) {
            return new \WP_Error( 'dap_invalid_theme_mode', esc_html__( 'Modo de tema inválido.', 'dap' ), [ 'status' => 400 ] );
        }

        $updated = dap_set_user_theme_mode( $mode, get_current_user_id() );

        if ( ! $updated ) {
            return new \WP_Error( 'dap_theme_mode_not_saved', esc_html__( 'Não foi possível salvar a preferência de tema.', 'dap' ), [ 'status' => 500 ] );
        }

        return rest_ensure_response(
            [
                'mode'  => $mode,
                'label' => dap_get_theme_mode_label( $mode ),
            ]
        );
    }
}
