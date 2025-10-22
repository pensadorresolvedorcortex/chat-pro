<?php
/**
 * Principal handler para inicialização e assets.
 */
class LAE_Plugin {

    /**
     * Instância singleton.
     *
     * @var LAE_Plugin
     */
    private static $instance;

    /**
     * Instância do gerenciador de páginas.
     *
     * @var LAE_Pages
     */
    private $pages;

    /**
     * Instância do registrador de shortcodes.
     *
     * @var LAE_Shortcodes
     */
    private $shortcodes;

    /**
     * Recupera a instância única do plugin.
     *
     * @return LAE_Plugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Inicializa hooks principais do plugin.
     */
    public function init() {
        $this->pages      = new LAE_Pages();
        $this->shortcodes = new LAE_Shortcodes( $this, $this->pages );

        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
        add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
        add_action( 'wp', array( $this, 'protect_profile_from_woocommerce' ), 1 );
        add_action( 'wp_ajax_lae_upload_avatar', array( $this, 'handle_avatar_upload' ) );
        add_action( 'wp_ajax_lae_remove_avatar', array( $this, 'handle_avatar_remove' ) );
        add_action( 'wp_ajax_lae_toggle_two_factor', array( $this, 'handle_two_factor_toggle' ) );
        add_action( 'wp_ajax_lae_change_password', array( $this, 'handle_password_change' ) );
        add_action( 'wp_ajax_lae_login_user', array( $this, 'handle_login_submission' ) );
        add_action( 'wp_ajax_nopriv_lae_login_user', array( $this, 'handle_login_submission' ) );
        add_action( 'wp_ajax_lae_register_user', array( $this, 'handle_register_submission' ) );
        add_action( 'wp_ajax_nopriv_lae_register_user', array( $this, 'handle_register_submission' ) );
        add_action( 'wp_ajax_lae_resend_two_factor', array( $this, 'handle_ajax_resend_two_factor' ) );
        add_action( 'wp_ajax_nopriv_lae_resend_two_factor', array( $this, 'handle_ajax_resend_two_factor' ) );
        add_filter( 'pre_get_avatar_data', array( $this, 'filter_custom_avatar' ), 10, 2 );
    }

    /**
     * Carrega o textdomain do plugin.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'login-academia-da-educacao', false, dirname( plugin_basename( LAE_PLUGIN_FILE ) ) . '/languages' );
    }

    /**
     * Registra os assets de frontend.
     */
    public function register_assets() {
        wp_register_style(
            'lae-user-menu',
            LAE_PLUGIN_URL . 'assets/css/style.css',
            array(),
            LAE_PLUGIN_VERSION
        );

        wp_register_script(
            'lae-user-menu',
            LAE_PLUGIN_URL . 'assets/js/script.js',
            array(),
            LAE_PLUGIN_VERSION,
            true
        );
    }

    /**
     * Registra a página administrativa do plugin.
     */
    public function register_admin_page() {
        add_menu_page(
            __( 'Login Academia da Educação', 'login-academia-da-educacao' ),
            __( 'Login Hub', 'login-academia-da-educacao' ),
            'manage_options',
            'adc-login-hub',
            array( $this, 'render_admin_page' ),
            'dashicons-admin-users',
            58
        );
    }

    /**
     * Renderiza a página administrativa principal.
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'login-academia-da-educacao' ) );
        }

        $registered_pages = $this->pages instanceof LAE_Pages ? $this->pages->get_registered_pages() : array();
        $pages            = array();

        foreach ( $registered_pages as $slug => $data ) {
            $pages[] = array(
                'title'     => isset( $data['title'] ) ? $data['title'] : '',
                'shortcode' => isset( $data['shortcode'] ) ? $data['shortcode'] : '',
                'url'       => $this->pages instanceof LAE_Pages ? $this->pages->get_page_url( $slug ) : '',
            );
        }

        echo $this->render_template(
            'admin-overview.php',
            array(
                'pages'          => $pages,
                'menu_shortcode' => '[lae_user_menu]',
            )
        );
    }

    /**
     * Enfileira os assets necessários para o menu do usuário.
     */
    public function enqueue_assets() {
        $this->enqueue_style();
        wp_enqueue_script( 'lae-user-menu' );

        wp_localize_script(
            'lae-user-menu',
            'LAEAvatar',
            array(
                'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                'nonce'          => wp_create_nonce( 'lae_avatar_nonce' ),
                'defaultAvatar'  => LAE_PLUGIN_URL . 'assets/img/default-avatar.svg',
                'messages'       => array(
                    'uploading'     => __( 'Enviando foto...', 'login-academia-da-educacao' ),
                    'removing'      => __( 'Removendo foto...', 'login-academia-da-educacao' ),
                    'uploadSuccess' => __( 'Foto atualizada com sucesso!', 'login-academia-da-educacao' ),
                    'removeSuccess' => __( 'Foto removida com sucesso.', 'login-academia-da-educacao' ),
                    'error'         => __( 'Não foi possível atualizar a foto de perfil.', 'login-academia-da-educacao' ),
                ),
            )
        );

        wp_localize_script(
            'lae-user-menu',
            'LAEAccount',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonces'  => array(
                    'twoFactor' => wp_create_nonce( 'lae_toggle_two_factor' ),
                    'password'  => wp_create_nonce( 'lae_change_password' ),
                ),
                'messages' => array(
                    'twoFactorEnabling'  => __( 'Ativando autenticação em duas etapas...', 'login-academia-da-educacao' ),
                    'twoFactorDisabling' => __( 'Desativando autenticação em duas etapas...', 'login-academia-da-educacao' ),
                    'twoFactorEnabled'   => __( 'Autenticação em duas etapas ativada!', 'login-academia-da-educacao' ),
                    'twoFactorDisabled'  => __( 'Autenticação em duas etapas desativada.', 'login-academia-da-educacao' ),
                    'twoFactorError'     => __( 'Não foi possível atualizar a autenticação em duas etapas.', 'login-academia-da-educacao' ),
                    'passwordWorking'    => __( 'Atualizando senha...', 'login-academia-da-educacao' ),
                    'passwordSuccess'    => __( 'Senha atualizada com sucesso. Faça login novamente.', 'login-academia-da-educacao' ),
                    'passwordError'      => __( 'Não foi possível atualizar a senha.', 'login-academia-da-educacao' ),
                    'passwordMismatch'   => __( 'As senhas informadas não coincidem.', 'login-academia-da-educacao' ),
                    'passwordWeak'       => __( 'A nova senha deve ter ao menos 8 caracteres.', 'login-academia-da-educacao' ),
                ),
                'labels' => array(
                    'twoFactorEnabled'  => __( 'Ativa', 'login-academia-da-educacao' ),
                    'twoFactorDisabled' => __( 'Desativada', 'login-academia-da-educacao' ),
                    'enableTwoFactor'   => __( 'Ativar', 'login-academia-da-educacao' ),
                    'disableTwoFactor'  => __( 'Desativar', 'login-academia-da-educacao' ),
                ),
            )
        );

        wp_localize_script(
            'lae-user-menu',
            'LAELogin',
            array(
                'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
                'redirect' => $this->get_login_redirect_url(),
                'nonces'   => array(
                    'login'   => wp_create_nonce( 'lae_login_user' ),
                    'register'=> wp_create_nonce( 'lae_register_user' ),
                    'resend'  => wp_create_nonce( 'lae_resend_two_factor' ),
                ),
                'challenge' => $this->get_frontend_challenge_context(),
                'messages' => array(
                    'loginWorking'     => __( 'Validando seus dados...', 'login-academia-da-educacao' ),
                    'registerWorking'  => __( 'Criando sua conta...', 'login-academia-da-educacao' ),
                    'success'          => __( 'Tudo certo! Estamos te redirecionando.', 'login-academia-da-educacao' ),
                    'twoFactorRequired'=> __( 'Enviamos um código de verificação para o seu email.', 'login-academia-da-educacao' ),
                    'twoFactorInvalid' => __( 'O código informado está incorreto. Confira e tente novamente.', 'login-academia-da-educacao' ),
                    'twoFactorExpired' => __( 'O código expirou. Enviamos um novo para você.', 'login-academia-da-educacao' ),
                    'twoFactorExpires' => __( 'O código expira em %s.', 'login-academia-da-educacao' ),
                    'resendWait'       => __( 'Aguarde alguns instantes antes de solicitar um novo código.', 'login-academia-da-educacao' ),
                    'resendSuccess'    => __( 'Um novo código foi enviado para o seu email.', 'login-academia-da-educacao' ),
                    'error'            => __( 'Não foi possível concluir a solicitação. Tente novamente.', 'login-academia-da-educacao' ),
                    'registrationClosed'=> __( 'No momento não é possível criar novas contas.', 'login-academia-da-educacao' ),
                    'passwordWeak'     => __( 'A senha precisa ter ao menos 8 caracteres.', 'login-academia-da-educacao' ),
                    'missingFields'    => __( 'Preencha todos os campos obrigatórios.', 'login-academia-da-educacao' ),
                    'passwordMismatch' => __( 'As senhas informadas não coincidem.', 'login-academia-da-educacao' ),
                    'passwordStronger' => __( 'Inclua letras maiúsculas, números ou símbolos para fortalecer sua senha.', 'login-academia-da-educacao' ),
                ),
                'labels'   => array(
                    'showPassword' => __( 'Mostrar', 'login-academia-da-educacao' ),
                    'hidePassword' => __( 'Ocultar', 'login-academia-da-educacao' ),
                ),
                'strength' => array(
                    'labels' => array(
                        'very-weak' => __( 'Muito fraca', 'login-academia-da-educacao' ),
                        'weak'      => __( 'Fraca', 'login-academia-da-educacao' ),
                        'medium'    => __( 'Moderada', 'login-academia-da-educacao' ),
                        'strong'    => __( 'Forte', 'login-academia-da-educacao' ),
                    ),
                    'hints'  => array(
                        'very-weak' => __( 'Use pelo menos 8 caracteres.', 'login-academia-da-educacao' ),
                        'weak'      => __( 'Adicione letras maiúsculas ou números.', 'login-academia-da-educacao' ),
                        'medium'    => __( 'Inclua símbolos para aumentar a proteção.', 'login-academia-da-educacao' ),
                        'strong'    => __( 'Excelente! Sua senha está segura.', 'login-academia-da-educacao' ),
                    ),
                ),
            )
        );
    }

    /**
     * Enfileira apenas as folhas de estilo do plugin.
     */
    public function enqueue_style() {
        wp_enqueue_style( 'lae-user-menu' );
    }

    /**
     * Impede que o WooCommerce sobrescreva a página de perfil do plugin.
     */
    public function protect_profile_from_woocommerce() {
        if ( is_admin() ) {
            return;
        }

        if ( ! ( class_exists( 'WooCommerce' ) || function_exists( 'WC' ) ) ) {
            return;
        }

        if ( ! $this->pages instanceof LAE_Pages ) {
            return;
        }

        if ( ! $this->pages->is_current_page_slug( 'minha-conta-academia' ) ) {
            return;
        }

        add_filter( 'pre_option_woocommerce_myaccount_page_id', array( $this, 'filter_wc_myaccount_page_id' ) );

        if ( function_exists( 'remove_filter' ) && has_filter( 'the_content', 'woocommerce_account_content' ) ) {
            remove_filter( 'the_content', 'woocommerce_account_content', 10 );
        }
    }

    /**
     * Retorna 0 para o ID da página Minha Conta do WooCommerce durante a renderização do perfil personalizado.
     *
     * @return int
     */
    public function filter_wc_myaccount_page_id( $pre ) {
        return 0;
    }

    /**
     * Monta o contexto inicial de desafio compartilhado com o frontend.
     *
     * @return array
     */
    private function get_frontend_challenge_context() {
        if ( ! class_exists( 'Introducao_Auth' ) ) {
            return array();
        }

        $context = Introducao_Auth::get_client_challenge_context();

        if ( empty( $context ) || empty( $context['challenge'] ) ) {
            return array();
        }

        $masked = '';

        if ( isset( $context['masked_email'] ) && $context['masked_email'] ) {
            $masked = $context['masked_email'];
        } elseif ( isset( $context['email'] ) ) {
            $masked = Introducao_Auth::mask_email( $context['email'] );
        }

        $message = ( isset( $context['type'] ) && 'register' === $context['type'] )
            ? sprintf( __( 'Enviamos um código de ativação para %s. Informe-o para finalizar seu cadastro.', 'login-academia-da-educacao' ), $masked )
            : sprintf( __( 'Enviamos um código de verificação para %s. Informe-o para concluir seu acesso.', 'login-academia-da-educacao' ), $masked );

        $context['masked_email'] = $masked;
        $context['message']      = $message;

        return $context;
    }

    /**
     * Retorna a URL de redirecionamento padrão após o login pelo plugin.
     *
     * @return string
     */
    public function get_login_redirect_url() {
        $target = home_url( '/questoes-de-concursos/' );

        return apply_filters( 'lae_login_redirect', $target );
    }

    /**
     * Processa a submissão de login via AJAX.
     */
    public function handle_login_submission() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'lae_login_user' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Requisição inválida. Atualize a página e tente novamente.', 'login-academia-da-educacao' ),
                ),
                400
            );
        }

        $redirect_requested = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : '';
        $redirect_safe      = $this->prepare_login_redirect_url( $redirect_requested );
        $challenge_id       = isset( $_POST['challenge'] ) ? sanitize_text_field( wp_unslash( $_POST['challenge'] ) ) : '';
        $code               = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';

        if ( is_user_logged_in() && ! $challenge_id ) {
            wp_send_json_success(
                array(
                    'status'   => 'logged_in',
                    'redirect' => $redirect_safe,
                    'message'  => __( 'Você já está autenticado.', 'login-academia-da-educacao' ),
                )
            );
        }

        if ( $challenge_id && $code ) {
            if ( ! class_exists( 'Introducao_Auth' ) ) {
                wp_send_json_error(
                    array(
                        'message' => __( 'Não foi possível validar o código no momento. Tente novamente.', 'login-academia-da-educacao' ),
                    )
                );
            }

            $verification = Introducao_Auth::verify_code( $challenge_id, $code );

            if ( is_wp_error( $verification ) ) {
                $status          = 'two_factor_required';
                $error_code      = $verification->get_error_code();
                $challenge_state = Introducao_Auth::get_challenge( $challenge_id );
                $context         = $this->build_challenge_context( $challenge_id, $challenge_state );

                if ( in_array( $error_code, array( 'introducao_otp_invalid', 'introducao_otp_required' ), true ) ) {
                    $status = 'two_factor_invalid';
                } elseif ( in_array( $error_code, array( 'introducao_otp_expired', 'introducao_otp_max_attempts' ), true ) ) {
                    $status = 'two_factor_expired';
                }

                wp_send_json_error(
                    array(
                        'status'    => $status,
                        'message'   => $verification->get_error_message(),
                        'challenge' => $challenge_id,
                        'context'   => $context,
                    )
                );
            }

            $user_id  = isset( $verification['user_id'] ) ? (int) $verification['user_id'] : 0;
            $remember = ! empty( $verification['remember'] );
            $context  = isset( $verification['context'] ) && is_array( $verification['context'] ) ? $verification['context'] : array();
            $user     = $user_id ? get_user_by( 'id', $user_id ) : null;

            if ( ! ( $user instanceof WP_User ) ) {
                wp_send_json_error(
                    array(
                        'message' => __( 'Não foi possível completar o login. Solicite um novo código.', 'login-academia-da-educacao' ),
                    )
                );
            }

            $redirect_target = $redirect_safe;

            if ( empty( $redirect_requested ) && isset( $context['redirect_to'] ) && $context['redirect_to'] ) {
                $redirect_target = $this->prepare_login_redirect_url( $context['redirect_to'] );
            }

            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID, $remember );
            do_action( 'wp_login', $user->user_login, $user );

            wp_send_json_success(
                array(
                    'status'   => 'logged_in',
                    'redirect' => $redirect_target,
                    'message'  => __( 'Login realizado com sucesso! Redirecionando...', 'login-academia-da-educacao' ),
                )
            );
        }

        $login_raw = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';
        $password  = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
        $remember  = ! empty( $_POST['remember'] );

        if ( '' === $login_raw || '' === $password ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Informe usuário/email e senha para continuar.', 'login-academia-da-educacao' ),
                )
            );
        }

        $login_identifier = $this->normalize_login_identifier( $login_raw );
        $user             = wp_authenticate_username_password( null, $login_identifier, $password );

        if ( is_wp_error( $user ) ) {
            wp_send_json_error(
                array(
                    'message' => wp_strip_all_tags( $user->get_error_message() ),
                )
            );
        }

        if ( ! class_exists( 'Introducao_Auth' ) || ! $this->is_two_factor_enabled( $user->ID ) ) {
            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID, $remember );
            do_action( 'wp_login', $user->user_login, $user );

            wp_send_json_success(
                array(
                    'status'   => 'logged_in',
                    'redirect' => $redirect_safe,
                    'message'  => __( 'Login realizado com sucesso! Redirecionando...', 'login-academia-da-educacao' ),
                )
            );
        }

        $challenge = Introducao_Auth::create_login_challenge(
            $user,
            $remember,
            $login_raw,
            array(
                'source'      => 'login-academia',
                'redirect_to' => $redirect_safe,
            )
        );

        if ( is_wp_error( $challenge ) ) {
            wp_send_json_error(
                array(
                    'message' => $challenge->get_error_message(),
                )
            );
        }

        $payload            = $this->format_two_factor_payload( 'login', $challenge['challenge_id'], $challenge['email'], $redirect_safe );
        $payload['login']    = $user->user_login;
        $payload['remember'] = $remember ? '1' : '0';

        wp_send_json_success( $payload );
    }

    /**
     * Processa a criação de conta via AJAX.
     */
    public function handle_register_submission() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'lae_register_user' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Requisição inválida. Atualize a página e tente novamente.', 'login-academia-da-educacao' ),
                ),
                400
            );
        }

        if ( ! get_option( 'users_can_register' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'No momento não estamos aceitando novos cadastros.', 'login-academia-da-educacao' ),
                    'status'  => 'registration_disabled',
                )
            );
        }

        $redirect_requested = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : '';
        $redirect_safe      = $this->prepare_login_redirect_url( $redirect_requested );
        $challenge_id       = isset( $_POST['challenge'] ) ? sanitize_text_field( wp_unslash( $_POST['challenge'] ) ) : '';
        $code               = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';

        if ( $challenge_id && $code ) {
            if ( ! class_exists( 'Introducao_Auth' ) ) {
                wp_send_json_error(
                    array(
                        'message' => __( 'Não foi possível validar o código no momento. Tente novamente.', 'login-academia-da-educacao' ),
                    )
                );
            }

            $verification = Introducao_Auth::verify_code( $challenge_id, $code );

            if ( is_wp_error( $verification ) ) {
                $status          = 'two_factor_required';
                $error_code      = $verification->get_error_code();
                $challenge_state = Introducao_Auth::get_challenge( $challenge_id );
                $context         = $this->build_challenge_context( $challenge_id, $challenge_state );

                if ( in_array( $error_code, array( 'introducao_otp_invalid', 'introducao_otp_required' ), true ) ) {
                    $status = 'two_factor_invalid';
                } elseif ( in_array( $error_code, array( 'introducao_otp_expired', 'introducao_otp_max_attempts' ), true ) ) {
                    $status = 'two_factor_expired';
                }

                wp_send_json_error(
                    array(
                        'status'    => $status,
                        'message'   => $verification->get_error_message(),
                        'challenge' => $challenge_id,
                        'context'   => $context,
                    )
                );
            }

            $pending_user = isset( $verification['pending_user'] ) ? $verification['pending_user'] : array();
            $pending_meta = isset( $verification['pending_meta'] ) ? $verification['pending_meta'] : array();
            $username     = isset( $pending_user['user_login'] ) ? $pending_user['user_login'] : '';
            $email        = isset( $pending_user['user_email'] ) ? $pending_user['user_email'] : '';
            $password     = isset( $pending_user['user_pass'] ) ? $pending_user['user_pass'] : '';

            if ( ! $username || ! $email || ! $password ) {
                wp_send_json_error(
                    array(
                        'message' => __( 'Os dados de cadastro expiraram. Recomece o processo.', 'login-academia-da-educacao' ),
                    )
                );
            }

            if ( username_exists( $username ) ) {
                wp_send_json_error(
                    array(
                        'message' => __( 'Este usuário já foi registrado. Faça login para continuar.', 'login-academia-da-educacao' ),
                    )
                );
            }

            if ( email_exists( $email ) ) {
                wp_send_json_error(
                    array(
                        'message' => __( 'Este email já está cadastrado. Faça login para continuar.', 'login-academia-da-educacao' ),
                    )
                );
            }

            $user_id = wp_create_user( $username, $password, $email );

            if ( is_wp_error( $user_id ) ) {
                wp_send_json_error(
                    array(
                        'message' => wp_strip_all_tags( $user_id->get_error_message() ),
                    )
                );
            }

            $this->apply_pending_user_meta( $user_id, $pending_meta );
            wp_new_user_notification( $user_id, null, 'both' );

            $user = get_user_by( 'id', $user_id );

            if ( $user instanceof WP_User ) {
                wp_set_current_user( $user->ID );
                wp_set_auth_cookie( $user->ID, true );
                do_action( 'wp_login', $user->user_login, $user );
            }

            $context = isset( $verification['context'] ) && is_array( $verification['context'] ) ? $verification['context'] : array();

            if ( empty( $redirect_requested ) && isset( $context['redirect_to'] ) && $context['redirect_to'] ) {
                $redirect_safe = $this->prepare_login_redirect_url( $context['redirect_to'] );
            }

            wp_send_json_success(
                array(
                    'status'   => 'registered',
                    'redirect' => $redirect_safe,
                    'message'  => __( 'Conta criada com sucesso! Estamos preparando seu ambiente.', 'login-academia-da-educacao' ),
                )
            );
        }

        $name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
        $confirm  = isset( $_POST['confirm'] ) ? (string) wp_unslash( $_POST['confirm'] ) : '';

        if ( ! $name || ! $email || ! $password || ! $confirm ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Preencha todos os campos para criar sua conta.', 'login-academia-da-educacao' ),
                )
            );
        }

        if ( ! is_email( $email ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Informe um email válido.', 'login-academia-da-educacao' ),
                )
            );
        }

        if ( strlen( $password ) < 8 ) {
            wp_send_json_error(
                array(
                    'message' => __( 'A senha deve ter pelo menos 8 caracteres.', 'login-academia-da-educacao' ),
                    'status'  => 'weak_password',
                )
            );
        }

        if ( $password !== $confirm ) {
            wp_send_json_error(
                array(
                    'message' => __( 'As senhas informadas não coincidem.', 'login-academia-da-educacao' ),
                )
            );
        }

        if ( email_exists( $email ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Este email já está cadastrado. Faça login para continuar.', 'login-academia-da-educacao' ),
                )
            );
        }

        $username = $this->generate_username_from_email( $email );

        if ( username_exists( $username ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Não foi possível gerar um usuário exclusivo. Tente novamente em instantes.', 'login-academia-da-educacao' ),
                )
            );
        }

        if ( ! class_exists( 'Introducao_Auth' ) ) {
            $user_id = wp_create_user( $username, $password, $email );

            if ( is_wp_error( $user_id ) ) {
                wp_send_json_error(
                    array(
                        'message' => wp_strip_all_tags( $user_id->get_error_message() ),
                    )
                );
            }

            $this->apply_pending_user_meta( $user_id, $this->extract_user_meta_from_name( $name ) );
            wp_new_user_notification( $user_id, null, 'both' );

            $user = get_user_by( 'id', $user_id );

            if ( $user instanceof WP_User ) {
                wp_set_current_user( $user->ID );
                wp_set_auth_cookie( $user->ID, true );
                do_action( 'wp_login', $user->user_login, $user );
            }

            wp_send_json_success(
                array(
                    'status'   => 'registered',
                    'redirect' => $redirect_safe,
                    'message'  => __( 'Conta criada com sucesso! Estamos preparando seu ambiente.', 'login-academia-da-educacao' ),
                )
            );
        }

        $pending_meta = $this->extract_user_meta_from_name( $name );

        $challenge = Introducao_Auth::create_register_challenge(
            $username,
            $email,
            $password,
            $pending_meta,
            array(
                'source'      => 'login-academia',
                'redirect_to' => $redirect_safe,
            )
        );

        if ( is_wp_error( $challenge ) ) {
            wp_send_json_error(
                array(
                    'message' => $challenge->get_error_message(),
                )
            );
        }

        $payload = $this->format_two_factor_payload( 'register', $challenge['challenge_id'], $challenge['email'], $redirect_safe );

        wp_send_json_success( $payload );
    }

    /**
     * Processa o reenvio do código de autenticação em duas etapas via AJAX.
     */
    public function handle_ajax_resend_two_factor() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'lae_resend_two_factor' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Requisição inválida. Atualize a página e tente novamente.', 'login-academia-da-educacao' ),
                ),
                400
            );
        }

        $challenge_id = isset( $_POST['challenge'] ) ? sanitize_text_field( wp_unslash( $_POST['challenge'] ) ) : '';

        if ( ! $challenge_id ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Não encontramos um desafio ativo. Recomece o processo.', 'login-academia-da-educacao' ),
                )
            );
        }

        if ( ! class_exists( 'Introducao_Auth' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Não foi possível reenviar o código agora.', 'login-academia-da-educacao' ),
                )
            );
        }

        $result = Introducao_Auth::resend_challenge( $challenge_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error(
                array(
                    'message' => $result->get_error_message(),
                )
            );
        }

        $challenge_state = Introducao_Auth::get_challenge( $challenge_id );
        $context         = $this->build_challenge_context( $challenge_id, $challenge_state );

        $email  = isset( $result['email'] ) ? $result['email'] : '';
        $type   = isset( $result['type'] ) ? $result['type'] : 'login';
        $masked = class_exists( 'Introducao_Auth' ) ? Introducao_Auth::mask_email( $email ) : $email;

        $message = ( 'register' === $type )
            ? sprintf( __( 'Enviamos um novo código de ativação para %s.', 'login-academia-da-educacao' ), $masked )
            : sprintf( __( 'Enviamos um novo código de acesso para %s.', 'login-academia-da-educacao' ), $masked );

        wp_send_json_success(
            array(
                'status'    => 'two_factor_required',
                'challenge' => $challenge_id,
                'message'   => $message,
                'context'   => $context,
            )
        );
    }

    /**
     * Normaliza o identificador de login aceitando email ou usuário.
     *
     * @param string $identifier Identificador informado.
     *
     * @return string
     */
    private function normalize_login_identifier( $identifier ) {
        $identifier = trim( $identifier );

        if ( is_email( $identifier ) ) {
            $user = get_user_by( 'email', $identifier );

            if ( $user instanceof WP_User ) {
                return $user->user_login;
            }
        }

        return sanitize_user( $identifier, true );
    }

    /**
     * Gera um nome de usuário único baseado no email informado.
     *
     * @param string $email Email do usuário.
     *
     * @return string
     */
    private function generate_username_from_email( $email ) {
        $base = sanitize_user( current( explode( '@', $email ) ), true );

        if ( ! $base ) {
            $base = 'usuario';
        }

        $username = $base;
        $suffix   = 1;

        while ( username_exists( $username ) ) {
            $username = $base . $suffix;
            $suffix++; // phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall.NotAllowed
        }

        return $username;
    }

    /**
     * Valida e prepara a URL de redirecionamento após o login.
     *
     * @param string $redirect URL solicitada.
     *
     * @return string
     */
    private function prepare_login_redirect_url( $redirect ) {
        $fallback = $this->get_login_redirect_url();
        $redirect = $redirect ? esc_url_raw( $redirect ) : '';

        return wp_validate_redirect( $redirect, $fallback );
    }

    /**
     * Processa o upload do avatar personalizado do usuário.
     */
    public function handle_avatar_upload() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Você precisa estar logado para atualizar a foto.', 'login-academia-da-educacao' ),
                ),
                403
            );
        }

        check_ajax_referer( 'lae_avatar_nonce', 'nonce' );

        if ( empty( $_FILES['avatar'] ) || ! is_array( $_FILES['avatar'] ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Nenhum arquivo foi enviado.', 'login-academia-da-educacao' ),
                )
            );
        }

        $file       = $_FILES['avatar'];
        $upload_err = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_OK;

        if ( UPLOAD_ERR_OK !== $upload_err ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Falha ao enviar o arquivo. Tente novamente.', 'login-academia-da-educacao' ),
                )
            );
        }

        $file_type = wp_check_filetype( isset( $file['name'] ) ? $file['name'] : '', null );

        if ( empty( $file_type['type'] ) || 0 !== strpos( $file_type['type'], 'image/' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Envie apenas arquivos de imagem (JPG, PNG, GIF ou WEBP).', 'login-academia-da-educacao' ),
                )
            );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $uploaded = wp_handle_upload(
            $file,
            array(
                'test_form' => false,
            )
        );

        if ( ! $uploaded || isset( $uploaded['error'] ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Não foi possível salvar a imagem enviada.', 'login-academia-da-educacao' ),
                )
            );
        }

        $user_id = get_current_user_id();

        $attachment = array(
            'post_mime_type' => $file_type['type'],
            'post_title'     => isset( $uploaded['file'] ) ? sanitize_file_name( basename( $uploaded['file'] ) ) : '',
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attachment_id = wp_insert_attachment( $attachment, $uploaded['file'], 0 );

        if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
            if ( isset( $uploaded['file'] ) ) {
                wp_delete_file( $uploaded['file'] );
            }

            wp_send_json_error(
                array(
                    'message' => __( 'Não foi possível registrar a imagem enviada.', 'login-academia-da-educacao' ),
                )
            );
        }

        $metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );

        if ( is_wp_error( $metadata ) ) {
            wp_delete_attachment( $attachment_id, true );

            wp_send_json_error(
                array(
                    'message' => __( 'Não foi possível processar a imagem enviada.', 'login-academia-da-educacao' ),
                )
            );
        }

        wp_update_attachment_metadata( $attachment_id, $metadata );

        $previous_avatar_id = (int) get_user_meta( $user_id, 'lae_custom_avatar_id', true );

        update_user_meta( $user_id, 'lae_custom_avatar_id', $attachment_id );

        if ( $previous_avatar_id && $previous_avatar_id !== $attachment_id ) {
            wp_delete_attachment( $previous_avatar_id, true );
        }

        $url = wp_get_attachment_image_url( $attachment_id, 'full' );

        if ( ! $url ) {
            $url = LAE_PLUGIN_URL . 'assets/img/default-avatar.svg';
        }

        wp_send_json_success(
            array(
                'url'       => $url,
                'hasCustom' => true,
                'message'   => __( 'Foto atualizada com sucesso!', 'login-academia-da-educacao' ),
            )
        );
    }

    /**
     * Remove o avatar personalizado do usuário.
     */
    public function handle_avatar_remove() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Você precisa estar logado para remover a foto.', 'login-academia-da-educacao' ),
                ),
                403
            );
        }

        check_ajax_referer( 'lae_avatar_nonce', 'nonce' );

        $user_id     = get_current_user_id();
        $avatar_id   = (int) get_user_meta( $user_id, 'lae_custom_avatar_id', true );
        $default_url = LAE_PLUGIN_URL . 'assets/img/default-avatar.svg';

        if ( $avatar_id ) {
            delete_user_meta( $user_id, 'lae_custom_avatar_id' );
            wp_delete_attachment( $avatar_id, true );
        }

        $current_url = get_avatar_url( $user_id, array( 'size' => 240 ) );

        if ( ! $current_url ) {
            $current_url = $default_url;
        }

        wp_send_json_success(
            array(
                'url'       => $current_url,
                'hasCustom' => false,
                'message'   => __( 'Foto removida com sucesso.', 'login-academia-da-educacao' ),
            )
        );
    }

    /**
     * Substitui o avatar padrão pelo avatar personalizado, quando disponível.
     *
     * @param array|false  $args        Dados atuais do avatar.
     * @param int|object   $id_or_email Usuário, email ou ID alvo.
     *
     * @return array|false
     */
    public function filter_custom_avatar( $args, $id_or_email ) {
        $user_id = 0;

        if ( is_numeric( $id_or_email ) ) {
            $user_id = (int) $id_or_email;
        } elseif ( $id_or_email instanceof WP_User ) {
            $user_id = $id_or_email->ID;
        } elseif ( $id_or_email instanceof WP_Post ) {
            $user_id = (int) $id_or_email->post_author;
        } elseif ( $id_or_email instanceof WP_Comment ) {
            $user_id = (int) $id_or_email->user_id;
        } elseif ( is_string( $id_or_email ) ) {
            $user     = get_user_by( 'email', $id_or_email );
            $user_id  = $user instanceof WP_User ? $user->ID : 0;
        }

        if ( ! $user_id ) {
            return $args;
        }

        $avatar_id = (int) get_user_meta( $user_id, 'lae_custom_avatar_id', true );

        if ( ! $avatar_id ) {
            return $args;
        }

        $avatar_url = wp_get_attachment_image_url( $avatar_id, 'full' );

        if ( ! $avatar_url ) {
            delete_user_meta( $user_id, 'lae_custom_avatar_id' );
            return $args;
        }

        if ( ! is_array( $args ) ) {
            $args = array();
        }

        $args['url']          = $avatar_url;
        $args['found_avatar'] = true;

        return $args;
    }

    /**
     * Alterna o status da autenticação em duas etapas do usuário atual.
     */
    public function handle_two_factor_toggle() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Você precisa estar logado para alterar esta configuração.', 'login-academia-da-educacao' ),
                ),
                403
            );
        }

        check_ajax_referer( 'lae_toggle_two_factor', 'nonce' );

        $user_id    = get_current_user_id();
        $user       = get_userdata( $user_id );
        $user       = $user instanceof WP_User ? $user : null;
        $enable_raw = isset( $_POST['enable'] ) ? wp_unslash( $_POST['enable'] ) : '';
        $enable     = false;

        if ( is_bool( $enable_raw ) ) {
            $enable = $enable_raw;
        } else {
            $enable = in_array( strtolower( (string) $enable_raw ), array( '1', 'true', 'yes', 'on' ), true );
        }

        if ( $enable && ( ! $user || empty( $user->user_email ) || ! is_email( $user->user_email ) ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Adicione um email válido ao seu perfil antes de ativar a autenticação em duas etapas.', 'login-academia-da-educacao' ),
                )
            );
        }

        $status_label = $enable ? __( 'Ativa', 'login-academia-da-educacao' ) : __( 'Desativada', 'login-academia-da-educacao' );

        update_user_meta( $user_id, 'lae_two_factor_enabled', $enable ? '1' : '0' );
        update_user_meta( $user_id, 'two_factor_status', $status_label );

        if ( $enable ) {
            update_user_meta( $user_id, 'lae_two_factor_last_enabled', current_time( 'mysql' ) );
        }

        $message = $enable
            ? __( 'Autenticação em duas etapas ativada!', 'login-academia-da-educacao' )
            : __( 'Autenticação em duas etapas desativada.', 'login-academia-da-educacao' );

        wp_send_json_success(
            array(
                'enabled' => $enable,
                'status'  => $status_label,
                'message' => $message,
            )
        );
    }

    /**
     * Processa a alteração de senha via AJAX.
     */
    public function handle_password_change() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Você precisa estar logado para alterar a senha.', 'login-academia-da-educacao' ),
                ),
                403
            );
        }

        check_ajax_referer( 'lae_change_password', 'nonce' );

        $user     = wp_get_current_user();
        $current  = isset( $_POST['current_password'] ) ? (string) wp_unslash( $_POST['current_password'] ) : '';
        $new      = isset( $_POST['new_password'] ) ? (string) wp_unslash( $_POST['new_password'] ) : '';
        $confirm  = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';

        if ( ! $current || ! $new || ! $confirm ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Preencha todos os campos para alterar a senha.', 'login-academia-da-educacao' ),
                )
            );
        }

        if ( strlen( $new ) < 8 ) {
            wp_send_json_error(
                array(
                    'message' => __( 'A nova senha deve ter pelo menos 8 caracteres.', 'login-academia-da-educacao' ),
                )
            );
        }

        if ( $new !== $confirm ) {
            wp_send_json_error(
                array(
                    'message' => __( 'As senhas informadas não coincidem.', 'login-academia-da-educacao' ),
                )
            );
        }

        if ( ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'A senha atual informada está incorreta.', 'login-academia-da-educacao' ),
                )
            );
        }

        wp_set_password( $new, $user->ID );
        update_user_meta( $user->ID, 'password_last_changed', current_time( 'mysql' ) );
        $this->send_password_change_email( $user );

        wp_send_json_success(
            array(
                'message' => __( 'Senha atualizada com sucesso. Faça login novamente.', 'login-academia-da-educacao' ),
            )
        );
    }

    /**
     * Renderiza o campo de autenticação em duas etapas na tela de login do WordPress.
     */

    /**
     * Retorna se a autenticação em duas etapas está ativa para o usuário.
     *
     * @param int $user_id ID do usuário.
     *
     * @return bool
     */
    private function is_two_factor_enabled( $user_id ) {
        if ( class_exists( 'Introducao_Auth' ) ) {
            return Introducao_Auth::should_require_two_factor( $user_id );
        }

        $flag = get_user_meta( $user_id, 'lae_two_factor_enabled', true );

        return ! empty( $flag ) && '0' !== $flag && 'no' !== $flag;
    }

    /**
     * Monta o contexto atualizado de um desafio OTP.
     *
     * @param string $challenge_id Identificador do desafio.
     * @param array  $challenge_state Estado bruto retornado pelo armazenamento.
     *
     * @return array
     */
    private function build_challenge_context( $challenge_id, $challenge_state ) {
        $resend_in    = 0;
        $ttl          = 0;
        $masked_email = '';

        if ( class_exists( 'Introducao_Auth' ) ) {
            $resend_in = Introducao_Auth::seconds_until_resend( $challenge_id );
            $ttl       = Introducao_Auth::seconds_until_expiration( $challenge_id );

            if ( is_array( $challenge_state ) && isset( $challenge_state['user_email'] ) ) {
                $masked_email = Introducao_Auth::mask_email( $challenge_state['user_email'] );
            }
        }

        $context = array(
            'challenge'    => $challenge_id,
            'resend_in'    => max( 0, (int) $resend_in ),
            'ttl'          => max( 0, (int) $ttl ),
            'masked_email' => $masked_email,
        );

        if ( $context['ttl'] > 0 && class_exists( 'Introducao_Auth' ) ) {
            $context['ttl_label'] = sprintf( __( 'O código expira em %s.', 'login-academia-da-educacao' ), Introducao_Auth::format_duration( $context['ttl'] ) );
        } else {
            $context['ttl_label'] = '';
        }

        return $context;
    }

    /**
     * Cria a carga útil de resposta para desafios de autenticação em duas etapas.
     *
     * @param string $type         login|register.
     * @param string $challenge_id Identificador do desafio.
     * @param string $email        E-mail original para mascarar.
     * @param string $redirect     URL sanitizada de redirecionamento.
     *
     * @return array
     */
    private function format_two_factor_payload( $type, $challenge_id, $email, $redirect ) {
        $masked  = class_exists( 'Introducao_Auth' ) ? Introducao_Auth::mask_email( $email ) : $email;
        $context = $this->build_challenge_context( $challenge_id, array( 'user_email' => $email ) );

        $message = ( 'register' === $type )
            ? sprintf( __( 'Enviamos um código de ativação para %s. Informe-o para finalizar seu cadastro.', 'login-academia-da-educacao' ), $masked )
            : sprintf( __( 'Enviamos um código de verificação para %s. Informe-o para concluir o acesso.', 'login-academia-da-educacao' ), $masked );

        return array(
            'status'    => 'two_factor_required',
            'challenge' => $challenge_id,
            'email'     => $masked,
            'message'   => $message,
            'redirect'  => $redirect,
            'context'   => $context,
        );
    }

    /**
     * Extrai metadados padrões a partir do nome completo informado.
     *
     * @param string $name Nome completo informado.
     *
     * @return array
     */
    private function extract_user_meta_from_name( $name ) {
        $name = trim( (string) $name );

        if ( '' === $name ) {
            return array();
        }

        $parts = preg_split( '/\s+/', $name );
        $meta  = array( 'display_name' => $name );

        if ( ! empty( $parts ) ) {
            $meta['first_name'] = array_shift( $parts );

            if ( ! empty( $parts ) ) {
                $meta['last_name'] = trim( implode( ' ', $parts ) );
            }
        }

        return $meta;
    }

    /**
     * Aplica metadados pendentes ao usuário recém-criado.
     *
     * @param int   $user_id ID do usuário.
     * @param array $meta    Lista de metadados sanitizados.
     */
    private function apply_pending_user_meta( $user_id, array $meta ) {
        if ( isset( $meta['first_name'] ) ) {
            update_user_meta( $user_id, 'first_name', $meta['first_name'] );
        }

        if ( isset( $meta['last_name'] ) ) {
            update_user_meta( $user_id, 'last_name', $meta['last_name'] );
        }

        if ( isset( $meta['display_name'] ) ) {
            wp_update_user(
                array(
                    'ID'           => $user_id,
                    'display_name' => $meta['display_name'],
                )
            );
        }
    }

    /**
     * Retorna o gerenciador de páginas.
     *
     * @return LAE_Pages
     */
    public function get_pages() {
        return $this->pages;
    }

    /**
     * Renderiza um template e retorna o HTML resultante.
     *
     * @param string $template Arquivo de template em templates/.
     * @param array  $vars     Variáveis a serem extraídas para o template.
     *
     * @return string
     */
    public function render_template( $template, $vars = array() ) {
        $file = LAE_PLUGIN_DIR . 'templates/' . $template;

        if ( ! file_exists( $file ) ) {
            return '';
        }

        if ( ! empty( $vars ) ) {
            extract( $vars, EXTR_SKIP );
        }

        ob_start();
        include $file;

        return ob_get_clean();
    }
}
