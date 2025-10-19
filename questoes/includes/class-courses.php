<?php
/**
 * Courses management.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Questoes_Courses {

    /**
     * Post type slug.
     *
     * @var string
     */
    protected $post_type = 'quest_course';

    /**
     * Category taxonomy.
     *
     * @var string
     */
    protected $category_taxonomy = 'quest_course_category';

    /**
     * Board taxonomy.
     *
     * @var string
     */
    protected $board_taxonomy = 'quest_course_board';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post_' . $this->post_type, array( $this, 'save_course_meta' ), 10, 2 );
        add_filter( 'manage_edit-' . $this->post_type . '_columns', array( $this, 'register_columns' ) );
        add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
        add_filter( 'manage_edit-' . $this->post_type . '_sortable_columns', array( $this, 'register_sortable_columns' ) );
        add_action( 'admin_post_questoes_import_courses', array( $this, 'handle_import_request' ) );
        add_action( 'admin_notices', array( $this, 'maybe_render_import_notice' ) );
        add_action( 'admin_post_questoes_quick_add_course', array( $this, 'handle_quick_add_course' ) );
        add_action( 'admin_post_questoes_quick_add_course_category', array( $this, 'handle_quick_add_course_category' ) );
        add_action( 'admin_post_questoes_quick_add_course_board', array( $this, 'handle_quick_add_course_board' ) );
        add_action( 'admin_notices', array( $this, 'maybe_render_quick_add_notice' ) );
    }

    /**
     * Get post type slug.
     *
     * @return string
     */
    public function get_post_type() {
        return $this->post_type;
    }

    /**
     * Get category taxonomy slug.
     *
     * @return string
     */
    public function get_category_taxonomy() {
        return $this->category_taxonomy;
    }

    /**
     * Get board taxonomy slug.
     *
     * @return string
     */
    public function get_board_taxonomy() {
        return $this->board_taxonomy;
    }

    /**
     * Register post type.
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Cursos', 'Post Type General Name', 'questoes' ),
            'singular_name'         => _x( 'Curso', 'Post Type Singular Name', 'questoes' ),
            'menu_name'             => __( 'Cursos', 'questoes' ),
            'name_admin_bar'        => __( 'Curso', 'questoes' ),
            'add_new'               => __( 'Adicionar novo', 'questoes' ),
            'add_new_item'          => __( 'Adicionar novo curso', 'questoes' ),
            'edit_item'             => __( 'Editar curso', 'questoes' ),
            'new_item'              => __( 'Novo curso', 'questoes' ),
            'view_item'             => __( 'Ver curso', 'questoes' ),
            'view_items'            => __( 'Ver cursos', 'questoes' ),
            'search_items'          => __( 'Buscar cursos', 'questoes' ),
            'not_found'             => __( 'Nenhum curso encontrado.', 'questoes' ),
            'not_found_in_trash'    => __( 'Nenhum curso encontrado na lixeira.', 'questoes' ),
            'all_items'             => __( 'Todos os cursos', 'questoes' ),
            'archives'              => __( 'Arquivos de cursos', 'questoes' ),
            'attributes'            => __( 'Atributos do curso', 'questoes' ),
            'insert_into_item'      => __( 'Inserir no curso', 'questoes' ),
            'uploaded_to_this_item' => __( 'Enviado para este curso', 'questoes' ),
            'filter_items_list'     => __( 'Filtrar lista de cursos', 'questoes' ),
            'items_list_navigation' => __( 'Navegação da lista de cursos', 'questoes' ),
            'items_list'            => __( 'Lista de cursos', 'questoes' ),
        );

        $args = array(
            'label'               => __( 'Curso', 'questoes' ),
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => false,
            'menu_icon'           => 'dashicons-awards',
            'rewrite'             => array( 'slug' => 'cursos' ),
            'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'comments' ),
            'show_in_rest'        => true,
            'rest_base'           => 'questoes-cursos',
            'menu_position'       => 26,
            'description'         => __( 'Catálogo de cursos preparatórios e concursos destacados.', 'questoes' ),
            'capability_type'     => 'post',
        );

        register_post_type( $this->post_type, $args );

        $meta_fields = array(
            'questoes_course_salary'        => array( 'type' => 'string' ),
            'questoes_course_opportunities' => array( 'type' => 'string' ),
            'questoes_course_badge'         => array( 'type' => 'string' ),
            'questoes_course_highlight'     => array( 'type' => 'string' ),
            'questoes_course_featured'      => array( 'type' => 'string' ),
            'questoes_course_cta'           => array( 'type' => 'string' ),
            'questoes_course_url'           => array( 'type' => 'string' ),
            'questoes_course_questions'     => array( 'type' => 'number' ),
            'questoes_course_comments'      => array( 'type' => 'number' ),
            'questoes_course_priority'      => array( 'type' => 'number' ),
            'questoes_course_icon'          => array( 'type' => 'string' ),
        );

        foreach ( $meta_fields as $key => $schema ) {
            register_post_meta(
                $this->post_type,
                $key,
                array(
                    'type'         => $schema['type'],
                    'single'       => true,
                    'show_in_rest' => true,
                    'auth_callback' => function() {
                        return current_user_can( 'edit_posts' );
                    },
                )
            );
        }
    }

    /**
     * Register taxonomies.
     */
    public function register_taxonomies() {
        register_taxonomy(
            $this->category_taxonomy,
            $this->post_type,
            array(
                'labels' => array(
                    'name'          => __( 'Categorias de curso', 'questoes' ),
                    'singular_name' => __( 'Categoria de curso', 'questoes' ),
                ),
                'public'            => true,
                'hierarchical'      => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
            )
        );

        register_taxonomy(
            $this->board_taxonomy,
            $this->post_type,
            array(
                'labels' => array(
                    'name'          => __( 'Bancas', 'questoes' ),
                    'singular_name' => __( 'Banca', 'questoes' ),
                ),
                'public'            => true,
                'hierarchical'      => false,
                'show_admin_column' => true,
                'show_in_rest'      => true,
            )
        );
    }

    /**
     * Register meta boxes.
     */
    public function register_meta_boxes() {
        add_meta_box(
            'questoes-course-details',
            __( 'Detalhes do curso', 'questoes' ),
            array( $this, 'render_meta_box' ),
            $this->post_type,
            'normal',
            'high'
        );
    }

    /**
     * Render meta box.
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'questoes_save_course', 'questoes_course_nonce' );

        $meta = $this->get_course_meta( $post->ID );
        ?>
        <p>
            <label for="questoes-course-highlight" class="questoes-label"><?php esc_html_e( 'Resumo em destaque', 'questoes' ); ?></label>
            <textarea id="questoes-course-highlight" name="questoes_course_highlight" rows="3" class="widefat"><?php echo esc_textarea( $meta['highlight'] ); ?></textarea>
        </p>
        <p>
            <label for="questoes-course-salary" class="questoes-label"><?php esc_html_e( 'Remuneração', 'questoes' ); ?></label>
            <input type="text" id="questoes-course-salary" name="questoes_course_salary" class="widefat" value="<?php echo esc_attr( $meta['salary'] ); ?>" />
        </p>
        <p>
            <label for="questoes-course-opportunities" class="questoes-label"><?php esc_html_e( 'Oportunidades', 'questoes' ); ?></label>
            <input type="text" id="questoes-course-opportunities" name="questoes_course_opportunities" class="widefat" value="<?php echo esc_attr( $meta['opportunities'] ); ?>" />
        </p>
        <div class="questoes-course-meta-grid">
            <p>
                <label for="questoes-course-questions" class="questoes-label"><?php esc_html_e( 'Questões no banco', 'questoes' ); ?></label>
                <input type="number" id="questoes-course-questions" name="questoes_course_questions" class="small-text" min="0" value="<?php echo esc_attr( $meta['questions'] ); ?>" />
            </p>
            <p>
                <label for="questoes-course-comments" class="questoes-label"><?php esc_html_e( 'Comentários', 'questoes' ); ?></label>
                <input type="number" id="questoes-course-comments" name="questoes_course_comments" class="small-text" min="0" value="<?php echo esc_attr( $meta['comments'] ); ?>" />
            </p>
            <p>
                <label for="questoes-course-priority" class="questoes-label"><?php esc_html_e( 'Prioridade', 'questoes' ); ?></label>
                <input type="number" id="questoes-course-priority" name="questoes_course_priority" class="small-text" min="0" value="<?php echo esc_attr( $meta['priority'] ); ?>" />
            </p>
        </div>
        <p>
            <label for="questoes-course-badge" class="questoes-label"><?php esc_html_e( 'Selo', 'questoes' ); ?></label>
            <input type="text" id="questoes-course-badge" name="questoes_course_badge" class="widefat" value="<?php echo esc_attr( $meta['badge'] ); ?>" />
        </p>
        <p>
            <label for="questoes-course-icon" class="questoes-label"><?php esc_html_e( 'URL do ícone', 'questoes' ); ?></label>
            <input type="url" id="questoes-course-icon" name="questoes_course_icon" class="widefat" value="<?php echo esc_attr( $meta['icon'] ); ?>" />
        </p>
        <p>
            <label for="questoes-course-cta" class="questoes-label"><?php esc_html_e( 'Texto do botão', 'questoes' ); ?></label>
            <input type="text" id="questoes-course-cta" name="questoes_course_cta" class="widefat" value="<?php echo esc_attr( $meta['cta'] ); ?>" />
        </p>
        <p>
            <label for="questoes-course-url" class="questoes-label"><?php esc_html_e( 'Link do curso', 'questoes' ); ?></label>
            <input type="url" id="questoes-course-url" name="questoes_course_url" class="widefat" value="<?php echo esc_attr( $meta['url'] ); ?>" />
        </p>
        <p>
            <label class="questoes-switch questoes-switch--inline">
                <input type="checkbox" name="questoes_course_featured" value="yes" <?php checked( $meta['featured'], 'yes' ); ?> />
                <span class="questoes-switch-slider" aria-hidden="true"></span>
                <span class="questoes-switch-text"><?php esc_html_e( 'Destacar na vitrine principal', 'questoes' ); ?></span>
            </label>
        </p>
        <?php
    }

    /**
     * Retrieve course meta.
     *
     * @param int $post_id Post ID.
     *
     * @return array
     */
    protected function get_course_meta( $post_id ) {
        return array(
            'highlight'   => get_post_meta( $post_id, 'questoes_course_highlight', true ),
            'salary'      => get_post_meta( $post_id, 'questoes_course_salary', true ),
            'opportunities' => get_post_meta( $post_id, 'questoes_course_opportunities', true ),
            'badge'       => get_post_meta( $post_id, 'questoes_course_badge', true ),
            'featured'    => get_post_meta( $post_id, 'questoes_course_featured', true ),
            'cta'         => get_post_meta( $post_id, 'questoes_course_cta', true ),
            'url'         => get_post_meta( $post_id, 'questoes_course_url', true ),
            'questions'   => get_post_meta( $post_id, 'questoes_course_questions', true ),
            'comments'    => get_post_meta( $post_id, 'questoes_course_comments', true ),
            'priority'    => get_post_meta( $post_id, 'questoes_course_priority', true ),
            'icon'        => get_post_meta( $post_id, 'questoes_course_icon', true ),
        );
    }

    /**
     * Save meta box values.
     */
    public function save_course_meta( $post_id, $post ) {
        if ( ! isset( $_POST['questoes_course_nonce'] ) || ! wp_verify_nonce( $_POST['questoes_course_nonce'], 'questoes_save_course' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( $post->post_type !== $this->post_type ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = array(
            'questoes_course_highlight'     => 'wp_kses_post',
            'questoes_course_salary'        => 'sanitize_text_field',
            'questoes_course_opportunities' => 'sanitize_text_field',
            'questoes_course_badge'         => 'sanitize_text_field',
            'questoes_course_featured'      => function( $value ) {
                return 'yes' === $value ? 'yes' : '';
            },
            'questoes_course_cta'           => 'sanitize_text_field',
            'questoes_course_url'           => 'esc_url_raw',
            'questoes_course_questions'     => 'absint',
            'questoes_course_comments'      => 'absint',
            'questoes_course_priority'      => 'absint',
            'questoes_course_icon'          => 'esc_url_raw',
        );

        foreach ( $fields as $field => $callback ) {
            $value = isset( $_POST[ $field ] ) ? $_POST[ $field ] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

            if ( is_callable( $callback ) ) {
                $sanitized = call_user_func( $callback, wp_unslash( $value ) );
            } else {
                $sanitized = sanitize_text_field( wp_unslash( $value ) );
            }

            if ( '' === $sanitized || null === $sanitized ) {
                delete_post_meta( $post_id, $field );
            } else {
                update_post_meta( $post_id, $field, $sanitized );
            }
        }
    }

    /**
     * Add admin columns.
     */
    public function register_columns( $columns ) {
        $new = array();
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( 'title' === $key ) {
                $new['questoes_course_category'] = __( 'Categoria', 'questoes' );
                $new['questoes_course_salary']   = __( 'Remuneração', 'questoes' );
                $new['questoes_course_featured'] = __( 'Destaque', 'questoes' );
            }
        }

        return $new;
    }

    /**
     * Render custom columns.
     */
    public function render_columns( $column, $post_id ) {
        if ( 'questoes_course_category' === $column ) {
            $terms = get_the_terms( $post_id, $this->category_taxonomy );
            if ( empty( $terms ) || is_wp_error( $terms ) ) {
                esc_html_e( 'Sem categoria', 'questoes' );
                return;
            }

            $names = wp_list_pluck( $terms, 'name' );
            echo esc_html( implode( ', ', $names ) );
        } elseif ( 'questoes_course_salary' === $column ) {
            $salary = get_post_meta( $post_id, 'questoes_course_salary', true );
            echo esc_html( $salary );
        } elseif ( 'questoes_course_featured' === $column ) {
            $featured = get_post_meta( $post_id, 'questoes_course_featured', true );
            echo $featured ? esc_html__( 'Sim', 'questoes' ) : esc_html__( 'Não', 'questoes' );
        }
    }

    /**
     * Make columns sortable.
     */
    public function register_sortable_columns( $columns ) {
        $columns['questoes_course_featured'] = 'questoes_course_featured';
        $columns['questoes_course_salary']   = 'questoes_course_salary';
        return $columns;
    }

    /**
     * Import handler.
     */
    public function handle_import_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para importar cursos.', 'questoes' ) );
        }

        check_admin_referer( 'questoes_import_courses', 'questoes_import_courses_nonce' );

        $redirect_base = admin_url( 'admin.php' );
        $mode          = isset( $_POST['questoes_courses_mode'] ) ? sanitize_key( wp_unslash( $_POST['questoes_courses_mode'] ) ) : '';
        $payload       = '';

        if ( 'sample' === $mode ) {
            $sample_file = QUESTOES_PLUGIN_DIR . 'sample-data/courses.json';
            if ( ! file_exists( $sample_file ) ) {
                $this->redirect_with_error( $redirect_base, __( 'Não foi possível localizar o arquivo de cursos de exemplo.', 'questoes' ) );
            }
            $payload = file_get_contents( $sample_file );
        } elseif ( 'upload' === $mode ) {
            if ( empty( $_FILES['questoes_courses_file']['tmp_name'] ) ) {
                $this->redirect_with_error( $redirect_base, __( 'O arquivo JSON enviado está vazio.', 'questoes' ) );
            }
            $payload = file_get_contents( $_FILES['questoes_courses_file']['tmp_name'] );
        } elseif ( 'paste' === $mode ) {
            $payload = isset( $_POST['questoes_courses_json'] ) ? wp_unslash( $_POST['questoes_courses_json'] ) : '';
        } else {
            $this->redirect_with_error( $redirect_base, __( 'Selecione um modo de importação válido.', 'questoes' ) );
        }

        if ( false === $payload ) {
            $this->redirect_with_error( $redirect_base, __( 'Não foi possível ler o arquivo JSON informado.', 'questoes' ) );
        }

        $payload = trim( $payload );

        if ( empty( $payload ) ) {
            $this->redirect_with_error( $redirect_base, __( 'O arquivo JSON enviado está vazio.', 'questoes' ) );
        }

        $decoded = json_decode( $payload, true );

        if ( null === $decoded || ( JSON_ERROR_NONE !== json_last_error() ) ) {
            $this->redirect_with_error( $redirect_base, __( 'O JSON fornecido é inválido.', 'questoes' ) );
        }

        $result = $this->import_courses_from_array( $decoded );

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_error( $redirect_base, $result->get_error_message() );
        }

        $args = array(
            'page'                            => 'questoes',
            'questoes_courses_status'         => 'success',
            'questoes_courses_created'        => $result['created'],
            'questoes_courses_updated'        => $result['updated'],
        );

        if ( ! empty( $result['errors'] ) ) {
            $args['questoes_courses_notice'] = rawurlencode( $result['errors'][0] );
        }

        wp_safe_redirect( add_query_arg( $args, $redirect_base ) );
        exit;
    }

    /**
     * Redirect helper.
     */
    protected function redirect_with_error( $url, $message ) {
        $args = array(
            'page'                    => 'questoes',
            'questoes_courses_status' => 'error',
            'questoes_courses_message'=> rawurlencode( $message ),
        );

        wp_safe_redirect( add_query_arg( $args, $url ) );
        exit;
    }

    /**
     * Maybe render import notice.
     */
    public function maybe_render_import_notice() {
        if ( empty( $_GET['page'] ) || 'questoes' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        if ( empty( $_GET['questoes_courses_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $status  = sanitize_key( wp_unslash( $_GET['questoes_courses_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $message = '';
        $class   = 'notice notice-success';

        if ( 'success' === $status ) {
            $created = isset( $_GET['questoes_courses_created'] ) ? absint( $_GET['questoes_courses_created'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $updated = isset( $_GET['questoes_courses_updated'] ) ? absint( $_GET['questoes_courses_updated'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $message = sprintf(
                /* translators: 1: created count, 2: updated count */
                __( 'Cursos importados: %1$s criados, %2$s atualizados.', 'questoes' ),
                number_format_i18n( $created ),
                number_format_i18n( $updated )
            );
            if ( ! empty( $_GET['questoes_courses_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $message .= ' ' . esc_html( wp_unslash( rawurldecode( $_GET['questoes_courses_notice'] ) ) );
            }
        } else {
            $class   = 'notice notice-error';
            $message = ! empty( $_GET['questoes_courses_message'] ) ? wp_unslash( $_GET['questoes_courses_message'] ) : __( 'Ocorreu um erro durante a importação dos cursos.', 'questoes' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        if ( $message ) {
            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
        }
    }

    /**
     * Handle quick course creation form.
     */
    public function handle_quick_add_course() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para criar cursos.', 'questoes' ) );
        }

        check_admin_referer( 'questoes_quick_add_course', 'questoes_quick_course_nonce' );

        $title = isset( $_POST['questoes_quick_course_title'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_quick_course_title'] ) ) : '';

        if ( '' === $title ) {
            $this->redirect_quick_notice( 'course', 'error', __( 'Informe um título válido para o curso.', 'questoes' ) );
        }

        $highlight_raw = isset( $_POST['questoes_quick_course_highlight'] ) ? wp_unslash( $_POST['questoes_quick_course_highlight'] ) : '';
        $highlight     = wp_kses_post( $highlight_raw );
        $content       = $highlight;

        $postarr = array(
            'post_type'    => $this->post_type,
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_content' => $content,
        );

        $post_id = wp_insert_post( $postarr, true );

        if ( is_wp_error( $post_id ) ) {
            $this->redirect_quick_notice( 'course', 'error', $post_id->get_error_message() );
        }

        $meta_map = array(
            'questoes_course_highlight'     => $highlight,
            'questoes_course_salary'        => isset( $_POST['questoes_quick_course_salary'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_quick_course_salary'] ) ) : '',
            'questoes_course_opportunities' => isset( $_POST['questoes_quick_course_opportunities'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_quick_course_opportunities'] ) ) : '',
            'questoes_course_badge'         => isset( $_POST['questoes_quick_course_badge'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_quick_course_badge'] ) ) : '',
            'questoes_course_cta'           => isset( $_POST['questoes_quick_course_cta'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_quick_course_cta'] ) ) : '',
            'questoes_course_url'           => isset( $_POST['questoes_quick_course_url'] ) ? esc_url_raw( wp_unslash( $_POST['questoes_quick_course_url'] ) ) : '',
            'questoes_course_icon'          => isset( $_POST['questoes_quick_course_icon'] ) ? esc_url_raw( wp_unslash( $_POST['questoes_quick_course_icon'] ) ) : '',
            'questoes_course_questions'     => isset( $_POST['questoes_quick_course_questions'] ) ? absint( wp_unslash( $_POST['questoes_quick_course_questions'] ) ) : 0,
            'questoes_course_comments'      => isset( $_POST['questoes_quick_course_comments'] ) ? absint( wp_unslash( $_POST['questoes_quick_course_comments'] ) ) : 0,
            'questoes_course_featured'      => isset( $_POST['questoes_quick_course_featured'] ) ? 'yes' : '',
        );

        foreach ( $meta_map as $meta_key => $value ) {
            if ( '' === $value || null === $value ) {
                delete_post_meta( $post_id, $meta_key );
            } else {
                update_post_meta( $post_id, $meta_key, $value );
            }
        }

        $category_input = isset( $_POST['questoes_quick_course_categories'] ) ? wp_unslash( $_POST['questoes_quick_course_categories'] ) : '';
        $board_input    = isset( $_POST['questoes_quick_course_boards'] ) ? wp_unslash( $_POST['questoes_quick_course_boards'] ) : '';

        if ( $category_input ) {
            $this->assign_terms_from_list( $post_id, $category_input, $this->category_taxonomy );
        }

        if ( $board_input ) {
            $this->assign_terms_from_list( $post_id, $board_input, $this->board_taxonomy );
        }

        $this->redirect_quick_notice( 'course', 'success', '', array( 'label' => $title ) );
    }

    /**
     * Handle quick category creation.
     */
    public function handle_quick_add_course_category() {
        if ( ! current_user_can( 'manage_categories' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para criar categorias.', 'questoes' ) );
        }

        check_admin_referer( 'questoes_quick_add_category', 'questoes_quick_category_nonce' );

        $name = isset( $_POST['questoes_quick_category_name'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_quick_category_name'] ) ) : '';

        if ( '' === $name ) {
            $this->redirect_quick_notice( 'category', 'error', __( 'Informe um nome para a categoria.', 'questoes' ) );
        }

        $description = isset( $_POST['questoes_quick_category_description'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_quick_category_description'] ) ) : '';

        $term = wp_insert_term(
            $name,
            $this->category_taxonomy,
            array(
                'description' => $description,
            )
        );

        if ( is_wp_error( $term ) ) {
            $this->redirect_quick_notice( 'category', 'error', $term->get_error_message() );
        }

        $this->redirect_quick_notice( 'category', 'success', '', array( 'label' => $name ) );
    }

    /**
     * Handle quick board creation.
     */
    public function handle_quick_add_course_board() {
        if ( ! current_user_can( 'manage_categories' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para criar bancas.', 'questoes' ) );
        }

        check_admin_referer( 'questoes_quick_add_board', 'questoes_quick_board_nonce' );

        $name = isset( $_POST['questoes_quick_board_name'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_quick_board_name'] ) ) : '';

        if ( '' === $name ) {
            $this->redirect_quick_notice( 'board', 'error', __( 'Informe um nome para a banca.', 'questoes' ) );
        }

        $term = wp_insert_term( $name, $this->board_taxonomy );

        if ( is_wp_error( $term ) ) {
            $this->redirect_quick_notice( 'board', 'error', $term->get_error_message() );
        }

        $this->redirect_quick_notice( 'board', 'success', '', array( 'label' => $name ) );
    }

    /**
     * Display quick add notices.
     */
    public function maybe_render_quick_add_notice() {
        if ( empty( $_GET['page'] ) || 'questoes' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $types = array( 'course', 'category', 'board' );

        foreach ( $types as $type ) {
            $status_key = 'questoes_quick_' . $type . '_status';

            if ( empty( $_GET[ $status_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                continue;
            }

            $status  = sanitize_key( wp_unslash( $_GET[ $status_key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $label   = isset( $_GET[ 'questoes_quick_' . $type . '_label' ] ) ? sanitize_text_field( wp_unslash( rawurldecode( $_GET[ 'questoes_quick_' . $type . '_label' ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $message = isset( $_GET[ 'questoes_quick_' . $type . '_message' ] ) ? sanitize_text_field( wp_unslash( rawurldecode( $_GET[ 'questoes_quick_' . $type . '_message' ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

            if ( 'success' === $status ) {
                switch ( $type ) {
                    case 'course':
                        $content = sprintf( __( 'Curso “%s” criado com sucesso.', 'questoes' ), $label ? $label : __( 'sem título', 'questoes' ) );
                        break;
                    case 'category':
                        $content = sprintf( __( 'Categoria “%s” criada com sucesso.', 'questoes' ), $label ? $label : __( 'sem nome', 'questoes' ) );
                        break;
                    default:
                        $content = sprintf( __( 'Banca “%s” criada com sucesso.', 'questoes' ), $label ? $label : __( 'sem nome', 'questoes' ) );
                        break;
                }

                printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html( $content ) );
            } else {
                if ( ! $message ) {
                    switch ( $type ) {
                        case 'course':
                            $message = __( 'Não foi possível criar o curso.', 'questoes' );
                            break;
                        case 'category':
                            $message = __( 'Não foi possível criar a categoria.', 'questoes' );
                            break;
                        default:
                            $message = __( 'Não foi possível criar a banca.', 'questoes' );
                            break;
                    }
                }

                printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
            }
        }
    }

    /**
     * Assign comma separated terms to object.
     *
     * @param int    $post_id Post ID.
     * @param string $list    Term list.
     * @param string $taxonomy Taxonomy.
     */
    protected function assign_terms_from_list( $post_id, $list, $taxonomy ) {
        if ( empty( $taxonomy ) ) {
            return;
        }

        $names = array_filter( array_map( 'trim', explode( ',', $list ) ) );

        if ( empty( $names ) ) {
            return;
        }

        $term_ids = array();

        foreach ( $names as $name ) {
            $sanitized = sanitize_text_field( $name );

            if ( '' === $sanitized ) {
                continue;
            }

            $term = term_exists( $sanitized, $taxonomy );

            if ( ! $term ) {
                $term = wp_insert_term( $sanitized, $taxonomy );
            }

            if ( ! is_wp_error( $term ) ) {
                $term_ids[] = (int) $term['term_id'];
            }
        }

        if ( ! empty( $term_ids ) ) {
            wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );
        }
    }

    /**
     * Redirect helper for quick add actions.
     *
     * @param string $type    Type identifier.
     * @param string $status  Status string.
     * @param string $message Optional message.
     * @param array  $extra   Extra query args.
     */
    protected function redirect_quick_notice( $type, $status, $message = '', $extra = array() ) {
        $args = array(
            'page'                                => 'questoes',
            'questoes_quick_' . $type . '_status' => $status,
        );

        if ( $message ) {
            $args[ 'questoes_quick_' . $type . '_message' ] = rawurlencode( $message );
        }

        if ( ! empty( $extra['label'] ) ) {
            $args[ 'questoes_quick_' . $type . '_label' ] = rawurlencode( $extra['label'] );
        }

        wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * Import courses from array.
     *
     * @param array $items Items.
     *
     * @return array|WP_Error
     */
    protected function import_courses_from_array( $items ) {
        if ( ! is_array( $items ) ) {
            return new WP_Error( 'invalid', __( 'O pacote de cursos deve ser um array JSON.', 'questoes' ) );
        }

        $created = 0;
        $updated = 0;
        $errors  = array();

        foreach ( $items as $entry ) {
            if ( empty( $entry['title'] ) ) {
                $errors[] = __( 'Um dos cursos está sem título e foi ignorado.', 'questoes' );
                continue;
            }

            $title   = sanitize_text_field( $entry['title'] );
            $slug    = ! empty( $entry['slug'] ) ? sanitize_title( $entry['slug'] ) : sanitize_title( $title );
            $content = isset( $entry['content'] ) ? wp_kses_post( $entry['content'] ) : '';
            $excerpt = isset( $entry['excerpt'] ) ? sanitize_text_field( $entry['excerpt'] ) : '';

            $existing = get_page_by_path( $slug, OBJECT, $this->post_type );

            if ( ! $existing ) {
                $existing = get_posts(
                    array(
                        'post_type'      => $this->post_type,
                        'posts_per_page' => 1,
                        'title'          => $title,
                        'post_status'    => 'any',
                        'fields'         => 'ids',
                    )
                );

                if ( ! empty( $existing ) ) {
                    $existing = get_post( $existing[0] );
                }
            }

            $post_data = array(
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_content' => $content,
                'post_excerpt' => $excerpt,
                'post_type'    => $this->post_type,
                'post_status'  => 'publish',
            );

            if ( $existing ) {
                $post_data['ID'] = $existing->ID;
                $result_id       = wp_update_post( $post_data, true );
                if ( is_wp_error( $result_id ) ) {
                    $errors[] = $result_id->get_error_message();
                    continue;
                }
                $post_id = $result_id;
                $updated++;
            } else {
                $result_id = wp_insert_post( $post_data, true );
                if ( is_wp_error( $result_id ) ) {
                    $errors[] = $result_id->get_error_message();
                    continue;
                }
                $post_id = $result_id;
                $created++;
            }

            $this->persist_meta_from_entry( $post_id, $entry );
            $this->persist_taxonomies_from_entry( $post_id, $entry );
        }

        return array(
            'created' => $created,
            'updated' => $updated,
            'errors'  => $errors,
        );
    }

    /**
     * Persist meta from entry.
     */
    protected function persist_meta_from_entry( $post_id, $entry ) {
        $map = array(
            'questoes_course_highlight'     => array_key_exists( 'highlight', $entry ) ? wp_kses_post( $entry['highlight'] ) : '',
            'questoes_course_salary'        => array_key_exists( 'salary', $entry ) ? sanitize_text_field( $entry['salary'] ) : '',
            'questoes_course_opportunities' => array_key_exists( 'opportunities', $entry ) ? sanitize_text_field( $entry['opportunities'] ) : '',
            'questoes_course_badge'         => array_key_exists( 'badge', $entry ) ? sanitize_text_field( $entry['badge'] ) : '',
            'questoes_course_featured'      => ! empty( $entry['featured'] ) ? 'yes' : '',
            'questoes_course_cta'           => array_key_exists( 'cta', $entry ) ? sanitize_text_field( $entry['cta'] ) : '',
            'questoes_course_url'           => array_key_exists( 'url', $entry ) ? esc_url_raw( $entry['url'] ) : '',
            'questoes_course_questions'     => array_key_exists( 'questions', $entry ) ? absint( $entry['questions'] ) : 0,
            'questoes_course_comments'      => array_key_exists( 'comments', $entry ) ? absint( $entry['comments'] ) : 0,
            'questoes_course_priority'      => array_key_exists( 'priority', $entry ) ? absint( $entry['priority'] ) : 0,
            'questoes_course_icon'          => array_key_exists( 'icon', $entry ) ? esc_url_raw( $entry['icon'] ) : '',
        );

        foreach ( $map as $meta_key => $value ) {
            if ( '' === $value || null === $value ) {
                delete_post_meta( $post_id, $meta_key );
            } else {
                update_post_meta( $post_id, $meta_key, $value );
            }
        }
    }

    /**
     * Persist taxonomy terms from entry.
     */
    protected function persist_taxonomies_from_entry( $post_id, $entry ) {
        if ( ! empty( $entry['categories'] ) && is_array( $entry['categories'] ) ) {
            $category_ids = array();
            foreach ( $entry['categories'] as $category_name ) {
                $category_name = sanitize_text_field( $category_name );
                if ( '' === $category_name ) {
                    continue;
                }
                $term = term_exists( $category_name, $this->category_taxonomy );
                if ( ! $term ) {
                    $term = wp_insert_term( $category_name, $this->category_taxonomy );
                }
                if ( ! is_wp_error( $term ) ) {
                    $category_ids[] = (int) $term['term_id'];
                }
            }
            if ( ! empty( $category_ids ) ) {
                wp_set_object_terms( $post_id, $category_ids, $this->category_taxonomy );
            }
        }

        if ( ! empty( $entry['boards'] ) && is_array( $entry['boards'] ) ) {
            $board_ids = array();
            foreach ( $entry['boards'] as $board_name ) {
                $board_name = sanitize_text_field( $board_name );
                if ( '' === $board_name ) {
                    continue;
                }
                $term = term_exists( $board_name, $this->board_taxonomy );
                if ( ! $term ) {
                    $term = wp_insert_term( $board_name, $this->board_taxonomy );
                }
                if ( ! is_wp_error( $term ) ) {
                    $board_ids[] = (int) $term['term_id'];
                }
            }
            if ( ! empty( $board_ids ) ) {
                wp_set_object_terms( $post_id, $board_ids, $this->board_taxonomy );
            }
        }
    }

    /**
     * Retrieve catalog data for shortcode/widget.
     */
    public function get_catalog_data( $args = array() ) {
        $defaults = array(
            'featured_limit' => 6,
        );

        $args = wp_parse_args( $args, $defaults );

        $query = new WP_Query(
            array(
                'post_type'      => $this->post_type,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => array(
                    'meta_value_num' => 'DESC',
                    'date'           => 'DESC',
                ),
                'meta_key'       => 'questoes_course_priority',
            )
        );

        if ( ! $query->have_posts() ) {
            return array(
                'items'    => array(),
                'featured' => array(),
                'totals'   => array(
                    'courses'   => 0,
                    'questions' => 0,
                ),
            );
        }

        $items          = array();
        $featured_items = array();
        $questions_sum  = 0;

        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id = get_the_ID();

            $meta = $this->get_course_meta( $post_id );
            $categories = get_the_terms( $post_id, $this->category_taxonomy );
            $boards     = get_the_terms( $post_id, $this->board_taxonomy );

            $primary_category = ( ! empty( $categories ) && ! is_wp_error( $categories ) ) ? $categories[0] : null;
            $primary_board    = ( ! empty( $boards ) && ! is_wp_error( $boards ) ) ? $boards[0] : null;

            $course = array(
                'id'          => $post_id,
                'name'        => get_the_title(),
                'highlight'   => ! empty( $meta['highlight'] ) ? wpautop( $meta['highlight'] ) : get_the_excerpt(),
                'salary'      => $meta['salary'],
                'opportunities' => $meta['opportunities'],
                'badge'       => $meta['badge'],
                'featured'    => 'yes' === $meta['featured'],
                'cta'         => $meta['cta'],
                'link'        => ! empty( $meta['url'] ) ? $meta['url'] : get_permalink( $post_id ),
                'icon'        => $meta['icon'],
                'count'       => (int) $meta['questions'],
                'comments'    => (int) $meta['comments'],
                'priority'    => (int) $meta['priority'],
                'category'    => $primary_category ? $primary_category->name : '',
                'category_slug'=> $primary_category ? $primary_category->slug : '',
                'board'       => $primary_board ? $primary_board->name : '',
                'board_slug'  => $primary_board ? $primary_board->slug : '',
            );

            $questions_sum += $course['count'];

            $items[] = $course;

            if ( $course['featured'] ) {
                $featured_items[] = $course;
            }
        }

        wp_reset_postdata();

        if ( empty( $featured_items ) ) {
            $featured_items = array_slice( $items, 0, $args['featured_limit'] );
        } else {
            $featured_items = array_slice( $featured_items, 0, $args['featured_limit'] );
        }

        return array(
            'items'    => $items,
            'featured' => $featured_items,
            'totals'   => array(
                'courses'   => count( $items ),
                'questions' => $questions_sum,
            ),
        );
    }
}
