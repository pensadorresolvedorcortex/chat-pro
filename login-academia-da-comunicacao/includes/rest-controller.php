<?php
/**
 * Custom REST API endpoints.
 *
 * @package ADC\Login\REST
 */

namespace ADC\Login\REST;

use ADC\Login\Auth\Authentication;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function ADC\Login\get_option_value;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Controller
 */
class Controller extends WP_REST_Controller {

    /**
     * Authentication handler.
     *
     * @var Authentication
     */
    protected $auth;

    /**
     * Two factor manager.
     *
     * @var \ADC\Login\TwoFA\Manager
     */
    protected $twofa;

    /**
     * Constructor.
     *
     * @param Authentication                $auth  Authentication handler.
     * @param \ADC\Login\TwoFA\Manager $twofa Two factor manager.
     */
    public function __construct( Authentication $auth, $twofa ) {
        $this->auth  = $auth;
        $this->twofa = $twofa;
        $this->namespace = 'adc/v1';
        add_filter( 'rest_pre_serve_request', array( $this, 'maybe_add_cors_headers' ), 10, 4 );
    }

    /**
     * Register routes.
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/signup',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'signup' ),
                    'permission_callback' => array( $this, 'permission_callback' ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/login',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'login' ),
                    'permission_callback' => array( $this, 'permission_callback' ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/2fa/send',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'resend_twofa' ),
                    'permission_callback' => array( $this, 'permission_callback' ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/2fa/verify',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'verify_twofa' ),
                    'permission_callback' => array( $this, 'permission_callback' ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/social/login',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'social_login' ),
                    'permission_callback' => array( $this, 'permission_callback' ),
                ),
            )
        );
    }

    /**
     * Validate nonce when provided.
     *
     * @return bool|WP_Error
     */
    public function permission_callback() {
        $nonce = null;

        if ( isset( $_REQUEST['adc_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $nonce = sanitize_text_field( wp_unslash( $_REQUEST['adc_nonce'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        } elseif ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $nonce = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) );
        }

        if ( ! $this->is_origin_allowed() ) {
            return new WP_Error( 'adc_rest_origin', __( 'Origem não permitida.', 'login-academia-da-comunicacao' ), array( 'status' => 403 ) );
        }

        if ( null === $nonce ) {
            return true;
        }

        if ( wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return true;
        }

        return new WP_Error( 'adc_rest_nonce', __( 'Nonce inválido.', 'login-academia-da-comunicacao' ), array( 'status' => 403 ) );
    }

    /**
     * Handle REST signup.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function signup( WP_REST_Request $request ) {
        $payload = array(
            'name'             => $request->get_param( 'name' ),
            'email'            => $request->get_param( 'email' ),
            'password'         => $request->get_param( 'password' ),
            'password_confirm' => $request->get_param( 'password_confirm' ),
            'gender'           => $request->get_param( 'gender' ),
            'accepted_terms'   => (bool) $request->get_param( 'accepted_terms' ),
            'recaptcha_token'  => $request->get_param( 'recaptcha_token' ),
        );

        $result = $this->auth->signup( $payload, 'api' );

        if ( is_wp_error( $result ) ) {
            return $this->error_response( $result );
        }

        return $this->success_response( $result );
    }

    /**
     * Handle REST login.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function login( WP_REST_Request $request ) {
        $payload = array(
            'email'           => $request->get_param( 'email' ),
            'password'        => $request->get_param( 'password' ),
            'recaptcha_token' => $request->get_param( 'recaptcha_token' ),
        );

        $result = $this->auth->login( $payload, 'api' );

        if ( is_wp_error( $result ) ) {
            return $this->error_response( $result );
        }

        return $this->success_response( $result );
    }

    /**
     * Resend 2FA via REST.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function resend_twofa( WP_REST_Request $request ) {
        $payload = array(
            'user_id' => $request->get_param( 'user_id' ),
        );

        $result = $this->auth->resend_twofa( $payload );

        if ( is_wp_error( $result ) ) {
            return $this->error_response( $result );
        }

        return $this->success_response( $result );
    }

    /**
     * Handle REST social login.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function social_login( WP_REST_Request $request ) {
        $provider = $request->get_param( 'provider' );

        $payload = array(
            'token'       => $request->get_param( 'token' ),
            'remember'    => (bool) $request->get_param( 'remember' ),
            'redirect_to' => $request->get_param( 'redirect_to' ),
        );

        $extra = $request->get_param( 'payload' );
        if ( is_array( $extra ) ) {
            $payload = array_merge( $payload, $extra );
        }

        $payload = apply_filters( 'adc_login_social_rest_payload', $payload, $provider, $request );

        $result = $this->auth->social_login( $provider, $payload, 'api' );

        if ( is_wp_error( $result ) ) {
            return $this->error_response( $result );
        }

        return $this->success_response( $result );
    }

    /**
     * Verify 2FA via REST.
     *
     * @param WP_REST_Request $request Request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function verify_twofa( WP_REST_Request $request ) {
        $payload = array(
            'user_id' => $request->get_param( 'user_id' ),
            'code'    => $request->get_param( 'code' ),
        );

        $result = $this->auth->verify_twofa( $payload );

        if ( is_wp_error( $result ) ) {
            return $this->error_response( $result );
        }

        return $this->success_response( $result );
    }

    /**
     * Append CORS headers for allowed origins.
     *
     * @param bool             $served  Whether the request has already been served.
     * @param WP_REST_Response $result  Result.
     * @param WP_REST_Request  $request Request instance.
     * @param WP_REST_Server   $server  Server instance.
     *
     * @return bool
     */
    public function maybe_add_cors_headers( $served, $result, $request, $server ) {
        if ( 0 !== strpos( $request->get_route(), '/' . $this->namespace ) ) {
            return $served;
        }

        $allowed = $this->get_allowed_origins();

        if ( empty( $allowed ) ) {
            return $served;
        }

        $origin = $this->get_request_origin();
        $normalized_origin = $this->normalize_origin( $origin );

        if ( empty( $normalized_origin ) ) {
            return $served;
        }

        if ( in_array( $normalized_origin, $allowed, true ) ) {
            $server->send_header( 'Access-Control-Allow-Origin', $origin );
            $server->send_header( 'Access-Control-Allow-Credentials', 'true' );
            $server->send_header( 'Vary', 'Origin' );
        }

        return $served;
    }

    /**
     * Determine if origin is allowed to access the endpoints.
     *
     * @return bool
     */
    protected function is_origin_allowed() {
        $allowed = $this->get_allowed_origins();

        if ( empty( $allowed ) ) {
            return true;
        }

        $origin = $this->normalize_origin( $this->get_request_origin() );

        if ( empty( $origin ) ) {
            return false;
        }

        return in_array( $origin, $allowed, true );
    }

    /**
     * Retrieve allowed origins from settings.
     *
     * @return array
     */
    protected function get_allowed_origins() {
        $raw = (string) get_option_value( 'rest_allowed_origins', '' );

        if ( '' === trim( $raw ) ) {
            return array();
        }

        $lines   = preg_split( '/[\r\n]+/', $raw );
        $origins = array();

        foreach ( $lines as $line ) {
            $normalized = $this->normalize_origin( $line );

            if ( ! empty( $normalized ) ) {
                $origins[] = $normalized;
            }
        }

        $site_origin = $this->get_site_origin();

        if ( $site_origin ) {
            $origins[] = $site_origin;
        }

        $origins = array_filter( array_unique( $origins ) );

        return apply_filters( 'adc_login_allowed_origins', $origins );
    }

    /**
     * Retrieve the request origin header.
     *
     * @return string
     */
    protected function get_request_origin() {
        if ( isset( $_SERVER['HTTP_ORIGIN'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            return sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) );
        }

        return '';
    }

    /**
     * Determine the site origin based on home URL.
     *
     * @return string
     */
    protected function get_site_origin() {
        $home = home_url();

        return $this->normalize_origin( $home );
    }

    /**
     * Normalize origin strings to scheme://host:port.
     *
     * @param string $origin Origin string.
     *
     * @return string
     */
    protected function normalize_origin( $origin ) {
        if ( empty( $origin ) ) {
            return '';
        }

        $parts = wp_parse_url( trim( $origin ) );

        if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
            return '';
        }

        $normalized = strtolower( $parts['scheme'] ) . '://' . strtolower( $parts['host'] );

        if ( isset( $parts['port'] ) ) {
            $normalized .= ':' . $parts['port'];
        }

        return $normalized;
    }

    /**
     * Prepare error response.
     *
     * @param WP_Error $error Error.
     *
     * @return WP_REST_Response
     */
    protected function error_response( WP_Error $error ) {
        return new WP_REST_Response(
            array(
                'success' => false,
                'code'    => $error->get_error_code(),
                'message' => $error->get_error_message(),
            ),
            $error->get_error_data()['status'] ?? 400
        );
    }

    /**
     * Prepare success response.
     *
     * @param array $data Payload.
     *
     * @return WP_REST_Response
     */
    protected function success_response( $data ) {
        return new WP_REST_Response(
            array(
                'success' => true,
                'data'    => $data,
            ),
            200
        );
    }
}
