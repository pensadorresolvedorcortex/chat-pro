<?php
/**
 * Helpers to resolve JuntaPlay thank-you URLs for PIX polling.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'juntaplay_get_thankyou_url' ) ) {
    /**
     * Retrieve the JuntaPlay thank-you URL for a given order.
     *
     * Falls back to the WooCommerce order received URL and finally to
     * home_url('/meus-grupos/') when a dedicated JuntaPlay destination
     * cannot be detected.
     *
     * @param int $order_id Order identifier.
     *
     * @return string
     */
    function juntaplay_get_thankyou_url( $order_id = 0 ) {
        $order_id = absint( $order_id );
        $redirect = '';

        // Allow native JuntaPlay implementations to override this helper while keeping the same function name.
        if ( function_exists( 'juntaplay_resolve_thankyou_url' ) ) {
            $redirect = (string) juntaplay_resolve_thankyou_url( $order_id );
        }

        if ( empty( $redirect ) && $order_id && function_exists( 'wc_get_order' ) ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $redirect = $order->get_checkout_order_received_url();
            }
        }

        if ( empty( $redirect ) ) {
            $redirect = home_url( '/meus-grupos/' );
        }

        return $redirect;
    }
}
