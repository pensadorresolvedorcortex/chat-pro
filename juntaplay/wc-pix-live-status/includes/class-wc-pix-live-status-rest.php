<?php
/**
 * REST API for PIX status polling.
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

class WC_Pix_Live_Status_REST {
/**
 * Register routes.
 */
public function register_routes() {
add_action( 'rest_api_init', array( $this, 'register_status_route' ) );
}

/**
 * Registers status endpoint.
 */
public function register_status_route() {
register_rest_route(
'wc-pix-check/v1',
'/status',
array(
'args'   => array(
'order_id' => array(
'required'          => true,
'validate_callback' => array( $this, 'validate_order_id' ),
'sanitize_callback' => 'absint',
),
'order_key' => array(
'required'          => false,
'sanitize_callback' => 'wc_clean',
),
),
'methods'             => WP_REST_Server::READABLE,
'callback'            => array( $this, 'get_status' ),
'permission_callback' => '__return_true',
)
);
}

/**
 * Validate order id.
 *
 * @param int $order_id Order ID.
 *
 * @return bool
 */
public function validate_order_id( $order_id ) {
return is_numeric( $order_id ) && $order_id > 0;
}

/**
 * Returns sanitized status for polling.
 *
 * @param WP_REST_Request $request Request object.
 *
 * @return WP_REST_Response|WP_Error
 */
public function get_status( WP_REST_Request $request ) {
$order_id = absint( $request->get_param( 'order_id' ) );
$order_key = $request->get_param( 'order_key' );
if ( ! $order_id ) {
return new WP_Error( 'invalid_order_id', __( 'ID de pedido inválido.', 'wc-pix-live-status' ), array( 'status' => 400 ) );
}

$order = wc_get_order( $order_id );
if ( ! $order ) {
return new WP_Error( 'order_not_found', __( 'Pedido não encontrado.', 'wc-pix-live-status' ), array( 'status' => 404 ) );
}

if ( ! is_user_logged_in() && empty( $order_key ) ) {
return new WP_Error( 'forbidden', __( 'Chave do pedido ausente.', 'wc-pix-live-status' ), array( 'status' => 403 ) );
}

if ( $order_key && $order_key !== $order->get_order_key() ) {
return new WP_Error( 'forbidden', __( 'A chave do pedido não confere.', 'wc-pix-live-status' ), array( 'status' => 403 ) );
}

if ( is_user_logged_in() && (int) $order->get_user_id() !== get_current_user_id() ) {
return new WP_Error( 'forbidden', __( 'Você não tem permissão para ver este pedido.', 'wc-pix-live-status' ), array( 'status' => 403 ) );
}

        $status        = $order->get_status();
        $is_paid       = in_array( $status, array( 'processing', 'completed' ), true );
        // Resolve JuntaPlay thank-you page; helper will fall back to WooCommerce received URL and then /meus-grupos/.
        $redirect_url  = juntaplay_get_thankyou_url( $order_id );

        if ( empty( $redirect_url ) ) {
            $redirect_url = home_url( '/meus-grupos/' );
        }

        // Allow integrators to override the final redirect when PIX is approved.
        $redirect_url  = apply_filters( 'wc_pix_live_status_redirect', $redirect_url, $order_id );
        $response_data = array(
            'status'   => $status,
            'paid'     => (bool) $is_paid,
            'redirect' => esc_url_raw( $redirect_url ),
        );

return rest_ensure_response( $response_data );
}
}
