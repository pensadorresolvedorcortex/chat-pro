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
        add_action( 'login_form', array( $this, 'render_two_factor_login_field' ) );
        add_action( 'login_form_lae_resend_2fa', array( $this, 'handle_login_resend_two_factor' ) );
        add_filter( 'authenticate', array( $this, 'enforce_two_factor_login' ), 30, 3 );
        add_filter( 'login_message', array( $this, 'filter_login_message' ), 10, 1 );
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
                'messages' => array(
                    'loginWorking'     => __( 'Validando seus dados...', 'login-academia-da-educacao' ),
                    'registerWorking'  => __( 'Criando sua conta...', 'login-academia-da-educacao' ),
                    'success'          => __( 'Tudo certo! Estamos te redirecionando.', 'login-academia-da-educacao' ),
                    'twoFactorRequired'=> __( 'Enviamos um código de verificação para o seu email.', 'login-academia-da-educacao' ),
                    'twoFactorInvalid' => __( 'O código informado está incorreto. Confira e tente novamente.', 'login-academia-da-educacao' ),
                    'twoFactorExpired' => __( 'O código expirou. Enviamos um novo para você.', 'login-academia-da-educacao' ),
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

        if ( is_user_logged_in() ) {
            wp_send_json_success(
                array(
                    'status'   => 'logged_in',
                    'redirect' => $this->prepare_login_redirect_url( isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : '' ),
                    'message'  => __( 'Você já está autenticado.', 'login-academia-da-educacao' ),
                )
            );
        }

        $login_raw = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';
        $password  = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
        $code      = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
        $remember  = ! empty( $_POST['remember'] );
        $redirect  = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : '';

        if ( '' === $login_raw || '' === $password ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Informe usuário/email e senha para continuar.', 'login-academia-da-educacao' ),
                )
            );
        }

        $login = $this->normalize_login_identifier( $login_raw );

        $user = wp_authenticate_username_password( null, $login, $password );

        if ( is_wp_error( $user ) ) {
            wp_send_json_error(
                array(
                    'message' => wp_strip_all_tags( $user->get_error_message() ),
                )
            );
        }

        if ( $this->is_two_factor_enabled( $user->ID ) ) {
            $pending = get_user_meta( $user->ID, 'lae_two_factor_pending', true );

            if ( $code ) {
                $now = time();

                if ( ! is_array( $pending ) || empty( $pending['hash'] ) || empty( $pending['expires'] ) ) {
                    $result = $this->maybe_send_two_factor_code( $user, true );

                    if ( is_wp_error( $result ) ) {
                        wp_send_json_error(
                            array(
                                'message' => $result->get_error_message(),
                            )
                        );
                    }

                    wp_send_json_success(
                        array(
                            'status'  => 'two_factor_required',
                            'message' => __( 'Enviamos um novo código de verificação para o seu email.', 'login-academia-da-educacao' ),
                            'context' => $this->build_two_factor_error_context( $user, is_array( $pending ) ? $pending : array(), is_array( $result ) ? $result : array() ),
                            'login'   => $user->user_login,
                        )
                    );
                }

                if ( (int) $pending['expires'] < $now ) {
                    $this->clear_two_factor_code( $user->ID );

                    $result = $this->maybe_send_two_factor_code( $user, true );

                    if ( is_wp_error( $result ) ) {
                        wp_send_json_error(
                            array(
                                'message' => $result->get_error_message(),
                            )
                        );
                    }

                    wp_send_json_success(
                        array(
                            'status'  => 'two_factor_required',
                            'message' => __( 'O código anterior expirou. Enviamos um novo para o seu email.', 'login-academia-da-educacao' ),
                            'context' => $this->build_two_factor_error_context( $user, is_array( $pending ) ? $pending : array(), is_array( $result ) ? $result : array() ),
                            'login'   => $user->user_login,
                        )
                    );
                }

                if ( ! wp_check_password( $code, $pending['hash'], $user->ID ) ) {
                    wp_send_json_error(
                        array(
                            'status'  => 'two_factor_invalid',
                            'message' => __( 'O código informado está incorreto. Confira seu email ou solicite um novo envio.', 'login-academia-da-educacao' ),
                            'context' => $this->build_two_factor_error_context( $user, is_array( $pending ) ? $pending : array() ),
                            'login'   => $user->user_login,
                        )
                    );
                }

                $this->clear_two_factor_code( $user->ID );
                update_user_meta( $user->ID, 'two_factor_status', __( 'Ativa', 'login-academia-da-educacao' ) );
            } else {
                $result = $this->maybe_send_two_factor_code( $user );

                if ( is_wp_error( $result ) ) {
                    wp_send_json_error(
                        array(
                            'message' => $result->get_error_message(),
                        )
                    );
                }

                wp_send_json_success(
                    array(
                        'status'  => 'two_factor_required',
                        'message' => __( 'Enviamos um código de verificação para o seu email. Informe-o para concluir o acesso.', 'login-academia-da-educacao' ),
                        'context' => $this->build_two_factor_error_context( $user, is_array( $pending ) ? $pending : array(), is_array( $result ) ? $result : array() ),
                        'login'   => $user->user_login,
                    )
                );
            }
        }

        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, $remember );
        do_action( 'wp_login', $user->user_login, $user );

        wp_send_json_success(
            array(
                'status'   => 'logged_in',
                'redirect' => $this->prepare_login_redirect_url( $redirect ),
                'message'  => __( 'Login realizado com sucesso! Redirecionando...', 'login-academia-da-educacao' ),
            )
        );
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

        $name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $password  = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
        $confirm   = isset( $_POST['confirm'] ) ? (string) wp_unslash( $_POST['confirm'] ) : '';
        $redirect  = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : '';

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

        $user_id = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error(
                array(
                    'message' => wp_strip_all_tags( $user_id->get_error_message() ),
                )
            );
        }

        $parts = preg_split( '/\s+/', $name );

        if ( ! empty( $parts ) ) {
            update_user_meta( $user_id, 'first_name', isset( $parts[0] ) ? $parts[0] : '' );

            if ( count( $parts ) > 1 ) {
                update_user_meta( $user_id, 'last_name', trim( implode( ' ', array_slice( $parts, 1 ) ) ) );
            }

            wp_update_user(
                array(
                    'ID'           => $user_id,
                    'display_name' => $name,
                )
            );
        }

        wp_new_user_notification( $user_id, null, 'both' );

        $user = get_user_by( 'id', $user_id );

        if ( ! ( $user instanceof WP_User ) ) {
            wp_send_json_success(
                array(
                    'status'   => 'registered',
                    'redirect' => $this->prepare_login_redirect_url( $redirect ),
                    'message'  => __( 'Conta criada com sucesso! Faça login para começar.', 'login-academia-da-educacao' ),
                )
            );
        }

        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );
        do_action( 'wp_login', $user->user_login, $user );

        wp_send_json_success(
            array(
                'status'   => 'registered',
                'redirect' => $this->prepare_login_redirect_url( $redirect ),
                'message'  => __( 'Conta criada com sucesso! Estamos preparando seu ambiente.', 'login-academia-da-educacao' ),
            )
        );
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

        $login = isset( $_POST['login'] ) ? sanitize_user( wp_unslash( $_POST['login'] ), true ) : '';
        $key   = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';

        if ( ! $login || ! $key ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Não foi possível reenviar o código agora.', 'login-academia-da-educacao' ),
                )
            );
        }

        $user = get_user_by( 'login', $login );

        if ( ! ( $user instanceof WP_User ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Não foi possível reenviar o código agora.', 'login-academia-da-educacao' ),
                )
            );
        }

        $pending = get_user_meta( $user->ID, 'lae_two_factor_pending', true );

        if ( ! is_array( $pending ) || empty( $pending['resend_key'] ) || ! hash_equals( $pending['resend_key'], $key ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Aguarde alguns instantes antes de solicitar um novo código.', 'login-academia-da-educacao' ),
                    'status'  => 'wait',
                )
            );
        }

        $result = $this->maybe_send_two_factor_code( $user, true );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error(
                array(
                    'message' => $result->get_error_message(),
                )
            );
        }

        wp_send_json_success(
            array(
                'message' => __( 'Enviamos um novo código para o seu email.', 'login-academia-da-educacao' ),
                'context' => $this->build_two_factor_error_context( $user, is_array( $pending ) ? $pending : array(), is_array( $result ) ? $result : array() ),
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
        } else {
            $this->clear_two_factor_code( $user_id );
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
    public function render_two_factor_login_field() {
        $value = isset( $_REQUEST['lae_2fa_code'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['lae_2fa_code'] ) ) : '';

        echo '<p class="lae-login-2fa-field">';
        echo '<label for="lae_2fa_code">' . esc_html__( 'Código de verificação', 'login-academia-da-educacao' ) . '</label>';
        echo '<input type="text" name="lae_2fa_code" id="lae_2fa_code" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*" value="' . esc_attr( $value ) . '" />';
        echo '<span class="description">' . esc_html__( 'Informe o código recebido por email se a autenticação em duas etapas estiver ativada.', 'login-academia-da-educacao' ) . '</span>';
        echo '</p>';
    }

    /**
     * Força a segunda etapa de autenticação durante o login.
     *
     * @param WP_User|WP_Error|null $user     Resultado atual do processo de autenticação.
     * @param string                $username Usuário informado.
     * @param string                $password Senha informada.
     *
     * @return WP_User|WP_Error|null
     */
    public function enforce_two_factor_login( $user, $username, $password ) {
        if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) {
            return $user;
        }

        if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
            return $user;
        }

        if ( ! $this->is_two_factor_enabled( $user->ID ) ) {
            return $user;
        }

        $code    = isset( $_POST['lae_2fa_code'] ) ? sanitize_text_field( wp_unslash( $_POST['lae_2fa_code'] ) ) : '';
        $pending = get_user_meta( $user->ID, 'lae_two_factor_pending', true );
        $now     = time();

        if ( $code ) {
            if ( ! is_array( $pending ) || empty( $pending['hash'] ) || empty( $pending['expires'] ) ) {
                $result = $this->maybe_send_two_factor_code( $user, true );

                if ( is_wp_error( $result ) ) {
                    return $result;
                }

                return new WP_Error(
                    'lae_2fa_required',
                    __( 'Enviamos um novo código de verificação para o seu email.', 'login-academia-da-educacao' ),
                    $this->build_two_factor_error_context( $user, is_array( $pending ) ? $pending : array(), is_array( $result ) ? $result : array() )
                );
            }

            if ( (int) $pending['expires'] < $now ) {
                $this->clear_two_factor_code( $user->ID );

                $result = $this->maybe_send_two_factor_code( $user, true );

                if ( is_wp_error( $result ) ) {
                    return $result;
                }

                return new WP_Error(
                    'lae_2fa_required',
                    __( 'O código expirou. Enviamos um novo para o seu email.', 'login-academia-da-educacao' ),
                    $this->build_two_factor_error_context( $user, is_array( $pending ) ? $pending : array(), is_array( $result ) ? $result : array() )
                );
            }

            if ( ! wp_check_password( $code, $pending['hash'], $user->ID ) ) {
                return new WP_Error(
                    'lae_2fa_invalid',
                    __( 'O código informado está incorreto. Confira seu email ou solicite um novo envio.', 'login-academia-da-educacao' ),
                    $this->build_two_factor_error_context( $user, is_array( $pending ) ? $pending : array() )
                );
            }

            $this->clear_two_factor_code( $user->ID );

            update_user_meta( $user->ID, 'two_factor_status', __( 'Ativa', 'login-academia-da-educacao' ) );

            return $user;
        }

        $result = $this->maybe_send_two_factor_code( $user );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return new WP_Error(
            'lae_2fa_required',
            __( 'Enviamos um código de verificação para o seu email. Informe-o para concluir o login.', 'login-academia-da-educacao' ),
            $this->build_two_factor_error_context( $user, is_array( $pending ) ? $pending : array(), is_array( $result ) ? $result : array() )
        );
    }

    /**
     * Retorna se a autenticação em duas etapas está ativa para o usuário.
     *
     * @param int $user_id ID do usuário.
     *
     * @return bool
     */
    private function is_two_factor_enabled( $user_id ) {
        $flag = get_user_meta( $user_id, 'lae_two_factor_enabled', true );

        return ! empty( $flag ) && '0' !== $flag && 'no' !== $flag;
    }

    /**
     * Gera e envia o código de verificação quando necessário.
     *
     * @param WP_User $user Usuário alvo.
     *
     * @param WP_User $user  Usuário alvo.
     * @param bool    $force Se deve ignorar o tempo mínimo entre envios.
     *
     * @return array|WP_Error
     */
    private function maybe_send_two_factor_code( WP_User $user, $force = false ) {
        $now     = time();
        $pending = get_user_meta( $user->ID, 'lae_two_factor_pending', true );

        if ( ! is_array( $pending ) ) {
            $pending = array();
        }

        $created    = isset( $pending['created'] ) ? (int) $pending['created'] : 0;
        $expires    = isset( $pending['expires'] ) ? (int) $pending['expires'] : 0;
        $resend_key = isset( $pending['resend_key'] ) ? (string) $pending['resend_key'] : '';
        $valid_code = ! empty( $pending['hash'] ) && $expires > $now;

        if ( $valid_code && ! $force ) {
            $wait = 0;

            if ( $created > 0 ) {
                $elapsed = $now - $created;

                if ( $elapsed < 30 ) {
                    $wait = 30 - $elapsed;
                }
            }

            if ( ! $resend_key ) {
                $pending['resend_key'] = wp_generate_password( 20, false );
                $resend_key            = $pending['resend_key'];
                update_user_meta( $user->ID, 'lae_two_factor_pending', $pending );
            }

            return array(
                'resend_key' => $resend_key,
                'expires'    => $expires,
                'wait'       => $wait,
                'created'    => $created,
            );
        }

        $code       = (string) wp_rand( 100000, 999999 );
        $resend_key = wp_generate_password( 20, false );

        $data = array(
            'hash'       => wp_hash_password( $code ),
            'expires'    => $now + ( 5 * MINUTE_IN_SECONDS ),
            'created'    => $now,
            'resend_key' => $resend_key,
        );

        update_user_meta( $user->ID, 'lae_two_factor_pending', $data );
        update_user_meta( $user->ID, 'lae_two_factor_last_sent', current_time( 'mysql' ) );

        $sent = $this->send_two_factor_code_email( $user, $code );

        if ( ! $sent ) {
            $this->clear_two_factor_code( $user->ID );

            return new WP_Error(
                'lae_2fa_error',
                __( 'Não foi possível enviar o código de verificação. Tente novamente mais tarde.', 'login-academia-da-educacao' )
            );
        }

        return array(
            'resend_key' => $resend_key,
            'expires'    => $data['expires'],
            'wait'       => 0,
            'created'    => $now,
        );
    }

    /**
     * Remove informações temporárias do código de autenticação em duas etapas.
     *
     * @param int $user_id ID do usuário.
     */
    private function clear_two_factor_code( $user_id ) {
        delete_user_meta( $user_id, 'lae_two_factor_pending' );
    }

    /**
     * Exibe mensagens adicionais na tela de login quando a autenticação em duas etapas estiver ativa.
     *
     * @param string $message Mensagem atual do login.
     *
     * @return string
     */
    public function filter_login_message( $message ) {
        if ( is_user_logged_in() ) {
            return $message;
        }

        $notices = array();
        $status  = isset( $_GET['lae-2fa'] ) ? sanitize_key( wp_unslash( $_GET['lae-2fa'] ) ) : '';
        $reason  = isset( $_GET['reason'] ) ? sanitize_key( wp_unslash( $_GET['reason'] ) ) : '';

        if ( 'resent' === $status ) {
            $notices[] = '<div class="lae-login-2fa-alert lae-login-2fa-alert--success"><p>' . esc_html__( 'Enviamos um novo código de verificação. Confira seu email para prosseguir.', 'login-academia-da-educacao' ) . '</p></div>';
        } elseif ( 'error' === $status ) {
            $notices[] = '<div class="lae-login-2fa-alert lae-login-2fa-alert--error"><p>' . esc_html__( 'Não foi possível reenviar o código de verificação. Tente novamente em instantes.', 'login-academia-da-educacao' ) . '</p></div>';
        } elseif ( 'invalid' === $status ) {
            $notices[] = '<div class="lae-login-2fa-alert lae-login-2fa-alert--warning"><p>' . esc_html__( 'A solicitação de reenvio do código não pôde ser validada. Realize o login novamente para gerar um novo código.', 'login-academia-da-educacao' ) . '</p></div>';
        } elseif ( 'cooldown' === $status && isset( $_GET['wait'] ) ) {
            $wait = max( 0, (int) $_GET['wait'] );

            if ( $wait > 0 ) {
                $notices[] = '<div class="lae-login-2fa-alert lae-login-2fa-alert--info"><p>' . esc_html( sprintf( __( 'Aguarde %s para solicitar um novo código.', 'login-academia-da-educacao' ), human_time_diff( time(), time() + $wait ) ) ) . '</p></div>';
            }
        }

        if ( 'error' === $status && 'lae_2fa_error' === $reason ) {
            $notices[] = '<div class="lae-login-2fa-alert lae-login-2fa-alert--error"><p>' . esc_html__( 'O envio do email falhou temporariamente. Verifique suas configurações de email ou tente novamente mais tarde.', 'login-academia-da-educacao' ) . '</p></div>';
        }

        global $errors;

        if ( $errors instanceof WP_Error ) {
            foreach ( array( 'lae_2fa_required', 'lae_2fa_invalid' ) as $code ) {
                $data = $errors->get_error_data( $code );

                if ( ! is_array( $data ) ) {
                    continue;
                }

                $notices[] = $this->render_two_factor_login_notice( $data );
                break;
            }
        }

        if ( empty( $notices ) ) {
            return $message;
        }

        return $message . implode( '', $notices );
    }

    /**
     * Trata o reenvio manual do código de autenticação em duas etapas via tela de login do WordPress.
     */
    public function handle_login_resend_two_factor() {
        $username    = isset( $_REQUEST['user'] ) ? sanitize_user( wp_unslash( $_REQUEST['user'] ), true ) : '';
        $key         = isset( $_REQUEST['key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['key'] ) ) : '';
        $redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';

        $args = array();

        if ( ! $username || ! $key ) {
            $args['lae-2fa'] = 'invalid';
            if ( $redirect_to ) {
                $args['redirect_to'] = $redirect_to;
            }

            wp_safe_redirect( add_query_arg( $args, wp_login_url() ) );
            exit;
        }

        $user = get_user_by( 'login', $username );

        if ( ! ( $user instanceof WP_User ) ) {
            $args['lae-2fa'] = 'invalid';
            if ( $redirect_to ) {
                $args['redirect_to'] = $redirect_to;
            }

            wp_safe_redirect( add_query_arg( $args, wp_login_url() ) );
            exit;
        }

        $pending = get_user_meta( $user->ID, 'lae_two_factor_pending', true );

        if ( ! is_array( $pending ) || empty( $pending['resend_key'] ) || ! hash_equals( $pending['resend_key'], $key ) ) {
            $args['lae-2fa'] = 'invalid';
            if ( $redirect_to ) {
                $args['redirect_to'] = $redirect_to;
            }

            wp_safe_redirect( add_query_arg( $args, wp_login_url() ) );
            exit;
        }

        $result = $this->maybe_send_two_factor_code( $user, true );

        if ( is_wp_error( $result ) ) {
            $args['lae-2fa'] = 'error';
            $args['reason']  = sanitize_key( $result->get_error_code() );
        } else {
            $args['lae-2fa'] = 'resent';
        }

        if ( $redirect_to ) {
            $args['redirect_to'] = $redirect_to;
        }

        wp_safe_redirect( add_query_arg( $args, wp_login_url() ) );
        exit;
    }

    /**
     * Garante que o contexto de erro de duas etapas possua as informações necessárias para instruções adicionais.
     *
     * @param WP_User $user    Usuário alvo.
     * @param array   $pending Dados atuais armazenados para o usuário.
     * @param array   $extra   Dados adicionais retornados durante a tentativa de envio.
     *
     * @return array
     */
    private function build_two_factor_error_context( WP_User $user, array $pending = array(), array $extra = array() ) {
        $now  = time();
        $data = array_merge( $pending, $extra );

        $context = array(
            'user_id'    => $user->ID,
            'login'      => $user->user_login,
            'resend_key' => isset( $data['resend_key'] ) ? (string) $data['resend_key'] : '',
            'expires'    => isset( $data['expires'] ) ? (int) $data['expires'] : 0,
            'wait'       => 0,
        );

        if ( isset( $data['created'] ) ) {
            $created = (int) $data['created'];

            if ( $created > 0 ) {
                $elapsed = $now - $created;

                if ( $elapsed < 30 ) {
                    $context['wait'] = 30 - $elapsed;
                }
            }
        }

        if ( isset( $extra['wait'] ) ) {
            $context['wait'] = max( 0, (int) $extra['wait'] );
        }

        if ( ! $context['resend_key'] && ! empty( $data ) ) {
            $context['resend_key'] = $this->ensure_two_factor_resend_key( $user, $data );
        }

        return $context;
    }

    /**
     * Gera um bloco informativo com instruções sobre o código de verificação atual e reenvio manual.
     *
     * @param array $data Dados associados ao erro.
     *
     * @return string
     */
    private function render_two_factor_login_notice( array $data ) {
        $now         = time();
        $resend_key  = isset( $data['resend_key'] ) ? (string) $data['resend_key'] : '';
        $login       = isset( $data['login'] ) ? (string) $data['login'] : '';
        $expires     = isset( $data['expires'] ) ? (int) $data['expires'] : 0;
        $wait        = isset( $data['wait'] ) ? max( 0, (int) $data['wait'] ) : 0;
        $redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';

        $notice   = array();
        $notice[] = '<div class="lae-login-2fa-notice">';
        $notice[] = '<p>' . esc_html__( 'Sua conta está protegida com autenticação em duas etapas. Verifique o email recebido e informe o código para continuar.', 'login-academia-da-educacao' ) . '</p>';

        if ( $expires > $now ) {
            $notice[] = '<p class="lae-login-2fa-notice__meta">' . esc_html( sprintf( __( 'O código atual expira em %s.', 'login-academia-da-educacao' ), human_time_diff( $now, $expires ) ) ) . '</p>';
        }

        if ( $resend_key && $login ) {
            $args = array(
                'action' => 'lae_resend_2fa',
                'user'   => $login,
                'key'    => $resend_key,
            );

            if ( $redirect_to ) {
                $args['redirect_to'] = $redirect_to;
            }

            if ( $wait > 0 ) {
                $notice[] = '<p class="lae-login-2fa-notice__meta">' . esc_html( sprintf( __( 'Você poderá solicitar um novo código em %s.', 'login-academia-da-educacao' ), human_time_diff( $now, $now + $wait ) ) ) . '</p>';
            } else {
                $notice[] = '<p class="lae-login-2fa-notice__meta">' . esc_html__( 'Não recebeu o email?', 'login-academia-da-educacao' ) . ' <a class="lae-login-2fa-resend" href="' . esc_url( add_query_arg( $args, wp_login_url() ) ) . '">' . esc_html__( 'Reenviar código', 'login-academia-da-educacao' ) . '</a></p>';
            }
        }

        $notice[] = '</div>';

        return implode( '', $notice );
    }

    /**
     * Garante que um token de reenvio esteja disponível para o usuário.
     *
     * @param WP_User $user Usuário alvo.
     * @param array   $data Dados atuais da autenticação em duas etapas.
     *
     * @return string
     */
    private function ensure_two_factor_resend_key( WP_User $user, array $data ) {
        if ( ! empty( $data['resend_key'] ) ) {
            return (string) $data['resend_key'];
        }

        $data['resend_key'] = wp_generate_password( 20, false );
        update_user_meta( $user->ID, 'lae_two_factor_pending', $data );

        return $data['resend_key'];
    }

    /**
     * Envia um email de confirmação de alteração de senha ao usuário quando possível.
     *
     * @param WP_User $user Usuário cujo password foi alterado.
     */
    private function send_password_change_email( WP_User $user ) {
        if ( empty( $user->user_email ) ) {
            return;
        }

        $blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
        $subject  = sprintf( __( '[%s] Confirmação de alteração de senha', 'login-academia-da-educacao' ), $blogname );

        $message_lines = array(
            sprintf( __( 'Olá %s,', 'login-academia-da-educacao' ), $user->display_name ? $user->display_name : $user->user_login ),
            '',
            __( 'Confirmamos que a senha da sua conta foi atualizada com sucesso.', 'login-academia-da-educacao' ),
            __( 'Caso não tenha realizado essa alteração, redefina a senha imediatamente e entre em contato com nossa equipe de suporte.', 'login-academia-da-educacao' ),
        );

        wp_mail( $user->user_email, $subject, implode( "\n", $message_lines ) );
    }

    /**
     * Envia o código de autenticação em duas etapas por email.
     *
     * @param WP_User $user Usuário alvo.
     * @param string  $code Código a ser enviado.
     *
     * @return bool
     */
    private function send_two_factor_code_email( WP_User $user, $code ) {
        if ( empty( $user->user_email ) ) {
            return false;
        }

        $blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
        $subject  = sprintf( __( '[%s] Seu código de verificação', 'login-academia-da-educacao' ), $blogname );

        $message_lines = array(
            sprintf( __( 'Olá %s,', 'login-academia-da-educacao' ), $user->display_name ? $user->display_name : $user->user_login ),
            '',
            sprintf( __( 'Seu código de verificação é: %s', 'login-academia-da-educacao' ), $code ),
            __( 'Este código expira em 5 minutos.', 'login-academia-da-educacao' ),
            '',
            sprintf( __( 'Se você não solicitou este código, contate o suporte de %s.', 'login-academia-da-educacao' ), $blogname ),
        );

        $message = implode( "\n", $message_lines );

        return wp_mail( $user->user_email, $subject, $message );
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
