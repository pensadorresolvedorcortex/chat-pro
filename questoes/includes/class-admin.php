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
                    <h2><?php esc_html_e( 'Shortcodes disponíveis', 'questoes' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Use os shortcodes abaixo em páginas, posts, widgets ou no Elementor para exibir os componentes do plugin.', 'questoes' ); ?></p>
                    <ul class="questoes-shortcode-list">
                        <li>
                            <code>[questoes modo="ambos"]</code>
                            <span><?php esc_html_e( 'Exibe mapa mental e organograma com os dados configurados.', 'questoes' ); ?></span>
                        </li>
                        <li>
                            <code>[questoes modo="mapa" titulo="Mapa Personalizado"]JSON...</code>
                            <span><?php esc_html_e( 'Sobrescreve o título e utiliza o JSON informado entre as tags.', 'questoes' ); ?></span>
                        </li>
                        <li>
                            <code>[questoes modo="organograma"]</code>
                            <span><?php esc_html_e( 'Renderiza apenas o organograma com base nos dados salvos.', 'questoes' ); ?></span>
                        </li>
                        <li>
                            <code>[questoes_banco titulo="Banco de Questões" mostrar_filtros="sim" por_pagina="10"]</code>
                            <span><?php esc_html_e( 'Lista interativa com filtros e paginação para estudar o banco de questões.', 'questoes' ); ?></span>
                        </li>
                        <li>
                            <code>[questoes_busca titulo="Encontre Questões" mostrar_filtros="sim" por_pagina="10"]</code>
                            <span><?php esc_html_e( 'Exibe apenas o formulário de busca e carrega questões após a pesquisa do usuário.', 'questoes' ); ?></span>
                        </li>
                        <li>
                            <code>[questoes_disciplinas titulo="Disciplinas" descricao="Principais áreas"]</code>
                            <span><?php esc_html_e( 'Mostra o painel de disciplinas com filtros por palavra-chave e área de formação.', 'questoes' ); ?></span>
                        </li>
                    </ul>
                    <p class="description"><?php esc_html_e( 'No Elementor, adicione um widget de Shortcode ou os widgets “Questões – Mapa/Organograma” e “Questões – Banco” para configurar tudo diretamente no editor.', 'questoes' ); ?></p>
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

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="questoes-import-form">
                <?php wp_nonce_field( 'questoes_import_questions', 'questoes_import_nonce' ); ?>
                <input type="hidden" name="action" value="questoes_import_questions" />
                <div class="questoes-card">
                    <h2><?php esc_html_e( 'Importar banco de questões', 'questoes' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Importe o pacote com 20 questões de exemplo para testar rapidamente a experiência completa.', 'questoes' ); ?></p>

                    <div class="questoes-import-actions">
                        <button type="submit" class="button button-primary" name="questoes_import_mode" value="sample"><?php esc_html_e( 'Importar 20 questões de exemplo', 'questoes' ); ?></button>
                        <a class="button" href="<?php echo esc_url( QUESTOES_PLUGIN_URL . 'sample-data/questions.json' ); ?>" download>
                            <?php esc_html_e( 'Baixar modelo JSON', 'questoes' ); ?>
                        </a>
                    </div>

                    <p class="description"><?php esc_html_e( 'Faça upload de um arquivo JSON ou cole o conteúdo no campo abaixo para cadastrar ou atualizar questões em massa.', 'questoes' ); ?></p>

                    <label for="questoes-import-file" class="questoes-label"><?php esc_html_e( 'Arquivo JSON', 'questoes' ); ?></label>
                    <div class="questoes-import-inline">
                        <input type="file" id="questoes-import-file" name="questoes_import_file" accept="application/json" />
                        <button type="submit" class="button" name="questoes_import_mode" value="upload"><?php esc_html_e( 'Importar arquivo JSON', 'questoes' ); ?></button>
                    </div>

                    <label for="questoes-import-json" class="questoes-label"><?php esc_html_e( 'JSON (cole o conteúdo)', 'questoes' ); ?></label>
                    <textarea id="questoes-import-json" name="questoes_import_json" rows="8" class="large-text code"></textarea>
                    <button type="submit" class="button questoes-import-submit" name="questoes_import_mode" value="paste"><?php esc_html_e( 'Importar JSON colado', 'questoes' ); ?></button>
                </div>
            </form>
        </div>
        <?php
    }
}
