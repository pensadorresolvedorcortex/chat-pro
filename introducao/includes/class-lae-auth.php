<?php
/**
 * Utilidades de autenticação com verificação em duas etapas.
 */
class Introducao_Auth {

    const OTP_TRANSIENT_PREFIX = 'introducao_otp_';

    /**
     * Tempo de expiração dos códigos em segundos.
     */
    const OTP_TTL = 10 * MINUTE_IN_SECONDS;

    /**
     * Quantidade máxima de tentativas antes de expirar o desafio.
     */
    const OTP_MAX_ATTEMPTS = 5;

    /**
     * Intervalo mínimo em segundos entre novos envios do código.
     */
    const OTP_RESEND_INTERVAL = 60;

    /**
     * Prefixo utilizado para armazenar o desafio ativo vinculado ao cliente.
     */
    const CLIENT_STATE_TRANSIENT_PREFIX = 'introducao_client_otp_';

    /**
     * Prefixo utilizado para compartilhar a identidade sincronizada do cliente.
     */
    const CLIENT_IDENTITY_TRANSIENT_PREFIX = 'introducao_client_identity_';

    /**
     * Tempo de vida padrão da identidade sincronizada.
     */
    const CLIENT_IDENTITY_TTL = DAY_IN_SECONDS;

    /**
     * Quantidade máxima de reenvios permitidos por desafio.
     */
    const OTP_MAX_RESENDS = 5;

    /**
     * Contexto da autenticação atual utilizado para sincronizar eventos de login.
     *
     * @var array
     */
    private static $login_context = array();

    /**
     * Registra hooks globais necessários para manter a sincronia entre plugins.
     */
    public static function bootstrap() {
        add_action( 'wp_login', array( __CLASS__, 'handle_wp_login' ), 10, 2 );
        add_action( 'wp_logout', array( __CLASS__, 'handle_wp_logout' ) );
        add_action( 'introducao_auth_challenge_created', array( __CLASS__, 'handle_challenge_sent' ), 10, 2 );
        add_action( 'introducao_auth_challenge_resent', array( __CLASS__, 'handle_challenge_sent' ), 10, 2 );
        add_action( 'init', array( __CLASS__, 'maybe_sync_logged_identity' ), 20 );
    }

    /**
     * Define o contexto da autenticação corrente para ser aplicado no próximo evento wp_login.
     *
     * @param array $context Dados adicionais como origem, método e redirecionamento.
     */
    public static function set_login_context( array $context ) {
        self::$login_context = $context;
    }

    /**
     * Trata o evento de login do WordPress garantindo sincronia e metadados atualizados.
     *
     * @param string  $user_login Login do usuário.
     * @param WP_User $user       Instância do usuário autenticado.
     */
    public static function handle_wp_login( $user_login, $user ) {
        if ( $user instanceof WP_User ) {
            self::log_successful_login( $user->ID, self::$login_context );
            self::remember_client_identity( $user, self::$login_context );
        }

        self::$login_context = array();
        self::forget_client_challenge();
    }

    /**
     * Limpa o contexto compartilhado ao efetuar logout.
     */
    public static function handle_wp_logout() {
        self::$login_context = array();
        self::forget_client_challenge();
        self::forget_client_identity();
    }

    /**
     * Garante que a identidade compartilhada reflita o usuário autenticado atual.
     */
    public static function maybe_sync_logged_identity() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $user = wp_get_current_user();

        if ( ! ( $user instanceof WP_User ) || ! $user->exists() ) {
            return;
        }

        $identity = self::get_client_identity();

        if ( ! is_array( $identity ) || (int) ( isset( $identity['user_id'] ) ? $identity['user_id'] : 0 ) !== (int) $user->ID ) {
            self::remember_client_identity( $user );
        }
    }

    /**
     * Registra o envio de um desafio de segundo fator para fins de auditoria.
     *
     * @param string $challenge_id Identificador do desafio.
     * @param array  $data         Dados brutos do desafio.
     */
    public static function handle_challenge_sent( $challenge_id, $data ) {
        if ( empty( $data ) || ! is_array( $data ) ) {
            return;
        }

        if ( isset( $data['user_id'] ) ) {
            update_user_meta( (int) $data['user_id'], 'lae_two_factor_last_sent', current_time( 'mysql' ) );
        }
    }

    /**
     * Atualiza metadados do usuário após uma autenticação bem-sucedida.
     *
     * @param int   $user_id ID do usuário.
     * @param array $context Contexto adicional registrado via set_login_context().
     */
    private static function log_successful_login( $user_id, $context = array() ) {
        $user_id = (int) $user_id;

        if ( $user_id <= 0 ) {
            return;
        }

        $timestamp = current_time( 'timestamp' );
        $ip        = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

        update_user_meta( $user_id, 'last_login_at', $timestamp );
        update_user_meta( $user_id, 'last_login', $timestamp );
        update_user_meta( $user_id, 'wc_last_active', $timestamp );
        update_user_meta( $user_id, 'lae_last_login_at', $timestamp );

        if ( $ip ) {
            update_user_meta( $user_id, 'lae_last_login_ip', $ip );
        }

        if ( isset( $context['source'] ) && '' !== $context['source'] ) {
            update_user_meta( $user_id, 'lae_last_login_source', sanitize_text_field( $context['source'] ) );
        }

        if ( isset( $context['method'] ) && '' !== $context['method'] ) {
            update_user_meta( $user_id, 'lae_last_login_method', sanitize_text_field( $context['method'] ) );
        }

        if ( isset( $context['redirect'] ) && '' !== $context['redirect'] ) {
            update_user_meta( $user_id, 'lae_last_login_redirect', esc_url_raw( $context['redirect'] ) );
        }
    }

    /**
     * Cria um desafio de segundo fator para login.
     *
     * @param WP_User $user     Usuário autenticado.
     * @param bool    $remember Indica se o cookie deve ser persistente.
     *
     * @return array|WP_Error
     */
    public static function create_login_challenge( WP_User $user, $remember = false, $identifier = '', $extra = array() ) {
        if ( ! $user instanceof WP_User || ! $user->exists() ) {
            return new WP_Error( 'introducao_invalid_user', __( 'Não foi possível gerar o código de verificação.', 'introducao' ) );
        }

        $code         = self::generate_code();
        $challenge_id = self::generate_challenge_id();
        $now          = time();

        $data = array(
            'type'        => 'login',
            'user_id'     => $user->ID,
            'remember'    => (bool) $remember,
            'code_hash'   => wp_hash_password( $code ),
            'created_at'  => $now,
            'attempts'    => 0,
            'last_sent'   => $now,
            'resend_count' => 0,
            'user_email'      => $user->user_email,
            'user_name'       => $user->display_name ? $user->display_name : $user->user_login,
            'user_login'      => $user->user_login,
            'submitted_login' => $identifier ? $identifier : $user->user_login,
            'context'         => self::prepare_context( $extra ),
        );

        if ( ! self::persist_challenge( $challenge_id, $data ) ) {
            return new WP_Error( 'introducao_otp_store_failed', __( 'Não foi possível preparar o código de verificação.', 'introducao' ) );
        }

        $email_sent = self::send_code_email(
            $data['user_email'],
            $data['user_name'],
            $code,
            'login'
        );

        if ( ! $email_sent ) {
            self::clear_challenge( $challenge_id );

            return new WP_Error( 'introducao_otp_email_failed', __( 'Não foi possível enviar o código de verificação. Tente novamente em instantes.', 'introducao' ) );
        }

        do_action( 'introducao_auth_challenge_created', $challenge_id, $data );

        self::remember_client_challenge( $challenge_id, $data );

        return array(
            'challenge_id' => $challenge_id,
            'email'        => $data['user_email'],
            'resend_in'    => self::OTP_RESEND_INTERVAL,
            'ttl'          => self::OTP_TTL,
        );
    }

    /**
     * Cria um desafio de segundo fator para cadastro.
     *
     * @param string $user_login Nome de usuário desejado.
     * @param string $user_email Email informado.
     * @param string $user_pass  Senha escolhida.
     * @param array  $meta       Metadados pendentes associados ao cadastro.
     * @param array  $extra      Contexto adicional utilizado por integrações.
     *
     * @return array|WP_Error
     */
    public static function create_register_challenge( $user_login, $user_email, $user_pass, $meta = array(), $extra = array() ) {
        $code         = self::generate_code();
        $challenge_id = self::generate_challenge_id();
        $now          = time();

        $data = array(
            'type'         => 'register',
            'code_hash'    => wp_hash_password( $code ),
            'created_at'   => $now,
            'attempts'     => 0,
            'last_sent'    => $now,
            'resend_count' => 0,
            'user_email'   => $user_email,
            'user_name'    => $user_login,
            'pending_user' => array(
                'user_login' => $user_login,
                'user_email' => $user_email,
                'user_pass'  => $user_pass,
            ),
            'pending_meta' => self::prepare_pending_meta( $meta, $user_login ),
            'context'      => self::prepare_context( $extra ),
        );

        if ( ! self::persist_challenge( $challenge_id, $data ) ) {
            return new WP_Error( 'introducao_otp_store_failed', __( 'Não foi possível preparar o código de confirmação.', 'introducao' ) );
        }

        $email_sent = self::send_code_email(
            $user_email,
            $user_login,
            $code,
            'register'
        );

        if ( ! $email_sent ) {
            self::clear_challenge( $challenge_id );

            return new WP_Error( 'introducao_otp_email_failed', __( 'Não foi possível enviar o código de confirmação. Tente novamente.', 'introducao' ) );
        }

        do_action( 'introducao_auth_challenge_created', $challenge_id, $data );

        self::remember_client_challenge( $challenge_id, $data );

        return array(
            'challenge_id' => $challenge_id,
            'email'        => $user_email,
            'resend_in'    => self::OTP_RESEND_INTERVAL,
            'ttl'          => self::OTP_TTL,
        );
    }

    /**
     * Realiza um novo envio do código de verificação para um desafio existente.
     *
     * @param string $challenge_id Identificador do desafio.
     *
     * @return array|WP_Error
     */
    public static function resend_challenge( $challenge_id ) {
        $data = self::get_challenge( $challenge_id );

        if ( false === $data ) {
            return new WP_Error( 'introducao_otp_expired', __( 'O código expirou. Solicite um novo para continuar.', 'introducao' ) );
        }

        $now            = time();
        $last_sent      = isset( $data['last_sent'] ) ? (int) $data['last_sent'] : 0;
        $resend_count   = isset( $data['resend_count'] ) ? (int) $data['resend_count'] : 0;
        $elapsed        = $last_sent ? $now - $last_sent : PHP_INT_MAX;
        $remaining_wait = self::OTP_RESEND_INTERVAL - $elapsed;

        if ( $remaining_wait > 0 ) {
            return new WP_Error(
                'introducao_otp_throttled',
                sprintf(
                    /* translators: %s: time until resend allowed. */
                    __( 'Aguarde %s para solicitar um novo código.', 'introducao' ),
                    self::format_duration( max( 1, $remaining_wait ) )
                )
            );
        }

        if ( $resend_count >= self::OTP_MAX_RESENDS ) {
            return new WP_Error( 'introducao_otp_resend_limit', __( 'Limite de reenvios atingido. Recomece o processo para continuar.', 'introducao' ) );
        }

        $email = isset( $data['user_email'] ) ? $data['user_email'] : '';
        $name  = isset( $data['user_name'] ) ? $data['user_name'] : '';

        if ( empty( $email ) && isset( $data['user_id'] ) ) {
            $user = get_user_by( 'id', (int) $data['user_id'] );

            if ( $user ) {
                $email            = $user->user_email;
                $name             = $user->display_name ? $user->display_name : $user->user_login;
                $data['user_email'] = $email;
                $data['user_name']  = $name;
            }
        }

        if ( empty( $email ) ) {
            return new WP_Error( 'introducao_otp_missing_email', __( 'Não foi possível reenviar o código. Inicie novamente o processo.', 'introducao' ) );
        }

        $code = self::generate_code();

        $data['code_hash']    = wp_hash_password( $code );
        $data['attempts']     = 0;
        $data['created_at']   = $now;
        $data['last_sent']    = $now;
        $data['resend_count'] = $resend_count + 1;

        if ( ! self::send_code_email( $email, $name, $code, isset( $data['type'] ) ? $data['type'] : 'login' ) ) {
            return new WP_Error( 'introducao_otp_email_failed', __( 'Não foi possível enviar o código de verificação. Tente novamente em instantes.', 'introducao' ) );
        }

        self::persist_challenge( $challenge_id, $data );

        self::remember_client_challenge( $challenge_id, $data );

        do_action( 'introducao_auth_challenge_resent', $challenge_id, $data );

        return array(
            'email' => $email,
            'type'  => isset( $data['type'] ) ? $data['type'] : 'login',
            'ttl'   => self::OTP_TTL,
        );
    }

    /**
     * Registra as rotas AJAX utilizadas na autenticação.
     */
    public static function register_ajax_routes() {
        add_action( 'wp_ajax_introducao_resend_otp', array( __CLASS__, 'handle_resend_otp' ) );
        add_action( 'wp_ajax_nopriv_introducao_resend_otp', array( __CLASS__, 'handle_resend_otp' ) );
    }

    /**
     * Manipula as requisições AJAX de reenvio do código OTP.
     */
    public static function handle_resend_otp() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'introducao_resend_otp' ) ) {
            wp_send_json_error( array( 'message' => __( 'Falha de segurança. Recarregue a página e tente novamente.', 'introducao' ) ), 403 );
        }

        $challenge_id = isset( $_POST['challenge'] ) ? sanitize_text_field( wp_unslash( $_POST['challenge'] ) ) : '';

        if ( empty( $challenge_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Não encontramos um desafio ativo. Recomece o processo.', 'introducao' ) ), 400 );
        }

        $result = self::resend_challenge( $challenge_id );

        if ( is_wp_error( $result ) ) {
            $status = in_array( $result->get_error_code(), array( 'introducao_otp_expired', 'introducao_otp_missing_email' ), true ) ? 410 : 400;

            wp_send_json_error( array( 'message' => $result->get_error_message() ), $status );
        }

        $masked_email = self::mask_email( $result['email'] );
        $type         = isset( $result['type'] ) ? $result['type'] : 'login';

        $message = ( 'register' === $type )
            ? sprintf( __( 'Enviamos um código de ativação para %s. Informe-o abaixo para concluir seu cadastro.', 'introducao' ), $masked_email )
            : sprintf( __( 'Enviamos um código de acesso para %s. Digite-o para concluir a entrada.', 'introducao' ), $masked_email );

        wp_send_json_success(
            array(
                'message'     => $message,
                'maskedEmail' => $masked_email,
                'challenge'   => $challenge_id,
                'resend_in'   => self::OTP_RESEND_INTERVAL,
                'ttl'         => isset( $result['ttl'] ) ? (int) $result['ttl'] : self::OTP_TTL,
            )
        );
    }

    /**
     * Verifica um código de segundo fator.
     *
     * @param string $challenge_id Identificador do desafio.
     * @param string $submitted    Código informado pelo usuário.
     *
     * @return array|WP_Error
     */
    public static function verify_code( $challenge_id, $submitted ) {
        $data = self::get_challenge( $challenge_id );

        if ( false === $data ) {
            return new WP_Error( 'introducao_otp_expired', __( 'O código expirou. Solicite um novo para continuar.', 'introducao' ) );
        }

        if ( empty( $submitted ) ) {
            return new WP_Error( 'introducao_otp_required', __( 'Informe o código de verificação.', 'introducao' ) );
        }

        if ( empty( $data['code_hash'] ) || ! wp_check_password( $submitted, $data['code_hash'] ) ) {
            $data['attempts'] = isset( $data['attempts'] ) ? (int) $data['attempts'] + 1 : 1;

            if ( $data['attempts'] >= self::OTP_MAX_ATTEMPTS ) {
                self::clear_challenge( $challenge_id );

                return new WP_Error( 'introducao_otp_max_attempts', __( 'Número máximo de tentativas excedido. Solicite um novo código.', 'introducao' ) );
            }

            self::persist_challenge( $challenge_id, $data, self::remaining_time( $data ) );

            return new WP_Error( 'introducao_otp_invalid', __( 'Código incorreto. Verifique o e-mail e tente novamente.', 'introducao' ) );
        }

        do_action( 'introducao_auth_challenge_verified', $challenge_id, $data );

        self::forget_client_challenge( $challenge_id );
        self::clear_challenge( $challenge_id );

        return $data;
    }

    /**
     * Indica se um usuário deve obrigatoriamente confirmar o segundo fator.
     *
     * @param WP_User|int $user Usuário ou ID do usuário.
     *
     * @return bool
     */
    public static function should_require_two_factor( $user ) {
        if ( is_numeric( $user ) ) {
            $user = get_user_by( 'id', (int) $user );
        }

        if ( ! ( $user instanceof WP_User ) ) {
            /**
             * Permite que o comportamento padrão seja alterado.
             */
            return (bool) apply_filters( 'introducao_auth_require_two_factor', true, null );
        }

        $meta   = get_user_meta( $user->ID, 'lae_two_factor_enabled', true );
        $enable = '' === $meta ? true : in_array( strtolower( (string) $meta ), array( '1', 'true', 'yes', 'on' ), true );

        return (bool) apply_filters( 'introducao_auth_require_two_factor', $enable, $user );
    }

    /**
     * Obtém um desafio armazenado.
     *
     * @param string $challenge_id Identificador do desafio.
     *
     * @return array|false
     */
    public static function get_challenge( $challenge_id ) {
        if ( empty( $challenge_id ) ) {
            return false;
        }

        return get_transient( self::OTP_TRANSIENT_PREFIX . $challenge_id );
    }

    /**
     * Remove um desafio armazenado.
     *
     * @param string $challenge_id Identificador do desafio.
     */
    public static function clear_challenge( $challenge_id ) {
        if ( empty( $challenge_id ) ) {
            return;
        }

        delete_transient( self::OTP_TRANSIENT_PREFIX . $challenge_id );

        self::forget_client_challenge( $challenge_id );
    }

    /**
     * Formata um e-mail para exibição parcial ao usuário.
     *
     * @param string $email Endereço completo.
     *
     * @return string
     */
    public static function mask_email( $email ) {
        if ( ! is_email( $email ) ) {
            return $email;
        }

        list( $local, $domain ) = explode( '@', $email );
        $local_length           = strlen( $local );

        if ( $local_length <= 2 ) {
            $masked_local = substr( $local, 0, 1 ) . '***';
        } else {
            $masked_local = substr( $local, 0, 1 ) . str_repeat( '*', max( 1, $local_length - 2 ) ) . substr( $local, -1 );
        }

        return $masked_local . '@' . $domain;
    }

    /**
     * Persiste o desafio ativo vinculado ao visitante atual para compartilhamento entre plugins.
     *
     * @param string $challenge_id Identificador do desafio.
     * @param array  $data         Estado bruto do desafio.
     */
    private static function remember_client_challenge( $challenge_id, array $data ) {
        if ( empty( $challenge_id ) ) {
            return;
        }

        $context = self::extract_client_context( $challenge_id, $data );

        if ( empty( $context ) ) {
            return;
        }

        $context['resend_in'] = self::seconds_until_resend( $challenge_id );
        $context['ttl']       = self::seconds_until_expiration( $challenge_id );
        $context['ttl_label'] = $context['ttl'] > 0
            ? sprintf( __( 'O código expira em %s.', 'introducao' ), self::format_duration( $context['ttl'] ) )
            : '';

        $existing = get_transient( self::get_client_state_key() );

        if ( is_array( $existing ) ) {
            if ( empty( $context['identifier'] ) && ! empty( $existing['identifier'] ) ) {
                $context['identifier'] = $existing['identifier'];
            }

            if ( empty( $context['pending_login'] ) && ! empty( $existing['pending_login'] ) ) {
                $context['pending_login'] = $existing['pending_login'];
            }

            if ( empty( $context['pending_email'] ) && ! empty( $existing['pending_email'] ) ) {
                $context['pending_email'] = $existing['pending_email'];
            }

            if ( empty( $context['pending_name'] ) && ! empty( $existing['pending_name'] ) ) {
                $context['pending_name'] = $existing['pending_name'];
            }

            if ( empty( $context['friendly_name'] ) && ! empty( $existing['friendly_name'] ) ) {
                $context['friendly_name'] = $existing['friendly_name'];
            }

            if ( ! isset( $context['remember'] ) && isset( $existing['remember'] ) ) {
                $context['remember'] = (bool) $existing['remember'];
            }

            if ( empty( $context['redirect'] ) && ! empty( $existing['redirect'] ) ) {
                $context['redirect'] = $existing['redirect'];
            }

            if ( isset( $existing['issued_at'] ) ) {
                $context['issued_at'] = (int) $existing['issued_at'];
            }
        }

        if ( ! isset( $context['issued_at'] ) ) {
            $context['issued_at'] = time();
        }

        set_transient( self::get_client_state_key(), $context, self::OTP_TTL );
    }

    /**
     * Persiste uma identidade sincronizada do cliente atual.
     *
     * @param WP_User|int|array $user    Instância, ID ou dados sanitizados do usuário.
     * @param array             $context Contexto adicional (ex.: origem, redirecionamento).
     */
    public static function remember_client_identity( $user, $context = array() ) {
        $identity = array();

        if ( $user instanceof WP_User ) {
            if ( ! $user->exists() ) {
                return;
            }

            $identity = self::build_identity_from_user( $user );
        } elseif ( is_numeric( $user ) ) {
            $user = get_user_by( 'id', (int) $user );

            if ( $user instanceof WP_User ) {
                $identity = self::build_identity_from_user( $user );
            }
        } elseif ( is_array( $user ) ) {
            $display_name = isset( $user['display_name'] ) ? sanitize_text_field( $user['display_name'] ) : '';
            $user_login   = isset( $user['user_login'] ) ? sanitize_text_field( $user['user_login'] ) : '';
            $user_email   = isset( $user['user_email'] ) ? sanitize_email( $user['user_email'] ) : '';

            if ( $display_name || $user_login || $user_email ) {
                $identity = array(
                    'user_id'      => isset( $user['user_id'] ) ? (int) $user['user_id'] : 0,
                    'display_name' => $display_name ? $display_name : $user_login,
                    'friendly_name'=> $display_name ? $display_name : $user_login,
                    'user_login'   => $user_login,
                    'user_email'   => $user_email,
                    'avatar_url'   => isset( $user['avatar_url'] ) ? esc_url_raw( $user['avatar_url'] ) : '',
                );
            }
        }

        if ( empty( $identity ) ) {
            return;
        }

        if ( empty( $identity['friendly_name'] ) && ! empty( $identity['display_name'] ) ) {
            $identity['friendly_name'] = $identity['display_name'];
        }

        if ( empty( $identity['initial'] ) ) {
            $initial_source = ! empty( $identity['display_name'] ) ? $identity['display_name'] : ( isset( $identity['user_login'] ) ? $identity['user_login'] : '' );
            $identity['initial'] = self::generate_initial( $initial_source );
        }

        if ( empty( $identity['avatar_url'] ) && ! empty( $identity['user_id'] ) ) {
            $identity['avatar_url'] = get_avatar_url( $identity['user_id'], array( 'size' => 160 ) );
        }

        if ( isset( $context['redirect'] ) && $context['redirect'] ) {
            $identity['redirect'] = esc_url_raw( $context['redirect'] );
        }

        $identity['synced_at'] = time();

        set_transient( self::get_client_identity_key(), $identity, self::CLIENT_IDENTITY_TTL );
    }

    /**
     * Recupera a identidade sincronizada do cliente atual.
     *
     * @return array
     */
    public static function get_client_identity() {
        $identity = get_transient( self::get_client_identity_key() );

        return is_array( $identity ) ? $identity : array();
    }

    /**
     * Remove a identidade sincronizada do cliente.
     */
    public static function forget_client_identity() {
        delete_transient( self::get_client_identity_key() );
    }

    /**
     * Calcula a chave de armazenamento da identidade do cliente.
     *
     * @return string
     */
    private static function get_client_identity_key() {
        return self::CLIENT_IDENTITY_TRANSIENT_PREFIX . self::get_client_fingerprint();
    }

    /**
     * Monta a identidade sincronizada a partir de um usuário do WordPress.
     *
     * @param WP_User $user Usuário autenticado.
     *
     * @return array
     */
    private static function build_identity_from_user( WP_User $user ) {
        $display_name = $user->display_name ? $user->display_name : $user->user_login;

        return array(
            'user_id'      => (int) $user->ID,
            'display_name' => sanitize_text_field( $display_name ),
            'friendly_name'=> sanitize_text_field( $display_name ),
            'user_login'   => sanitize_text_field( $user->user_login ),
            'user_email'   => sanitize_email( $user->user_email ),
            'avatar_url'   => get_avatar_url( $user->ID, array( 'size' => 160 ) ),
            'initial'      => self::generate_initial( $display_name ),
        );
    }

    /**
     * Gera a inicial a partir de um nome exibido.
     *
     * @param string $value Nome de referência.
     *
     * @return string
     */
    private static function generate_initial( $value ) {
        $value = trim( (string) $value );

        if ( '' === $value ) {
            return 'U';
        }

        $charset = get_bloginfo( 'charset' );

        $first_char = function_exists( 'mb_substr' )
            ? mb_substr( $value, 0, 1, $charset )
            : substr( $value, 0, 1 );

        return function_exists( 'mb_strtoupper' )
            ? mb_strtoupper( $first_char, $charset )
            : strtoupper( $first_char );
    }

    /**
     * Recupera o desafio ativo associado ao cliente atual.
     *
     * @return array
     */
    public static function get_client_challenge_context() {
        $state = get_transient( self::get_client_state_key() );

        if ( ! is_array( $state ) || empty( $state['challenge'] ) ) {
            return array();
        }

        $challenge = self::get_challenge( $state['challenge'] );

        if ( false === $challenge ) {
            self::forget_client_challenge( $state['challenge'] );

            return array();
        }

        $context = self::extract_client_context( $state['challenge'], $challenge );

        if ( empty( $context ) ) {
            self::forget_client_challenge( $state['challenge'] );

            return array();
        }

        $context['resend_in'] = self::seconds_until_resend( $state['challenge'] );
        $context['ttl']       = self::seconds_until_expiration( $state['challenge'] );
        $context['ttl_label'] = $context['ttl'] > 0
            ? sprintf( __( 'O código expira em %s.', 'introducao' ), self::format_duration( $context['ttl'] ) )
            : '';

        if ( isset( $state['issued_at'] ) ) {
            $context['issued_at'] = (int) $state['issued_at'];
        }

        if ( isset( $state['remember'] ) && ! isset( $context['remember'] ) ) {
            $context['remember'] = (bool) $state['remember'];
        }

        if ( empty( $context['identifier'] ) && ! empty( $state['identifier'] ) ) {
            $context['identifier'] = $state['identifier'];
        }

        if ( empty( $context['pending_login'] ) && ! empty( $state['pending_login'] ) ) {
            $context['pending_login'] = $state['pending_login'];
        }

        if ( empty( $context['pending_email'] ) && ! empty( $state['pending_email'] ) ) {
            $context['pending_email'] = $state['pending_email'];
        }

        if ( empty( $context['pending_name'] ) && ! empty( $state['pending_name'] ) ) {
            $context['pending_name'] = $state['pending_name'];
        }

        if ( empty( $context['redirect'] ) && ! empty( $state['redirect'] ) ) {
            $context['redirect'] = $state['redirect'];
        }

        if ( empty( $context['friendly_name'] ) && ! empty( $state['friendly_name'] ) ) {
            $context['friendly_name'] = $state['friendly_name'];
        }

        return $context;
    }

    /**
     * Remove o desafio ativo associado ao cliente atual.
     *
     * @param string $challenge_id Identificador do desafio que deve ser esquecido.
     */
    public static function forget_client_challenge( $challenge_id = '' ) {
        $key   = self::get_client_state_key();
        $state = get_transient( $key );

        if ( ! is_array( $state ) ) {
            return;
        }

        if ( $challenge_id && isset( $state['challenge'] ) && $state['challenge'] !== $challenge_id ) {
            return;
        }

        delete_transient( $key );
    }

    /**
     * Extrai e sanitiza o contexto que será compartilhado com o frontend.
     *
     * @param string $challenge_id Identificador do desafio.
     * @param array  $data         Dados persistidos do desafio.
     *
     * @return array
     */
    private static function extract_client_context( $challenge_id, array $data ) {
        if ( empty( $challenge_id ) ) {
            return array();
        }

        $type = isset( $data['type'] ) && in_array( $data['type'], array( 'login', 'register' ), true )
            ? $data['type']
            : 'login';

        $email   = isset( $data['user_email'] ) ? sanitize_email( $data['user_email'] ) : '';
        $friendly_name = '';

        if ( isset( $data['user_name'] ) ) {
            $friendly_name = sanitize_text_field( $data['user_name'] );
        }

        $context = array(
            'challenge'     => $challenge_id,
            'type'          => $type,
            'email'         => $email,
            'masked_email'  => $email ? self::mask_email( $email ) : '',
            'identifier'    => '',
            'pending_login' => '',
            'pending_email' => '',
            'pending_name'  => '',
            'remember'      => ( isset( $data['remember'] ) && $data['remember'] ),
            'redirect'      => '',
            'friendly_name' => $friendly_name,
        );

        if ( 'login' === $type ) {
            if ( isset( $data['submitted_login'] ) ) {
                $context['identifier'] = sanitize_text_field( $data['submitted_login'] );
            } elseif ( isset( $data['user_login'] ) ) {
                $context['identifier'] = sanitize_text_field( $data['user_login'] );
            }

            if ( '' === $context['friendly_name'] && isset( $data['user_login'] ) ) {
                $context['friendly_name'] = sanitize_text_field( $data['user_login'] );
            }
        } else {
            if ( isset( $data['pending_user']['user_login'] ) ) {
                $context['pending_login'] = sanitize_text_field( $data['pending_user']['user_login'] );
            }

            if ( isset( $data['pending_user']['user_email'] ) ) {
                $context['pending_email'] = sanitize_email( $data['pending_user']['user_email'] );
            }

            if ( isset( $data['pending_meta']['display_name'] ) ) {
                $context['pending_name'] = sanitize_text_field( $data['pending_meta']['display_name'] );
            } elseif ( isset( $data['pending_user']['user_login'] ) ) {
                $context['pending_name'] = sanitize_text_field( $data['pending_user']['user_login'] );
            }

            if ( '' === $context['friendly_name'] && '' !== $context['pending_name'] ) {
                $context['friendly_name'] = $context['pending_name'];
            }
        }

        if ( isset( $data['context']['redirect_to'] ) ) {
            $context['redirect'] = esc_url_raw( $data['context']['redirect_to'] );
        }

        return $context;
    }

    /**
     * Recupera o identificador único do cliente atual.
     *
     * @return string
     */
    private static function get_client_state_key() {
        return self::CLIENT_STATE_TRANSIENT_PREFIX . self::get_client_fingerprint();
    }

    /**
     * Calcula uma impressão digital estável para o visitante.
     *
     * @return string
     */
    private static function get_client_fingerprint() {
        $address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
        $agent   = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : 'unknown';
        $accept  = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) : '';
        $seed    = implode( '|', array( $address, $agent, $accept ) );

        return hash_hmac( 'sha256', $seed, wp_salt( 'auth' ) );
    }

    /**
     * Calcula o tempo restante de vida do desafio.
     *
     * @param array $data Dados armazenados.
     *
     * @return int
     */
    private static function remaining_time( $data ) {
        $created_at = isset( $data['created_at'] ) ? (int) $data['created_at'] : time();
        $elapsed    = time() - $created_at;
        $remaining  = self::OTP_TTL - $elapsed;

        return max( 60, $remaining );
    }

    /**
     * Calcula em quantos segundos um novo envio poderá ser solicitado.
     *
     * @param string $challenge_id Identificador do desafio.
     *
     * @return int
     */
    public static function seconds_until_resend( $challenge_id ) {
        $data = self::get_challenge( $challenge_id );

        if ( false === $data ) {
            return 0;
        }

        $last_sent = isset( $data['last_sent'] ) ? (int) $data['last_sent'] : 0;

        if ( ! $last_sent ) {
            return 0;
        }

        $elapsed   = time() - $last_sent;
        $remaining = self::OTP_RESEND_INTERVAL - $elapsed;

        return max( 0, $remaining );
    }

    /**
     * Calcula o tempo restante de validade do desafio em segundos.
     *
     * @param string $challenge_id Identificador do desafio.
     *
     * @return int
     */
    public static function seconds_until_expiration( $challenge_id ) {
        $data = self::get_challenge( $challenge_id );

        if ( false === $data ) {
            return 0;
        }

        $created_at = isset( $data['created_at'] ) ? (int) $data['created_at'] : time();
        $elapsed    = time() - $created_at;

        return max( 0, self::OTP_TTL - $elapsed );
    }

    /**
     * Formata uma duração em segundos para apresentação ao usuário.
     *
     * @param int $seconds Quantidade em segundos.
     *
     * @return string
     */
    public static function format_duration( $seconds ) {
        $seconds = max( 0, (int) $seconds );

        if ( $seconds < MINUTE_IN_SECONDS ) {
            return sprintf( _n( '%d segundo', '%d segundos', $seconds, 'introducao' ), $seconds );
        }

        $minutes   = floor( $seconds / MINUTE_IN_SECONDS );
        $remaining = $seconds % MINUTE_IN_SECONDS;
        $minutes_label = sprintf( _n( '%d minuto', '%d minutos', $minutes, 'introducao' ), $minutes );

        if ( $remaining <= 0 ) {
            return $minutes_label;
        }

        $seconds_label = sprintf( _n( '%d segundo', '%d segundos', $remaining, 'introducao' ), $remaining );

        return sprintf(
            /* translators: 1: minutes label, 2: seconds label */
            __( '%1$s e %2$s', 'introducao' ),
            $minutes_label,
            $seconds_label
        );
    }

    /**
     * Persiste um desafio na API de transients.
     *
     * @param string $challenge_id Identificador.
     * @param array  $data         Dados do desafio.
     * @param int    $ttl          Tempo de expiração opcional.
     *
     * @return bool
     */
    private static function persist_challenge( $challenge_id, $data, $ttl = self::OTP_TTL ) {
        if ( empty( $challenge_id ) ) {
            return false;
        }

        return set_transient( self::OTP_TRANSIENT_PREFIX . $challenge_id, $data, $ttl );
    }

    /**
     * Limpa e sanitiza dados extras armazenados durante o desafio.
     *
     * @param array $extra Dados adicionais.
     *
     * @return array
     */
    private static function prepare_context( $extra ) {
        if ( empty( $extra ) || ! is_array( $extra ) ) {
            return array();
        }

        $sanitized = array();

        foreach ( $extra as $key => $value ) {
            $clean_key = sanitize_key( $key );

            if ( '' === $clean_key ) {
                continue;
            }

            if ( ! is_scalar( $value ) ) {
                continue;
            }

            if ( 'redirect_to' === $clean_key ) {
                $sanitized[ $clean_key ] = esc_url_raw( (string) $value );
                continue;
            }

            $sanitized[ $clean_key ] = sanitize_text_field( (string) $value );
        }

        return $sanitized;
    }

    /**
     * Sanitiza metadados pendentes associados ao cadastro.
     *
     * @param array $meta Metadados fornecidos.
     *
     * @return array
     */
    private static function prepare_pending_meta( $meta, $fallback_display_name = '' ) {
        if ( empty( $meta ) || ! is_array( $meta ) ) {
            $meta = array();
        }

        $sanitized = array();

        foreach ( $meta as $key => $value ) {
            $clean_key = sanitize_key( $key );

            if ( '' === $clean_key ) {
                continue;
            }

            if ( is_scalar( $value ) ) {
                $sanitized[ $clean_key ] = sanitize_text_field( (string) $value );
            }
        }

        if ( ! isset( $sanitized['display_name'] ) && '' !== $fallback_display_name ) {
            $sanitized['display_name'] = sanitize_text_field( $fallback_display_name );
        }

        if ( isset( $sanitized['display_name'] ) && ! isset( $sanitized['first_name'] ) ) {
            $parts = preg_split( '/\s+/', $sanitized['display_name'] );

            if ( ! empty( $parts ) ) {
                $sanitized['first_name'] = array_shift( $parts );

                if ( ! empty( $parts ) && ! isset( $sanitized['last_name'] ) ) {
                    $sanitized['last_name'] = trim( implode( ' ', $parts ) );
                }
            }
        }

        return $sanitized;
    }

    /**
     * Envia o e-mail com o código de verificação.
     *
     * @param string $email Endereço do destinatário.
     * @param string $name  Nome do destinatário.
     * @param string $code  Código numérico.
     * @param string $type  Tipo de operação: login|register.
     *
     * @return bool
     */
    private static function send_code_email( $email, $name, $code, $type ) {
        if ( empty( $email ) ) {
            return false;
        }

        $subject = ( 'login' === $type )
            ? __( 'Academia da Comunicação - Autenticação de 2 Fatores', 'introducao' )
            : __( 'Confirme sua conta na Academia', 'introducao' );

        $plugin = Introducao_Plugin::get_instance();

        $body = $plugin->render_template(
            'email-otp.php',
            array(
                'code' => $code,
                'name' => $name,
                'type' => $type,
            )
        );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        return wp_mail( $email, $subject, $body, $headers );
    }

    /**
     * Gera um identificador único para o desafio.
     *
     * @return string
     */
    private static function generate_challenge_id() {
        return wp_generate_password( 20, false, false );
    }

    /**
     * Gera um código numérico de seis dígitos.
     *
     * @return string
     */
    private static function generate_code() {
        $number = wp_rand( 100000, 999999 );

        return (string) $number;
    }

    /**
     * Remove marcações HTML e entidades de mensagens de erro.
     *
     * @param string|WP_Error $error Mensagem original ou objeto de erro.
     *
     * @return string
     */
    public static function clean_auth_error( $error ) {
        if ( $error instanceof WP_Error ) {
            $error = $error->get_error_message();
        }

        if ( ! is_string( $error ) || '' === $error ) {
            return '';
        }

        return trim( wp_strip_all_tags( wp_specialchars_decode( $error ) ) );
    }

    /**
     * Normaliza mensagens de erro conhecidas removendo links auxiliares e códigos.
     *
     * @param string|WP_Error $error Mensagem original ou objeto de erro.
     *
     * @return string Mensagem sanitizada pronta para exibição ao usuário.
     */
    public static function normalize_auth_error( $error ) {
        $message = self::clean_auth_error( $error );

        if ( '' === $message ) {
            return '';
        }

        $code = '';

        if ( $error instanceof WP_Error ) {
            $code = $error->get_error_code();

            if ( ! $code ) {
                $codes = $error->get_error_codes();

                if ( ! empty( $codes ) ) {
                    $code = reset( $codes );
                }
            }
        }

        switch ( $code ) {
            case 'incorrect_password':
                return __( 'A senha informada está incorreta.', 'introducao' );
            case 'invalid_email':
                return __( 'Não encontramos uma conta com esse e-mail.', 'introducao' );
            case 'invalid_username':
            case 'invalidcombo':
                return __( 'Não encontramos uma conta com essas credenciais.', 'introducao' );
        }

        $message = preg_replace( '/^Erro:\s*/iu', '', $message );
        $message = preg_replace( '/^Error:\s*/iu', '', $message );

        $phrases_to_strip = array(
            'Perdeu a senha?',
            'Esqueceu sua senha?',
            'Lost your password?',
            'Registre-se',
            'Cadastre-se',
            'Register',
        );

        foreach ( $phrases_to_strip as $needle ) {
            if ( false !== stripos( $message, $needle ) ) {
                $message = str_ireplace( $needle, '', $message );
            }
        }

        $message = preg_replace(
            '/\s*(Perdeu a senha\?|Esqueceu sua senha\?|Lost your password\?|Registre-se.*|Cadastre-se.*|Register.*)$/iu',
            '',
            $message
        );

        return trim( $message );
    }
}
