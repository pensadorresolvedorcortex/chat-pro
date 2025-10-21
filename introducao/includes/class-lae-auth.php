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
     * Cria um desafio de segundo fator para login.
     *
     * @param WP_User $user     Usuário autenticado.
     * @param bool    $remember Indica se o cookie deve ser persistente.
     *
     * @return array|WP_Error
     */
    public static function create_login_challenge( WP_User $user, $remember = false, $identifier = '' ) {
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
            'user_email'      => $user->user_email,
            'user_name'       => $user->display_name ? $user->display_name : $user->user_login,
            'user_login'      => $user->user_login,
            'submitted_login' => $identifier ? $identifier : $user->user_login,
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

        return array(
            'challenge_id' => $challenge_id,
            'email'        => $data['user_email'],
        );
    }

    /**
     * Cria um desafio de segundo fator para cadastro.
     *
     * @param string $user_login Nome de usuário desejado.
     * @param string $user_email Email informado.
     * @param string $user_pass  Senha escolhida.
     *
     * @return array|WP_Error
     */
    public static function create_register_challenge( $user_login, $user_email, $user_pass ) {
        $code         = self::generate_code();
        $challenge_id = self::generate_challenge_id();
        $now          = time();

        $data = array(
            'type'         => 'register',
            'code_hash'    => wp_hash_password( $code ),
            'created_at'   => $now,
            'attempts'     => 0,
            'user_email'   => $user_email,
            'user_name'    => $user_login,
            'pending_user' => array(
                'user_login' => $user_login,
                'user_email' => $user_email,
                'user_pass'  => $user_pass,
            ),
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

        return array(
            'challenge_id' => $challenge_id,
            'email'        => $user_email,
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

        self::clear_challenge( $challenge_id );

        return $data;
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
            ? __( 'Seu código de acesso seguro', 'introducao' )
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
