<?php
/**
 * Principal handler para inicialização e assets.
 */
class Introducao_Plugin {

    const SLIDER_OPTION = 'introducao_slider_settings';

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
        add_action( 'admin_init', array( $this, 'register_settings' ) );

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
            'introducao-onboarding-hub',
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
        $slider_settings  = $this->get_slider_settings();

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
                'pages'               => $pages,
                'menu_shortcode'      => '[introducao_user_menu]',
                'slider_settings'     => $slider_settings,
                'default_slider_copy' => $this->get_default_slider_texts(),
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

    /**
     * Registra as configurações editáveis no painel.
     */
    public function register_settings() {
        register_setting(
            'introducao_slider',
            self::SLIDER_OPTION,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_slider_settings' ),
                'default'           => $this->get_default_slider_settings(),
            )
        );
    }

    /**
     * Retorna as configurações padrão do slider.
     *
     * @return array
     */
    public function get_default_slider_settings() {
        return array(
            'brand_logo'  => 'https://www.agenciadigitalsaopaulo.com.br/app/wp-content/uploads/2024/05/logo-footer.png',
            'redirect_to' => home_url( user_trailingslashit( 'home' ) ),
            'slides'      => $this->get_default_slider_texts(),
        );
    }

    /**
     * Retorna os textos padrão do slider para reutilização.
     *
     * @return array
     */
    public function get_default_slider_texts() {
        return array(
            array(
                'title'       => __( 'Organize sua preparação', 'introducao' ),
                'description' => __( 'Conheça o plano de estudos inteligente da Academia da Comunicação e centralize teoria, prática e simulados em um só lugar.', 'introducao' ),
                'image'       => '',
            ),
            array(
                'title'       => __( 'Acompanhe o seu progresso', 'introducao' ),
                'description' => __( 'Receba lembretes personalizados, veja sua evolução em tempo real e desbloqueie recomendações alinhadas às suas metas.', 'introducao' ),
                'image'       => '',
            ),
            array(
                'title'       => __( 'Comece agora mesmo', 'introducao' ),
                'description' => __( 'Crie sua conta ou acesse para liberar turmas, simulados e o apoio do nosso treinador virtual 24h.', 'introducao' ),
                'image'       => '',
            ),
        );
    }

    /**
     * Recupera as configurações definidas para o slider.
     *
     * @return array
     */
    public function get_slider_settings() {
        $saved    = get_option( self::SLIDER_OPTION, array() );
        $defaults = $this->get_default_slider_settings();

        if ( empty( $saved ) || ! is_array( $saved ) ) {
            return $defaults;
        }

        $saved   = $this->sanitize_slider_settings( $saved );
        $slides  = isset( $saved['slides'] ) && is_array( $saved['slides'] ) ? $saved['slides'] : array();

        if ( count( $slides ) !== count( $defaults['slides'] ) ) {
            $slides = array_slice( array_merge( $slides, $defaults['slides'] ), 0, count( $defaults['slides'] ) );
        }

        return array(
            'brand_logo'  => ! empty( $saved['brand_logo'] ) ? esc_url_raw( $saved['brand_logo'] ) : $defaults['brand_logo'],
            'redirect_to' => ! empty( $saved['redirect_to'] ) ? esc_url_raw( $saved['redirect_to'] ) : $defaults['redirect_to'],
            'slides'      => $slides,
        );
    }

    /**
     * Sanitiza os valores submetidos no painel.
     *
     * @param array $raw_settings Dados recebidos do formulário.
     *
     * @return array
     */
    public function sanitize_slider_settings( $raw_settings ) {
        $defaults = $this->get_default_slider_settings();
        $sanitized = array(
            'brand_logo'  => $defaults['brand_logo'],
            'redirect_to' => $defaults['redirect_to'],
            'slides'      => $defaults['slides'],
        );

        if ( isset( $raw_settings['brand_logo'] ) ) {
            $logo = esc_url_raw( trim( $raw_settings['brand_logo'] ) );
            $sanitized['brand_logo'] = $logo ? $logo : $defaults['brand_logo'];
        }

        if ( isset( $raw_settings['redirect_to'] ) ) {
            $redirect = esc_url_raw( trim( $raw_settings['redirect_to'] ) );
            $sanitized['redirect_to'] = $redirect ? $redirect : $defaults['redirect_to'];
        }

        if ( isset( $raw_settings['slides'] ) && is_array( $raw_settings['slides'] ) ) {
            $sanitized['slides'] = array();

            foreach ( $defaults['slides'] as $index => $default_slide ) {
                $incoming = isset( $raw_settings['slides'][ $index ] ) && is_array( $raw_settings['slides'][ $index ] )
                    ? $raw_settings['slides'][ $index ]
                    : array();

                $title       = isset( $incoming['title'] ) ? sanitize_text_field( $incoming['title'] ) : '';
                $description = isset( $incoming['description'] ) ? wp_kses_post( $incoming['description'] ) : '';
                $image       = isset( $incoming['image'] ) ? esc_url_raw( trim( $incoming['image'] ) ) : '';

                if ( ! $title ) {
                    $title = $default_slide['title'];
                }

                if ( ! $description ) {
                    $description = $default_slide['description'];
                }

                if ( empty( $image ) && ! empty( $default_slide['image'] ) ) {
                    $image = esc_url_raw( $default_slide['image'] );
                }

                $sanitized['slides'][ $index ] = array(
                    'title'       => $title,
                    'description' => $description,
                    'image'       => $image,
                );
            }
        }

        return $sanitized;
    }
}
