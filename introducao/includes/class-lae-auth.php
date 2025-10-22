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
     * Quantidade máxima de reenvios permitidos por desafio.
     */
    const OTP_MAX_RESENDS = 5;

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
            'pending_meta' => self::prepare_pending_meta( $meta ),
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
    private static function prepare_pending_meta( $meta ) {
        if ( empty( $meta ) || ! is_array( $meta ) ) {
            return array();
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
}
