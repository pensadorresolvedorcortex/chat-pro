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
            'enable_global_skin' => 0,
        ];
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
