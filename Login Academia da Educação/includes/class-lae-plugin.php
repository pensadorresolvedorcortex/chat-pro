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
     * Enfileira os assets necessários para o menu do usuário.
     */
    public function enqueue_assets() {
        $this->enqueue_style();
        wp_enqueue_script( 'lae-user-menu' );
    }

    /**
     * Enfileira apenas as folhas de estilo do plugin.
     */
    public function enqueue_style() {
        wp_enqueue_style( 'lae-user-menu' );
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
