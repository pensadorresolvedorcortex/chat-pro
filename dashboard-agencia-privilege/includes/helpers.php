<?php
/**
 * Helper functions for Dashboard Agência Privilege.
 *
 * @package DashboardAgenciaPrivilege
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'dap_get_default_settings' ) ) {
    function dap_get_default_settings() {
        return [
            'hero_button_slug'   => 'admin.php?page=juntaplay-groups',
            'enable_global_skin' => 1,
        ];
    }
}

if ( ! function_exists( 'dap_initialize_settings_option' ) ) {
    /**
     * Ensures the plugin settings option exists and contains all default keys.
     */
    function dap_initialize_settings_option() {
        $defaults = dap_get_default_settings();
        $current  = get_option( 'dap_settings', null );

        if ( false === $current ) {
            add_option( 'dap_settings', $defaults, '', false );

            return;
        }

        if ( ! is_array( $current ) ) {
            $current = [];
        }

        $needs_update = false;

        foreach ( $defaults as $key => $value ) {
            if ( ! array_key_exists( $key, $current ) ) {
                $current[ $key ] = $value;
                $needs_update     = true;
            }
        }

        if ( $needs_update ) {
            update_option( 'dap_settings', $current, false );
        }
    }
}

if ( ! function_exists( 'dap_get_settings' ) ) {
    function dap_get_settings() {
        $defaults = dap_get_default_settings();
        $settings = get_option( 'dap_settings', [] );

        if ( ! is_array( $settings ) ) {
            $settings = [];
        }

        return wp_parse_args( $settings, $defaults );
    }
}

if ( ! function_exists( 'dap_get_option' ) ) {
    function dap_get_option( $key, $default = '' ) {
        $settings = dap_get_settings();

        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }
}

if ( ! function_exists( 'dap_asset_exists' ) ) {
    function dap_asset_exists( $relative_path ) {
        $path = DAP_PATH . ltrim( $relative_path, '/' );

        return file_exists( $path );
    }
}

if ( ! function_exists( 'dap_first_existing_asset_path' ) ) {
    /**
     * Returns the first available asset path within the plugin directory.
     *
     * @param array $relative_paths Candidate relative paths.
     *
     * @return string
     */
    function dap_first_existing_asset_path( $relative_paths ) {
        foreach ( (array) $relative_paths as $relative_path ) {
            if ( dap_asset_exists( $relative_path ) ) {
                return ltrim( $relative_path, '/' );
            }
        }

        return '';
    }
}

if ( ! function_exists( 'dap_get_brand_asset_urls' ) ) {
    /**
     * Resolves brand logo URLs based on the bundled Ubold assets.
     *
     * @return array
     */
    function dap_get_brand_asset_urls() {
        $light = dap_first_existing_asset_path(
            [
                'assets/ubold/assets/images/logo-light.png',
                'assets/images/logo-light.png',
                'assets/images/logo-light.svg',
                'assets/ubold/assets/images/logo.png',
            ]
        );

        $dark = dap_first_existing_asset_path(
            [
                'assets/ubold/assets/images/logo-dark.png',
                'assets/images/logo-dark.png',
                'assets/images/logo-dark.svg',
                'assets/ubold/assets/images/logo.png',
            ]
        );

        $icon = dap_first_existing_asset_path(
            [
                'assets/ubold/assets/images/logo-sm.png',
                'assets/images/logo-sm.png',
                'assets/images/logo-icon.svg',
            ]
        );

        return [
            'light' => $light ? DAP_URL . $light : '',
            'dark'  => $dark ? DAP_URL . $dark : '',
            'icon'  => $icon ? DAP_URL . $icon : '',
        ];
    }
}

if ( ! function_exists( 'dap_has_local_ubold_assets' ) ) {
    /**
     * Checks if the bundled Ubold assets are available locally.
     *
     * @return bool
     */
    function dap_has_local_ubold_assets() {
        $app_css    = dap_first_existing_asset_path(
            [
                'assets/ubold/assets/css/app.min.css',
                'assets/ubold/assets/css/app.css',
                'assets/css/app.min.css',
                'assets/css/app.css',
            ]
        );
        $vendor_css = dap_first_existing_asset_path(
            [
                'assets/ubold/assets/css/vendor.min.css',
                'assets/ubold/assets/css/vendor.css',
                'assets/css/vendor.min.css',
                'assets/css/vendor.css',
            ]
        );

        return ( $app_css && $vendor_css );
    }
}

if ( ! function_exists( 'dap_is_woocommerce_active' ) ) {
    /**
     * Determines whether WooCommerce is available.
     *
     * @return bool
     */
    function dap_is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }
}

if ( ! function_exists( 'dap_get_currency_symbol' ) ) {
    /**
     * Resolves the currency symbol used across dashboard widgets.
     *
     * @return string
     */
    function dap_get_currency_symbol() {
        $symbol = 'R$';

        if ( dap_is_woocommerce_active() && function_exists( 'get_woocommerce_currency_symbol' ) ) {
            $symbol = get_woocommerce_currency_symbol();
        }

        /**
         * Filters the currency symbol rendered within dashboard widgets.
         *
         * @param string $symbol Currency symbol.
         */
        return apply_filters( 'dap_currency_symbol', $symbol );
    }
}

if ( ! function_exists( 'dap_get_order_status_accent' ) ) {
    /**
     * Maps WooCommerce order statuses into Ubold badge accents.
     *
     * @param string $status Order status slug.
     *
     * @return string
     */
    function dap_get_order_status_accent( $status ) {
        $map = [
            'completed'  => 'success',
            'processing' => 'info',
            'on-hold'    => 'warning',
            'pending'    => 'warning',
            'cancelled'  => 'secondary',
            'refunded'   => 'secondary',
            'failed'     => 'danger',
        ];

        return isset( $map[ $status ] ) ? $map[ $status ] : 'info';
    }
}

if ( ! function_exists( 'dap_get_woocommerce_sales_metrics' ) ) {
    /**
     * Aggregates WooCommerce sales data for dashboard widgets.
     *
     * @return array
     */
    function dap_get_woocommerce_sales_metrics() {
        static $metrics = null;

        if ( null !== $metrics ) {
            return $metrics;
        }

        if ( ! dap_is_woocommerce_active() || ! function_exists( 'wc_get_orders' ) ) {
            $metrics = [];

            return $metrics;
        }

        $statuses = apply_filters( 'dap_dashboard_woocommerce_statuses', [ 'completed', 'processing', 'on-hold' ] );
        $limit    = (int) apply_filters( 'dap_dashboard_woocommerce_orders_limit', 200 );
        $limit    = $limit > 0 ? $limit : 200;
        $now_gmt  = current_time( 'timestamp', true );

        $month_start = gmdate( 'Y-m-d H:i:s', $now_gmt - ( 30 * DAY_IN_SECONDS ) );
        $week_start  = gmdate( 'Y-m-d H:i:s', $now_gmt - ( 7 * DAY_IN_SECONDS ) );

        $month_orders = wc_get_orders(
            [
                'status'       => $statuses,
                'limit'        => $limit,
                'orderby'      => 'date',
                'order'        => 'DESC',
                'return'       => 'objects',
                'date_created' => '>= ' . $month_start,
            ]
        );

        $week_orders = wc_get_orders(
            [
                'status'       => $statuses,
                'limit'        => $limit,
                'orderby'      => 'date',
                'order'        => 'DESC',
                'return'       => 'objects',
                'date_created' => '>= ' . $week_start,
            ]
        );

        $month_total  = 0.0;
        $week_total   = 0.0;
        $status_count = [
            'completed'  => 0,
            'processing' => 0,
            'on-hold'    => 0,
        ];

        foreach ( $month_orders as $order ) {
            $month_total += (float) $order->get_total();
        }

        foreach ( $week_orders as $order ) {
            $week_total += (float) $order->get_total();
            $status      = $order->get_status();

            if ( isset( $status_count[ $status ] ) ) {
                $status_count[ $status ]++;
            }
        }

        $pending_invoices = 0;

        if ( function_exists( 'wc_orders_count' ) ) {
            foreach ( [ 'pending', 'on-hold' ] as $pending_status ) {
                $pending_invoices += (int) wc_orders_count( $pending_status );
            }
        }

        $customers_total = 0;

        if ( function_exists( 'wc_get_customer_count' ) ) {
            $customers_total = (int) wc_get_customer_count();
        }

        if ( ! $customers_total ) {
            $users           = count_users();
            $customer_count  = isset( $users['avail_roles']['customer'] ) ? (int) $users['avail_roles']['customer'] : 0;
            $subscriber_count = isset( $users['avail_roles']['subscriber'] ) ? (int) $users['avail_roles']['subscriber'] : 0;
            $customers_total = $customer_count + $subscriber_count;

            if ( ! $customers_total && isset( $users['total_users'] ) ) {
                $customers_total = (int) $users['total_users'];
            }
        }

        $metrics = [
            'currency'            => dap_get_currency_symbol(),
            'total_sales'         => function_exists( 'wc_get_total_sales' ) ? (float) wc_get_total_sales() : $month_total,
            'month_total'         => $month_total,
            'month_count'         => count( $month_orders ),
            'week_total'          => $week_total,
            'week_count'          => count( $week_orders ),
            'week_status_counts'  => $status_count,
            'pending_invoices'    => $pending_invoices,
            'customers_total'     => $customers_total,
            'average_order_value' => ( $month_orders ? ( $month_total / max( 1, count( $month_orders ) ) ) : 0 ),
        ];

        return $metrics;
    }
}

if ( ! function_exists( 'dap_format_currency_short' ) ) {
    /**
     * Formats a number into a compact currency representation.
     *
     * @param float  $amount   Amount to format.
     * @param string $currency Currency symbol.
     *
     * @return string
     */
    function dap_format_currency_short( $amount, $currency = '' ) {
        $amount   = floatval( $amount );
        $currency = $currency ? $currency : dap_get_currency_symbol();
        $suffixes = [ '', 'K', 'M', 'B', 'T' ];
        $suffix   = '';

        if ( 0 !== $amount ) {
            $index = (int) floor( log10( abs( $amount ) ) / 3 );
            $index = max( 0, min( $index, count( $suffixes ) - 1 ) );
            $suffix = $suffixes[ $index ];
            $amount = $amount / pow( 1000, $index );
        }

        $formatted = number_format_i18n( $amount, $amount >= 100 ? 0 : 1 );

        return trim( sprintf( '%1$s%2$s%3$s', $currency ? $currency . ' ' : '', $formatted, $suffix ) );
    }
}

if ( ! function_exists( 'dap_get_dashboard_cache_key' ) ) {
    /**
     * Returns the cache key used to store dashboard datasets.
     *
     * @return string
     */
    function dap_get_dashboard_cache_key() {
        return 'dap_dashboard_dataset_v1';
    }
}

if ( ! function_exists( 'dap_invalidate_dashboard_cache' ) ) {
    /**
     * Clears the persisted dashboard dataset cache.
     */
    function dap_invalidate_dashboard_cache() {
        delete_transient( dap_get_dashboard_cache_key() );
    }
}

if ( ! function_exists( 'dap_register_dashboard_cache_invalidation_hooks' ) ) {
    /**
     * Hooks into content mutations to ensure the dashboard dataset is refreshed.
     */
    function dap_register_dashboard_cache_invalidation_hooks() {
        static $registered = false;

        if ( $registered ) {
            return;
        }

        $registered = true;

        $actions = [
            'save_post',
            'deleted_post',
            'trash_post',
            'transition_post_status',
            'comment_post',
            'edit_comment',
            'deleted_comment',
            'trashed_comment',
            'untrashed_comment',
            'spam_comment',
            'unspam_comment',
        ];

        foreach ( $actions as $action ) {
            add_action( $action, 'dap_invalidate_dashboard_cache', 10, 0 );
        }
    }
}

if ( ! function_exists( 'dap_get_dashboard_post_types' ) ) {
    /**
     * Returns the post types considered when building dashboard insights.
     *
     * @return array
     */
    function dap_get_dashboard_post_types() {
        $defaults   = [ 'post', 'page' ];
        $post_types = apply_filters( 'dap_dashboard_post_types', $defaults );

        $post_types = array_filter(
            array_map( 'sanitize_key', (array) $post_types )
        );

        return ! empty( $post_types ) ? $post_types : $defaults;
    }
}

if ( ! function_exists( 'dap_prepare_post_type_query' ) ) {
    /**
     * Prepares an SQL statement that includes a dynamic IN clause for post types.
     *
     * @param string $sql_template SQL template containing a %s placeholder for the IN clause.
     * @param array  $post_types   Post types to include.
     * @param array  $extra_params Additional parameters appended after post types.
     *
     * @return string|false
     */
    function dap_prepare_post_type_query( $sql_template, $post_types, $extra_params = [] ) {
        global $wpdb;

        $post_types = ! empty( $post_types ) ? array_map( 'sanitize_key', (array) $post_types ) : dap_get_dashboard_post_types();
        $extra      = (array) $extra_params;

        if ( empty( $post_types ) ) {
            return false;
        }

        $placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
        $sql          = preg_replace( '/%s/', $placeholders, $sql_template, 1 );

        if ( null === $sql || $sql === $sql_template ) {
            return false;
        }
        $params       = array_merge( $post_types, $extra );

        array_unshift( $params, $sql );

        return call_user_func_array( [ $wpdb, 'prepare' ], $params );
    }
}

if ( ! function_exists( 'dap_count_recent_posts' ) ) {
    /**
     * Counts published posts within the last X days for the configured post types.
     *
     * @param array $post_types Post types.
     * @param int   $days       Window in days.
     *
     * @return int
     */
    function dap_count_recent_posts( $post_types, $days = 7 ) {
        global $wpdb;

        $days      = max( 1, (int) $days );
        $now_gmt   = current_time( 'timestamp', true );
        $after_gmt = gmdate( 'Y-m-d H:i:s', $now_gmt - ( $days * DAY_IN_SECONDS ) );

        $sql_template = "
            SELECT COUNT(ID)
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'
                AND post_type IN (%s)
                AND post_date_gmt >= %s
        ";

        $prepared = dap_prepare_post_type_query( $sql_template, $post_types, [ $after_gmt ] );

        if ( false === $prepared ) {
            return 0;
        }

        return (int) $wpdb->get_var( $prepared );
    }
}

if ( ! function_exists( 'dap_get_widget_area_post_id' ) ) {
    /**
     * Retrieves the Elementor widget area post ID.
     *
     * @return int
     */
    function dap_get_widget_area_post_id() {
        $cached = (int) get_option( 'dap_widget_area_id' );

        if ( $cached ) {
            $post = get_post( $cached );

            if ( $post && 'trash' !== $post->post_status ) {
                return $cached;
            }
        }

        $post = get_posts(
            [
                'post_type'      => 'dap_widget_area',
                'post_status'    => [ 'publish', 'draft' ],
                'posts_per_page' => 1,
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'fields'         => 'ids',
            ]
        );

        if ( ! empty( $post ) && isset( $post[0] ) ) {
            update_option( 'dap_widget_area_id', (int) $post[0] );

            return (int) $post[0];
        }

        return 0;
    }
}

if ( ! function_exists( 'dap_get_widget_area_markup' ) ) {
    /**
     * Returns the rendered widget area markup.
     *
     * @return string
     */
    function dap_get_widget_area_markup() {
        $post_id         = dap_get_widget_area_post_id();
        $fallback_markup = dap_get_default_widget_area_markup();

        if ( ! $post_id ) {
            return $fallback_markup;
        }

        if ( did_action( 'elementor/loaded' ) && class_exists( '\\Elementor\\Plugin' ) ) {
            return \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $post_id, true );
        }

        $post = get_post( $post_id );

        if ( ! $post || 'trash' === $post->post_status ) {
            return $fallback_markup;
        }

        setup_postdata( $post );
        $content = apply_filters( 'the_content', $post->post_content );
        wp_reset_postdata();

        if ( $content ) {
            return $content;
        }

        return $fallback_markup;
    }
}

if ( ! function_exists( 'dap_get_default_widget_area_markup' ) ) {
    /**
     * Provides a default Modern widget grid when Elementor content is unavailable.
     *
     * @return string
     */
    function dap_get_default_widget_area_markup() {
        $cta_url = admin_url( 'index.php?page=dap-dashboard-widgets' );

        ob_start();
        ?>
        <div class="dap-default-widgets">
            <div class="dap-default-widgets__header d-flex flex-wrap flex-md-nowrap align-items-md-center justify-content-between gap-3 mb-4">
                <div>
                    <h4 class="fw-semibold mb-1"><?php echo esc_html__( 'Área inicial personalizável', 'dap' ); ?></h4>
                    <p class="mb-0 text-muted"><?php echo esc_html__( 'Use o Elementor para adaptar estes cards Modern ao contexto da sua agência.', 'dap' ); ?></p>
                </div>
                <a class="btn btn-soft-primary" href="<?php echo esc_url( $cta_url ); ?>">
                    <i class="ri-paint-brush-line me-2"></i>
                    <?php echo esc_html__( 'Editar com Elementor', 'dap' ); ?>
                </a>
            </div>
            <div class="row g-4">
                <div class="col-12 col-xl-6">
                    <div class="card h-100 dap-default-widget">
                        <div class="card-body d-flex flex-column gap-4">
                            <div class="d-flex align-items-center gap-3">
                                <span class="dap-default-widget__icon bg-soft-primary text-primary"><i class="ri-bubble-chart-line"></i></span>
                                <div>
                                    <h5 class="mb-0 fw-semibold"><?php echo esc_html__( 'Pipeline Overview', 'dap' ); ?></h5>
                                    <small class="text-muted"><?php echo esc_html__( 'Acompanhe squads ativos e próximos marcos.', 'dap' ); ?></small>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-6">
                                    <p class="text-muted text-uppercase small mb-1"><?php echo esc_html__( 'Fase atual', 'dap' ); ?></p>
                                    <h3 class="fw-semibold mb-0"><?php echo esc_html__( 'Discovery', 'dap' ); ?></h3>
                                </div>
                                <div class="col-6 text-md-end">
                                    <p class="text-muted text-uppercase small mb-1"><?php echo esc_html__( 'Squads', 'dap' ); ?></p>
                                    <h3 class="fw-semibold mb-0">8</h3>
                                </div>
                                <div class="col-12">
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: 64%;" aria-valuenow="64" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted d-block mt-2"><?php echo esc_html__( '64% da meta do trimestre concluída.', 'dap' ); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-3">
                    <div class="card h-100 dap-default-widget">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase fw-semibold small mb-3"><?php echo esc_html__( 'Produtividade', 'dap' ); ?></h6>
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <span class="dap-default-widget__icon bg-soft-success text-success"><i class="ri-team-line"></i></span>
                                <div>
                                    <h3 class="fw-semibold mb-0">26</h3>
                                    <small class="text-muted"><?php echo esc_html__( 'Membros ativos', 'dap' ); ?></small>
                                </div>
                            </div>
                            <ul class="list-unstyled mb-0 small text-muted">
                                <li class="d-flex justify-content-between align-items-center mb-2">
                                    <span><?php echo esc_html__( 'SLA cumprido', 'dap' ); ?></span>
                                    <span class="badge bg-soft-success text-success">94%</span>
                                </li>
                                <li class="d-flex justify-content-between align-items-center mb-2">
                                    <span><?php echo esc_html__( 'Entregas na semana', 'dap' ); ?></span>
                                    <span class="badge bg-soft-primary text-primary">18</span>
                                </li>
                                <li class="d-flex justify-content-between align-items-center">
                                    <span><?php echo esc_html__( 'Alertas críticos', 'dap' ); ?></span>
                                    <span class="badge bg-soft-danger text-danger">2</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-3">
                    <div class="card h-100 dap-default-widget">
                        <div class="card-body d-flex flex-column gap-4">
                            <div class="d-flex align-items-center gap-3">
                                <span class="dap-default-widget__icon bg-soft-warning text-warning"><i class="ri-calendar-check-line"></i></span>
                                <div>
                                    <h5 class="mb-0 fw-semibold"><?php echo esc_html__( 'Próximos rituais', 'dap' ); ?></h5>
                                    <small class="text-muted"><?php echo esc_html__( 'Sincronize o time com os eventos estratégicos.', 'dap' ); ?></small>
                                </div>
                            </div>
                            <ul class="list-unstyled mb-0 small text-muted">
                                <li class="mb-2">
                                    <span class="fw-semibold text-dark d-block"><?php echo esc_html__( 'Daily squads', 'dap' ); ?></span>
                                    <span><?php echo esc_html__( '09:30 - Liderado por Júlia', 'dap' ); ?></span>
                                </li>
                                <li class="mb-2">
                                    <span class="fw-semibold text-dark d-block"><?php echo esc_html__( 'Review mensal', 'dap' ); ?></span>
                                    <span><?php echo esc_html__( 'Quarta às 14h - Diretoria', 'dap' ); ?></span>
                                </li>
                                <li>
                                    <span class="fw-semibold text-dark d-block"><?php echo esc_html__( 'Retrospectiva squads', 'dap' ); ?></span>
                                    <span><?php echo esc_html__( 'Sexta às 16h - Squad Atlas', 'dap' ); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php

        $markup = ob_get_clean();

        /**
         * Filters the default widget area markup rendered when Elementor content is unavailable.
         *
         * @param string $markup Default markup.
         */
        return apply_filters( 'dap_default_widget_area_markup', $markup );
    }
}

if ( ! function_exists( 'dap_get_dashboard_kpis' ) ) {
    /**
     * Builds KPI cards based on WordPress content activity.
     *
     * @return array
     */
    function dap_get_dashboard_kpis() {
        $post_types = dap_get_dashboard_post_types();
        $published  = 0;
        $drafts     = 0;
        $pending    = 0;

        foreach ( $post_types as $post_type ) {
            $counts = wp_count_posts( $post_type );

            if ( ! $counts ) {
                continue;
            }

            $published += isset( $counts->publish ) ? (int) $counts->publish : 0;
            $drafts    += isset( $counts->draft ) ? (int) $counts->draft : 0;
            $pending   += isset( $counts->pending ) ? (int) $counts->pending : 0;
        }

        $new_projects     = dap_count_recent_posts( $post_types, 7 );
        $total_known      = max( 1, $published + $drafts + $pending );
        $progress_percent = min( 100, max( 0, round( ( $published / $total_known ) * 100 ) ) );
        $unfinished       = $drafts + $pending;
        $weekly_ratio     = round( ( $new_projects / $total_known ) * 100 );

        return [
            [
                'key'         => 'total_projects',
                'label'       => esc_html__( 'Total Projects', 'dap' ),
                'value'       => number_format_i18n( $published ),
                'delta'       => sprintf( esc_html__( '+%s nesta semana', 'dap' ), number_format_i18n( $new_projects ) ),
                'delta_label' => esc_html__( 'publicados nos últimos 7 dias', 'dap' ),
                'accent'      => 'primary',
            ],
            [
                'key'         => 'new_projects',
                'label'       => esc_html__( 'New Projects', 'dap' ),
                'value'       => number_format_i18n( $new_projects ),
                'delta'       => sprintf( esc_html__( '%s%% do volume total', 'dap' ), number_format_i18n( max( 0, $weekly_ratio ) ) ),
                'delta_label' => esc_html__( 'participação semanal', 'dap' ),
                'accent'      => 'success',
            ],
            [
                'key'         => 'on_progress',
                'label'       => esc_html__( 'On Progress', 'dap' ),
                'value'       => number_format_i18n( $progress_percent ) . '%',
                'value_raw'   => $progress_percent,
                'delta'       => sprintf( esc_html__( '%s conteúdos ativos', 'dap' ), number_format_i18n( $published ) ),
                'delta_label' => esc_html__( 'publicados e em acompanhamento', 'dap' ),
                'accent'      => 'info',
            ],
            [
                'key'         => 'unfinished',
                'label'       => esc_html__( 'Unfinished', 'dap' ),
                'value'       => number_format_i18n( $unfinished ),
                'delta'       => esc_html__( 'precisam de revisão', 'dap' ),
                'delta_label' => esc_html__( 'rascunhos e pendências', 'dap' ),
                'accent'      => 'danger',
            ],
        ];
    }
}

if ( ! function_exists( 'dap_get_dashboard_sales_cards' ) ) {
    /**
     * Returns summary cards inspired by the Ubold Modern layout.
     *
     * @param array $kpis Dashboard KPI payload.
     *
     * @return array
     */
    function dap_get_dashboard_sales_cards( $kpis ) {
        $post_types = dap_get_dashboard_post_types();
        $counts     = wp_count_comments();
        $totals     = [
            'published' => 0,
            'drafts'    => 0,
            'pending'   => 0,
        ];

        foreach ( $post_types as $post_type ) {
            $post_counts = wp_count_posts( $post_type );

            if ( ! $post_counts ) {
                continue;
            }

            $totals['published'] += isset( $post_counts->publish ) ? (int) $post_counts->publish : 0;
            $totals['drafts']    += isset( $post_counts->draft ) ? (int) $post_counts->draft : 0;
            $totals['pending']   += isset( $post_counts->pending ) ? (int) $post_counts->pending : 0;
        }

        $orders_last_week = dap_count_recent_posts( $post_types, 7 ) + ( $counts ? (int) $counts->approved : 0 );
        $unfinished        = $totals['drafts'] + $totals['pending'];
        $users             = count_users();
        $customer_count    = isset( $users['avail_roles']['customer'] ) ? (int) $users['avail_roles']['customer'] : 0;
        $subscriber_count  = isset( $users['avail_roles']['subscriber'] ) ? (int) $users['avail_roles']['subscriber'] : 0;
        $customers_total   = $customer_count + $subscriber_count;

        if ( ! $customers_total && isset( $users['total_users'] ) ) {
            $customers_total = (int) $users['total_users'];
        }

        $revenue_seed = max( 1, $totals['published'] + $unfinished );
        $total_sales  = $revenue_seed * 980;
        $currency     = dap_get_currency_symbol();

        if ( dap_is_woocommerce_active() ) {
            $metrics          = dap_get_woocommerce_sales_metrics();
            $customers_total  = max( $customers_total, isset( $metrics['customers_total'] ) ? (int) $metrics['customers_total'] : 0 );
            $orders_last_week = isset( $metrics['week_count'] ) ? (int) $metrics['week_count'] : $orders_last_week;
            $unfinished       = isset( $metrics['pending_invoices'] ) ? (int) $metrics['pending_invoices'] : $unfinished;
            $total_sales      = isset( $metrics['month_total'] ) ? (float) $metrics['month_total'] : $total_sales;

            $status_counts = isset( $metrics['week_status_counts'] ) && is_array( $metrics['week_status_counts'] ) ? $metrics['week_status_counts'] : [];
            $completed     = isset( $status_counts['completed'] ) ? (int) $status_counts['completed'] : 0;

            return apply_filters(
                'dap_dashboard_sales_cards',
                [
                    [
                        'label'       => esc_html__( 'Total Sales', 'dap' ),
                        'value'       => dap_format_currency_short( $total_sales, $currency ),
                        'delta'       => esc_html__( 'últimos 30 dias', 'dap' ),
                        'icon'        => 'ri-briefcase-4-line',
                        'trend_class' => 'success',
                        'trend_label' => sprintf(
                            /* translators: %s: order count */
                            esc_html__( '+%s pedidos', 'dap' ),
                            number_format_i18n( max( 1, isset( $metrics['month_count'] ) ? (int) $metrics['month_count'] : $orders_last_week ) )
                        ),
                    ],
                    [
                        'label'       => esc_html__( 'Orders Placed', 'dap' ),
                        'value'       => number_format_i18n( max( 0, $orders_last_week ) ),
                        'delta'       => esc_html__( 'nos últimos 7 dias', 'dap' ),
                        'icon'        => 'ri-shopping-basket-2-line',
                        'trend_class' => 'info',
                        'trend_label' => sprintf(
                            /* translators: %s: completed orders count */
                            esc_html__( '%s concluídos', 'dap' ),
                            number_format_i18n( max( 0, $completed ) )
                        ),
                    ],
                    [
                        'label'       => esc_html__( 'Active Clients', 'dap' ),
                        'value'       => number_format_i18n( max( 1, $customers_total ) ),
                        'delta'       => esc_html__( 'clientes com pedidos recentes', 'dap' ),
                        'icon'        => 'ri-user-3-line',
                        'trend_class' => 'warning',
                        'trend_label' => esc_html__( 'Base fidelizada', 'dap' ),
                    ],
                    [
                        'label'       => esc_html__( 'Pending Invoices', 'dap' ),
                        'value'       => number_format_i18n( max( 0, $unfinished ) ),
                        'delta'       => esc_html__( 'aguardando ação da equipe', 'dap' ),
                        'icon'        => 'ri-bill-line',
                        'trend_class' => 'danger',
                        'trend_label' => esc_html__( 'Revisão urgente', 'dap' ),
                    ],
                ],
                $kpis
            );
        }

        return apply_filters(
            'dap_dashboard_sales_cards',
            [
                [
                    'label'       => esc_html__( 'Total Sales', 'dap' ),
                    'value'       => dap_format_currency_short( $total_sales, $currency ),
                    'delta'       => esc_html__( 'vs. últimos 30 dias', 'dap' ),
                    'icon'        => 'ri-briefcase-4-line',
                    'trend_class' => 'success',
                    'trend_label' => sprintf( esc_html__( '+%s', 'dap' ), number_format_i18n( max( 3, round( $revenue_seed / 2 ) ) ) . '%' ),
                ],
                [
                    'label'       => esc_html__( 'Orders Placed', 'dap' ),
                    'value'       => number_format_i18n( max( 1, $orders_last_week ) ),
                    'delta'       => esc_html__( 'nos últimos 7 dias', 'dap' ),
                    'icon'        => 'ri-shopping-basket-2-line',
                    'trend_class' => 'info',
                    'trend_label' => esc_html__( '+12 pedidos', 'dap' ),
                ],
                [
                    'label'       => esc_html__( 'Active Clients', 'dap' ),
                    'value'       => number_format_i18n( max( 1, $customers_total ) ),
                    'delta'       => esc_html__( 'com interações recentes', 'dap' ),
                    'icon'        => 'ri-user-3-line',
                    'trend_class' => 'warning',
                    'trend_label' => esc_html__( '+8 novos', 'dap' ),
                ],
                [
                    'label'       => esc_html__( 'Pending Invoices', 'dap' ),
                    'value'       => number_format_i18n( max( 0, $unfinished ) ),
                    'delta'       => esc_html__( 'à espera de aprovação', 'dap' ),
                    'icon'        => 'ri-bill-line',
                    'trend_class' => 'danger',
                    'trend_label' => esc_html__( 'Revisão urgente', 'dap' ),
                ],
            ],
            $kpis
        );
    }
}

if ( ! function_exists( 'dap_get_dashboard_sales_summary' ) ) {
    /**
     * Builds the hero summary metrics for the dashboard hero card.
     *
     * @param array $kpis Dashboard KPIs.
     *
     * @return array
     */
    function dap_get_dashboard_sales_summary( $kpis ) {
        $orders           = 0;
        $progress_percent = 72;

        foreach ( $kpis as $kpi ) {
            if ( isset( $kpi['key'] ) && 'new_projects' === $kpi['key'] ) {
                $orders = isset( $kpi['value_raw'] ) ? (int) $kpi['value_raw'] : (int) str_replace( [ '.', ',' ], '', $kpi['value'] );
            }

            if ( isset( $kpi['key'] ) && 'on_progress' === $kpi['key'] && isset( $kpi['value_raw'] ) ) {
                $progress_percent = (int) $kpi['value_raw'];
            }
        }

        $sales_cards = dap_get_dashboard_sales_cards( $kpis );
        $headline    = isset( $sales_cards[0] ) ? $sales_cards[0] : null;
        $currency    = dap_get_currency_symbol();
        $average     = max( 1, $orders * 42 );

        if ( dap_is_woocommerce_active() ) {
            $metrics       = dap_get_woocommerce_sales_metrics();
            $headline      = $headline ? $headline : [];
            $month_total   = isset( $metrics['month_total'] ) ? (float) $metrics['month_total'] : 0.0;
            $week_count    = isset( $metrics['week_count'] ) ? (int) $metrics['week_count'] : 0;
            $month_count   = isset( $metrics['month_count'] ) ? (int) $metrics['month_count'] : max( 1, $week_count );
            $average_order = isset( $metrics['average_order_value'] ) ? (float) $metrics['average_order_value'] : 0.0;

            if ( $headline ) {
                $headline['value'] = dap_format_currency_short( $month_total, $currency );
                $headline['delta'] = esc_html__( 'últimos 30 dias', 'dap' );
                $headline['headline'] = true;
            }

            $conversion_rate = $month_count > 0 ? round( min( 96, max( 12, ( $week_count / max( 1, $month_count ) ) * 100 ) ) ) : $progress_percent;
            $progress_percent = $conversion_rate;
            $average          = $average_order ? $average_order : $average;
        } elseif ( $headline ) {
            $headline['headline'] = true;
        }

        return apply_filters(
            'dap_dashboard_sales_summary',
            [
                'headline' => $headline,
                'metrics'  => [
                    [
                        'label' => esc_html__( 'Conversion Rate', 'dap' ),
                        'value' => sprintf( '%s%%', number_format_i18n( max( 12, min( 96, $progress_percent ) ) ) ),
                    ],
                    [
                        'label' => esc_html__( 'Average Order', 'dap' ),
                        'value' => dap_format_currency_short( $average, $currency ),
                    ],
                    [
                        'label' => esc_html__( 'Returning Customers', 'dap' ),
                        'value' => sprintf( '%s%%', number_format_i18n( max( 10, min( 92, $progress_percent - 8 ) ) ) ),
                    ],
                ],
            ],
            $kpis
        );
    }
}

if ( ! function_exists( 'dap_get_recent_projects_table' ) ) {
    /**
     * Returns rows for the recent projects table based on site content.
     *
     * @return array
     */
    function dap_get_recent_projects_table() {
        $rows       = [];
        $post_types = dap_get_dashboard_post_types();

        $query = new WP_Query(
            [
                'post_type'           => $post_types,
                'post_status'         => [ 'publish', 'draft', 'pending', 'future', 'private' ],
                'posts_per_page'      => 4,
                'orderby'             => 'modified',
                'order'               => 'DESC',
                'no_found_rows'       => true,
                'ignore_sticky_posts' => true,
            ]
        );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();

                $status       = get_post_status();
                $status_obj   = get_post_status_object( $status );
                $status_label = $status_obj ? $status_obj->label : ucfirst( $status );
                $badge        = 'info';
                $progress     = '45%';

                switch ( $status ) {
                    case 'publish':
                        $badge    = 'success';
                        $progress = '100%';
                        break;
                    case 'draft':
                        $badge    = 'warning';
                        $progress = '40%';
                        break;
                    case 'pending':
                        $badge    = 'primary';
                        $progress = '65%';
                        break;
                    case 'future':
                        $badge    = 'info';
                        $progress = '80%';
                        break;
                    case 'private':
                        $badge    = 'secondary';
                        $progress = '55%';
                        break;
                }

                $author_id = (int) get_post_field( 'post_author', get_the_ID() );
                $owner     = get_the_author_meta( 'display_name', $author_id );

                if ( ! $owner ) {
                    $owner = esc_html__( 'Equipe', 'dap' );
                }

                $rows[] = [
                    'project'  => get_the_title() ? get_the_title() : esc_html__( '(Sem título)', 'dap' ),
                    'owner'    => $owner,
                    'progress' => $progress,
                    'status'   => $status_label,
                    'badge'    => $badge,
                ];
            }

            wp_reset_postdata();
        }

        if ( empty( $rows ) ) {
            $rows = [
                [ 'project' => esc_html__( 'Privilege Commerce Revamp', 'dap' ), 'owner' => 'Squad Growth', 'status' => esc_html__( 'In Review', 'dap' ), 'badge' => 'warning', 'progress' => '78%' ],
                [ 'project' => esc_html__( 'CRM Automation Flows', 'dap' ), 'owner' => 'Squad CRM', 'status' => esc_html__( 'Live', 'dap' ), 'badge' => 'success', 'progress' => '100%' ],
                [ 'project' => esc_html__( 'Influencer Campaign Blitz', 'dap' ), 'owner' => 'Squad Media', 'status' => esc_html__( 'On Hold', 'dap' ), 'badge' => 'danger', 'progress' => '35%' ],
                [ 'project' => esc_html__( 'Privilege App 3.0', 'dap' ), 'owner' => 'Squad Product', 'status' => esc_html__( 'Development', 'dap' ), 'badge' => 'primary', 'progress' => '62%' ],
            ];
        }

        return $rows;
    }
}

if ( ! function_exists( 'dap_get_product_inventory_rows' ) ) {
    /**
     * Maps site content into the Product Inventory table.
     *
     * @return array
     */
    function dap_get_product_inventory_rows() {
        $rows       = [];
        $currency   = dap_get_currency_symbol();

        if ( dap_is_woocommerce_active() && function_exists( 'wc_get_products' ) ) {
            $products = wc_get_products(
                [
                    'limit'   => 6,
                    'orderby' => 'date',
                    'order'   => 'DESC',
                ]
            );

            foreach ( $products as $product ) {
                $category_list = function_exists( 'wc_get_product_category_list' ) ? wc_get_product_category_list( $product->get_id() ) : '';
                $category      = $category_list ? wp_strip_all_tags( $category_list ) : esc_html__( 'Sem categoria', 'dap' );
                $stock_qty     = $product->get_stock_quantity();
                $stock_qty     = ( null === $stock_qty ) ? 0 : (int) $stock_qty;
                $stock_status  = $product->get_stock_status();
                $status_label  = esc_html__( 'In Stock', 'dap' );
                $status_accent = 'success';

                if ( 'outofstock' === $stock_status ) {
                    $status_label  = esc_html__( 'Out of Stock', 'dap' );
                    $status_accent = 'danger';
                } elseif ( 'onbackorder' === $stock_status ) {
                    $status_label  = esc_html__( 'Backorder', 'dap' );
                    $status_accent = 'warning';
                } elseif ( $stock_qty <= 5 ) {
                    $status_label  = esc_html__( 'Low Stock', 'dap' );
                    $status_accent = 'warning';
                }

                $price = $product->get_price() ? (float) $product->get_price() : (float) $product->get_regular_price();

                $rows[] = [
                    'product'      => $product->get_name(),
                    'category'     => $category,
                    'stock'        => max( 0, $stock_qty ),
                    'price'        => dap_format_currency_short( $price, $currency ),
                    'status'       => $status_accent,
                    'status_label' => $status_label,
                ];
            }
        }

        if ( empty( $rows ) ) {
            $post_types = dap_get_dashboard_post_types();

            $query = new WP_Query(
                [
                    'post_type'           => $post_types,
                    'post_status'         => [ 'publish', 'draft', 'pending', 'private' ],
                    'posts_per_page'      => 6,
                    'orderby'             => 'date',
                    'order'               => 'DESC',
                    'no_found_rows'       => true,
                    'ignore_sticky_posts' => true,
                ]
            );

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();

                    $terms     = get_the_terms( get_the_ID(), 'category' );
                    $category  = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0]->name : esc_html__( 'Sem categoria', 'dap' );
                    $stock_raw = get_post_meta( get_the_ID(), '_stock', true );
                    $stock     = $stock_raw ? absint( $stock_raw ) : max( 6, (int) get_comments_number( get_the_ID() ) * 3 );
                    $status    = 'success';
                    $status_label = esc_html__( 'In Stock', 'dap' );

                    if ( $stock <= 5 ) {
                        $status       = 'danger';
                        $status_label = esc_html__( 'Out of Stock', 'dap' );
                    } elseif ( $stock <= 12 ) {
                        $status       = 'warning';
                        $status_label = esc_html__( 'Low Stock', 'dap' );
                    }

                    $price_meta = get_post_meta( get_the_ID(), '_price', true );
                    $price      = $price_meta ? floatval( $price_meta ) : ( $stock * 19.8 );

                    $rows[] = [
                        'product'      => get_the_title() ? get_the_title() : esc_html__( '(Sem título)', 'dap' ),
                        'category'     => $category,
                        'stock'        => $stock,
                        'price'        => dap_format_currency_short( $price, $currency ),
                        'status'       => $status,
                        'status_label' => $status_label,
                    ];
                }

                wp_reset_postdata();
            }
        }

        if ( empty( $rows ) ) {
            $rows = [
                [ 'product' => esc_html__( 'Marketing Automation Suite', 'dap' ), 'category' => esc_html__( 'SaaS', 'dap' ), 'stock' => 28, 'price' => dap_format_currency_short( 12400 ), 'status' => 'success', 'status_label' => esc_html__( 'In Stock', 'dap' ) ],
                [ 'product' => esc_html__( 'Privilege CRM Onboarding', 'dap' ), 'category' => esc_html__( 'Services', 'dap' ), 'stock' => 9, 'price' => dap_format_currency_short( 5800 ), 'status' => 'warning', 'status_label' => esc_html__( 'Low Stock', 'dap' ) ],
                [ 'product' => esc_html__( 'Inbound Starter Kit', 'dap' ), 'category' => esc_html__( 'Templates', 'dap' ), 'stock' => 16, 'price' => dap_format_currency_short( 3200 ), 'status' => 'success', 'status_label' => esc_html__( 'In Stock', 'dap' ) ],
                [ 'product' => esc_html__( 'Media Blitz Campaign', 'dap' ), 'category' => esc_html__( 'Campaigns', 'dap' ), 'stock' => 4, 'price' => dap_format_currency_short( 8900 ), 'status' => 'danger', 'status_label' => esc_html__( 'Out of Stock', 'dap' ) ],
                [ 'product' => esc_html__( 'Design Ops Toolkit', 'dap' ), 'category' => esc_html__( 'Design', 'dap' ), 'stock' => 22, 'price' => dap_format_currency_short( 2400 ), 'status' => 'success', 'status_label' => esc_html__( 'In Stock', 'dap' ) ],
                [ 'product' => esc_html__( 'Privilege Ads Booster', 'dap' ), 'category' => esc_html__( 'Ads', 'dap' ), 'stock' => 11, 'price' => dap_format_currency_short( 4600 ), 'status' => 'warning', 'status_label' => esc_html__( 'Low Stock', 'dap' ) ],
            ];
        }

        return $rows;
    }
}

if ( ! function_exists( 'dap_get_recent_orders_rows' ) ) {
    /**
     * Builds the Recent Orders dataset using comments or posts as stand-ins.
     *
     * @return array
     */
    function dap_get_recent_orders_rows() {
        $rows     = [];
        $currency = dap_get_currency_symbol();

        if ( dap_is_woocommerce_active() && function_exists( 'wc_get_orders' ) ) {
            $orders = wc_get_orders(
                [
                    'limit'   => 6,
                    'orderby' => 'date',
                    'order'   => 'DESC',
                    'status'  => apply_filters( 'dap_dashboard_recent_orders_statuses', [ 'completed', 'processing', 'on-hold', 'pending', 'failed' ] ),
                    'return'  => 'objects',
                ]
            );

            foreach ( $orders as $order ) {
                $status       = $order->get_status();
                $status_label = function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( $status ) : ucfirst( $status );
                $customer     = $order->get_formatted_billing_full_name();

                if ( ! $customer ) {
                    $customer = $order->get_billing_email() ? $order->get_billing_email() : esc_html__( 'Guest', 'dap' );
                }

                $date = $order->get_date_created();

                if ( $date ) {
                    $date = function_exists( 'wc_format_datetime' ) ? wc_format_datetime( $date, get_option( 'date_format' ) ) : $date->date_i18n( get_option( 'date_format' ) );
                } else {
                    $date = '';
                }

                $rows[] = [
                    'order'        => '#' . $order->get_order_number(),
                    'customer'     => $customer,
                    'amount'       => dap_format_currency_short( (float) $order->get_total(), $currency ),
                    'status'       => dap_get_order_status_accent( $status ),
                    'status_label' => $status_label,
                    'date'         => $date,
                ];
            }
        }

        if ( empty( $rows ) ) {
            $comments = get_comments(
                [
                    'number'  => 6,
                    'status'  => 'approve',
                    'orderby' => 'comment_date_gmt',
                    'order'   => 'DESC',
                ]
            );

            if ( ! empty( $comments ) && ! is_wp_error( $comments ) ) {
                foreach ( $comments as $comment ) {
                    $amount_seed = strlen( wp_strip_all_tags( $comment->comment_content ) );
                    $amount      = $amount_seed ? $amount_seed * 2.75 : wp_rand( 120, 480 );
                    $status      = ( $amount_seed % 3 === 0 ) ? 'warning' : 'success';
                    $status_label = ( 'warning' === $status ) ? esc_html__( 'Processing', 'dap' ) : esc_html__( 'Completed', 'dap' );

                    $rows[] = [
                        'order'        => sprintf( '#%1$05d', (int) $comment->comment_ID ),
                        'customer'     => $comment->comment_author ? $comment->comment_author : esc_html__( 'Guest', 'dap' ),
                        'amount'       => dap_format_currency_short( $amount, $currency ),
                        'status'       => $status,
                        'status_label' => $status_label,
                        'date'         => mysql2date( get_option( 'date_format' ), $comment->comment_date ),
                    ];
                }
            }
        }

        if ( empty( $rows ) ) {
            $rows = [
                [ 'order' => '#2451', 'customer' => 'Gabriela Mota', 'amount' => dap_format_currency_short( 1290 ), 'status' => 'success', 'status_label' => esc_html__( 'Completed', 'dap' ), 'date' => esc_html__( 'Hoje', 'dap' ) ],
                [ 'order' => '#2450', 'customer' => 'Leonardo Ramos', 'amount' => dap_format_currency_short( 870 ), 'status' => 'warning', 'status_label' => esc_html__( 'Processing', 'dap' ), 'date' => esc_html__( 'Ontem', 'dap' ) ],
                [ 'order' => '#2449', 'customer' => 'Ana Júlia', 'amount' => dap_format_currency_short( 1640 ), 'status' => 'success', 'status_label' => esc_html__( 'Completed', 'dap' ), 'date' => esc_html__( 'Ontem', 'dap' ) ],
                [ 'order' => '#2448', 'customer' => 'João Vitor', 'amount' => dap_format_currency_short( 540 ), 'status' => 'success', 'status_label' => esc_html__( 'Completed', 'dap' ), 'date' => esc_html__( '2 dias atrás', 'dap' ) ],
                [ 'order' => '#2447', 'customer' => 'Marina Lopes', 'amount' => dap_format_currency_short( 720 ), 'status' => 'danger', 'status_label' => esc_html__( 'Chargeback', 'dap' ), 'date' => esc_html__( '2 dias atrás', 'dap' ) ],
                [ 'order' => '#2446', 'customer' => 'Equipe CRM', 'amount' => dap_format_currency_short( 910 ), 'status' => 'success', 'status_label' => esc_html__( 'Completed', 'dap' ), 'date' => esc_html__( '3 dias atrás', 'dap' ) ],
            ];
        }

        return $rows;
    }
}

if ( ! function_exists( 'dap_get_activity_feed_items' ) ) {
    /**
     * Returns activity items from recent comments or posts.
     *
     * @return array
     */
    function dap_get_activity_feed_items() {
        $items  = [];
        $colors = [ 'primary', 'success', 'warning', 'info', 'danger' ];

        if ( dap_is_woocommerce_active() && function_exists( 'wc_get_orders' ) ) {
            $orders = wc_get_orders(
                [
                    'limit'   => 4,
                    'orderby' => 'date',
                    'order'   => 'DESC',
                    'status'  => apply_filters( 'dap_dashboard_activity_order_statuses', [ 'completed', 'processing', 'on-hold', 'pending' ] ),
                    'return'  => 'objects',
                ]
            );

            foreach ( $orders as $index => $order ) {
                $status_label = function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( $order->get_status() ) : ucfirst( $order->get_status() );
                $customer     = $order->get_formatted_billing_full_name();

                if ( ! $customer ) {
                    $customer = $order->get_billing_email() ? $order->get_billing_email() : esc_html__( 'Guest', 'dap' );
                }

                $items[] = [
                    'color' => $colors[ $index % count( $colors ) ],
                    'title' => sprintf(
                        /* translators: %s: order number */
                        esc_html__( 'Pedido %s atualizado', 'dap' ),
                        '#' . $order->get_order_number()
                    ),
                    'meta'  => sprintf(
                        /* translators: 1: order status, 2: customer name */
                        esc_html__( '%1$s · %2$s', 'dap' ),
                        $status_label,
                        $customer
                    ),
                ];
            }

            if ( ! empty( $items ) ) {
                return $items;
            }
        }

        $comments = get_comments(
            [
                'number'  => 4,
                'status'  => 'approve',
                'orderby' => 'comment_date_gmt',
                'order'   => 'DESC',
            ]
        );

        if ( ! empty( $comments ) && ! is_wp_error( $comments ) ) {
            foreach ( $comments as $index => $comment ) {
                $items[] = [
                    'color' => $colors[ $index % count( $colors ) ],
                    'title' => wp_trim_words( wp_strip_all_tags( $comment->comment_content ), 18, '…' ),
                    'meta'  => sprintf(
                        esc_html__( '%1$s ago · %2$s', 'dap' ),
                        human_time_diff( strtotime( $comment->comment_date_gmt . ' UTC' ), current_time( 'timestamp', true ) ),
                        $comment->comment_author ? $comment->comment_author : esc_html__( 'Guest', 'dap' )
                    ),
                ];
            }

            return $items;
        }

        $post_types = dap_get_dashboard_post_types();
        $posts      = get_posts(
            [
                'post_type'      => $post_types,
                'post_status'    => [ 'publish', 'draft', 'pending' ],
                'posts_per_page' => 4,
                'orderby'        => 'modified',
                'order'          => 'DESC',
            ]
        );

        if ( ! empty( $posts ) ) {
            foreach ( $posts as $index => $post ) {
                $items[] = [
                    'color' => $colors[ $index % count( $colors ) ],
                    'title' => get_the_title( $post ) ? get_the_title( $post ) : esc_html__( '(Sem título)', 'dap' ),
                    'meta'  => sprintf(
                        esc_html__( 'Atualizado %1$s atrás · %2$s', 'dap' ),
                        human_time_diff( get_post_modified_time( 'U', true, $post ), current_time( 'timestamp', true ) ),
                        get_the_author_meta( 'display_name', $post->post_author )
                    ),
                ];
            }
        }

        if ( empty( $items ) ) {
            $items = [
                [ 'color' => 'primary', 'title' => esc_html__( 'Design system tokens merged to main', 'dap' ), 'meta' => esc_html__( '09:24 · Added by Júlia Martins', 'dap' ) ],
                [ 'color' => 'success', 'title' => esc_html__( 'CRM automation paused for QA approval', 'dap' ), 'meta' => esc_html__( '11:03 · Workflow Bot', 'dap' ) ],
                [ 'color' => 'warning', 'title' => esc_html__( 'Media team awaiting creatives for Blitz', 'dap' ), 'meta' => esc_html__( '14:17 · Comment by Joana Reis', 'dap' ) ],
                [ 'color' => 'info', 'title' => esc_html__( 'Inbound squad scheduled content refresh', 'dap' ), 'meta' => esc_html__( '16:42 · Calendar Sync', 'dap' ) ],
            ];
        }

        return $items;
    }
}

if ( ! function_exists( 'dap_get_dashboard_notifications' ) ) {
    /**
     * Extracts notification items for the topbar dropdown.
     *
     * @return array
     */
    function dap_get_dashboard_notifications() {
        $items = [];

        if ( dap_is_woocommerce_active() && function_exists( 'wc_get_orders' ) ) {
            $orders = wc_get_orders(
                [
                    'limit'   => 3,
                    'orderby' => 'date',
                    'order'   => 'DESC',
                    'status'  => apply_filters( 'dap_dashboard_notification_order_statuses', [ 'processing', 'on-hold', 'pending' ] ),
                    'return'  => 'objects',
                ]
            );

            foreach ( $orders as $order ) {
                $status_label = function_exists( 'wc_get_order_status_name' ) ? wc_get_order_status_name( $order->get_status() ) : ucfirst( $order->get_status() );
                $customer     = $order->get_formatted_billing_full_name();

                if ( ! $customer ) {
                    $customer = $order->get_billing_email() ? $order->get_billing_email() : esc_html__( 'Guest', 'dap' );
                }

                $meta = $order->get_date_created() ? sprintf(
                    esc_html__( 'por %1$s · %2$s atrás', 'dap' ),
                    $customer,
                    human_time_diff( $order->get_date_created()->getTimestamp(), current_time( 'timestamp', true ) )
                ) : $customer;

                $items[] = [
                    'title' => sprintf(
                        /* translators: %s: order number */
                        esc_html__( 'Pedido %s %s', 'dap' ),
                        '#' . $order->get_order_number(),
                        $status_label
                    ),
                    'meta'  => $meta,
                    'icon'  => 'ri-shopping-cart-line',
                ];
            }

            if ( ! empty( $items ) ) {
                return $items;
            }
        }

        $comments = get_comments(
            [
                'number'  => 3,
                'status'  => 'approve',
                'orderby' => 'comment_date_gmt',
                'order'   => 'DESC',
            ]
        );

        if ( ! empty( $comments ) && ! is_wp_error( $comments ) ) {
            foreach ( $comments as $comment ) {
                $items[] = [
                    'title' => wp_trim_words( wp_strip_all_tags( $comment->comment_content ), 12, '…' ),
                    'meta'  => sprintf(
                        esc_html__( 'por %1$s · %2$s', 'dap' ),
                        $comment->comment_author ? $comment->comment_author : esc_html__( 'Visitante', 'dap' ),
                        human_time_diff( strtotime( $comment->comment_date_gmt . ' UTC' ), current_time( 'timestamp', true ) ) . ' ' . esc_html__( 'atrás', 'dap' )
                    ),
                    'icon'  => 'ri-message-3-line',
                ];
            }

            return $items;
        }

        return [
            [ 'title' => esc_html__( 'Novo briefing aguardando aprovação', 'dap' ), 'meta' => esc_html__( 'por Squad Media · há 2h', 'dap' ), 'icon' => 'ri-file-list-3-line' ],
            [ 'title' => esc_html__( 'Lead scoring ajustado para contas B2B', 'dap' ), 'meta' => esc_html__( 'por CRM Bot · há 4h', 'dap' ), 'icon' => 'ri-lightbulb-line' ],
            [ 'title' => esc_html__( 'Campanha Blitz atingiu 92% do orçamento', 'dap' ), 'meta' => esc_html__( 'por Finance · há 6h', 'dap' ), 'icon' => 'ri-pie-chart-line' ],
        ];
    }
}

if ( ! function_exists( 'dap_get_locale_label' ) ) {
    /**
     * Basic locale label mapper.
     *
     * @param string $locale Locale slug.
     *
     * @return string
     */
    function dap_get_locale_label( $locale ) {
        $map = [
            'pt_BR' => __( 'Português', 'dap' ),
            'en_US' => __( 'English', 'dap' ),
            'es_ES' => __( 'Español', 'dap' ),
            'fr_FR' => __( 'Français', 'dap' ),
        ];

        if ( isset( $map[ $locale ] ) ) {
            return $map[ $locale ];
        }

        $parts = explode( '_', $locale );

        if ( ! empty( $parts[0] ) ) {
            return ucfirst( $parts[0] );
        }

        return strtoupper( $locale );
    }
}

if ( ! function_exists( 'dap_get_switch_locale_url' ) ) {
    /**
     * Builds the admin-post URL responsible for switching the user locale.
     *
     * @param string $locale Locale slug. Empty string reverts to site default.
     *
     * @return string
     */
    function dap_get_switch_locale_url( $locale = '' ) {
        $args = [
            'action' => 'dap_switch_locale',
        ];

        if ( '' !== $locale ) {
            $args['locale'] = $locale;
        }

        $url = add_query_arg( $args, admin_url( 'admin-post.php' ) );

        return wp_nonce_url( $url, 'dap_switch_locale' );
    }
}

if ( ! function_exists( 'dap_get_dashboard_languages' ) ) {
    /**
     * Returns available locale options for the language dropdown.
     *
     * @return array
     */
    function dap_get_dashboard_languages() {
        $current_user_id  = get_current_user_id();
        $user_locale_meta = $current_user_id ? get_user_meta( $current_user_id, 'locale', true ) : '';
        $current_locale   = get_user_locale( $current_user_id );
        $site_locale      = get_locale();
        $languages        = get_available_languages();

        if ( empty( $languages ) ) {
            $languages = [ 'pt_BR', 'en_US', 'es_ES', 'fr_FR' ];
        }

        $options   = [];
        $options[] = [
            'code'   => '',
            'label'  => esc_html__( 'Padrão do site', 'dap' ),
            'active' => ( '' === $user_locale_meta || ! $user_locale_meta ),
            'url'    => dap_get_switch_locale_url( '' ),
        ];

        foreach ( array_unique( array_merge( [ $site_locale ], $languages ) ) as $locale ) {
            if ( '' === $locale ) {
                continue;
            }

            $options[] = [
                'code'   => $locale,
                'label'  => dap_get_locale_label( $locale ),
                'active' => ( $locale === $current_locale ),
                'url'    => dap_get_switch_locale_url( $locale ),
            ];
        }

        return apply_filters( 'dap_dashboard_languages', $options );
    }
}

if ( ! function_exists( 'dap_get_dashboard_topbar_data' ) ) {
    /**
     * Composes the dataset required to render the Modern topbar.
     *
     * @return array
     */
    function dap_get_dashboard_topbar_data() {
        $current_user = wp_get_current_user();
        $brand        = dap_get_brand_asset_urls();
        $languages    = dap_get_dashboard_languages();
        $notifications = dap_get_dashboard_notifications();
        $avatar       = get_avatar_url( $current_user->ID, [ 'size' => 64 ] );

        if ( ! $avatar ) {
            $avatar = 'https://secure.gravatar.com/avatar/?s=64&d=mp';
        }

        $roles = (array) $current_user->roles;
        $role  = ! empty( $roles ) ? $roles[0] : 'administrator';
        $role  = translate_user_role( ucwords( str_replace( '_', ' ', $role ) ) );

        return apply_filters(
            'dap_dashboard_topbar_data',
            [
                'site_name'        => get_bloginfo( 'name' ),
                'site_tagline'     => get_bloginfo( 'description' ),
                'logo_light'       => $brand['light'],
                'logo_dark'        => $brand['dark'],
                'logo_icon'        => $brand['icon'],
                'search_action'    => admin_url( 'edit.php' ),
                'notifications'    => $notifications,
                'languages'        => $languages,
                'user'             => [
                    'name'   => $current_user->display_name ? $current_user->display_name : esc_html__( 'Usuário', 'dap' ),
                    'email'  => $current_user->user_email,
                    'role'   => $role,
                    'avatar' => $avatar,
                    'profile_url' => admin_url( 'profile.php' ),
                    'logout_url'  => wp_logout_url(),
                ],
                'customizer_url'   => admin_url( 'customize.php' ),
            ]
        );
    }
}

if ( ! function_exists( 'dap_get_important_project_items' ) ) {
    /**
     * Returns highlighted projects (sticky posts or most recent pages).
     *
     * @return array
     */
    function dap_get_important_project_items() {
        $post_types = dap_get_dashboard_post_types();
        $items      = [];

        $args = [
            'post_type'      => $post_types,
            'post_status'    => [ 'publish', 'draft', 'pending' ],
            'posts_per_page' => 3,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ];

        $sticky = get_option( 'sticky_posts', [] );

        if ( ! empty( $sticky ) ) {
            $args['post__in'] = $sticky;
            $args['orderby']  = 'post__in';
        }

        $posts = get_posts( $args );

        if ( ! empty( $posts ) ) {
            foreach ( $posts as $post ) {
                $items[] = [
                    'name'  => get_the_title( $post ) ? get_the_title( $post ) : esc_html__( '(Sem título)', 'dap' ),
                    'badge' => ( 'publish' === $post->post_status ) ? 'success' : 'warning',
                    'status' => sprintf(
                        esc_html__( 'Atualizado %s atrás', 'dap' ),
                        human_time_diff( get_post_modified_time( 'U', true, $post ), current_time( 'timestamp', true ) )
                    ),
                ];
            }
        }

        if ( empty( $items ) ) {
            $items = [
                [ 'name' => esc_html__( 'Experience Hub 2.0', 'dap' ), 'badge' => 'primary', 'status' => esc_html__( 'Milestone due tomorrow', 'dap' ) ],
                [ 'name' => esc_html__( 'Privilege Rewards Launch', 'dap' ), 'badge' => 'success', 'status' => esc_html__( 'Go-live confirmed', 'dap' ) ],
                [ 'name' => esc_html__( 'Retail Analytics Dashboard', 'dap' ), 'badge' => 'info', 'status' => esc_html__( 'Stakeholder review Friday', 'dap' ) ],
            ];
        }

        return $items;
    }
}

if ( ! function_exists( 'dap_get_widget_area_edit_link' ) ) {
    /**
     * Retrieves the edit link for the widget area.
     *
     * @return string
     */
    function dap_get_widget_area_edit_link() {
        $post_id = dap_get_widget_area_post_id();

        return $post_id ? get_edit_post_link( $post_id, '' ) : '';
    }
}

if ( ! function_exists( 'dap_get_monthly_post_counts' ) ) {
    /**
     * Retrieves published post counts grouped by month.
     *
     * @param array $post_types Post types.
     * @param int   $months     Number of months to include.
     *
     * @return array
     */
    function dap_get_monthly_post_counts( $post_types, $months = 12 ) {
        global $wpdb;

        $months   = max( 1, (int) $months );
        $now_gmt  = current_time( 'timestamp', true );
        $start_ts = strtotime( gmdate( 'Y-m-01 00:00:00', $now_gmt ) . ' -' . ( $months - 1 ) . ' months' );
        $start    = gmdate( 'Y-m-d H:i:s', $start_ts );

        $sql_template = "
            SELECT DATE_FORMAT(post_date_gmt, '%%Y-%%m') AS period_key, COUNT(ID) AS total
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'
                AND post_type IN (%s)
                AND post_date_gmt >= %s
            GROUP BY period_key
        ";

        $prepared = dap_prepare_post_type_query( $sql_template, $post_types, [ $start ] );
        $results  = $prepared ? $wpdb->get_results( $prepared, ARRAY_A ) : [];
        $map      = [];

        foreach ( $results as $row ) {
            $map[ $row['period_key'] ] = (int) $row['total'];
        }

        $data = [];

        for ( $i = $months - 1; $i >= 0; $i-- ) {
            $timestamp = strtotime( '-' . $i . ' months', $now_gmt );
            $key       = gmdate( 'Y-m', $timestamp );
            $label     = wp_date( 'M', $timestamp );

            $data[] = [
                'label' => $label,
                'value' => isset( $map[ $key ] ) ? (int) $map[ $key ] : 0,
            ];
        }

        return $data;
    }
}

if ( ! function_exists( 'dap_get_daily_post_counts' ) ) {
    /**
     * Retrieves published post counts grouped by day for the last X days.
     *
     * @param array $post_types Post types.
     * @param int   $days       Number of days to include.
     *
     * @return array
     */
    function dap_get_daily_post_counts( $post_types, $days = 7 ) {
        global $wpdb;

        $days     = max( 1, (int) $days );
        $now_gmt  = current_time( 'timestamp', true );
        $start_ts = $now_gmt - ( ( $days - 1 ) * DAY_IN_SECONDS );
        $start    = gmdate( 'Y-m-d 00:00:00', $start_ts );

        $sql_template = "
            SELECT DATE_FORMAT(post_date_gmt, '%%Y-%%m-%%d') AS period_key, COUNT(ID) AS total
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'
                AND post_type IN (%s)
                AND post_date_gmt >= %s
            GROUP BY period_key
        ";

        $prepared = dap_prepare_post_type_query( $sql_template, $post_types, [ $start ] );
        $results  = $prepared ? $wpdb->get_results( $prepared, ARRAY_A ) : [];
        $map      = [];

        foreach ( $results as $row ) {
            $map[ $row['period_key'] ] = (int) $row['total'];
        }

        $data = [];

        for ( $i = $days - 1; $i >= 0; $i-- ) {
            $timestamp = strtotime( '-' . $i . ' days', $now_gmt );
            $key       = gmdate( 'Y-m-d', $timestamp );
            $label     = wp_date( 'D', $timestamp );

            $data[] = [
                'label' => $label,
                'value' => isset( $map[ $key ] ) ? (int) $map[ $key ] : 0,
            ];
        }

        return $data;
    }
}

if ( ! function_exists( 'dap_get_today_segments_counts' ) ) {
    /**
     * Buckets today's published posts into six segments.
     *
     * @param array $post_types Post types.
     *
     * @return array
     */
    function dap_get_today_segments_counts( $post_types ) {
        global $wpdb;

        $now_gmt     = current_time( 'timestamp', true );
        $start_of_day = strtotime( gmdate( 'Y-m-d 00:00:00', $now_gmt ) );
        $start        = gmdate( 'Y-m-d H:i:s', $start_of_day );

        $sql_template = "
            SELECT DATE_FORMAT(post_date_gmt, '%%H') AS hour_slot, COUNT(ID) AS total
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'
                AND post_type IN (%s)
                AND post_date_gmt >= %s
            GROUP BY hour_slot
        ";

        $prepared = dap_prepare_post_type_query( $sql_template, $post_types, [ $start ] );
        $results  = $prepared ? $wpdb->get_results( $prepared, ARRAY_A ) : [];

        $hour_counts = array_fill( 0, 24, 0 );

        foreach ( $results as $row ) {
            $hour = (int) $row['hour_slot'];

            if ( $hour >= 0 && $hour < 24 ) {
                $hour_counts[ $hour ] = (int) $row['total'];
            }
        }

        $segments = [
            [ 'start' => 0, 'end' => 3, 'label' => '00h' ],
            [ 'start' => 4, 'end' => 7, 'label' => '04h' ],
            [ 'start' => 8, 'end' => 11, 'label' => '08h' ],
            [ 'start' => 12, 'end' => 15, 'label' => '12h' ],
            [ 'start' => 16, 'end' => 19, 'label' => '16h' ],
            [ 'start' => 20, 'end' => 23, 'label' => '20h' ],
        ];

        $labels = [];
        $values = [];

        foreach ( $segments as $segment ) {
            $labels[] = $segment['label'];
            $sum      = 0;

            for ( $hour = $segment['start']; $hour <= $segment['end']; $hour++ ) {
                $sum += $hour_counts[ $hour ];
            }

            $values[] = $sum;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}

if ( ! function_exists( 'dap_get_email_categories_data' ) ) {
    /**
     * Builds donut chart data based on the most used categories.
     *
     * @return array
     */
    function dap_get_email_categories_data() {
        $terms = get_terms(
            [
                'taxonomy'   => 'category',
                'orderby'    => 'count',
                'order'      => 'DESC',
                'hide_empty' => false,
                'number'     => 4,
            ]
        );

        $labels = [];
        $series = [];

        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            foreach ( $terms as $term ) {
                $labels[] = $term->name;
                $series[] = (int) $term->count;
            }
        }

        if ( empty( $labels ) ) {
            $labels = [
                esc_html__( 'Campanhas sazonais', 'dap' ),
                esc_html__( 'Fluxos automáticos', 'dap' ),
                esc_html__( 'Nutrição leads', 'dap' ),
                esc_html__( 'Transacionais', 'dap' ),
            ];

            $series = [ 45, 28, 19, 8 ];
        }

        return [
            'labels'    => $labels,
            'series'    => $series,
            'colors'    => [ '#4f46e5', '#10b981', '#f59e0b', '#38bdf8' ],
            'ui_colors' => [ 'primary', 'success', 'warning', 'info' ],
        ];
    }
}

if ( ! function_exists( 'dap_get_chart_data_payload' ) ) {
    /**
     * Composes the chart dataset shared with the JS layer.
     *
     * @param array $kpis KPI entries.
     *
     * @return array
     */
    function dap_get_chart_data_payload( $kpis ) {
        $post_types      = dap_get_dashboard_post_types();
        $monthly         = dap_get_monthly_post_counts( $post_types );
        $weekly          = dap_get_daily_post_counts( $post_types );
        $today           = dap_get_today_segments_counts( $post_types );
        $email_data      = dap_get_email_categories_data();
        $progress_percent = 72;

        foreach ( $kpis as $kpi ) {
            if ( isset( $kpi['key'] ) && 'on_progress' === $kpi['key'] && isset( $kpi['value_raw'] ) ) {
                $progress_percent = (int) $kpi['value_raw'];
                break;
            }
        }

        return [
            'projectStatistics' => [
                'series'     => [
                    'monthly' => wp_list_pluck( $monthly, 'value' ),
                    'weekly'  => wp_list_pluck( $weekly, 'value' ),
                    'today'   => isset( $today['values'] ) ? $today['values'] : [ 1, 2, 2, 3, 3, 4 ],
                ],
                'categories' => [
                    'monthly' => wp_list_pluck( $monthly, 'label' ),
                    'weekly'  => wp_list_pluck( $weekly, 'label' ),
                    'today'   => isset( $today['labels'] ) ? $today['labels'] : [ '08h', '10h', '12h', '14h', '16h', '18h' ],
                ],
            ],
            'radialProgress' => [
                'value' => $progress_percent,
            ],
            'emailCategories' => $email_data,
        ];
    }
}

if ( ! function_exists( 'dap_get_dashboard_data' ) ) {
    /**
     * Returns the dashboard data structure shared between PHP and JS.
     *
     * @return array
     */
    function dap_get_dashboard_data() {
        static $runtime_cache = null;

        if ( null !== $runtime_cache ) {
            return $runtime_cache;
        }

        $transient = get_transient( dap_get_dashboard_cache_key() );

        if ( false !== $transient && is_array( $transient ) ) {
            if ( empty( $transient['generated_at_gmt'] ) ) {
                $transient['generated_at_gmt'] = current_time( 'mysql', true );
            }

            if ( empty( $transient['generated_at_local'] ) && ! empty( $transient['generated_at_gmt'] ) ) {
                $transient['generated_at_local'] = get_date_from_gmt( $transient['generated_at_gmt'] );
            }

            if ( empty( $transient['generated_timestamp'] ) && ! empty( $transient['generated_at_gmt'] ) ) {
                $transient['generated_timestamp'] = strtotime( $transient['generated_at_gmt'] . ' UTC' );
            }

            $runtime_cache = $transient;

            return $runtime_cache;
        }

        $kpis             = dap_get_dashboard_kpis();
        $sales_cards      = dap_get_dashboard_sales_cards( $kpis );
        $sales_summary    = dap_get_dashboard_sales_summary( $kpis );
        $inventory_rows   = dap_get_product_inventory_rows();
        $orders_rows      = dap_get_recent_orders_rows();
        $charts           = dap_get_chart_data_payload( $kpis );

        if ( isset( $charts['projectStatistics'] ) && ! isset( $charts['salesAnalytics'] ) ) {
            $charts['salesAnalytics'] = $charts['projectStatistics'];
        }

        $generated_at_gmt = current_time( 'mysql', true );
        $cache            = [
            'kpis'               => $kpis,
            'sales_cards'        => $sales_cards,
            'sales_summary'      => $sales_summary,
            'project_rows'       => dap_get_recent_projects_table(),
            'inventory_rows'     => $inventory_rows,
            'orders_rows'        => $orders_rows,
            'activity_items'     => dap_get_activity_feed_items(),
            'important_projects' => dap_get_important_project_items(),
            'charts'             => $charts,
            'generated_at_gmt'   => $generated_at_gmt,
            'generated_at_local' => current_time( 'mysql', false ),
            'generated_timestamp' => strtotime( $generated_at_gmt . ' UTC' ),
        ];

        $cache          = apply_filters( 'dap_dashboard_data', $cache );
        $runtime_cache  = $cache;
        $cache_lifetime = apply_filters( 'dap_dashboard_cache_ttl', 5 * MINUTE_IN_SECONDS );

        if ( $cache_lifetime > 0 ) {
            set_transient( dap_get_dashboard_cache_key(), $cache, (int) $cache_lifetime );
        }

        return $runtime_cache;
    }
}

if ( ! function_exists( 'dap_record_error_log' ) ) {
    /**
     * Records a plugin-specific error log entry.
     *
     * @param string $message Error message.
     * @param array  $context Optional associative context values.
     */
    function dap_record_error_log( $message, $context = [] ) {
        if ( empty( $message ) ) {
            return;
        }

        $logs = get_option( 'dap_error_logs', [] );

        if ( ! is_array( $logs ) ) {
            $logs = [];
        }

        $sanitized_context = [];

        if ( ! empty( $context ) && is_array( $context ) ) {
            foreach ( $context as $key => $value ) {
                if ( is_scalar( $value ) ) {
                    $sanitized_context[ sanitize_text_field( (string) $key ) ] = sanitize_text_field( (string) $value );
                }
            }
        }

        array_unshift(
            $logs,
            [
                'time'    => current_time( 'mysql' ),
                'message' => sanitize_text_field( $message ),
                'context' => $sanitized_context,
            ]
        );

        $logs = array_slice( $logs, 0, 50 );

        update_option( 'dap_error_logs', $logs, false );
    }
}

if ( ! function_exists( 'dap_get_error_logs' ) ) {
    /**
     * Retrieves the stored plugin error logs.
     *
     * @param int $limit Maximum number of entries to return.
     *
     * @return array
     */
    function dap_get_error_logs( $limit = 25 ) {
        $logs = get_option( 'dap_error_logs', [] );

        if ( ! is_array( $logs ) ) {
            $logs = [];
        }

        $logs = array_slice( $logs, 0, absint( $limit ) );

        /**
         * Filters the dashboard error logs before they are rendered.
         *
         * @param array $logs Error logs.
         */
        return apply_filters( 'dap_error_logs', $logs );
    }
}

if ( ! function_exists( 'dap_clear_error_logs' ) ) {
    /**
     * Clears all stored plugin error logs.
     */
    function dap_clear_error_logs() {
        delete_option( 'dap_error_logs' );
    }
}

if ( ! function_exists( 'dap_get_dashboard_diagnostics' ) ) {
    /**
     * Builds a diagnostics payload describing the plugin status.
     *
     * @param array $dashboard Current dashboard dataset.
     *
     * @return array
     */
    function dap_get_dashboard_diagnostics( $dashboard = [] ) {
        $settings              = dap_get_settings();
        $has_ubold_assets      = dap_has_local_ubold_assets();
        $global_skin_enabled   = ! empty( $settings['enable_global_skin'] );
        $widget_area_id        = dap_get_widget_area_post_id();
        $widget_area_exists    = ! empty( $widget_area_id );
        $widget_area_status    = $widget_area_exists ? get_post_status( $widget_area_id ) : '';
        $widget_area_edit_link = '';

        if ( $widget_area_exists ) {
            $edit_link = get_edit_post_link( $widget_area_id, '' );

            if ( $edit_link ) {
                $widget_area_edit_link = $edit_link;
            }
        }

        $elementor_ready = did_action( 'elementor/loaded' ) && class_exists( '\\Elementor\\Plugin' );
        $cache_key       = dap_get_dashboard_cache_key();
        $raw_cache       = get_transient( $cache_key );
        $cache_state     = ( false !== $raw_cache && is_array( $raw_cache ) ) ? 'hit' : 'miss';

        $dataset = is_array( $dashboard ) ? $dashboard : [];

        $generated_at_gmt    = isset( $dataset['generated_at_gmt'] ) ? $dataset['generated_at_gmt'] : '';
        $generated_at_local  = isset( $dataset['generated_at_local'] ) ? $dataset['generated_at_local'] : '';
        $generated_timestamp = isset( $dataset['generated_timestamp'] ) ? (int) $dataset['generated_timestamp'] : 0;

        if ( ! $generated_at_gmt && is_array( $raw_cache ) && isset( $raw_cache['generated_at_gmt'] ) ) {
            $generated_at_gmt = $raw_cache['generated_at_gmt'];
        }

        if ( ! $generated_at_local && is_array( $raw_cache ) && isset( $raw_cache['generated_at_local'] ) ) {
            $generated_at_local = $raw_cache['generated_at_local'];
        }

        if ( ! $generated_timestamp && is_array( $raw_cache ) && isset( $raw_cache['generated_timestamp'] ) ) {
            $generated_timestamp = (int) $raw_cache['generated_timestamp'];
        }

        $generated_label = '';
        $date_format     = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

        if ( $generated_at_gmt ) {
            $generated_label = get_date_from_gmt( $generated_at_gmt, $date_format );
        } elseif ( $generated_at_local ) {
            $generated_label = mysql2date( $date_format, $generated_at_local );
        } elseif ( $generated_timestamp ) {
            $generated_label = date_i18n( $date_format, $generated_timestamp );
        }

        $settings_url      = admin_url( 'options-general.php?page=dap-settings' );
        $widget_manage_url = admin_url( 'index.php?page=dap-dashboard-widgets' );

        $woocommerce = [ 'active' => dap_is_woocommerce_active() ];

        if ( $woocommerce['active'] ) {
            $wc_metrics = dap_get_woocommerce_sales_metrics();

            $woocommerce['orders_week']   = isset( $wc_metrics['week_count'] ) ? (int) $wc_metrics['week_count'] : 0;
            $woocommerce['pending']       = isset( $wc_metrics['pending_invoices'] ) ? (int) $wc_metrics['pending_invoices'] : 0;
            $woocommerce['currency']      = isset( $wc_metrics['currency'] ) ? $wc_metrics['currency'] : dap_get_currency_symbol();
            $woocommerce['customers']     = isset( $wc_metrics['customers_total'] ) ? (int) $wc_metrics['customers_total'] : 0;
            $woocommerce['month_total']   = isset( $wc_metrics['month_total'] ) ? (float) $wc_metrics['month_total'] : 0.0;
        }

        $diagnostics = [
            'has_ubold_assets'    => $has_ubold_assets,
            'global_skin_enabled' => $global_skin_enabled,
            'widget_area'         => [
                'exists'         => $widget_area_exists,
                'id'             => $widget_area_exists ? (int) $widget_area_id : 0,
                'status'         => $widget_area_status,
                'edit_link'      => $widget_area_edit_link,
                'manage_url'     => $widget_manage_url,
                'elementor_ready' => $elementor_ready,
            ],
            'dataset'             => [
                'state'            => $cache_state,
                'generated_label'  => $generated_label,
                'kpi_count'        => isset( $dataset['kpis'] ) && is_array( $dataset['kpis'] ) ? count( $dataset['kpis'] ) : 0,
                'projects_count'   => isset( $dataset['project_rows'] ) && is_array( $dataset['project_rows'] ) ? count( $dataset['project_rows'] ) : 0,
                'inventory_count'  => isset( $dataset['inventory_rows'] ) && is_array( $dataset['inventory_rows'] ) ? count( $dataset['inventory_rows'] ) : 0,
                'orders_count'     => isset( $dataset['orders_rows'] ) && is_array( $dataset['orders_rows'] ) ? count( $dataset['orders_rows'] ) : 0,
            ],
            'settings_url'        => $settings_url,
            'woocommerce'         => $woocommerce,
        ];

        /**
         * Filters the dashboard diagnostics array.
         *
         * @param array $diagnostics Diagnostics payload.
         * @param array $dashboard   Dashboard dataset.
         */
        return apply_filters( 'dap_dashboard_diagnostics', $diagnostics, $dashboard );
    }
}
