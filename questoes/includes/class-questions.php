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

            <div class="questoes-meta-field">
                <label for="questoes-difficulty" class="questoes-meta-label"><?php esc_html_e( 'Dificuldade', 'questoes' ); ?></label>
                <select id="questoes-difficulty" name="questoes_difficulty">
                    <option value="" <?php selected( $difficulty, '' ); ?>><?php esc_html_e( 'Selecione', 'questoes' ); ?></option>
                    <option value="easy" <?php selected( $difficulty, 'easy' ); ?>><?php esc_html_e( 'Fácil', 'questoes' ); ?></option>
                    <option value="medium" <?php selected( $difficulty, 'medium' ); ?>><?php esc_html_e( 'Média', 'questoes' ); ?></option>
                    <option value="hard" <?php selected( $difficulty, 'hard' ); ?>><?php esc_html_e( 'Difícil', 'questoes' ); ?></option>
                </select>
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

        $reference = isset( $_POST['questoes_reference'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_reference'] ) ) : '';
        $source    = isset( $_POST['questoes_source'] ) ? sanitize_text_field( wp_unslash( $_POST['questoes_source'] ) ) : '';

        $estimated_time = isset( $_POST['questoes_estimated_time'] ) ? absint( wp_unslash( $_POST['questoes_estimated_time'] ) ) : 0;
        $explanation    = isset( $_POST['questoes_explanation'] ) ? wp_kses_post( wp_unslash( $_POST['questoes_explanation'] ) ) : '';

        $this->update_question_meta(
            $post_id,
            array(
                'answers'        => $answers,
                'difficulty'     => $difficulty,
                'reference'      => $reference,
                'source'         => $source,
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

        $payload = array(
            'id'             => $post->ID,
            'title'          => get_the_title( $post ),
            'content'        => apply_filters( 'the_content', $post->post_content ),
            'excerpt'        => get_the_excerpt( $post ),
            'difficulty'     => get_post_meta( $post->ID, 'questoes_difficulty', true ),
            'reference'      => get_post_meta( $post->ID, 'questoes_reference', true ),
            'source'         => get_post_meta( $post->ID, 'questoes_source', true ),
            'estimated_time' => (int) get_post_meta( $post->ID, 'questoes_estimated_time', true ),
            'explanation'    => get_post_meta( $post->ID, 'questoes_explanation', true ),
            'answers'        => $this->get_answers( $post->ID ),
            'categories'     => $categories,
            'bancas'         => $bancas,
            'permalink'      => get_permalink( $post ),
            'comment_status' => $post->comment_status,
        );

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
    public function query_questions( $args = array() ) {
        $defaults = array(
            'post_type'      => $this->post_type,
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'paged'          => 1,
        );

        $query_args = wp_parse_args( $args, $defaults );

        $query = new WP_Query( $query_args );

        $items = array();

        foreach ( $query->posts as $post ) {
            $items[] = $this->prepare_question_for_response( $post );
        }

        return array(
            'items' => $items,
            'total' => (int) $query->found_posts,
            'pages' => (int) $query->max_num_pages,
        );
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

        $this->update_question_meta(
            $post_id,
            array(
                'answers'        => $this->sanitize_answers( $payload['answers'] ),
                'difficulty'     => $this->sanitize_difficulty( sanitize_key( $payload['difficulty'] ) ),
                'reference'      => sanitize_text_field( $payload['reference'] ),
                'source'         => sanitize_text_field( $payload['source'] ),
                'estimated_time' => absint( $payload['estimated_time'] ),
                'explanation'    => wp_kses_post( $payload['explanation'] ),
            )
        );

        return $post_id;
    }
}
