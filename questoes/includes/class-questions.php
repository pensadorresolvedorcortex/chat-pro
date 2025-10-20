<?php
/**
 * Question post type registration and management.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles the Questões question bank.
 */
class Questoes_Questions {

    /**
     * Post type slug.
     *
     * @var string
     */
    protected $post_type = 'questao';

    /**
     * Taxonomy slug for categories.
     *
     * @var string
     */
    protected $category_taxonomy = 'questao_categoria';

    /**
     * Taxonomy slug for banca (exam board).
     *
     * @var string
     */
    protected $banca_taxonomy = 'questao_banca';

    /**
     * Taxonomy slug for subject/topics.
     *
     * @var string
     */
    protected $subject_taxonomy = 'questao_assunto';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post_' . $this->post_type, array( $this, 'save_question' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'register_admin_columns' ) );
        add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'render_admin_columns' ), 10, 2 );
        add_filter( 'manage_edit-' . $this->post_type . '_sortable_columns', array( $this, 'register_sortable_columns' ) );
        add_action( 'pre_get_posts', array( $this, 'handle_admin_sorting' ) );
        add_action( 'admin_init', array( $this, 'register_category_meta_fields' ) );

        if ( is_admin() ) {
            add_action( 'admin_post_questoes_import_questions', array( $this, 'handle_import_request' ) );
            add_action( 'admin_notices', array( $this, 'maybe_render_import_notice' ) );
        }
    }

    public function register_category_meta_fields() {
        add_action( $this->category_taxonomy . '_add_form_fields', array( $this, 'render_category_add_fields' ) );
        add_action( $this->category_taxonomy . '_edit_form_fields', array( $this, 'render_category_edit_fields' ), 10, 2 );
        add_action( 'created_' . $this->category_taxonomy, array( $this, 'save_category_fields' ) );
        add_action( 'edited_' . $this->category_taxonomy, array( $this, 'save_category_fields' ) );
    }

    /**
     * Get registered post type slug.
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
     * Get banca taxonomy slug.
     *
     * @return string
     */
    public function get_banca_taxonomy() {
        return $this->banca_taxonomy;
    }

    /**
     * Get subject taxonomy slug.
     *
     * @return string
     */
    public function get_subject_taxonomy() {
        return $this->subject_taxonomy;
    }

    /**
     * Handle import submission.
     */
    public function handle_import_request() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para importar questões.', 'questoes' ) );
        }

        check_admin_referer( 'questoes_import_questions', 'questoes_import_nonce' );

        $redirect_base = admin_url( 'admin.php' );
        $mode     = isset( $_POST['questoes_import_mode'] ) ? sanitize_key( wp_unslash( $_POST['questoes_import_mode'] ) ) : '';
        $payload  = '';

        if ( 'sample' === $mode ) {
            $sample_file = QUESTOES_PLUGIN_DIR . 'sample-data/questions.json';

            if ( ! file_exists( $sample_file ) ) {
                $this->redirect_with_error( $redirect_base, __( 'Não foi possível ler o arquivo de exemplo.', 'questoes' ) );
            }

            $payload = file_get_contents( $sample_file );
        } elseif ( 'upload' === $mode ) {
            if ( empty( $_FILES['questoes_import_file']['tmp_name'] ) ) {
                $this->redirect_with_error( $redirect_base, __( 'O arquivo JSON enviado está vazio.', 'questoes' ) );
            }

            $payload = file_get_contents( $_FILES['questoes_import_file']['tmp_name'] );
        } elseif ( 'paste' === $mode ) {
            $payload = isset( $_POST['questoes_import_json'] ) ? wp_unslash( $_POST['questoes_import_json'] ) : '';
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

        $result = $this->import_questions_from_array( $decoded );

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_error( $redirect_base, $result->get_error_message() );
        }

        $success_args = array(
            'page'                         => 'questoes',
            'questoes_import_status'       => 'success',
            'questoes_import_created'      => $result['created'],
            'questoes_import_updated'      => $result['updated'],
            'questoes_import_skipped'      => $result['skipped'],
        );

        if ( ! empty( $result['errors'] ) ) {
            $success_args['questoes_import_notice'] = rawurlencode( $result['errors'][0] );
        }

        wp_safe_redirect( add_query_arg( $success_args, $redirect_base ) );
        exit;
    }

    /**
     * Redirect back to the settings page with an error message.
     *
     * @param string $url     Base URL.
     * @param string $message Message to display.
     */
    protected function redirect_with_error( $url, $message ) {
        $args = array(
            'page'                       => 'questoes',
            'questoes_import_status'     => 'error',
            'questoes_import_message'    => rawurlencode( $message ),
        );

        wp_safe_redirect( add_query_arg( $args, $url ) );
        exit;
    }

    /**
     * Render import notices when available.
     */
    public function maybe_render_import_notice() {
        if ( ! isset( $_GET['page'] ) || 'questoes' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        if ( empty( $_GET['questoes_import_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $status  = sanitize_key( wp_unslash( $_GET['questoes_import_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $message = '';
        $class   = 'notice notice-success';

        if ( 'success' === $status ) {
            $created = isset( $_GET['questoes_import_created'] ) ? absint( $_GET['questoes_import_created'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $updated = isset( $_GET['questoes_import_updated'] ) ? absint( $_GET['questoes_import_updated'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $skipped = isset( $_GET['questoes_import_skipped'] ) ? absint( $_GET['questoes_import_skipped'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

            $message = sprintf(
                /* translators: 1: created count, 2: updated count, 3: skipped count */
                esc_html__( 'Importação concluída: %1$s questões novas, %2$s atualizadas e %3$s ignoradas.', 'questoes' ),
                number_format_i18n( $created ),
                number_format_i18n( $updated ),
                number_format_i18n( $skipped )
            );

            if ( ! empty( $_GET['questoes_import_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $notice = rawurldecode( wp_unslash( $_GET['questoes_import_notice'] ) );
                $message .= ' ' . esc_html( sanitize_text_field( $notice ) );
            }
        } else {
            $class   = 'notice notice-error';
            $raw_msg = isset( $_GET['questoes_import_message'] ) ? rawurldecode( wp_unslash( $_GET['questoes_import_message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $message = sprintf( esc_html__( 'Falha na importação: %s', 'questoes' ), esc_html( sanitize_text_field( $raw_msg ) ) );
        }

        if ( empty( $message ) ) {
            return;
        }

        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
    }

    /**
     * Import questions from decoded array.
     *
     * @param array $data Decoded data.
     *
     * @return array|WP_Error
     */
    public function import_questions_from_array( $data ) {
        if ( isset( $data['questions'] ) && is_array( $data['questions'] ) ) {
            $questions = $data['questions'];
        } elseif ( is_array( $data ) ) {
            $questions = $data;
        } else {
            return new WP_Error( 'questoes_import_invalid', __( 'Nenhuma questão encontrada no arquivo.', 'questoes' ) );
        }

        if ( empty( $questions ) ) {
            return new WP_Error( 'questoes_import_empty', __( 'Nenhuma questão encontrada no arquivo.', 'questoes' ) );
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors  = array();

        foreach ( $questions as $question ) {
            $result = $this->import_single_question( $question );

            if ( is_wp_error( $result ) ) {
                $skipped++;

                if ( empty( $errors ) ) {
                    $errors[] = $result->get_error_message();
                }

                continue;
            }

            if ( 'updated' === $result['status'] ) {
                $updated++;
            } else {
                $created++;
            }
        }

        return array(
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors'  => $errors,
        );
    }

    /**
     * Import a single question item.
     *
     * @param array $question Question payload.
     *
     * @return array|WP_Error
     */
    protected function import_single_question( $question ) {
        if ( empty( $question['title'] ) || empty( $question['content'] ) ) {
            return new WP_Error( 'questoes_import_missing', __( 'Título e enunciado são obrigatórios em cada questão.', 'questoes' ) );
        }

        $title   = sanitize_text_field( $question['title'] );
        $content = isset( $question['content'] ) ? wp_kses_post( $question['content'] ) : '';

        $slug = '';

        if ( ! empty( $question['slug'] ) ) {
            $slug = sanitize_title( $question['slug'] );
        }

        if ( empty( $slug ) ) {
            $slug = sanitize_title( $title );
        }

        $existing = get_page_by_path( $slug, OBJECT, $this->post_type );

        if ( ! $existing ) {
            $existing = get_page_by_title( $title, OBJECT, $this->post_type );
        }

        $postarr = array(
            'post_type'      => $this->post_type,
            'post_title'     => $title,
            'post_content'   => $content,
            'post_status'    => 'publish',
            'post_author'    => get_current_user_id(),
            'post_excerpt'   => isset( $question['excerpt'] ) ? wp_strip_all_tags( $question['excerpt'] ) : '',
            'post_name'      => $slug,
            'comment_status' => ( isset( $question['comment_status'] ) && 'closed' === $question['comment_status'] ) ? 'closed' : 'open',
        );

        if ( $existing ) {
            $postarr['ID'] = $existing->ID;
            $post_id       = wp_update_post( wp_slash( $postarr ), true );
            $status        = 'updated';
        } else {
            $post_id = wp_insert_post( wp_slash( $postarr ), true );
            $status  = 'created';
        }

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        $this->maybe_set_terms( $post_id, $this->category_taxonomy, isset( $question['categories'] ) ? $question['categories'] : array() );
        $this->maybe_set_terms( $post_id, $this->banca_taxonomy, isset( $question['bancas'] ) ? $question['bancas'] : array() );
        $this->maybe_set_terms( $post_id, $this->subject_taxonomy, isset( $question['subjects'] ) ? $question['subjects'] : array() );

        $difficulty = isset( $question['difficulty'] ) ? $this->sanitize_difficulty( sanitize_key( $question['difficulty'] ) ) : '';
        $question_type = isset( $question['question_type'] ) ? $this->sanitize_question_type( sanitize_key( $question['question_type'] ) ) : '';
        $year = isset( $question['year'] ) ? $this->sanitize_year( $question['year'] ) : 0;
        $answers = isset( $question['answers'] ) ? $this->sanitize_answers( $question['answers'] ) : array();
        $reference = isset( $question['reference'] ) ? sanitize_text_field( $question['reference'] ) : '';
        $source = isset( $question['source'] ) ? sanitize_text_field( $question['source'] ) : '';
        $video_url = isset( $question['video_url'] ) ? esc_url_raw( $question['video_url'] ) : '';
        $source_url = isset( $question['source_url'] ) ? esc_url_raw( $question['source_url'] ) : '';
        $estimated_time = isset( $question['estimated_time'] ) ? absint( $question['estimated_time'] ) : 0;
        $explanation = isset( $question['explanation'] ) ? wp_kses_post( $question['explanation'] ) : '';

        $this->update_question_meta(
            $post_id,
            array(
                'answers'        => $answers,
                'difficulty'     => $difficulty,
                'question_type'  => $question_type,
                'year'           => $year,
                'reference'      => $reference,
                'source'         => $source,
                'video_url'      => $video_url,
                'source_url'     => $source_url,
                'estimated_time' => $estimated_time,
                'explanation'    => $explanation,
            )
        );

        return array(
            'id'     => $post_id,
            'status' => $status,
        );
    }

    /**
     * Assign taxonomy terms when provided.
     *
     * @param int    $post_id Post ID.
     * @param string $taxonomy Taxonomy.
     * @param mixed  $terms    Terms payload.
     */
    protected function maybe_set_terms( $post_id, $taxonomy, $terms ) {
        $terms = $this->normalize_term_list( $terms );

        if ( empty( $terms ) ) {
            return;
        }

        wp_set_object_terms( $post_id, $terms, $taxonomy );
    }

    /**
     * Normalize list of terms.
     *
     * @param mixed $terms Raw terms.
     *
     * @return array
     */
    protected function normalize_term_list( $terms ) {
        if ( empty( $terms ) ) {
            return array();
        }

        $list = array();

        foreach ( (array) $terms as $term ) {
            if ( is_array( $term ) && isset( $term['name'] ) ) {
                $term = $term['name'];
            }

            $term = sanitize_text_field( $term );

            if ( '' !== $term ) {
                $list[ $term ] = $term;
            }
        }

        return array_values( $list );
    }

    /**
     * Register custom post type.
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Questões', 'Post Type General Name', 'questoes' ),
            'singular_name'         => _x( 'Questão', 'Post Type Singular Name', 'questoes' ),
            'menu_name'             => __( 'Questões', 'questoes' ),
            'name_admin_bar'        => __( 'Questão', 'questoes' ),
            'add_new'               => __( 'Adicionar nova', 'questoes' ),
            'add_new_item'          => __( 'Adicionar nova questão', 'questoes' ),
            'edit_item'             => __( 'Editar questão', 'questoes' ),
            'new_item'              => __( 'Nova questão', 'questoes' ),
            'view_item'             => __( 'Ver questão', 'questoes' ),
            'view_items'            => __( 'Ver questões', 'questoes' ),
            'search_items'          => __( 'Buscar questões', 'questoes' ),
            'not_found'             => __( 'Nenhuma questão encontrada.', 'questoes' ),
            'not_found_in_trash'    => __( 'Nenhuma questão encontrada na lixeira.', 'questoes' ),
            'all_items'             => __( 'Todas as questões', 'questoes' ),
            'archives'              => __( 'Arquivos de questões', 'questoes' ),
            'attributes'            => __( 'Atributos da questão', 'questoes' ),
            'insert_into_item'      => __( 'Inserir na questão', 'questoes' ),
            'uploaded_to_this_item' => __( 'Enviado para esta questão', 'questoes' ),
            'filter_items_list'     => __( 'Filtrar lista de questões', 'questoes' ),
            'items_list_navigation' => __( 'Navegação da lista de questões', 'questoes' ),
            'items_list'            => __( 'Lista de questões', 'questoes' ),
        );

        $args = array(
            'label'               => __( 'Questão', 'questoes' ),
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => true,
            'menu_icon'           => 'dashicons-welcome-learn-more',
            'rewrite'             => array( 'slug' => 'questoes' ),
            'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments' ),
            'show_in_rest'        => true,
            'rest_base'           => 'questoes',
            'menu_position'       => 25,
            'description'         => __( 'Banco de questões com alternativas, explicações e referências.', 'questoes' ),
            'capability_type'     => 'post',
            'taxonomies'          => array( $this->category_taxonomy, $this->banca_taxonomy, $this->subject_taxonomy ),
        );

        register_post_type( $this->post_type, $args );

        register_post_meta(
            $this->post_type,
            'questoes_answers',
            array(
                'type'         => 'array',
                'single'       => true,
                'show_in_rest' => array(
                    'schema' => array(
                        'type'       => 'array',
                        'items'      => array(
                            'type'       => 'object',
                            'properties' => array(
                                'id'         => array( 'type' => 'string' ),
                                'text'       => array( 'type' => 'string' ),
                                'is_correct' => array( 'type' => 'boolean' ),
                                'feedback'   => array( 'type' => 'string' ),
                            ),
                        ),
                    ),
                ),
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_post_meta(
            $this->post_type,
            'questoes_difficulty',
            array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_post_meta(
            $this->post_type,
            'questoes_reference',
            array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_post_meta(
            $this->post_type,
            'questoes_source',
            array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_post_meta(
            $this->post_type,
            'questoes_estimated_time',
            array(
                'type'         => 'integer',
                'single'       => true,
                'show_in_rest' => true,
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_post_meta(
            $this->post_type,
            'questoes_explanation',
            array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_post_meta(
            $this->post_type,
            'questoes_question_type',
            array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_post_meta(
            $this->post_type,
            'questoes_year',
            array(
                'type'         => 'integer',
                'single'       => true,
                'show_in_rest' => true,
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_post_meta(
            $this->post_type,
            'questoes_video_url',
            array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_post_meta(
            $this->post_type,
            'questoes_source_url',
            array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );
    }

    /**
     * Register taxonomies for categories and banca.
     */
    public function register_taxonomies() {
        $category_labels = array(
            'name'              => _x( 'Categorias', 'taxonomy general name', 'questoes' ),
            'singular_name'     => _x( 'Categoria', 'taxonomy singular name', 'questoes' ),
            'search_items'      => __( 'Buscar categorias', 'questoes' ),
            'all_items'         => __( 'Todas as categorias', 'questoes' ),
            'parent_item'       => __( 'Categoria pai', 'questoes' ),
            'parent_item_colon' => __( 'Categoria pai:', 'questoes' ),
            'edit_item'         => __( 'Editar categoria', 'questoes' ),
            'update_item'       => __( 'Atualizar categoria', 'questoes' ),
            'add_new_item'      => __( 'Adicionar nova categoria', 'questoes' ),
            'new_item_name'     => __( 'Nome da nova categoria', 'questoes' ),
            'menu_name'         => __( 'Categorias', 'questoes' ),
        );

        register_taxonomy(
            $this->category_taxonomy,
            $this->post_type,
            array(
                'hierarchical'      => true,
                'labels'            => $category_labels,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite'           => array( 'slug' => 'categoria-questao' ),
            )
        );

        $banca_labels = array(
            'name'                       => _x( 'Bancas', 'taxonomy general name', 'questoes' ),
            'singular_name'              => _x( 'Banca', 'taxonomy singular name', 'questoes' ),
            'search_items'               => __( 'Buscar bancas', 'questoes' ),
            'popular_items'              => __( 'Bancas populares', 'questoes' ),
            'all_items'                  => __( 'Todas as bancas', 'questoes' ),
            'edit_item'                  => __( 'Editar banca', 'questoes' ),
            'update_item'                => __( 'Atualizar banca', 'questoes' ),
            'add_new_item'               => __( 'Adicionar nova banca', 'questoes' ),
            'new_item_name'              => __( 'Nome da nova banca', 'questoes' ),
            'separate_items_with_commas' => __( 'Separe as bancas com vírgulas', 'questoes' ),
            'add_or_remove_items'        => __( 'Adicionar ou remover bancas', 'questoes' ),
            'choose_from_most_used'      => __( 'Escolher entre as mais usadas', 'questoes' ),
            'menu_name'                  => __( 'Bancas', 'questoes' ),
        );

        register_taxonomy(
            $this->banca_taxonomy,
            $this->post_type,
            array(
                'hierarchical'      => false,
                'labels'            => $banca_labels,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite'           => array( 'slug' => 'banca' ),
            )
        );

        $subject_labels = array(
            'name'              => _x( 'Assuntos', 'taxonomy general name', 'questoes' ),
            'singular_name'     => _x( 'Assunto', 'taxonomy singular name', 'questoes' ),
            'search_items'      => __( 'Buscar assuntos', 'questoes' ),
            'all_items'         => __( 'Todos os assuntos', 'questoes' ),
            'edit_item'         => __( 'Editar assunto', 'questoes' ),
            'update_item'       => __( 'Atualizar assunto', 'questoes' ),
            'add_new_item'      => __( 'Adicionar novo assunto', 'questoes' ),
            'new_item_name'     => __( 'Nome do novo assunto', 'questoes' ),
            'menu_name'         => __( 'Assuntos', 'questoes' ),
        );

        register_taxonomy(
            $this->subject_taxonomy,
            $this->post_type,
            array(
                'hierarchical'      => true,
                'labels'            => $subject_labels,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite'           => array( 'slug' => 'assunto-questao' ),
            )
        );
    }

    public function get_default_course_meta() {
        return array(
            'featured'      => 'no',
            'salary'        => '',
            'opportunities' => '',
            'region'        => 'nacional',
            'badge'         => '',
            'highlight'     => '',
            'cta'           => '',
            'link'          => '',
            'icon'          => '',
            'priority'      => 0,
        );
    }

    public function get_category_course_meta( $term_id ) {
        $term_id  = absint( $term_id );
        $defaults = $this->get_default_course_meta();

        if ( ! $term_id ) {
            return $defaults;
        }

        $meta = array(
            'featured'      => get_term_meta( $term_id, 'questoes_course_featured', true ),
            'salary'        => get_term_meta( $term_id, 'questoes_course_salary', true ),
            'opportunities' => get_term_meta( $term_id, 'questoes_course_opportunities', true ),
            'region'        => get_term_meta( $term_id, 'questoes_course_region', true ),
            'badge'         => get_term_meta( $term_id, 'questoes_course_badge', true ),
            'highlight'     => get_term_meta( $term_id, 'questoes_course_highlight', true ),
            'cta'           => get_term_meta( $term_id, 'questoes_course_cta', true ),
            'link'          => get_term_meta( $term_id, 'questoes_course_link', true ),
            'icon'          => get_term_meta( $term_id, 'questoes_course_icon', true ),
            'priority'      => get_term_meta( $term_id, 'questoes_course_priority', true ),
        );

        foreach ( $meta as $key => $value ) {
            if ( is_array( $value ) ) {
                $meta[ $key ] = isset( $value[0] ) ? $value[0] : '';
            }
        }

        $meta['priority'] = isset( $meta['priority'] ) ? (int) $meta['priority'] : 0;

        return array_merge( $defaults, $meta );
    }

    public function render_category_add_fields() {
        $meta    = $this->get_default_course_meta();
        $regions = questoes_get_course_regions();

        wp_nonce_field( 'questoes_save_category_meta', 'questoes_category_meta_nonce' );
        ?>
        <div class="form-field">
            <label for="questoes-course-featured">
                <?php esc_html_e( 'Destacar na seção “Cursos em alta”', 'questoes' ); ?>
            </label>
            <input type="checkbox" id="questoes-course-featured" name="questoes_course_featured" value="1" />
            <p class="description"><?php esc_html_e( 'Marque para exibir este curso entre os destaques.', 'questoes' ); ?></p>
        </div>

        <div class="form-field">
            <label for="questoes-course-salary"><?php esc_html_e( 'Faixa salarial ou benefício principal', 'questoes' ); ?></label>
            <input type="text" id="questoes-course-salary" name="questoes_course_salary" value="<?php echo esc_attr( $meta['salary'] ); ?>" />
        </div>

        <div class="form-field">
            <label for="questoes-course-opportunities"><?php esc_html_e( 'Resumo de vagas ou oportunidades', 'questoes' ); ?></label>
            <input type="text" id="questoes-course-opportunities" name="questoes_course_opportunities" value="<?php echo esc_attr( $meta['opportunities'] ); ?>" />
        </div>

        <div class="form-field">
            <label for="questoes-course-region"><?php esc_html_e( 'Região predominante', 'questoes' ); ?></label>
            <select id="questoes-course-region" name="questoes_course_region">
                <?php foreach ( $regions as $slug => $label ) : ?>
                    <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $meta['region'], $slug ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="questoes-course-badge"><?php esc_html_e( 'Rótulo/Borda superior', 'questoes' ); ?></label>
            <input type="text" id="questoes-course-badge" name="questoes_course_badge" value="<?php echo esc_attr( $meta['badge'] ); ?>" />
        </div>

        <div class="form-field">
            <label for="questoes-course-highlight"><?php esc_html_e( 'Descrição em destaque', 'questoes' ); ?></label>
            <textarea id="questoes-course-highlight" name="questoes_course_highlight" rows="3"></textarea>
            <p class="description"><?php esc_html_e( 'Texto curto sobre o diferencial do curso. Aceita formatação simples.', 'questoes' ); ?></p>
        </div>

        <div class="form-field">
            <label for="questoes-course-cta"><?php esc_html_e( 'Texto do botão', 'questoes' ); ?></label>
            <input type="text" id="questoes-course-cta" name="questoes_course_cta" value="<?php echo esc_attr( $meta['cta'] ); ?>" placeholder="<?php esc_attr_e( 'Ver cursos disponíveis', 'questoes' ); ?>" />
        </div>

        <div class="form-field">
            <label for="questoes-course-link"><?php esc_html_e( 'Link do botão', 'questoes' ); ?></label>
            <input type="url" id="questoes-course-link" name="questoes_course_link" placeholder="https://" />
        </div>

        <div class="form-field">
            <label for="questoes-course-icon"><?php esc_html_e( 'Ícone ou brasão (URL da imagem)', 'questoes' ); ?></label>
            <input type="url" id="questoes-course-icon" name="questoes_course_icon" placeholder="https://" />
        </div>
        <div class="form-field">
            <label for="questoes-course-priority"><?php esc_html_e( 'Ordem nos destaques', 'questoes' ); ?></label>
            <input type="number" id="questoes-course-priority" name="questoes_course_priority" value="<?php echo esc_attr( $meta['priority'] ); ?>" min="0" step="1" />
            <p class="description"><?php esc_html_e( 'Use 1 para aparecer primeiro na seção “Cursos em alta”. Deixe 0 para ordenar automaticamente.', 'questoes' ); ?></p>
        </div>
        <?php
    }

    public function render_category_edit_fields( $term ) {
        $meta    = $this->get_category_course_meta( $term->term_id );
        $regions = questoes_get_course_regions();

        wp_nonce_field( 'questoes_save_category_meta', 'questoes_category_meta_nonce' );
        ?>
        <tr class="form-field">
            <th scope="row"><label for="questoes-course-featured"><?php esc_html_e( 'Destacar na seção “Cursos em alta”', 'questoes' ); ?></label></th>
            <td>
                <label for="questoes-course-featured">
                    <input type="checkbox" id="questoes-course-featured" name="questoes_course_featured" value="1" <?php checked( $meta['featured'], 'yes' ); ?> />
                    <?php esc_html_e( 'Marcar como destaque', 'questoes' ); ?>
                </label>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="questoes-course-salary"><?php esc_html_e( 'Faixa salarial ou benefício', 'questoes' ); ?></label></th>
            <td>
                <input type="text" id="questoes-course-salary" name="questoes_course_salary" value="<?php echo esc_attr( $meta['salary'] ); ?>" class="regular-text" />
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="questoes-course-opportunities"><?php esc_html_e( 'Resumo de vagas ou oportunidades', 'questoes' ); ?></label></th>
            <td>
                <input type="text" id="questoes-course-opportunities" name="questoes_course_opportunities" value="<?php echo esc_attr( $meta['opportunities'] ); ?>" class="regular-text" />
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="questoes-course-region"><?php esc_html_e( 'Região predominante', 'questoes' ); ?></label></th>
            <td>
                <select id="questoes-course-region" name="questoes_course_region">
                    <?php foreach ( $regions as $slug => $label ) : ?>
                        <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $meta['region'], $slug ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="questoes-course-badge"><?php esc_html_e( 'Rótulo/Borda superior', 'questoes' ); ?></label></th>
            <td>
                <input type="text" id="questoes-course-badge" name="questoes_course_badge" value="<?php echo esc_attr( $meta['badge'] ); ?>" class="regular-text" />
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="questoes-course-highlight"><?php esc_html_e( 'Descrição em destaque', 'questoes' ); ?></label></th>
            <td>
                <textarea id="questoes-course-highlight" name="questoes_course_highlight" rows="4" class="large-text"><?php echo esc_textarea( $meta['highlight'] ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Texto curto sobre o diferencial do curso. Aceita formatação simples.', 'questoes' ); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="questoes-course-cta"><?php esc_html_e( 'Texto do botão', 'questoes' ); ?></label></th>
            <td>
                <input type="text" id="questoes-course-cta" name="questoes_course_cta" value="<?php echo esc_attr( $meta['cta'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Ver cursos disponíveis', 'questoes' ); ?>" />
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="questoes-course-link"><?php esc_html_e( 'Link do botão', 'questoes' ); ?></label></th>
            <td>
                <input type="url" id="questoes-course-link" name="questoes_course_link" value="<?php echo esc_attr( $meta['link'] ); ?>" class="regular-text" placeholder="https://" />
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="questoes-course-icon"><?php esc_html_e( 'Ícone ou brasão (URL da imagem)', 'questoes' ); ?></label></th>
            <td>
                <input type="url" id="questoes-course-icon" name="questoes_course_icon" value="<?php echo esc_attr( $meta['icon'] ); ?>" class="regular-text" placeholder="https://" />
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="questoes-course-priority"><?php esc_html_e( 'Ordem nos destaques', 'questoes' ); ?></label></th>
            <td>
                <input type="number" id="questoes-course-priority" name="questoes_course_priority" value="<?php echo esc_attr( $meta['priority'] ); ?>" class="small-text" min="0" step="1" />
                <p class="description"><?php esc_html_e( 'Defina a posição manual dos cursos em alta (1 aparece primeiro).', 'questoes' ); ?></p>
            </td>
        </tr>
        <?php
    }

    public function save_category_fields( $term_id ) {
        if ( ! isset( $_POST['questoes_category_meta_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['questoes_category_meta_nonce'] ), 'questoes_save_category_meta' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_categories' ) ) {
            return;
        }

        $regions = questoes_get_course_regions();

        $featured = isset( $_POST['questoes_course_featured'] ) ? 'yes' : 'no';
        $salary   = isset( $_POST['questoes_course_salary'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_course_salary'] ) ) : '';
        $opps     = isset( $_POST['questoes_course_opportunities'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_course_opportunities'] ) ) : '';
        $region   = isset( $_POST['questoes_course_region'] ) ? sanitize_key( wp_unslash( $_POST['questoes_course_region'] ) ) : 'nacional';
        $badge    = isset( $_POST['questoes_course_badge'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_course_badge'] ) ) : '';
        $highlight_raw = isset( $_POST['questoes_course_highlight'] ) ? wp_unslash( $_POST['questoes_course_highlight'] ) : '';
        $highlight = wp_kses_post( $highlight_raw );
        $cta      = isset( $_POST['questoes_course_cta'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_course_cta'] ) ) : '';
        $link     = isset( $_POST['questoes_course_link'] ) ? esc_url_raw( wp_unslash( $_POST['questoes_course_link'] ) ) : '';
        $icon     = isset( $_POST['questoes_course_icon'] ) ? esc_url_raw( wp_unslash( $_POST['questoes_course_icon'] ) ) : '';
        $priority = isset( $_POST['questoes_course_priority'] ) ? absint( wp_unslash( $_POST['questoes_course_priority'] ) ) : 0;

        if ( empty( $region ) || ! isset( $regions[ $region ] ) ) {
            $region = 'nacional';
        }

        update_term_meta( $term_id, 'questoes_course_featured', $featured );
        update_term_meta( $term_id, 'questoes_course_salary', $salary );
        update_term_meta( $term_id, 'questoes_course_opportunities', $opps );
        update_term_meta( $term_id, 'questoes_course_region', $region );
        update_term_meta( $term_id, 'questoes_course_badge', $badge );
        update_term_meta( $term_id, 'questoes_course_highlight', $highlight );
        update_term_meta( $term_id, 'questoes_course_cta', $cta );
        update_term_meta( $term_id, 'questoes_course_link', $link );
        update_term_meta( $term_id, 'questoes_course_icon', $icon );
        update_term_meta( $term_id, 'questoes_course_priority', $priority );
    }

    /**
     * Register meta boxes.
     */
    public function register_meta_boxes() {
        add_meta_box(
            'questoes-question-details',
            __( 'Detalhes da questão', 'questoes' ),
            array( $this, 'render_question_meta_box' ),
            $this->post_type,
            'normal',
            'default'
        );
    }

    /**
     * Render question meta box.
     *
     * @param WP_Post $post Current post.
     */
    public function render_question_meta_box( $post ) {
        wp_nonce_field( 'questoes_save_question', 'questoes_question_nonce' );

        $answers        = get_post_meta( $post->ID, 'questoes_answers', true );
        $difficulty     = get_post_meta( $post->ID, 'questoes_difficulty', true );
        $reference      = get_post_meta( $post->ID, 'questoes_reference', true );
        $source         = get_post_meta( $post->ID, 'questoes_source', true );
        $estimated_time = get_post_meta( $post->ID, 'questoes_estimated_time', true );
        $explanation    = get_post_meta( $post->ID, 'questoes_explanation', true );
        $question_type  = get_post_meta( $post->ID, 'questoes_question_type', true );
        $year           = get_post_meta( $post->ID, 'questoes_year', true );
        $video_url      = get_post_meta( $post->ID, 'questoes_video_url', true );
        $source_url     = get_post_meta( $post->ID, 'questoes_source_url', true );

        if ( ! is_array( $answers ) ) {
            $answers = array();
        }

        if ( empty( $answers ) ) {
            $answers = array(
                array(
                    'id'         => uniqid( 'alt_' ),
                    'text'       => '',
                    'is_correct' => true,
                    'feedback'   => '',
                ),
                array(
                    'id'         => uniqid( 'alt_' ),
                    'text'       => '',
                    'is_correct' => false,
                    'feedback'   => '',
                ),
            );
        }
        ?>
        <div class="questoes-meta" id="questoes-answer-manager" data-answers="<?php echo esc_attr( wp_json_encode( $answers ) ); ?>">
            <p class="description"><?php esc_html_e( 'Cadastre alternativas, marque a correta, adicione feedback e mantenha a consistência do banco.', 'questoes' ); ?></p>

            <div class="questoes-meta-grid">
                <div class="questoes-meta-field">
                    <label for="questoes-difficulty" class="questoes-meta-label"><?php esc_html_e( 'Dificuldade', 'questoes' ); ?></label>
                    <select id="questoes-difficulty" name="questoes_difficulty">
                        <option value="" <?php selected( $difficulty, '' ); ?>><?php esc_html_e( 'Selecione', 'questoes' ); ?></option>
                        <option value="easy" <?php selected( $difficulty, 'easy' ); ?>><?php esc_html_e( 'Fácil', 'questoes' ); ?></option>
                        <option value="medium" <?php selected( $difficulty, 'medium' ); ?>><?php esc_html_e( 'Média', 'questoes' ); ?></option>
                        <option value="hard" <?php selected( $difficulty, 'hard' ); ?>><?php esc_html_e( 'Difícil', 'questoes' ); ?></option>
                    </select>
                </div>
                <div class="questoes-meta-field">
                    <label for="questoes-question-type" class="questoes-meta-label"><?php esc_html_e( 'Formato da questão', 'questoes' ); ?></label>
                    <select id="questoes-question-type" name="questoes_question_type">
                        <option value="" <?php selected( $question_type, '' ); ?>><?php esc_html_e( 'Selecione', 'questoes' ); ?></option>
                        <?php foreach ( questoes_get_question_types() as $type_key => $type_label ) : ?>
                            <option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $question_type, $type_key ); ?>><?php echo esc_html( $type_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="questoes-meta-field">
                    <label for="questoes-year" class="questoes-meta-label"><?php esc_html_e( 'Ano da aplicação', 'questoes' ); ?></label>
                    <input type="number" id="questoes-year" name="questoes_year" value="<?php echo esc_attr( $year ); ?>" min="1900" max="2100" />
                </div>
            </div>

            <div class="questoes-meta-grid">
                <div class="questoes-meta-field">
                    <label for="questoes-reference" class="questoes-meta-label"><?php esc_html_e( 'Referência / Fonte bibliográfica', 'questoes' ); ?></label>
                    <input type="text" id="questoes-reference" name="questoes_reference" value="<?php echo esc_attr( $reference ); ?>" />
                </div>
                <div class="questoes-meta-field">
                    <label for="questoes-source" class="questoes-meta-label"><?php esc_html_e( 'Fonte da questão / Concurso', 'questoes' ); ?></label>
                    <input type="text" id="questoes-source" name="questoes_source" value="<?php echo esc_attr( $source ); ?>" />
                </div>
                <div class="questoes-meta-field">
                    <label for="questoes-source-url" class="questoes-meta-label"><?php esc_html_e( 'URL da prova/edital', 'questoes' ); ?></label>
                    <input type="url" id="questoes-source-url" name="questoes_source_url" value="<?php echo esc_attr( $source_url ); ?>" placeholder="https://" />
                </div>
                <div class="questoes-meta-field">
                    <label for="questoes-video-url" class="questoes-meta-label"><?php esc_html_e( 'Vídeo de comentário', 'questoes' ); ?></label>
                    <input type="url" id="questoes-video-url" name="questoes_video_url" value="<?php echo esc_attr( $video_url ); ?>" placeholder="https://" />
                </div>
                <div class="questoes-meta-field">
                    <label for="questoes-estimated-time" class="questoes-meta-label"><?php esc_html_e( 'Tempo estimado (minutos)', 'questoes' ); ?></label>
                    <input type="number" id="questoes-estimated-time" name="questoes_estimated_time" value="<?php echo esc_attr( $estimated_time ); ?>" min="0" step="1" />
                </div>
            </div>

            <div class="questoes-meta-field">
                <label for="questoes-explanation" class="questoes-meta-label"><?php esc_html_e( 'Comentário / Explicação da resposta', 'questoes' ); ?></label>
                <textarea id="questoes-explanation" name="questoes_explanation" rows="5"><?php echo esc_textarea( $explanation ); ?></textarea>
            </div>

            <fieldset class="questoes-answers">
                <legend class="questoes-meta-label"><?php esc_html_e( 'Alternativas', 'questoes' ); ?></legend>
                <div class="questoes-answer-list"></div>
                <button type="button" class="button questoes-add-answer"><?php esc_html_e( 'Adicionar alternativa', 'questoes' ); ?></button>
                <input type="hidden" name="questoes_answer_data" id="questoes-answer-data" value="" />
            </fieldset>
        </div>
        <?php
    }

    /**
     * Enqueue admin assets for question meta UI.
     *
     * @param string $hook Current screen hook.
     */
    public function enqueue_admin_assets( $hook ) {
        global $typenow;

        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        if ( $typenow !== $this->post_type ) {
            return;
        }

        wp_enqueue_style(
            'questoes-question-admin',
            QUESTOES_PLUGIN_URL . 'assets/admin/admin.css',
            array(),
            questoes_asset_version( 'assets/admin/admin.css' )
        );

        wp_enqueue_script(
            'questoes-question-admin',
            QUESTOES_PLUGIN_URL . 'assets/admin/questions.js',
            array( 'jquery' ),
            questoes_asset_version( 'assets/admin/questions.js' ),
            true
        );

        wp_localize_script(
            'questoes-question-admin',
            'questoesQuestionMeta',
            array(
                'messages' => array(
                    'correct'  => __( 'Resposta correta', 'questoes' ),
                    'feedback' => __( 'Feedback / comentário', 'questoes' ),
                    'remove'   => __( 'Remover alternativa', 'questoes' ),
                    'placeholder' => __( 'Descrição da alternativa', 'questoes' ),
                    'minimum'     => __( 'Mantenha ao menos duas alternativas.', 'questoes' ),
                ),
            )
        );
    }

    /**
     * Save question meta.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function save_question( $post_id, $post ) {
        if ( ! isset( $_POST['questoes_question_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['questoes_question_nonce'] ) ), 'questoes_save_question' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $raw_answers = isset( $_POST['questoes_answer_data'] ) ? wp_unslash( $_POST['questoes_answer_data'] ) : '[]';
        $answers     = $this->sanitize_answers( $raw_answers );

        $difficulty = isset( $_POST['questoes_difficulty'] ) ? sanitize_key( wp_unslash( $_POST['questoes_difficulty'] ) ) : '';
        $difficulty = $this->sanitize_difficulty( $difficulty );

        $question_type = isset( $_POST['questoes_question_type'] ) ? sanitize_key( wp_unslash( $_POST['questoes_question_type'] ) ) : '';
        $question_type = $this->sanitize_question_type( $question_type );

        $year = isset( $_POST['questoes_year'] ) ? absint( wp_unslash( $_POST['questoes_year'] ) ) : 0;
        $year = $this->sanitize_year( $year );

        $reference = isset( $_POST['questoes_reference'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_reference'] ) ) : '';
        $source    = isset( $_POST['questoes_source'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_source'] ) ) : '';
        $video_url = isset( $_POST['questoes_video_url'] ) ? esc_url_raw( wp_unslash( $_POST['questoes_video_url'] ) ) : '';
        $source_url = isset( $_POST['questoes_source_url'] ) ? esc_url_raw( wp_unslash( $_POST['questoes_source_url'] ) ) : '';

        $estimated_time = isset( $_POST['questoes_estimated_time'] ) ? absint( wp_unslash( $_POST['questoes_estimated_time'] ) ) : 0;
        $explanation    = isset( $_POST['questoes_explanation'] ) ? wp_kses_post( wp_unslash( $_POST['questoes_explanation'] ) ) : '';

        $this->update_question_meta(
            $post_id,
            array(
                'answers'        => $answers,
                'difficulty'     => $difficulty,
                'question_type'  => $question_type,
                'year'           => $year,
                'reference'      => $reference,
                'source'         => $source,
                'video_url'      => $video_url,
                'source_url'     => $source_url,
                'estimated_time' => $estimated_time,
                'explanation'    => $explanation,
            )
        );
    }

    /**
     * Sanitize difficulty value.
     *
     * @param string $value Raw value.
     *
     * @return string
     */
    public function sanitize_difficulty( $value ) {
        $allowed = array( 'easy', 'medium', 'hard' );

        if ( in_array( $value, $allowed, true ) ) {
            return $value;
        }

        return '';
    }

    /**
     * Sanitize question type value.
     *
     * @param string $value Raw value.
     *
     * @return string
     */
    public function sanitize_question_type( $value ) {
        $types = questoes_get_question_types();

        if ( isset( $types[ $value ] ) ) {
            return $value;
        }

        return '';
    }

    /**
     * Sanitize year value.
     *
     * @param int $value Raw value.
     *
     * @return int
     */
    public function sanitize_year( $value ) {
        $year = absint( $value );

        if ( $year < 1900 || $year > 2100 ) {
            return 0;
        }

        return $year;
    }

    /**
     * Sanitize answers payload.
     *
     * @param string|array $raw Raw answers (JSON string or array).
     *
     * @return array
     */
    public function sanitize_answers( $raw ) {
        if ( is_string( $raw ) ) {
            $decoded = json_decode( $raw, true );
        } else {
            $decoded = $raw;
        }

        if ( ! is_array( $decoded ) ) {
            return array();
        }

        $answers       = array();
        $has_correct   = false;
        $index         = 0;

        foreach ( $decoded as $answer ) {
            if ( empty( $answer['text'] ) ) {
                continue;
            }

            $index++;
            $id = isset( $answer['id'] ) ? sanitize_key( $answer['id'] ) : 'alt_' . $index;

            $item = array(
                'id'         => $id,
                'text'       => wp_kses_post( $answer['text'] ),
                'is_correct' => ! empty( $answer['is_correct'] ),
                'feedback'   => isset( $answer['feedback'] ) ? wp_kses_post( $answer['feedback'] ) : '',
            );

            if ( $item['is_correct'] ) {
                if ( $has_correct ) {
                    $item['is_correct'] = false;
                } else {
                    $has_correct = true;
                }
            }

            $answers[] = $item;
        }

        if ( ! $has_correct && ! empty( $answers ) ) {
            $answers[0]['is_correct'] = true;
        }

        return $answers;
    }

    /**
     * Update post meta with sanitized data.
     *
     * @param int   $post_id Post ID.
     * @param array $data    Data payload.
     */
    public function update_question_meta( $post_id, $data ) {
        if ( isset( $data['answers'] ) ) {
            if ( empty( $data['answers'] ) ) {
                delete_post_meta( $post_id, 'questoes_answers' );
            } else {
                update_post_meta( $post_id, 'questoes_answers', array_values( $data['answers'] ) );
            }
        }

        if ( isset( $data['difficulty'] ) ) {
            if ( $data['difficulty'] ) {
                update_post_meta( $post_id, 'questoes_difficulty', $data['difficulty'] );
            } else {
                delete_post_meta( $post_id, 'questoes_difficulty' );
            }
        }

        if ( isset( $data['question_type'] ) ) {
            if ( $data['question_type'] ) {
                update_post_meta( $post_id, 'questoes_question_type', $data['question_type'] );
            } else {
                delete_post_meta( $post_id, 'questoes_question_type' );
            }
        }

        if ( isset( $data['year'] ) ) {
            if ( $data['year'] ) {
                update_post_meta( $post_id, 'questoes_year', absint( $data['year'] ) );
            } else {
                delete_post_meta( $post_id, 'questoes_year' );
            }
        }

        if ( isset( $data['reference'] ) ) {
            if ( $data['reference'] ) {
                update_post_meta( $post_id, 'questoes_reference', $data['reference'] );
            } else {
                delete_post_meta( $post_id, 'questoes_reference' );
            }
        }

        if ( isset( $data['source'] ) ) {
            if ( $data['source'] ) {
                update_post_meta( $post_id, 'questoes_source', $data['source'] );
            } else {
                delete_post_meta( $post_id, 'questoes_source' );
            }
        }

        if ( isset( $data['video_url'] ) ) {
            if ( $data['video_url'] ) {
                update_post_meta( $post_id, 'questoes_video_url', esc_url_raw( $data['video_url'] ) );
            } else {
                delete_post_meta( $post_id, 'questoes_video_url' );
            }
        }

        if ( isset( $data['source_url'] ) ) {
            if ( $data['source_url'] ) {
                update_post_meta( $post_id, 'questoes_source_url', esc_url_raw( $data['source_url'] ) );
            } else {
                delete_post_meta( $post_id, 'questoes_source_url' );
            }
        }

        if ( isset( $data['estimated_time'] ) ) {
            if ( $data['estimated_time'] ) {
                update_post_meta( $post_id, 'questoes_estimated_time', absint( $data['estimated_time'] ) );
            } else {
                delete_post_meta( $post_id, 'questoes_estimated_time' );
            }
        }

        if ( isset( $data['explanation'] ) ) {
            if ( $data['explanation'] ) {
                update_post_meta( $post_id, 'questoes_explanation', wp_kses_post( $data['explanation'] ) );
            } else {
                delete_post_meta( $post_id, 'questoes_explanation' );
            }
        }
    }

    /**
     * Increment view counters for a set of questions.
     *
     * @param array|int $question_ids Question IDs.
     */
    public function increment_question_views( $question_ids ) {
        if ( empty( $question_ids ) ) {
            return;
        }

        $ids = array_unique( array_map( 'absint', (array) $question_ids ) );

        foreach ( $ids as $question_id ) {
            if ( ! $question_id ) {
                continue;
            }

            $current = (int) get_post_meta( $question_id, 'questoes_question_views', true );
            update_post_meta( $question_id, 'questoes_question_views', max( 0, $current ) + 1 );
        }
    }

    /**
     * Prepare answers array.
     *
     * @param int $post_id Post ID.
     *
     * @return array
     */
    protected function get_answers( $post_id ) {
        $answers = get_post_meta( $post_id, 'questoes_answers', true );

        if ( ! is_array( $answers ) ) {
            return array();
        }

        return array_map(
            function( $answer ) {
                return array(
                    'id'         => isset( $answer['id'] ) ? $answer['id'] : '',
                    'text'       => isset( $answer['text'] ) ? wp_kses_post( $answer['text'] ) : '',
                    'is_correct' => ! empty( $answer['is_correct'] ),
                    'feedback'   => isset( $answer['feedback'] ) ? wp_kses_post( $answer['feedback'] ) : '',
                );
            },
            $answers
        );
    }

    /**
     * Prepare question payload for APIs.
     *
     * @param WP_Post $post Post object.
     *
     * @return array
     */
    public function prepare_question_for_response( $post ) {
        $categories = wp_get_post_terms( $post->ID, $this->category_taxonomy, array( 'fields' => 'names' ) );
        $bancas     = wp_get_post_terms( $post->ID, $this->banca_taxonomy, array( 'fields' => 'names' ) );
        $subjects   = wp_get_post_terms( $post->ID, $this->subject_taxonomy, array( 'fields' => 'names' ) );

        $payload = array(
            'id'             => $post->ID,
            'title'          => get_the_title( $post ),
            'content'        => apply_filters( 'the_content', $post->post_content ),
            'excerpt'        => get_the_excerpt( $post ),
            'difficulty'     => get_post_meta( $post->ID, 'questoes_difficulty', true ),
            'reference'      => get_post_meta( $post->ID, 'questoes_reference', true ),
            'source'         => get_post_meta( $post->ID, 'questoes_source', true ),
            'source_url'     => get_post_meta( $post->ID, 'questoes_source_url', true ),
            'estimated_time' => (int) get_post_meta( $post->ID, 'questoes_estimated_time', true ),
            'explanation'    => get_post_meta( $post->ID, 'questoes_explanation', true ),
            'question_type'  => get_post_meta( $post->ID, 'questoes_question_type', true ),
            'year'           => (int) get_post_meta( $post->ID, 'questoes_year', true ),
            'video_url'      => get_post_meta( $post->ID, 'questoes_video_url', true ),
            'answers'        => $this->get_answers( $post->ID ),
            'categories'     => $categories,
            'bancas'         => $bancas,
            'subjects'       => $subjects,
            'permalink'      => get_permalink( $post ),
            'comment_status' => $post->comment_status,
            'views'         => (int) get_post_meta( $post->ID, 'questoes_question_views', true ),
        );

        if ( ! empty( $payload['source_url'] ) ) {
            $payload['source_url'] = esc_url( $payload['source_url'] );
        }

        if ( ! empty( $payload['video_url'] ) ) {
            $payload['video_url'] = esc_url( $payload['video_url'] );
        }

        $payload['html'] = questoes_render_question_card( $payload );

        return $payload;
    }

    /**
     * Query questions with filters.
     *
     * @param array $args Query arguments.
     *
     * @return array
     */
    public function query_questions( $args = array(), $options = array() ) {
        $defaults = array(
            'post_type'      => $this->post_type,
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'paged'          => 1,
        );

        $query_args = wp_parse_args( $args, $defaults );

        $options = wp_parse_args(
            $options,
            array(
                'record_views' => false,
            )
        );

        $query = new WP_Query( $query_args );

        $items = array();
        $ids   = array();

        foreach ( $query->posts as $post ) {
            $prepared = $this->prepare_question_for_response( $post );
            $items[]  = $prepared;

            if ( isset( $prepared['id'] ) ) {
                $ids[] = (int) $prepared['id'];
            }
        }

        if ( ! empty( $ids ) && ! empty( $options['record_views'] ) ) {
            $this->increment_question_views( $ids );
        }

        return array(
            'items' => $items,
            'total' => (int) $query->found_posts,
            'pages' => (int) $query->max_num_pages,
        );
    }

    /**
     * Retrieve category overview data with aggregated counts.
     *
     * @param array $args Optional arguments.
     *
     * @return array
     */
    public function get_trending_questions( $args = array() ) {
        $defaults = array(
            'limit' => 6,
        );

        $args  = wp_parse_args( $args, $defaults );
        $limit = max( 1, absint( $args['limit'] ) );

        $results = $this->query_questions(
            array(
                'posts_per_page' => $limit,
                'meta_key'       => 'questoes_question_views',
                'orderby'        => array(
                    'meta_value_num' => 'DESC',
                    'date'           => 'DESC',
                ),
            ),
            array(
                'record_views' => false,
            )
        );

        $items = isset( $results['items'] ) ? $results['items'] : array();

        if ( empty( $items ) ) {
            $fallback = $this->query_questions(
                array(
                    'posts_per_page' => $limit,
                    'orderby'        => array(
                        'comment_count' => 'DESC',
                        'date'          => 'DESC',
                    ),
                ),
                array(
                    'record_views' => false,
                )
            );

            $items = isset( $fallback['items'] ) ? $fallback['items'] : array();
        }

        return $items;
    }

    public function get_trending_categories( $args = array() ) {
        $defaults = array(
            'limit' => 6,
            'pool'  => 40,
        );

        $args  = wp_parse_args( $args, $defaults );
        $limit = max( 1, absint( $args['limit'] ) );
        $pool  = max( $limit, absint( $args['pool'] ) );

        $query = new WP_Query(
            array(
                'post_type'      => $this->post_type,
                'post_status'    => 'publish',
                'posts_per_page' => $pool,
                'meta_key'       => 'questoes_question_views',
                'orderby'        => array(
                    'meta_value_num' => 'DESC',
                    'date'           => 'DESC',
                ),
            )
        );

        $categories = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();

                $post_id  = get_the_ID();
                $views    = max( 0, (int) get_post_meta( $post_id, 'questoes_question_views', true ) );
                $comments = max( 0, (int) get_comments_number( $post_id ) );
                $terms    = get_the_terms( $post_id, $this->category_taxonomy );

                if ( empty( $terms ) || is_wp_error( $terms ) ) {
                    continue;
                }

                $increment = $views > 0 ? $views : 1;

                foreach ( $terms as $term ) {
                    $term_id = (int) $term->term_id;

                    if ( ! isset( $categories[ $term_id ] ) ) {
                        $link = get_term_link( $term, $this->category_taxonomy );
                        if ( is_wp_error( $link ) ) {
                            $link = '';
                        }

                        $categories[ $term_id ] = array(
                            'id'        => $term_id,
                            'name'      => $term->name,
                            'slug'      => $term->slug,
                            'link'      => $link,
                            'views'     => 0,
                            'questions' => 0,
                            'comments'  => 0,
                        );
                    }

                    $categories[ $term_id ]['views']     += $increment;
                    $categories[ $term_id ]['questions'] += 1;
                    $categories[ $term_id ]['comments']  += $comments;
                }
            }
        }

        wp_reset_postdata();

        if ( empty( $categories ) ) {
            $overview = $this->get_category_overview_data();
            $items    = isset( $overview['items'] ) ? $overview['items'] : array();

            $items = array_slice( $items, 0, $limit );

            foreach ( $items as &$item ) {
                if ( ! isset( $item['views'] ) ) {
                    $item['views'] = isset( $item['count'] ) ? (int) $item['count'] : 0;
                }

                if ( ! isset( $item['comments'] ) ) {
                    $item['comments'] = 0;
                }
            }

            return $items;
        }

        $items = array_values( $categories );

        usort(
            $items,
            function ( $a, $b ) {
                if ( $a['views'] === $b['views'] ) {
                    if ( $a['questions'] === $b['questions'] ) {
                        return $b['comments'] <=> $a['comments'];
                    }

                    return $b['questions'] <=> $a['questions'];
                }

                return $b['views'] <=> $a['views'];
            }
        );

        return array_slice( $items, 0, $limit );
    }

    /**
     * Retrieve category overview data with aggregated counts.
     *
     * @param array $args Optional arguments.
     *
     * @return array
     */
    public function get_category_overview_data( $args = array() ) {
        $defaults = array(
            'hide_empty' => false,
            'orderby'    => 'count',
            'order'      => 'DESC',
        );

        $args = wp_parse_args( $args, $defaults );

        $terms = get_terms(
            array(
                'taxonomy'   => $this->category_taxonomy,
                'hide_empty' => false,
            )
        );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return array(
                'items' => array(),
                'areas' => array(),
            );
        }

        $items     = array();
        $areas_map = array();

        foreach ( $terms as $term ) {
            $stats = $this->calculate_category_term_stats( $term );

            if ( $args['hide_empty'] && 0 === $stats['questions'] ) {
                continue;
            }

            $area_term = $this->resolve_category_area_term( $term );
            $area_slug = $area_term ? $area_term->slug : '';

            if ( $area_term && ! isset( $areas_map[ $area_slug ] ) ) {
                $areas_map[ $area_slug ] = array(
                    'id'   => (int) $area_term->term_id,
                    'name' => $area_term->name,
                    'slug' => $area_term->slug,
                );
            }

            $link = get_term_link( $term, $this->category_taxonomy );
            if ( is_wp_error( $link ) ) {
                $link = '';
            }

            $items[] = array(
                'id'          => (int) $term->term_id,
                'name'        => $term->name,
                'slug'        => $term->slug,
                'description' => $term->description,
                'count'       => (int) $stats['questions'],
                'comments'    => (int) $stats['comments'],
                'area_slug'   => $area_slug,
                'area_name'   => $area_term ? $area_term->name : '',
                'area_id'     => $area_term ? (int) $area_term->term_id : 0,
                'parent'      => (int) $term->parent,
                'link'        => $link,
            );
        }

        $orderby = in_array( strtolower( $args['orderby'] ), array( 'count', 'name' ), true ) ? strtolower( $args['orderby'] ) : 'count';
        $order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

        usort(
            $items,
            function ( $a, $b ) use ( $orderby, $order ) {
                if ( 'name' === $orderby ) {
                    $comparison = strcasecmp( $a['name'], $b['name'] );
                } else {
                    $comparison = $a['count'] <=> $b['count'];
                }

                return 'DESC' === $order ? -1 * $comparison : $comparison;
            }
        );

        $areas = array_values( $areas_map );

        usort(
            $areas,
            function ( $a, $b ) {
                return strcasecmp( $a['name'], $b['name'] );
            }
        );

        return array(
            'items' => $items,
            'areas' => $areas,
        );
    }

    public function get_course_catalog_data( $args = array() ) {
        $defaults = array(
            'hide_empty'     => false,
            'featured_limit' => 6,
        );

        $args = wp_parse_args( $args, $defaults );

        $terms = get_terms(
            array(
                'taxonomy'   => $this->category_taxonomy,
                'hide_empty' => false,
            )
        );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return array(
                'items'   => array(),
                'featured'=> array(),
                'regions' => array(),
                'totals'  => array(
                    'courses'   => 0,
                    'questions' => 0,
                ),
            );
        }

        $regions_map = questoes_get_course_regions();
        $items       = array();
        $featured    = array();
        $region_data = array();
        $questions_total = 0;

        foreach ( $terms as $term ) {
            $stats = $this->calculate_category_term_stats( $term );

            if ( $args['hide_empty'] && 0 === $stats['questions'] ) {
                continue;
            }

            $meta   = $this->get_category_course_meta( $term->term_id );
            $region = isset( $meta['region'] ) && isset( $regions_map[ $meta['region'] ] ) ? $meta['region'] : 'nacional';
            $link   = get_term_link( $term, $this->category_taxonomy );

            if ( is_wp_error( $link ) ) {
                $link = '';
            }

            $highlight = ! empty( $meta['highlight'] ) ? $meta['highlight'] : $term->description;
            $cta       = ! empty( $meta['cta'] ) ? $meta['cta'] : __( 'Ver cursos disponíveis', 'questoes' );
            $priority  = isset( $meta['priority'] ) ? max( 0, (int) $meta['priority'] ) : 0;

            $item = array(
                'id'            => (int) $term->term_id,
                'name'          => $term->name,
                'slug'          => $term->slug,
                'description'   => $term->description,
                'count'         => (int) $stats['questions'],
                'comments'      => (int) $stats['comments'],
                'region'        => $region,
                'region_label'  => questoes_get_course_region_label( $region ),
                'salary'        => $meta['salary'],
                'opportunities' => $meta['opportunities'],
                'badge'         => $meta['badge'],
                'highlight'     => $highlight,
                'cta'           => $cta,
                'link'          => $link,
                'icon'          => $meta['icon'],
                'featured'      => 'yes' === $meta['featured'],
                'priority'      => $priority,
            );

            $items[] = $item;

            if ( $item['featured'] ) {
                $featured[] = $item;
            }

            if ( ! isset( $region_data[ $region ] ) ) {
                $region_data[ $region ] = array(
                    'courses'   => 0,
                    'questions' => 0,
                );
            }

            $region_data[ $region ]['courses']   += 1;
            $region_data[ $region ]['questions'] += (int) $stats['questions'];
            $questions_total                      += (int) $stats['questions'];
        }

        if ( empty( $items ) ) {
            return array(
                'items'   => array(),
                'featured'=> array(),
                'regions' => array(),
                'totals'  => array(
                    'courses'   => 0,
                    'questions' => 0,
                ),
            );
        }

        usort(
            $items,
            function ( $a, $b ) {
                $a_priority = ! empty( $a['priority'] ) ? (int) $a['priority'] : PHP_INT_MAX;
                $b_priority = ! empty( $b['priority'] ) ? (int) $b['priority'] : PHP_INT_MAX;

                if ( $a_priority !== $b_priority ) {
                    return $a_priority <=> $b_priority;
                }

                $comparison = $b['count'] <=> $a['count'];

                if ( 0 === $comparison ) {
                    $comparison = strcasecmp( $a['name'], $b['name'] );
                }

                return $comparison;
            }
        );

        usort(
            $featured,
            function ( $a, $b ) {
                $a_priority = ! empty( $a['priority'] ) ? (int) $a['priority'] : PHP_INT_MAX;
                $b_priority = ! empty( $b['priority'] ) ? (int) $b['priority'] : PHP_INT_MAX;

                if ( $a_priority !== $b_priority ) {
                    return $a_priority <=> $b_priority;
                }

                $comparison = $b['count'] <=> $a['count'];

                if ( 0 === $comparison ) {
                    $comparison = strcasecmp( $a['name'], $b['name'] );
                }

                return $comparison;
            }
        );

        $featured_limit = max( 1, (int) $args['featured_limit'] );
        $featured       = array_slice( $featured, 0, $featured_limit );

        $regions = array();

        foreach ( $region_data as $slug => $data ) {
            $label = questoes_get_course_region_label( $slug );

            if ( empty( $label ) && isset( $regions_map[ $slug ] ) ) {
                $label = $regions_map[ $slug ];
            }

            $regions[] = array(
                'slug'      => $slug,
                'label'     => $label,
                'courses'   => (int) $data['courses'],
                'questions' => (int) $data['questions'],
            );
        }

        usort(
            $regions,
            function ( $a, $b ) {
                return strcasecmp( $a['label'], $b['label'] );
            }
        );

        return array(
            'items'   => $items,
            'featured'=> $featured,
            'regions' => $regions,
            'totals'  => array(
                'courses'   => count( $items ),
                'questions' => $questions_total,
            ),
        );
    }


    /**
     * Calculate aggregated stats for a category term.
     *
     * @param WP_Term $term Term object.
     *
     * @return array
     */
    protected function calculate_category_term_stats( WP_Term $term ) {
        global $wpdb;

        $term_taxonomy_id = isset( $term->term_taxonomy_id ) ? (int) $term->term_taxonomy_id : 0;

        if ( ! $term_taxonomy_id ) {
            return array(
                'questions' => 0,
                'comments'  => 0,
            );
        }

        $results = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT p.ID) AS questions, COALESCE(SUM(p.comment_count), 0) AS comments
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                WHERE tr.term_taxonomy_id = %d
                AND p.post_type = %s
                AND p.post_status = 'publish'",
                $term_taxonomy_id,
                $this->post_type
            ),
            ARRAY_A
        );

        if ( empty( $results ) ) {
            return array(
                'questions' => 0,
                'comments'  => 0,
            );
        }

        return array(
            'questions' => (int) $results['questions'],
            'comments'  => (int) $results['comments'],
        );
    }

    /**
     * Resolve the top-level area term for a category entry.
     *
     * @param WP_Term $term Category term.
     *
     * @return WP_Term|null
     */
    protected function resolve_category_area_term( WP_Term $term ) {
        if ( empty( $term->parent ) ) {
            return $term;
        }

        $ancestors = get_ancestors( $term->term_id, $this->category_taxonomy );

        if ( ! empty( $ancestors ) ) {
            $root_id = end( $ancestors );

            if ( $root_id ) {
                $root = get_term( $root_id, $this->category_taxonomy );

                if ( $root && ! is_wp_error( $root ) ) {
                    return $root;
                }
            }
        }

        $parent = get_term( $term->parent, $this->category_taxonomy );

        if ( $parent && ! is_wp_error( $parent ) ) {
            return $parent;
        }

        return $term;
    }

    /**
     * Register custom columns for admin list.
     *
     * @param array $columns Columns.
     *
     * @return array
     */
    public function register_admin_columns( $columns ) {
        $new_columns = array();

        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;

            if ( 'title' === $key ) {
                $new_columns['questoes_difficulty'] = __( 'Dificuldade', 'questoes' );
                $new_columns['questoes_answers']    = __( 'Alternativas', 'questoes' );
                $new_columns['questoes_question_type'] = __( 'Formato', 'questoes' );
                $new_columns['questoes_year']          = __( 'Ano', 'questoes' );
            }
        }

        return $new_columns;
    }

    /**
     * Render custom column content.
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function render_admin_columns( $column, $post_id ) {
        if ( 'questoes_difficulty' === $column ) {
            $difficulty = get_post_meta( $post_id, 'questoes_difficulty', true );
            switch ( $difficulty ) {
                case 'easy':
                    esc_html_e( 'Fácil', 'questoes' );
                    break;
                case 'medium':
                    esc_html_e( 'Média', 'questoes' );
                    break;
                case 'hard':
                    esc_html_e( 'Difícil', 'questoes' );
                    break;
                default:
                    echo '—';
            }
        } elseif ( 'questoes_answers' === $column ) {
            $answers = $this->get_answers( $post_id );
            echo esc_html( count( $answers ) );
        } elseif ( 'questoes_question_type' === $column ) {
            $type = get_post_meta( $post_id, 'questoes_question_type', true );
            echo esc_html( questoes_get_question_type_label( $type ) );
        } elseif ( 'questoes_year' === $column ) {
            $year = get_post_meta( $post_id, 'questoes_year', true );
            echo $year ? esc_html( $year ) : '—';
        }
    }

    /**
     * Register sortable columns.
     *
     * @param array $columns Columns.
     *
     * @return array
     */
    public function register_sortable_columns( $columns ) {
        $columns['questoes_difficulty'] = 'questoes_difficulty';
        $columns['questoes_year']       = 'questoes_year';
        $columns['questoes_question_type'] = 'questoes_question_type';

        return $columns;
    }

    /**
     * Handle sortable column query adjustments.
     *
     * @param WP_Query $query Query instance.
     */
    public function handle_admin_sorting( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( $query->get( 'post_type' ) !== $this->post_type ) {
            return;
        }

        $orderby = $query->get( 'orderby' );

        if ( 'questoes_difficulty' === $orderby ) {
            $query->set( 'meta_key', 'questoes_difficulty' );
            $query->set( 'orderby', 'meta_value' );
        } elseif ( 'questoes_year' === $orderby ) {
            $query->set( 'meta_key', 'questoes_year' );
            $query->set( 'orderby', 'meta_value_num' );
        } elseif ( 'questoes_question_type' === $orderby ) {
            $query->set( 'meta_key', 'questoes_question_type' );
            $query->set( 'orderby', 'meta_value' );
        }
    }

    /**
     * Create or update a question from array payload.
     *
     * @param array $data Question data.
     *
     * @return int|WP_Error
     */
    public function upsert_question_from_array( $data ) {
        $defaults = array(
            'ID'            => 0,
            'post_title'    => '',
            'post_content'  => '',
            'post_excerpt'  => '',
            'post_status'   => 'publish',
            'post_author'   => get_current_user_id(),
            'answers'       => array(),
            'difficulty'    => '',
            'reference'     => '',
            'source'        => '',
            'estimated_time'=> 0,
            'explanation'   => '',
            'categories'    => array(),
            'bancas'        => array(),
            'subjects'      => array(),
            'question_type' => '',
            'year'          => 0,
            'video_url'     => '',
            'source_url'    => '',
        );

        $payload = wp_parse_args( $data, $defaults );

        if ( empty( $payload['post_title'] ) ) {
            return new WP_Error( 'questoes_invalid_title', __( 'O título da questão é obrigatório.', 'questoes' ) );
        }

        if ( $payload['ID'] && ! current_user_can( 'edit_post', $payload['ID'] ) ) {
            return new WP_Error( 'questoes_forbidden', __( 'Você não tem permissão para editar esta questão.', 'questoes' ) );
        }

        $postarr = array(
            'ID'           => absint( $payload['ID'] ),
            'post_title'   => sanitize_text_field( $payload['post_title'] ),
            'post_content' => wp_kses_post( $payload['post_content'] ),
            'post_excerpt' => wp_kses_post( $payload['post_excerpt'] ),
            'post_status'  => in_array( $payload['post_status'], array( 'publish', 'draft', 'pending', 'private' ), true ) ? $payload['post_status'] : 'publish',
            'post_author'  => absint( $payload['post_author'] ),
            'post_type'    => $this->post_type,
        );

        $post_id = wp_insert_post( $postarr, true );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        if ( ! empty( $payload['categories'] ) ) {
            wp_set_object_terms( $post_id, array_map( 'sanitize_text_field', (array) $payload['categories'] ), $this->category_taxonomy );
        }

        if ( ! empty( $payload['bancas'] ) ) {
            wp_set_object_terms( $post_id, array_map( 'sanitize_text_field', (array) $payload['bancas'] ), $this->banca_taxonomy );
        }

        if ( ! empty( $payload['subjects'] ) ) {
            wp_set_object_terms( $post_id, array_map( 'sanitize_text_field', (array) $payload['subjects'] ), $this->subject_taxonomy );
        }

        $question_type = $this->sanitize_question_type( sanitize_key( $payload['question_type'] ) );
        $year          = $this->sanitize_year( $payload['year'] );
        $video_url     = isset( $payload['video_url'] ) ? esc_url_raw( $payload['video_url'] ) : '';
        $source_url    = isset( $payload['source_url'] ) ? esc_url_raw( $payload['source_url'] ) : '';

        $this->update_question_meta(
            $post_id,
            array(
                'answers'        => $this->sanitize_answers( $payload['answers'] ),
                'difficulty'     => $this->sanitize_difficulty( sanitize_key( $payload['difficulty'] ) ),
                'reference'      => sanitize_text_field( $payload['reference'] ),
                'source'         => sanitize_text_field( $payload['source'] ),
                'estimated_time' => absint( $payload['estimated_time'] ),
                'explanation'    => wp_kses_post( $payload['explanation'] ),
                'question_type'  => $question_type,
                'year'           => $year,
                'video_url'      => $video_url,
                'source_url'     => $source_url,
            )
        );

        return $post_id;
    }
}
