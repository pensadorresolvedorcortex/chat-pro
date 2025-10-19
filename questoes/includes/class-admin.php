<?php
/**
 * Admin settings page.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin controller.
 */
class Questoes_Admin {

    /**
     * Settings.
     *
     * @var Questoes_Settings
     */
    protected $settings;

    /**
     * Renderer.
     *
     * @var Questoes_Renderer
     */
    protected $renderer;

    /**
     * Constructor.
     *
     * @param Questoes_Settings $settings Settings handler.
     * @param Questoes_Renderer $renderer Renderer.
     */
    public function __construct( Questoes_Settings $settings, Questoes_Renderer $renderer ) {
        $this->settings = $settings;
        $this->renderer = $renderer;

        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Register menu.
     */
    public function register_menu() {
        add_menu_page(
            __( 'Questões', 'questoes' ),
            __( 'Questões', 'questoes' ),
            'manage_options',
            'questoes',
            array( $this, 'render_page' ),
            'dashicons-chart-network'
        );
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_assets( $hook ) {
        if ( 'toplevel_page_questoes' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'questoes-admin',
            QUESTOES_PLUGIN_URL . 'assets/admin/admin.css',
            array(),
            questoes_asset_version( 'assets/admin/admin.css' )
        );

        wp_enqueue_script(
            'questoes-admin',
            QUESTOES_PLUGIN_URL . 'assets/admin/admin.js',
            array( 'jquery' ),
            questoes_asset_version( 'assets/admin/admin.js' ),
            true
        );

        wp_localize_script(
            'questoes-admin',
            'questoesAdmin',
            array(
                'testNonce' => wp_create_nonce( 'questoes_test_render' ),
                'testUrl'   => rest_url( 'questoes/v1/data' ),
                'messages'  => array(
                    'invalid' => __( 'JSON inválido: verifique vírgulas e chaves.', 'questoes' ),
                    'saved'   => __( 'Dados salvos com sucesso.', 'questoes' ),
                ),
            )
        );
    }

    /**
     * Render settings page.
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $options = $this->settings->all();
        $palette = $options['palette'];
        ?>
        <div class="wrap questoes-admin">
            <h1><?php esc_html_e( 'Questões — Academia da Comunicação', 'questoes' ); ?></h1>
            <form method="post" action="options.php" enctype="multipart/form-data">
                <?php settings_fields( 'questoes_settings' ); ?>
                <div class="questoes-card">
                    <h2><?php esc_html_e( 'Configurações Gerais', 'questoes' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Cole seu JSON no campo abaixo e clique em Testar.', 'questoes' ); ?></p>
                    <label for="questoes-title" class="questoes-label"><?php esc_html_e( 'Título padrão', 'questoes' ); ?></label>
                    <input type="text" id="questoes-title" name="questoes_settings[title]" value="<?php echo esc_attr( $options['title'] ); ?>" class="regular-text" />

                    <fieldset class="questoes-toggle">
                        <legend><?php esc_html_e( 'Permitir personalização avançada da paleta', 'questoes' ); ?></legend>
                        <label for="questoes-allow-palette" class="questoes-switch">
                            <input type="checkbox" id="questoes-allow-palette" name="questoes_settings[allow_palette]" value="1" <?php checked( $options['allow_palette'], 1 ); ?> />
                            <span class="questoes-switch-slider" aria-hidden="true"></span>
                            <span class="screen-reader-text"><?php esc_html_e( 'Permitir edição das cores', 'questoes' ); ?></span>
                        </label>
                    </fieldset>

                    <div class="questoes-palette" aria-disabled="<?php echo $options['allow_palette'] ? 'false' : 'true'; ?>">
                        <?php foreach ( questoes_get_default_palette() as $key => $default_color ) : ?>
                            <label>
                                <span><?php echo esc_html( ucfirst( $key ) ); ?></span>
                                <input type="text" name="questoes_settings[palette][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $palette[ $key ] ); ?>" <?php disabled( ! $options['allow_palette'] ); ?> />
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <label for="questoes-data" class="questoes-label"><?php esc_html_e( 'Dados (JSON)', 'questoes' ); ?></label>
                    <textarea id="questoes-data" name="questoes_settings[data]" rows="12" class="large-text code"><?php echo esc_textarea( $options['data'] ); ?></textarea>

                    <label for="questoes-data-source" class="questoes-label"><?php esc_html_e( 'Fonte dos dados', 'questoes' ); ?></label>
                    <select id="questoes-data-source" name="questoes_settings[data_source]">
                        <option value="stored" <?php selected( $options['data_source'], 'stored' ); ?>><?php esc_html_e( 'Configuração manual (acima)', 'questoes' ); ?></option>
                        <option value="endpoint" <?php selected( $options['data_source'], 'endpoint' ); ?>><?php esc_html_e( 'Endpoint remoto', 'questoes' ); ?></option>
                    </select>

                    <label for="questoes-upload" class="questoes-label"><?php esc_html_e( 'Upload de arquivo .json', 'questoes' ); ?></label>
                    <input type="file" id="questoes-upload" accept="application/json" />

                    <label for="questoes-endpoint" class="questoes-label"><?php esc_html_e( 'Endpoint remoto (opcional)', 'questoes' ); ?></label>
                    <input type="url" id="questoes-endpoint" name="questoes_settings[data_endpoint]" value="<?php echo esc_attr( $options['data_endpoint'] ); ?>" class="regular-text" />

                    <fieldset class="questoes-toggle">
                        <legend><?php esc_html_e( 'Exibir comentários abaixo do componente', 'questoes' ); ?></legend>
                        <label for="questoes-comments" class="questoes-switch">
                            <input type="checkbox" id="questoes-comments" name="questoes_settings[comments_enabled]" value="1" <?php checked( $options['comments_enabled'], 1 ); ?> />
                            <span class="questoes-switch-slider" aria-hidden="true"></span>
                            <span class="screen-reader-text"><?php esc_html_e( 'Habilitar comentários', 'questoes' ); ?></span>
                        </label>
                    </fieldset>
                </div>

                <div class="questoes-card">
                    <h2><?php esc_html_e( 'Exportar / Importar', 'questoes' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Exportar as configurações atuais ou importar um pacote JSON.', 'questoes' ); ?></p>
                    <button type="button" class="button button-secondary" id="questoes-export-config"><?php esc_html_e( 'Exportar Configurações', 'questoes' ); ?></button>
                    <button type="button" class="button button-secondary" id="questoes-export-data"><?php esc_html_e( 'Exportar JSON', 'questoes' ); ?></button>
                    <input type="file" id="questoes-import" accept="application/json" />
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
