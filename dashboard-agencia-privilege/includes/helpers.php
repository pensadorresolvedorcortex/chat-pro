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
        $post_id = dap_get_widget_area_post_id();

        if ( ! $post_id ) {
            return '';
        }

        if ( did_action( 'elementor/loaded' ) && class_exists( '\\Elementor\\Plugin' ) ) {
            return \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $post_id, true );
        }

        $post = get_post( $post_id );

        if ( ! $post || 'trash' === $post->post_status ) {
            return '';
        }

        setup_postdata( $post );
        $content = apply_filters( 'the_content', $post->post_content );
        wp_reset_postdata();

        return $content;
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

if ( ! function_exists( 'dap_get_activity_feed_items' ) ) {
    /**
     * Returns activity items from recent comments or posts.
     *
     * @return array
     */
    function dap_get_activity_feed_items() {
        $items  = [];
        $colors = [ 'primary', 'success', 'warning', 'info', 'danger' ];

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
        $generated_at_gmt = current_time( 'mysql', true );
        $cache            = [
            'kpis'               => $kpis,
            'project_rows'       => dap_get_recent_projects_table(),
            'activity_items'     => dap_get_activity_feed_items(),
            'important_projects' => dap_get_important_project_items(),
            'charts'             => dap_get_chart_data_payload( $kpis ),
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
            ],
            'settings_url'        => $settings_url,
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
