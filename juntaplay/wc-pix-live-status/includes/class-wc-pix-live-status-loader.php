<?php
/**
 * Loader for scripts and hooks.
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

class WC_Pix_Live_Status_Loader {
/**
 * Set up hooks.
 */
public function setup() {
add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
}

/**
 * Enqueue polling script only when needed.
 */
public function enqueue_scripts() {
if ( ! $this->should_enqueue() ) {
return;
}

$order    = $this->get_order_from_request();
$order_id = $order ? $order->get_id() : 0;
if ( ! $order || ! $order_id ) {
return;
}

        $polling_interval = apply_filters( 'wc_pix_live_status_polling_interval', 5 );
        // Localize the WooCommerce order-received URL (JuntaPlay customizes this page) so the frontend can redirect
        // immediately after PIX approval.
        $redirect_url     = apply_filters( 'wc_pix_live_status_redirect', juntaplay_get_thankyou_url( $order_id ), $order_id );

wp_register_script(
'wc-pix-live-status-polling',
WC_PLS_URL . 'assets/js/pix-polling.js',
array( 'jquery' ),
WC_PLS_VERSION,
true
);

wp_localize_script(
'wc-pix-live-status-polling',
'WCPixLiveStatus',
array(
'order_id'         => $order_id,
'order_key'        => $order->get_order_key(),
'polling_interval' => absint( $polling_interval ),
'redirect_url'     => esc_url_raw( $redirect_url ),
'endpoint'         => esc_url_raw( rest_url( 'wc-pix-check/v1/status' ) ),
)
);

wp_enqueue_script( 'wc-pix-live-status-polling' );
}

/**
 * Determines if the script should be enqueued.
 *
 * @return bool
 */
private function should_enqueue() {
if ( is_admin() || ! function_exists( 'is_checkout' ) ) {
return false;
}

$order = $this->get_order_from_request();
if ( ! $order ) {
return false;
}

if ( is_user_logged_in() && (int) $order->get_user_id() !== get_current_user_id() ) {
return false;
}

// Only on payment or order-received pages.
if ( ! ( is_checkout_pay_page() || is_order_received_page() ) ) {
return false;
}

// Ensure Mercado Pago PIX is selected.
$payment_method = $order->get_payment_method();
if ( 'woo-mercado-pago-pix' !== $payment_method && 'woo-mercado-pago-custom-pix' !== $payment_method ) {
return false;
}

// Do not enqueue when already completed/processing.
if ( in_array( $order->get_status(), array( 'processing', 'completed' ), true ) ) {
return false;
}

return true;
}

/**
 * Retrieve order from request data, validating order key when available.
 *
 * @return WC_Order|false
 */
private function get_order_from_request() {
$order_id = absint( $_GET['order_id'] ?? 0 );
$order    = $order_id ? wc_get_order( $order_id ) : false;

if ( ! $order && ! empty( $_GET['key'] ) ) {
$order_key = wc_clean( wp_unslash( $_GET['key'] ) );
$order_id  = wc_get_order_id_by_order_key( $order_key );
$order     = $order_id ? wc_get_order( $order_id ) : false;
}

if ( ! $order instanceof WC_Order ) {
return false;
}

if ( ! empty( $_GET['key'] ) ) {
$order_key = wc_clean( wp_unslash( $_GET['key'] ) );
if ( $order_key !== $order->get_order_key() ) {
return false;
}
}

return $order;
}
}
