<?php
/**
 * Front-end form handlers for signup, login and 2FA.
 *
 * @package ADC\Login\Auth
 */

namespace ADC\Login\Auth;

use ADC\Login\Email\Emails;
use ADC\Login\TwoFA\Manager as TwoFA_Manager;
use WP_Error;
use WP_User;
use function ADC\Login\get_onboarding_url;
use function ADC\Login\get_option_value;
use function ADC\Login\get_post_login_url;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Authentication
 */
class Authentication {

    /**
     * Two factor handler.
     *
     * @var TwoFA_Manager
     */
    protected $twofa;

    /**
     * Email handler.
     *
     * @var Emails
     */
    protected $emails;

    /**
     * Constructor.
     *
     * @param TwoFA_Manager $twofa Two factor manager.
     * @param Emails        $emails Email manager.
     */
    public function __construct( TwoFA_Manager $twofa, Emails $emails ) {
        $this->twofa  = $twofa;
        $this->emails = $emails;
    }

    /**
     * Initialize hooks.
     */
    public function init() {
        add_action( 'init', array( $this, 'handle_requests' ) );
        add_action( 'admin_post_nopriv_adc_social_login', array( $this, 'handle_social_callback' ) );
        add_action( 'admin_post_adc_social_login', array( $this, 'handle_social_callback' ) );
    }

    /**
     * Handle POST requests from front-end forms.
     */
    public function handle_requests() {
        if ( empty( $_POST['adc_login_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        $action = sanitize_text_field( wp_unslash( $_POST['adc_login_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

        switch ( $action ) {
            case 'signup':
                $this->handle_signup_web();
                break;
            case 'login':
                $this->handle_login_web();
                break;
            case 'forgot_password':
                $this->handle_password_web();
                break;
            case 'verify_2fa':
                $this->handle_twofa_web();
                break;
            case 'resend_2fa':
                $this->handle_twofa_resend_web();
                break;
        }
    }

    /**
     * Execute signup from web form.
     */
    protected function handle_signup_web() {
        $nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        if ( ! wp_verify_nonce( $nonce, 'adc_signup' ) ) {
            $this->redirect_with_error( __( 'Token inválido. Atualize a página e tente novamente.', 'login-academia-da-comunicacao' ) );
        }

        $payload = array(
            'name'              => isset( $_POST['adc_name'] ) ? sanitize_text_field( wp_unslash( $_POST['adc_name'] ) ) : '',
            'email'             => isset( $_POST['adc_email'] ) ? sanitize_email( wp_unslash( $_POST['adc_email'] ) ) : '',
            'password'          => isset( $_POST['adc_password'] ) ? wp_unslash( $_POST['adc_password'] ) : '',
            'password_confirm'  => isset( $_POST['adc_password_confirm'] ) ? wp_unslash( $_POST['adc_password_confirm'] ) : '',
            'gender'            => isset( $_POST['adc_gender'] ) ? sanitize_text_field( wp_unslash( $_POST['adc_gender'] ) ) : '',
            'accepted_terms'    => ! empty( $_POST['adc_terms'] ),
            'redirect_to'       => isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '',
            'recaptcha_token'   => isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) : '',
        );

        $result = $this->signup( $payload, 'web' );

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_error( $result->get_error_message() );
        }

        wp_safe_redirect( $result['redirect'] );
        exit;
    }

    /**
     * Execute login from web form.
     */
    protected function handle_login_web() {
        $nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        if ( ! wp_verify_nonce( $nonce, 'adc_login' ) ) {
            $this->redirect_with_error( __( 'Token inválido. Atualize a página e tente novamente.', 'login-academia-da-comunicacao' ) );
        }

        $payload = array(
            'email'           => isset( $_POST['adc_email'] ) ? sanitize_email( wp_unslash( $_POST['adc_email'] ) ) : '',
            'password'        => isset( $_POST['adc_password'] ) ? wp_unslash( $_POST['adc_password'] ) : '',
            'remember'        => ! empty( $_POST['rememberme'] ),
            'redirect_to'     => isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '',
            'recaptcha_token' => isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) : '',
        );

        $result = $this->login( $payload, 'web' );

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_error( $result->get_error_message() );
        }

        wp_safe_redirect( $result['redirect'] );
        exit;
    }

    /**
     * Handle forgot password from web form.
     */
    protected function handle_password_web() {
        $nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        if ( ! wp_verify_nonce( $nonce, 'adc_forgot_password' ) ) {
            $this->redirect_with_error( __( 'Token inválido.', 'login-academia-da-comunicacao' ) );
        }

        $payload = array(
            'email' => isset( $_POST['adc_email'] ) ? sanitize_email( wp_unslash( $_POST['adc_email'] ) ) : '',
        );

        $result = $this->forgot_password( $payload );

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_error( $result->get_error_message() );
        }

        $this->redirect_with_success( $result['message'] );
    }

    /**
     * Handle 2FA verification from web form.
     */
    protected function handle_twofa_web() {
        $nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        if ( ! wp_verify_nonce( $nonce, 'adc_2fa' ) ) {
            $this->redirect_with_error( __( 'Token inválido.', 'login-academia-da-comunicacao' ) );
        }

        $payload = array(
            'code'    => isset( $_POST['adc_code'] ) ? sanitize_text_field( wp_unslash( $_POST['adc_code'] ) ) : '',
            'user_id' => get_current_user_id(),
        );

        $result = $this->verify_twofa( $payload );

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_error( $result->get_error_message() );
        }

        wp_safe_redirect( get_post_login_url() );
        exit;
    }

    /**
     * Handle resend of 2FA code from web form.
     */
    protected function handle_twofa_resend_web() {
        $nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
        if ( ! wp_verify_nonce( $nonce, 'adc_2fa_resend' ) ) {
            $this->redirect_with_error( __( 'Token inválido.', 'login-academia-da-comunicacao' ) );
        }

        $payload = array(
            'user_id' => get_current_user_id(),
        );

        $result = $this->resend_twofa( $payload );

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_error( $result->get_error_message() );
        }

        $this->redirect_with_success( __( 'Um novo código foi enviado.', 'login-academia-da-comunicacao' ) );
    }

    /**
     * Handle social login callback routed via admin-post.
     */
    public function handle_social_callback() {
        $provider = isset( $_REQUEST['provider'] ) ? sanitize_key( wp_unslash( $_REQUEST['provider'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

        if ( empty( $provider ) ) {
            $this->redirect_with_error( __( 'Provedor social inválido.', 'login-academia-da-comunicacao' ) );
        }

        $payload = array();

        if ( isset( $_REQUEST['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $payload['redirect_to'] = esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) );
        }

        if ( isset( $_REQUEST['remember'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
            $payload['remember'] = ! empty( $_REQUEST['remember'] );
        }

        $payload = apply_filters( 'adc_login_social_request_payload', $payload, $provider, $_REQUEST ); // phpcs:ignore WordPress.Security.NonceVerification
        $payload = apply_filters( 'adc_login_social_request_payload_' . $provider, $payload, $_REQUEST ); // phpcs:ignore WordPress.Security.NonceVerification

        if ( ! is_array( $payload ) ) {
            $payload = array();
        }

        $payload['remember']    = ! empty( $payload['remember'] );
        $payload['redirect_to'] = isset( $payload['redirect_to'] ) ? esc_url_raw( $payload['redirect_to'] ) : '';

        $result = $this->social_login( $provider, $payload, 'web' );

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_error( $result->get_error_message() );
        }

        wp_safe_redirect( $result['redirect'] );
        exit;
    }

    /**
     * Public signup handler used by REST and forms.
     *
     * @param array  $payload Request payload.
     * @param string $context web|api.
     *
     * @return array|WP_Error
     */
    public function signup( $payload, $context = 'web' ) {
        $name     = isset( $payload['name'] ) ? sanitize_text_field( $payload['name'] ) : '';
        $email    = isset( $payload['email'] ) ? sanitize_email( $payload['email'] ) : '';
        $password = isset( $payload['password'] ) ? $payload['password'] : '';
        $confirm  = isset( $payload['password_confirm'] ) ? $payload['password_confirm'] : '';
        $gender   = isset( $payload['gender'] ) ? sanitize_text_field( $payload['gender'] ) : '';
        $accepted = ! empty( $payload['accepted_terms'] );
        $redirect = isset( $payload['redirect_to'] ) ? esc_url_raw( $payload['redirect_to'] ) : '';
        $token    = isset( $payload['recaptcha_token'] ) ? sanitize_text_field( $payload['recaptcha_token'] ) : '';

        if ( empty( $name ) || empty( $email ) || empty( $password ) ) {
            return new WP_Error( 'adc_signup_required', __( 'Preencha todos os campos obrigatórios.', 'login-academia-da-comunicacao' ) );
        }

        if ( ! is_email( $email ) ) {
            return new WP_Error( 'adc_signup_email', __( 'Informe um e-mail válido.', 'login-academia-da-comunicacao' ) );
        }

        if ( $password !== $confirm ) {
            return new WP_Error( 'adc_signup_password', __( 'As senhas não conferem.', 'login-academia-da-comunicacao' ) );
        }

        if ( ! $accepted ) {
            return new WP_Error( 'adc_signup_terms', __( 'Você precisa aceitar os Termos e a Política de Privacidade.', 'login-academia-da-comunicacao' ) );
        }

        if ( 'web' === $context && ! $this->validate_recaptcha( $token ) ) {
            return new WP_Error( 'adc_signup_recaptcha', __( 'Falha na verificação do reCAPTCHA.', 'login-academia-da-comunicacao' ) );
        }

        if ( email_exists( $email ) ) {
            return new WP_Error( 'adc_signup_exists', __( 'Este e-mail já está cadastrado.', 'login-academia-da-comunicacao' ) );
        }

        $username = sanitize_user( current( explode( '@', $email ) ), true );
        if ( username_exists( $username ) ) {
            $username .= wp_generate_password( 4, false );
        }

        $user_id = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        wp_update_user(
            array(
                'ID'           => $user_id,
                'display_name' => $name,
                'first_name'   => $name,
            )
        );

        if ( ! empty( $gender ) ) {
            update_user_meta( $user_id, 'adc_gender', $gender );
        }

        $this->emails->send_account_created( $user_id );

        $requires_2fa = $this->twofa->is_enforced();

        if ( 'web' === $context ) {
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id, true );
        }

        if ( $requires_2fa ) {
            $this->twofa->mark_pending( $user_id );
            $this->twofa->send_code( $user_id );
            $redirect = add_query_arg( 'step', '2fa', get_onboarding_url() );
        } else {
            $redirect = $redirect ? $redirect : get_post_login_url();
        }

        return array(
            'user_id'     => $user_id,
            'redirect'    => $redirect,
            'requires_2fa'=> $requires_2fa,
        );
    }

    /**
     * Public login handler.
     *
     * @param array  $payload Request payload.
     * @param string $context web|api.
     *
     * @return array|WP_Error
     */
    public function login( $payload, $context = 'web' ) {
        $email       = isset( $payload['email'] ) ? sanitize_email( $payload['email'] ) : '';
        $password    = isset( $payload['password'] ) ? $payload['password'] : '';
        $remember    = ! empty( $payload['remember'] );
        $redirect    = isset( $payload['redirect_to'] ) ? esc_url_raw( $payload['redirect_to'] ) : '';
        $token       = isset( $payload['recaptcha_token'] ) ? sanitize_text_field( $payload['recaptcha_token'] ) : '';

        if ( empty( $email ) || empty( $password ) ) {
            return new WP_Error( 'adc_login_required', __( 'Informe e-mail e senha.', 'login-academia-da-comunicacao' ) );
        }

        if ( 'web' === $context && ! $this->validate_recaptcha( $token ) ) {
            return new WP_Error( 'adc_login_recaptcha', __( 'Falha na verificação do reCAPTCHA.', 'login-academia-da-comunicacao' ) );
        }

        $ip = $this->get_user_ip();
        if ( ! $this->check_rate_limit( 'login', $ip ) ) {
            return new WP_Error( 'adc_login_limit', __( 'Muitas tentativas. Tente novamente em alguns minutos.', 'login-academia-da-comunicacao' ) );
        }

        if ( 'web' === $context ) {
            $user = wp_signon(
                array(
                    'user_login'    => $email,
                    'user_password' => $password,
                    'remember'      => $remember,
                ),
                false
            );
        } else {
            $user = $this->authenticate_by_email( $email, $password );
        }

        if ( is_wp_error( $user ) ) {
            $this->log_rate_attempt( 'login', $ip );
            return $user;
        }

        $this->reset_rate_limit( 'login', $ip );

        return $this->finalize_login( $user, $context, $remember, $redirect, 'password' );
    }

    /**
     * Execute login via social provider response.
     *
     * @param string $provider Social provider slug.
     * @param array  $payload  Payload from callback or REST.
     * @param string $context  web|api.
     *
     * @return array|WP_Error
     */
    public function social_login( $provider, $payload = array(), $context = 'web' ) {
        $provider = sanitize_key( $provider );

        if ( empty( $provider ) ) {
            return new WP_Error( 'adc_social_provider', __( 'Provedor social não informado.', 'login-academia-da-comunicacao' ) );
        }

        if ( ! is_array( $payload ) ) {
            $payload = array();
        }

        $remember = ! empty( $payload['remember'] );
        $redirect = isset( $payload['redirect_to'] ) ? esc_url_raw( $payload['redirect_to'] ) : '';

        $response = apply_filters( 'adc_login_social_authenticate_' . $provider, null, $payload, $context, $this );

        if ( null === $response ) {
            $response = apply_filters( 'adc_login_social_authenticate', null, $provider, $payload, $context, $this );
        }

        if ( null === $response ) {
            return new WP_Error( 'adc_social_missing_handler', __( 'Integração social não configurada.', 'login-academia-da-comunicacao' ) );
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $user = $this->resolve_social_user( $response );

        if ( is_wp_error( $user ) ) {
            return $user;
        }

        if ( is_array( $response ) ) {
            if ( isset( $response['redirect_to'] ) && empty( $redirect ) ) {
                $redirect = esc_url_raw( $response['redirect_to'] );
            }

            if ( isset( $response['remember'] ) ) {
                $remember = (bool) $response['remember'];
            }
        }

        return $this->finalize_login( $user, $context, $remember, $redirect, $provider );
    }

    /**
     * Forgot password handler.
     *
     * @param array $payload Request payload.
     *
     * @return array|WP_Error
     */
    public function forgot_password( $payload ) {
        $email = isset( $payload['email'] ) ? sanitize_email( $payload['email'] ) : '';

        if ( empty( $email ) || ! is_email( $email ) ) {
            return new WP_Error( 'adc_password_email', __( 'Informe um e-mail válido.', 'login-academia-da-comunicacao' ) );
        }

        $user = get_user_by( 'email', $email );

        if ( ! $user ) {
            return new WP_Error( 'adc_password_user', __( 'Usuário não encontrado.', 'login-academia-da-comunicacao' ) );
        }

        $key = get_password_reset_key( $user );

        if ( is_wp_error( $key ) ) {
            return $key;
        }

        $reset_url = network_site_url( 'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user->user_login ), 'login' );
        $this->emails->send_password_reminder( $user->ID, $reset_url );

        return array(
            'message' => __( 'Enviamos instruções para seu e-mail.', 'login-academia-da-comunicacao' ),
        );
    }

    /**
     * Validate two factor code.
     *
     * @param array $payload Request payload.
     *
     * @return array|WP_Error
     */
    public function verify_twofa( $payload ) {
        $user_id = isset( $payload['user_id'] ) ? absint( $payload['user_id'] ) : get_current_user_id();
        $code    = isset( $payload['code'] ) ? sanitize_text_field( $payload['code'] ) : '';

        if ( empty( $user_id ) ) {
            return new WP_Error( 'adc_twofa_user', __( 'Sessão expirada. Faça login novamente.', 'login-academia-da-comunicacao' ) );
        }

        if ( empty( $code ) ) {
            return new WP_Error( 'adc_twofa_code', __( 'Informe o código recebido.', 'login-academia-da-comunicacao' ) );
        }

        if ( ! $this->check_rate_limit( '2fa', $user_id ) ) {
            return new WP_Error( 'adc_twofa_limit', __( 'Muitas tentativas. Aguarde antes de tentar novamente.', 'login-academia-da-comunicacao' ) );
        }

        if ( ! $this->twofa->validate_code( $user_id, $code ) ) {
            $this->log_rate_attempt( '2fa', $user_id );
            return new WP_Error( 'adc_twofa_invalid', __( 'Código inválido ou expirado.', 'login-academia-da-comunicacao' ) );
        }

        $this->twofa->clear_pending( $user_id );
        $this->reset_rate_limit( '2fa', $user_id );

        return array(
            'message' => __( 'Autenticação concluída com sucesso.', 'login-academia-da-comunicacao' ),
        );
    }

    /**
     * Resend 2FA code.
     *
     * @param array $payload Request payload.
     *
     * @return array|WP_Error
     */
    public function resend_twofa( $payload ) {
        $user_id = isset( $payload['user_id'] ) ? absint( $payload['user_id'] ) : get_current_user_id();

        if ( empty( $user_id ) ) {
            return new WP_Error( 'adc_twofa_user', __( 'Sessão expirada. Faça login novamente.', 'login-academia-da-comunicacao' ) );
        }

        if ( ! $this->twofa->can_request_new_code( $user_id ) ) {
            return new WP_Error( 'adc_twofa_wait', __( 'Aguarde antes de solicitar um novo código.', 'login-academia-da-comunicacao' ) );
        }

        $this->twofa->send_code( $user_id );

        return array(
            'message' => __( 'Um novo código foi enviado.', 'login-academia-da-comunicacao' ),
        );
    }

    /**
     * Finalize login by handling session, redirect and 2FA workflow.
     *
     * @param WP_User $user      Authenticated user.
     * @param string  $context   Request context.
     * @param bool    $remember  Remember session.
     * @param string  $redirect  Requested redirect URL.
     * @param string  $provider  Auth provider identifier.
     *
     * @return array
     */
    protected function finalize_login( WP_User $user, $context, $remember, $redirect, $provider ) {
        $requires_2fa = apply_filters( 'adc_login_require_2fa', $this->twofa->is_enforced(), $user, $provider, $context );

        if ( 'web' === $context ) {
            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID, (bool) $remember );
        }

        if ( $requires_2fa ) {
            $this->twofa->mark_pending( $user->ID );
            $this->twofa->send_code( $user->ID );
            $redirect = add_query_arg( 'step', '2fa', get_onboarding_url() );
        } else {
            $redirect = $redirect ? $redirect : get_post_login_url();
        }

        $redirect = apply_filters( 'adc_login_redirect', $redirect, $user, $provider, $context, $requires_2fa );

        do_action( 'adc_login_after_auth', $user, $provider, $context, $requires_2fa, $redirect );

        return array(
            'user_id'      => $user->ID,
            'redirect'     => $redirect,
            'requires_2fa' => $requires_2fa,
        );
    }

    /**
     * Normalize different response formats from social providers.
     *
     * @param mixed $response Response returned by filters.
     *
     * @return WP_User|WP_Error
     */
    protected function resolve_social_user( $response ) {
        if ( $response instanceof WP_User ) {
            return $response;
        }

        if ( is_array( $response ) ) {
            if ( isset( $response['user'] ) && $response['user'] instanceof WP_User ) {
                return $response['user'];
            }

            if ( isset( $response['user_id'] ) ) {
                $user = get_user_by( 'id', absint( $response['user_id'] ) );

                if ( $user instanceof WP_User ) {
                    return $user;
                }
            }

            if ( isset( $response['email'] ) ) {
                $email = sanitize_email( $response['email'] );

                if ( is_email( $email ) ) {
                    $user = get_user_by( 'email', $email );

                    if ( $user instanceof WP_User ) {
                        return $user;
                    }
                }
            }
        }

        if ( is_numeric( $response ) ) {
            $user = get_user_by( 'id', absint( $response ) );

            if ( $user instanceof WP_User ) {
                return $user;
            }
        }

        if ( is_string( $response ) ) {
            $email = sanitize_email( $response );

            if ( is_email( $email ) ) {
                $user = get_user_by( 'email', $email );

                if ( $user instanceof WP_User ) {
                    return $user;
                }
            }
        }

        return new WP_Error( 'adc_social_user', __( 'Não foi possível localizar o usuário retornado pelo provedor social.', 'login-academia-da-comunicacao' ) );
    }

    /**
     * Validate reCAPTCHA token when keys are configured.
     *
     * @param string $token Token.
     *
     * @return bool
     */
    protected function validate_recaptcha( $token ) {
        $site_key   = get_option_value( 'recaptcha_site_key', '' );
        $secret_key = get_option_value( 'recaptcha_secret_key', '' );

        if ( empty( $site_key ) || empty( $secret_key ) ) {
            return true;
        }

        if ( empty( $token ) ) {
            return false;
        }

        $request = wp_remote_post(
            'https://www.google.com/recaptcha/api/siteverify',
            array(
                'body' => array(
                    'secret'   => $secret_key,
                    'response' => $token,
                    'remoteip' => $this->get_user_ip(),
                ),
            )
        );

        if ( is_wp_error( $request ) ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $request ), true );

        return ! empty( $data['success'] );
    }

    /**
     * Authenticate a user by email without creating session.
     *
     * @param string $email Email.
     * @param string $password Password.
     *
     * @return WP_User|WP_Error
     */
    protected function authenticate_by_email( $email, $password ) {
        $user = get_user_by( 'email', $email );

        if ( ! $user ) {
            return new WP_Error( 'adc_login_invalid', __( 'Credenciais inválidas.', 'login-academia-da-comunicacao' ) );
        }

        $authenticated = wp_check_password( $password, $user->user_pass, $user->ID );

        if ( ! $authenticated ) {
            return new WP_Error( 'adc_login_invalid', __( 'Credenciais inválidas.', 'login-academia-da-comunicacao' ) );
        }

        return $user;
    }

    /**
     * Check login/2fa rate limits.
     *
     * @param string $type login|2fa.
     * @param string $key  Identifier (IP or user ID).
     *
     * @return bool
     */
    protected function check_rate_limit( $type, $key ) {
        $limit_key = 'adc_' . $type . '_limit_' . md5( $key );
        $attempts  = get_transient( $limit_key );

        if ( ! $attempts ) {
            return true;
        }

        return $attempts < 5;
    }

    /**
     * Register failed attempts.
     *
     * @param string $type login|2fa.
     * @param string $key  Identifier.
     */
    protected function log_rate_attempt( $type, $key ) {
        $limit_key = 'adc_' . $type . '_limit_' . md5( $key );
        $attempts  = (int) get_transient( $limit_key );
        $attempts ++;
        set_transient( $limit_key, $attempts, 10 * MINUTE_IN_SECONDS );
    }

    /**
     * Reset rate limit once action succeeds.
     *
     * @param string $type login|2fa.
     * @param string $key  Identifier.
     */
    protected function reset_rate_limit( $type, $key ) {
        delete_transient( 'adc_' . $type . '_limit_' . md5( $key ) );
    }

    /**
     * Helper to redirect with error message.
     *
     * @param string $message Message.
     */
    protected function redirect_with_error( $message ) {
        $this->store_flash( 'error', $message );
        wp_safe_redirect( $this->get_referer() );
        exit;
    }

    /**
     * Helper to redirect with success message.
     *
     * @param string $message Message.
     */
    protected function redirect_with_success( $message ) {
        $this->store_flash( 'success', $message );
        wp_safe_redirect( $this->get_referer() );
        exit;
    }

    /**
     * Save message in transient keyed by IP.
     *
     * @param string $type    Message type.
     * @param string $message Text.
     */
    protected function store_flash( $type, $message ) {
        set_transient( 'adc_flash_' . $type . '_' . md5( $this->get_user_ip() ), $message, MINUTE_IN_SECONDS );
    }

    /**
     * Retrieve referer or fallback to onboarding.
     *
     * @return string
     */
    protected function get_referer() {
        $referer = wp_get_referer();
        return $referer ? $referer : get_onboarding_url();
    }

    /**
     * Determine user IP address.
     *
     * @return string
     */
    protected function get_user_ip() {
        $keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
        foreach ( $keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( current( explode( ',', $ip ) ) );
                }
                return $ip;
            }
        }

        return 'unknown';
    }
}
