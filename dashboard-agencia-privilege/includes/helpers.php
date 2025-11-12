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
