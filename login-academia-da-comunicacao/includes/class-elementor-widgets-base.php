<?php
/**
 * Shared Elementor widget utilities for the plugin.
 *
 * @package ADC\Login\Elementor
 */

namespace ADC\Login\Elementor\Widgets;

use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Base widget with helper methods for the ADC login widgets.
 */
abstract class Base extends Widget_Base {

    /**
     * Execute a render callback while temporarily applying filters.
     *
     * @param string   $shortcode Shortcode tag to render.
     * @param callable $renderer  Callback responsible for producing the HTML output.
     * @param array    $filters   Associative array of filters to attach during rendering.
     */
    protected function render_with_filters( $shortcode, callable $renderer, array $filters = array() ) {
        $attached = array();

        foreach ( $filters as $tag => $config ) {
            if ( is_callable( $config ) ) {
                $callback      = $config;
                $priority      = 10;
                $accepted_args = 1;
            } elseif ( is_array( $config ) && isset( $config['callback'] ) && is_callable( $config['callback'] ) ) {
                $callback      = $config['callback'];
                $priority      = isset( $config['priority'] ) ? (int) $config['priority'] : 10;
                $accepted_args = isset( $config['accepted_args'] ) ? (int) $config['accepted_args'] : 1;
            } else {
                continue;
            }

            add_filter( $tag, $callback, $priority, $accepted_args );
            $attached[] = array( $tag, $callback, $priority );
        }

        $output = '';

        if ( shortcode_exists( $shortcode ) ) {
            $output = (string) call_user_func( $renderer );
        } else {
            $output = sprintf(
                '<div class="adc-login-notice">%s</div>',
                esc_html__( 'O shortcode solicitado não está disponível.', 'login-academia-da-comunicacao' )
            );
        }

        foreach ( array_reverse( $attached ) as $item ) {
            remove_filter( $item[0], $item[1], $item[2] );
        }

        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Extract an attachment or direct URL from an Elementor media control.
     *
     * @param array|string $media_control Media control payload from Elementor.
     * @param string       $fallback      Fallback URL if none provided.
     *
     * @return string
     */
    protected function parse_media_url( $media_control, $fallback = '' ) {
        $url = '';

        if ( is_array( $media_control ) ) {
            if ( ! empty( $media_control['id'] ) ) {
                $attachment_id = absint( $media_control['id'] );
                if ( $attachment_id ) {
                    $url = wp_get_attachment_image_url( $attachment_id, 'full' );
                }
            }

            if ( empty( $url ) && ! empty( $media_control['url'] ) ) {
                $url = $media_control['url'];
            }
        } elseif ( is_string( $media_control ) ) {
            $url = $media_control;
        }

        if ( empty( $url ) && ! empty( $fallback ) ) {
            $url = $fallback;
        }

        return esc_url_raw( $url );
    }
}
