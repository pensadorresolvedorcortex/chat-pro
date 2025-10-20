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
     * Courses manager.
     *
     * @var Questoes_Courses
     */
    protected $courses;

    /**
     * Constructor.
     *
     * @param Questoes_Settings $settings Settings handler.
     * @param Questoes_Renderer $renderer Renderer.
     * @param Questoes_Courses  $courses  Courses manager.
     */
    public function __construct( Questoes_Settings $settings, Questoes_Renderer $renderer, Questoes_Courses $courses ) {
        $this->settings = $settings;
        $this->renderer = $renderer;
        $this->courses  = $courses;

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

        $options         = $this->settings->all();
        $palette         = $options['palette'];
        $course_post_type = $this->courses->get_post_type();
        $course_category  = $this->courses->get_category_taxonomy();
        $course_board     = $this->courses->get_board_taxonomy();
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
                    <h2><?php esc_html_e( 'Catálogo de cursos', 'questoes' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Cadastre cursos, categorias e bancas próprios para compor a vitrine do shortcode.', 'questoes' ); ?></p>
                    <div class="questoes-action-grid">
                        <a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $course_post_type ) ); ?>">
                            <?php esc_html_e( 'Adicionar curso', 'questoes' ); ?>
                        </a>
                        <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $course_post_type ) ); ?>">
                            <?php esc_html_e( 'Todos os cursos', 'questoes' ); ?>
                        </a>
                        <a class="button" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . $course_category . '&post_type=' . $course_post_type ) ); ?>">
                            <?php esc_html_e( 'Categorias de curso', 'questoes' ); ?>
                        </a>
                        <a class="button" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . $course_board . '&post_type=' . $course_post_type ) ); ?>">
                            <?php esc_html_e( 'Bancas', 'questoes' ); ?>
                        </a>
                    </div>
                    <p class="description"><?php esc_html_e( 'Use os campos de destaque, remuneração e selo para construir cards ricos, conforme o layout de Cursos em alta.', 'questoes' ); ?></p>
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
                        <li>
                            <code>[questoes_cursos titulo_destaques="Cursos em alta"]</code>
                            <span><?php esc_html_e( 'Mostra a vitrine de cursos com cards modernos, resumo numérico e carregamento dos destaques cadastrados no painel.', 'questoes' ); ?></span>
                        </li>
                        <li>
                            <code>[academia_cursos]</code>
                            <span><?php esc_html_e( 'Alias do shortcode de cursos para compatibilidade com páginas existentes.', 'questoes' ); ?></span>
                        </li>
                    </ul>
                    <p class="description"><?php esc_html_e( 'No Elementor, adicione um widget de Shortcode ou utilize os widgets “Questões – Mapa/Organograma”, “Questões – Banco” e “Questões – Cursos” para configurar tudo diretamente no editor.', 'questoes' ); ?></p>
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

            <div class="questoes-card questoes-card--quick">
                <h2><?php esc_html_e( 'Cadastro rápido de catálogo', 'questoes' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Adicione cursos, categorias e bancas sem sair desta página. Ideal para validar o layout do shortcode rapidamente.', 'questoes' ); ?></p>

                <div class="questoes-quick-grid">
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="questoes-quick-form">
                        <?php wp_nonce_field( 'questoes_quick_add_course', 'questoes_quick_course_nonce' ); ?>
                        <input type="hidden" name="action" value="questoes_quick_add_course" />
                        <h3><?php esc_html_e( 'Criar curso', 'questoes' ); ?></h3>
                        <p class="description"><?php esc_html_e( 'Informe os principais dados do curso. Você poderá editar detalhes avançados depois.', 'questoes' ); ?></p>

                        <label class="questoes-label" for="questoes-quick-course-title"><?php esc_html_e( 'Título do curso', 'questoes' ); ?></label>
                        <input type="text" id="questoes-quick-course-title" name="questoes_quick_course_title" class="widefat" required />

                        <label class="questoes-label" for="questoes-quick-course-highlight"><?php esc_html_e( 'Descrição em destaque', 'questoes' ); ?></label>
                        <textarea id="questoes-quick-course-highlight" name="questoes_quick_course_highlight" rows="3" class="widefat" placeholder="<?php esc_attr_e( 'Resumo curto sobre o diferencial do curso.', 'questoes' ); ?>"></textarea>

                        <div class="questoes-quick-inline">
                            <div>
                                <label class="questoes-label" for="questoes-quick-course-salary"><?php esc_html_e( 'Faixa salarial', 'questoes' ); ?></label>
                                <input type="text" id="questoes-quick-course-salary" name="questoes_quick_course_salary" class="widefat" placeholder="<?php esc_attr_e( 'Ex.: Até R$ 6.800,00', 'questoes' ); ?>" />
                            </div>
                            <div>
                                <label class="questoes-label" for="questoes-quick-course-opportunities"><?php esc_html_e( 'Oportunidades', 'questoes' ); ?></label>
                                <input type="text" id="questoes-quick-course-opportunities" name="questoes_quick_course_opportunities" class="widefat" placeholder="<?php esc_attr_e( 'Ex.: 1.200 vagas previstas', 'questoes' ); ?>" />
                            </div>
                        </div>

                        <label class="questoes-label" for="questoes-quick-course-badge"><?php esc_html_e( 'Selo', 'questoes' ); ?></label>
                        <input type="text" id="questoes-quick-course-badge" name="questoes_quick_course_badge" class="widefat" placeholder="<?php esc_attr_e( 'Ex.: Em alta', 'questoes' ); ?>" />

                        <label class="questoes-label" for="questoes-quick-course-cta"><?php esc_html_e( 'Texto do botão', 'questoes' ); ?></label>
                        <input type="text" id="questoes-quick-course-cta" name="questoes_quick_course_cta" class="widefat" placeholder="<?php esc_attr_e( 'Ver cursos disponíveis', 'questoes' ); ?>" />

                        <label class="questoes-label" for="questoes-quick-course-url"><?php esc_html_e( 'Link do curso', 'questoes' ); ?></label>
                        <input type="url" id="questoes-quick-course-url" name="questoes_quick_course_url" class="widefat" placeholder="https://" />

                        <label class="questoes-label" for="questoes-quick-course-icon"><?php esc_html_e( 'Ícone ou brasão (URL da imagem)', 'questoes' ); ?></label>
                        <input type="url" id="questoes-quick-course-icon" name="questoes_quick_course_icon" class="widefat" placeholder="https://" />

                        <div class="questoes-quick-inline">
                            <div>
                                <label class="questoes-label" for="questoes-quick-course-questions"><?php esc_html_e( 'Questões no banco', 'questoes' ); ?></label>
                                <input type="number" id="questoes-quick-course-questions" name="questoes_quick_course_questions" class="small-text" min="0" step="1" />
                            </div>
                            <div>
                                <label class="questoes-label" for="questoes-quick-course-comments"><?php esc_html_e( 'Comentários', 'questoes' ); ?></label>
                                <input type="number" id="questoes-quick-course-comments" name="questoes_quick_course_comments" class="small-text" min="0" step="1" />
                            </div>
                        </div>

                        <label class="questoes-label" for="questoes-quick-course-categories"><?php esc_html_e( 'Categorias (separe por vírgulas)', 'questoes' ); ?></label>
                        <input type="text" id="questoes-quick-course-categories" name="questoes_quick_course_categories" class="widefat" placeholder="<?php esc_attr_e( 'Ex.: Segurança Pública, Tribunais', 'questoes' ); ?>" />

                        <label class="questoes-label" for="questoes-quick-course-boards"><?php esc_html_e( 'Bancas (separe por vírgulas)', 'questoes' ); ?></label>
                        <input type="text" id="questoes-quick-course-boards" name="questoes_quick_course_boards" class="widefat" placeholder="<?php esc_attr_e( 'Ex.: Cebraspe, Vunesp', 'questoes' ); ?>" />

                        <label class="questoes-switch questoes-switch--inline">
                            <input type="checkbox" name="questoes_quick_course_featured" value="1" />
                            <span class="questoes-switch-slider" aria-hidden="true"></span>
                            <span class="questoes-switch-text"><?php esc_html_e( 'Destacar na vitrine', 'questoes' ); ?></span>
                        </label>

                        <button type="submit" class="button button-primary questoes-quick-submit"><?php esc_html_e( 'Criar curso', 'questoes' ); ?></button>
                    </form>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="questoes-quick-form">
                        <?php wp_nonce_field( 'questoes_quick_add_category', 'questoes_quick_category_nonce' ); ?>
                        <input type="hidden" name="action" value="questoes_quick_add_course_category" />
                        <h3><?php esc_html_e( 'Criar categoria', 'questoes' ); ?></h3>
                        <p class="description"><?php esc_html_e( 'Ideal para organizar os cursos em disciplinas ou áreas.', 'questoes' ); ?></p>

                        <label class="questoes-label" for="questoes-quick-category-name"><?php esc_html_e( 'Nome da categoria', 'questoes' ); ?></label>
                        <input type="text" id="questoes-quick-category-name" name="questoes_quick_category_name" class="widefat" required />

                        <label class="questoes-label" for="questoes-quick-category-description"><?php esc_html_e( 'Descrição (opcional)', 'questoes' ); ?></label>
                        <textarea id="questoes-quick-category-description" name="questoes_quick_category_description" rows="3" class="widefat"></textarea>

                        <button type="submit" class="button questoes-quick-submit"><?php esc_html_e( 'Criar categoria', 'questoes' ); ?></button>
                    </form>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="questoes-quick-form">
                        <?php wp_nonce_field( 'questoes_quick_add_board', 'questoes_quick_board_nonce' ); ?>
                        <input type="hidden" name="action" value="questoes_quick_add_course_board" />
                        <h3><?php esc_html_e( 'Criar banca', 'questoes' ); ?></h3>
                        <p class="description"><?php esc_html_e( 'Cadastre bancas organizadoras para relacionar aos cursos.', 'questoes' ); ?></p>

                        <label class="questoes-label" for="questoes-quick-board-name"><?php esc_html_e( 'Nome da banca', 'questoes' ); ?></label>
                        <input type="text" id="questoes-quick-board-name" name="questoes_quick_board_name" class="widefat" required />

                        <button type="submit" class="button questoes-quick-submit"><?php esc_html_e( 'Criar banca', 'questoes' ); ?></button>
                    </form>
                </div>
            </div>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="questoes-import-form">
                <?php wp_nonce_field( 'questoes_import_courses', 'questoes_import_courses_nonce' ); ?>
                <input type="hidden" name="action" value="questoes_import_courses" />
                <div class="questoes-card">
                    <h2><?php esc_html_e( 'Importar cursos de exemplo', 'questoes' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Carregue rapidamente um catálogo base com cursos, categorias e bancas para validar o layout.', 'questoes' ); ?></p>

                    <div class="questoes-import-actions">
                        <button type="submit" class="button button-primary" name="questoes_courses_mode" value="sample"><?php esc_html_e( 'Importar cursos sugeridos', 'questoes' ); ?></button>
                        <a class="button" href="<?php echo esc_url( QUESTOES_PLUGIN_URL . 'sample-data/courses.json' ); ?>" download>
                            <?php esc_html_e( 'Baixar modelo de cursos', 'questoes' ); ?>
                        </a>
                    </div>

                    <p class="description"><?php esc_html_e( 'O pacote sugerido inclui Polícia Militar de SP, INSS, Polícia Federal, Banco do Brasil, TJ-RJ e TCU para validar o layout apresentado.', 'questoes' ); ?></p>

                    <p class="description"><?php esc_html_e( 'Envie um arquivo ou cole um JSON com cursos personalizados, incluindo campos de destaque, remuneração e selo.', 'questoes' ); ?></p>

                    <label for="questoes-courses-file" class="questoes-label"><?php esc_html_e( 'Arquivo JSON de cursos', 'questoes' ); ?></label>
                    <div class="questoes-import-inline">
                        <input type="file" id="questoes-courses-file" name="questoes_courses_file" accept="application/json" />
                        <button type="submit" class="button" name="questoes_courses_mode" value="upload"><?php esc_html_e( 'Importar arquivo', 'questoes' ); ?></button>
                    </div>

                    <label for="questoes-courses-json" class="questoes-label"><?php esc_html_e( 'JSON (cole o conteúdo)', 'questoes' ); ?></label>
                    <textarea id="questoes-courses-json" name="questoes_courses_json" rows="6" class="large-text code"></textarea>
                    <button type="submit" class="button questoes-import-submit" name="questoes_courses_mode" value="paste"><?php esc_html_e( 'Importar cursos colados', 'questoes' ); ?></button>
                </div>
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
