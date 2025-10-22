<?php
/**
 * Principal handler para inicialização e assets.
 */
class Introducao_Plugin {

    /**
     * Instância singleton.
     *
     * @var Introducao_Plugin
     */
    private static $instance;

    /**
     * Instância do gerenciador de páginas.
     *
     * @var Introducao_Pages
     */
    private $pages;

    /**
     * Instância do registrador de shortcodes.
     *
     * @var Introducao_Shortcodes
     */
    private $shortcodes;

    /**
     * Recupera a instância única do plugin.
     *
     * @return Introducao_Plugin
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
        $this->pages      = new Introducao_Pages();
        $this->shortcodes = new Introducao_Shortcodes( $this, $this->pages );

        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
        add_action( 'admin_menu', array( $this, 'register_admin_page' ) );

        Introducao_Auth::bootstrap();
        Introducao_Auth::register_ajax_routes();
    }

    /**
     * Carrega o textdomain do plugin.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'introducao', false, dirname( plugin_basename( INTRODUCAO_PLUGIN_FILE ) ) . '/languages' );
    }

    /**
     * Registra os assets de frontend.
     */
    public function register_assets() {
        wp_register_style(
            'introducao-user-menu',
            INTRODUCAO_PLUGIN_URL . 'assets/css/style.css',
            array(),
            INTRODUCAO_PLUGIN_VERSION
        );

        wp_register_script(
            'introducao-user-menu',
            INTRODUCAO_PLUGIN_URL . 'assets/js/script.js',
            array(),
            INTRODUCAO_PLUGIN_VERSION,
            true
        );

        wp_localize_script(
            'introducao-user-menu',
            'introducaoPerfil',
            array(
                'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                'resendNonce'    => wp_create_nonce( 'introducao_resend_otp' ),
                'resendCooldown' => Introducao_Auth::OTP_RESEND_INTERVAL,
                'otpTtl'         => Introducao_Auth::OTP_TTL,
                'challenge'      => Introducao_Auth::get_client_challenge_context(),
                'i18n'           => array(
                    'resendLabel'    => __( 'Reenviar código', 'introducao' ),
                    'resendSending'  => __( 'Reenviando...', 'introducao' ),
                    'resendCountdown'=> __( 'Você poderá solicitar um novo código em %s.', 'introducao' ),
                    'resendReady'    => __( 'Pronto para solicitar um novo código.', 'introducao' ),
                    'resendSuccess'  => __( 'Enviamos um novo código para %s.', 'introducao' ),
                    'resendError'    => __( 'Não foi possível reenviar o código. Tente novamente em instantes.', 'introducao' ),
                    'ttlCountdown'   => __( 'O código expira em %s.', 'introducao' ),
                    'securityError'  => __( 'Sua sessão expirou. Recarregue a página para tentar novamente.', 'introducao' ),
                ),
            )
        );
    }

    /**
     * Registra a página administrativa do plugin.
     */
    public function register_admin_page() {
        add_menu_page(
            __( 'Introdução Academia da Educação', 'introducao' ),
            __( 'Introdução', 'introducao' ),
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
            wp_die( esc_html__( 'You do not have permission to access this page.', 'introducao' ) );
        }

        $registered_pages = $this->pages instanceof Introducao_Pages ? $this->pages->get_registered_pages() : array();
        $pages            = array();

        foreach ( $registered_pages as $slug => $data ) {
            $pages[] = array(
                'title'     => isset( $data['title'] ) ? $data['title'] : '',
                'shortcode' => isset( $data['shortcode'] ) ? $data['shortcode'] : '',
                'url'       => $this->pages instanceof Introducao_Pages ? $this->pages->get_page_url( $slug ) : '',
            );
        }

        echo $this->render_template(
            'admin-overview.php',
            array(
                'pages'          => $pages,
                'menu_shortcode' => '[introducao_user_menu]',
            )
        );
    }

    /**
     * Enfileira os assets necessários para o menu do usuário.
     */
    public function enqueue_assets() {
        $this->enqueue_style();
        wp_enqueue_script( 'introducao-user-menu' );
    }

    /**
     * Enfileira apenas as folhas de estilo do plugin.
     */
    public function enqueue_style() {
        wp_enqueue_style( 'introducao-user-menu' );
    }

    /**
     * Retorna o gerenciador de páginas.
     *
     * @return Introducao_Pages
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
        $file = INTRODUCAO_PLUGIN_DIR . 'templates/' . $template;

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
