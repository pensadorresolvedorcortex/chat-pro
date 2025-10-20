<?php
/**
 * Frontend controller.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Questoes_Frontend {

    protected $settings;
    protected $renderer;
    protected $accessibility;

    protected $questions;

    protected $courses;

    protected $knowledge;

    public function __construct( Questoes_Settings $settings, Questoes_Renderer $renderer, Questoes_Accessibility $accessibility, Questoes_Questions $questions, Questoes_Courses $courses, Questoes_Knowledge $knowledge ) {
        $this->settings      = $settings;
        $this->renderer      = $renderer;
        $this->accessibility = $accessibility;
        $this->questions     = $questions;
        $this->courses       = $courses;
        $this->knowledge     = $knowledge;

        add_shortcode( 'questoes', array( $this, 'render_shortcode' ) );
        add_shortcode( 'questoes_banco', array( $this, 'render_question_bank_shortcode' ) );
        add_shortcode( 'questoes_busca', array( $this, 'render_question_search_shortcode' ) );
        add_shortcode( 'questoes_disciplinas', array( $this, 'render_disciplines_shortcode' ) );
        add_shortcode( 'questoes_cursos', array( $this, 'render_courses_shortcode' ) );
        add_shortcode( 'academia_cursos', array( $this, 'render_courses_shortcode' ) );
        add_shortcode( 'questoes_em_alta', array( $this, 'render_trending_shortcode' ) );
        add_shortcode( 'questoes_meus_conhecimentos', array( $this, 'render_knowledge_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'questoes-frontend',
            QUESTOES_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            questoes_asset_version( 'assets/css/frontend.css' )
        );

        wp_enqueue_script(
            'questoes-frontend',
            QUESTOES_PLUGIN_URL . 'assets/js/frontend.js',
            array(),
            questoes_asset_version( 'assets/js/frontend.js' ),
            true
        );

        wp_localize_script(
            'questoes-frontend',
            'questoesFrontend',
            array(
                'restUrl' => esc_url_raw( rest_url( 'questoes/v1/questions' ) ),
                'nonce'   => wp_create_nonce( 'wp_rest' ),
                'knowledgeRest' => esc_url_raw( rest_url( 'questoes/v1/knowledge' ) ),
                'knowledgeCanPersist' => is_user_logged_in() ? '1' : '0',
                'texts'   => array(
                    'loading'  => __( 'Carregando questões…', 'questoes' ),
                    'empty'    => __( 'Nenhuma questão encontrada.', 'questoes' ),
                    'error'    => __( 'Não foi possível carregar as questões. Tente novamente.', 'questoes' ),
                    'correct'     => __( 'Resposta correta!', 'questoes' ),
                    'incorrect'   => __( 'Resposta incorreta. Tente novamente.', 'questoes' ),
                    'disciplineEmpty'    => __( 'Nenhuma disciplina encontrada para os filtros selecionados.', 'questoes' ),
                    'disciplineSummary'  => __( '%1$s %2$s encontradas.', 'questoes' ),
                    'disciplineTotal'    => __( '%s questões ao todo.', 'questoes' ),
                    'disciplineSingular' => __( 'disciplina', 'questoes' ),
                    'disciplinePlural'   => __( 'disciplinas', 'questoes' ),
                    'courseEmpty'        => __( 'Nenhum curso disponível para os filtros selecionados.', 'questoes' ),
                    'knowledgeEmpty'     => __( 'Você ainda não iniciou conteúdos. Explore os cursos ou o banco de questões para começar.', 'questoes' ),
                    'knowledgeResume'    => __( 'Retomar', 'questoes' ),
                    'knowledgeRemove'    => __( 'Remover', 'questoes' ),
                    'knowledgeQuestionsTitle' => __( 'Questões em andamento', 'questoes' ),
                    'knowledgeCoursesTitle'   => __( 'Cursos em andamento', 'questoes' ),
                    'knowledgeUpdated'        => __( 'Atualizado há %s', 'questoes' ),
                    'knowledgeProgressQuestions' => __( '%1$s de %2$s questões concluídas', 'questoes' ),
                ),
            )
        );

        if ( function_exists( 'questoes_comments_enabled' ) && questoes_comments_enabled() ) {
            wp_enqueue_script( 'comment-reply' );
        }
    }

    public function render_shortcode( $atts, $content = '' ) {
        $atts = shortcode_atts(
            array(
                'modo'   => 'ambos',
                'titulo' => '',
            ),
            $atts,
            'questoes'
        );

        $json_content = trim( $content );
        $mode         = $atts['modo'];
        $data         = array();

        if ( ! empty( $json_content ) ) {
            $sanitized = Questoes_Schema::sanitize_json( $json_content );
            $decoded   = json_decode( $sanitized, true );
            if ( ! empty( $decoded ) ) {
                $validation = Questoes_Schema::validate( $decoded );
                if ( ! is_wp_error( $validation ) ) {
                    $data = $this->renderer->prepare_from_array( $decoded, $mode );
                }
            }
        } else {
            $data = $this->renderer->get_data( $mode );
        }

        if ( empty( $data ) ) {
            return '<div class="questoes-empty">' . esc_html__( 'Nenhum dado disponível.', 'questoes' ) . '</div>';
        }

        if ( ! empty( $atts['titulo'] ) ) {
            $data['title'] = sanitize_text_field( $atts['titulo'] );
        }

        $palette = $this->settings->get( 'palette' );

        ob_start();
        $comments_enabled = $this->settings->get( 'comments_enabled' );
        $accessibility    = $this->accessibility;
        include QUESTOES_PLUGIN_DIR . 'views/tabs.php';

        if ( $comments_enabled && function_exists( 'comments_template' ) && is_singular() ) {
            comments_template();
        }

        return ob_get_clean();
    }

    public function render_courses_shortcode( $atts, $content = '' ) {
        $atts = shortcode_atts(
            array(
                'titulo_destaques'    => __( 'Cursos em alta', 'questoes' ),
                'descricao_destaques' => __( 'Principais concursos do momento.', 'questoes' ),
                'limite_destaques'    => 6,
            ),
            $atts,
            'questoes_cursos'
        );

        $limit   = max( 1, min( 12, absint( $atts['limite_destaques'] ) ) );
        $catalog = $this->courses->get_catalog_data(
            array(
                'featured_limit' => $limit,
            )
        );

        $courses  = isset( $catalog['items'] ) ? $catalog['items'] : array();
        $featured = isset( $catalog['featured'] ) ? $catalog['featured'] : array();
        $totals   = isset( $catalog['totals'] ) ? $catalog['totals'] : array(
            'courses'   => count( $courses ),
            'questions' => 0,
        );

        if ( empty( $courses ) ) {
            return '<div class="questoes-empty">' . esc_html__( 'Nenhum curso disponível no momento.', 'questoes' ) . '</div>';
        }

        $this->courses->record_course_views( wp_list_pluck( $courses, 'id' ) );

        $featured_title       = sanitize_text_field( $atts['titulo_destaques'] );
        $featured_description = ! empty( $atts['descricao_destaques'] ) ? wp_kses_post( $atts['descricao_destaques'] ) : '';
        $additional_content   = '';

        if ( ! empty( $content ) ) {
            $additional_content = do_shortcode( $content );
        }

        $palette = $this->settings->get( 'palette' );

        ob_start();
        include QUESTOES_PLUGIN_DIR . 'views/course-browser.php';

        return ob_get_clean();
    }

    public function render_trending_shortcode( $atts, $content = '' ) {
        $atts = shortcode_atts(
            array(
                'titulo'             => __( 'Em alta agora', 'questoes' ),
                'descricao'          => __( 'Confira os conteúdos mais acessados pela comunidade.', 'questoes' ),
                'limite_questoes'    => 6,
                'limite_cursos'      => 6,
                'limite_disciplinas' => 6,
            ),
            $atts,
            'questoes_em_alta'
        );

        $question_limit   = max( 1, min( 12, absint( $atts['limite_questoes'] ) ) );
        $course_limit     = max( 1, min( 12, absint( $atts['limite_cursos'] ) ) );
        $discipline_limit = max( 1, min( 12, absint( $atts['limite_disciplinas'] ) ) );

        $trending_questions = $this->questions->get_trending_questions(
            array(
                'limit' => $question_limit,
            )
        );

        $trending_courses = $this->courses->get_trending_courses(
            array(
                'limit' => $course_limit,
            )
        );

        $trending_disciplines = $this->questions->get_trending_categories(
            array(
                'limit' => $discipline_limit,
            )
        );

        $title       = sanitize_text_field( $atts['titulo'] );
        $description = ! empty( $atts['descricao'] ) ? wp_kses_post( $atts['descricao'] ) : '';
        $palette     = $this->settings->get( 'palette' );

        ob_start();
        include QUESTOES_PLUGIN_DIR . 'views/trending-browser.php';

        if ( ! empty( $content ) ) {
            echo do_shortcode( $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        return ob_get_clean();
    }

    public function render_knowledge_shortcode( $atts, $content = '' ) {
        $atts = shortcode_atts(
            array(
                'titulo'         => __( 'Meus conhecimentos', 'questoes' ),
                'descricao'      => __( 'Retome rapidamente conteúdos que você começou a estudar.', 'questoes' ),
                'mensagem_vazia' => __( 'Comece um curso ou resolva questões para vê-los aqui.', 'questoes' ),
                'mensagem_login' => __( 'Crie uma conta ou faça login para sincronizar seu progresso em todos os dispositivos.', 'questoes' ),
            ),
            $atts,
            'questoes_meus_conhecimentos'
        );

        $title             = sanitize_text_field( $atts['titulo'] );
        $description       = sanitize_text_field( $atts['descricao'] );
        $empty_message     = sanitize_text_field( $atts['mensagem_vazia'] );
        $login_message     = sanitize_text_field( $atts['mensagem_login'] );
        $show_login_notice = ! is_user_logged_in();
        $palette           = $this->settings->get( 'palette' );
        $knowledge_data    = $this->knowledge->get_user_knowledge();
        $extra_content     = '';

        if ( ! empty( $content ) ) {
            $extra_content = do_shortcode( $content );
        }

        $container_id = 'questoes-knowledge-' . wp_rand( 1000, 9999 );

        ob_start();
        include QUESTOES_PLUGIN_DIR . 'views/knowledge-dashboard.php';

        return ob_get_clean();
    }

    public function render_disciplines_shortcode( $atts, $content = '' ) {
        $atts = shortcode_atts(
            array(
                'titulo'     => '',
                'descricao'  => '',
                'mostrar_vazias' => 'nao',
            ),
            $atts,
            'questoes_disciplinas'
        );

        $mostrar_vazias = sanitize_text_field( $atts['mostrar_vazias'] );

        $data = $this->questions->get_category_overview_data(
            array(
                'hide_empty' => 'sim' !== strtolower( $mostrar_vazias ),
            )
        );

        $disciplines = isset( $data['items'] ) ? $data['items'] : array();
        $areas       = isset( $data['areas'] ) ? $data['areas'] : array();

        if ( empty( $disciplines ) ) {
            return '<div class="questoes-empty">' . esc_html__( 'Nenhuma disciplina cadastrada até o momento.', 'questoes' ) . '</div>';
        }

        $title       = sanitize_text_field( $atts['titulo'] );
        $description = sanitize_text_field( $atts['descricao'] );
        $palette     = $this->settings->get( 'palette' );
        $highlight   = '';

        if ( ! empty( $content ) ) {
            $highlight = do_shortcode( $content );
        }

        $total_questions = 0;
        foreach ( $disciplines as $entry ) {
            $total_questions += isset( $entry['count'] ) ? (int) $entry['count'] : 0;
        }

        $search_id = 'questoes-discipline-search-' . wp_rand( 1000, 9999 );
        $area_id   = 'questoes-discipline-area-' . wp_rand( 1000, 9999 );

        ob_start();
        include QUESTOES_PLUGIN_DIR . 'views/discipline-browser.php';

        return ob_get_clean();
    }

    public function render_question_bank_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'titulo'           => '',
                'categoria'        => '',
                'banca'            => '',
                'dificuldade'      => '',
                'assunto'          => '',
                'ano'              => '',
                'tipo'             => '',
                'por_pagina'       => 10,
                'mostrar_filtros'  => 'sim',
            ),
            $atts,
            'questoes_banco'
        );

        $per_page   = max( 1, min( 50, absint( $atts['por_pagina'] ) ) );
        $category   = sanitize_text_field( $atts['categoria'] );
        $banca      = sanitize_text_field( $atts['banca'] );
        $difficulty = $this->questions->sanitize_difficulty( sanitize_key( $atts['dificuldade'] ) );
        $subject    = sanitize_text_field( $atts['assunto'] );
        $year       = $this->questions->sanitize_year( absint( $atts['ano'] ) );
        $type       = $this->questions->sanitize_question_type( sanitize_key( $atts['tipo'] ) );

        $query_args = array(
            'posts_per_page' => $per_page,
            'paged'          => 1,
        );

        $tax_query = array();

        if ( ! empty( $category ) ) {
            $category_terms = array_filter( array_map( 'sanitize_title', explode( ',', $category ) ) );
            if ( ! empty( $category_terms ) ) {
                $tax_query[] = array(
                    'taxonomy' => $this->questions->get_category_taxonomy(),
                    'field'    => 'slug',
                    'terms'    => $category_terms,
                );
            }
        }

        if ( ! empty( $banca ) ) {
            $banca_terms = array_filter( array_map( 'sanitize_title', explode( ',', $banca ) ) );
            if ( ! empty( $banca_terms ) ) {
                $tax_query[] = array(
                    'taxonomy' => $this->questions->get_banca_taxonomy(),
                    'field'    => 'slug',
                    'terms'    => $banca_terms,
                );
            }
        }

        if ( ! empty( $subject ) ) {
            $subject_terms = array_filter( array_map( 'sanitize_title', explode( ',', $subject ) ) );
            if ( ! empty( $subject_terms ) ) {
                $tax_query[] = array(
                    'taxonomy' => $this->questions->get_subject_taxonomy(),
                    'field'    => 'slug',
                    'terms'    => $subject_terms,
                );
            }
        }

        if ( ! empty( $tax_query ) ) {
            $query_args['tax_query'] = $tax_query;
        }

        $meta_query = array();

        if ( ! empty( $difficulty ) ) {
            $meta_query[] = array(
                'key'   => 'questoes_difficulty',
                'value' => $difficulty,
            );
        }

        if ( ! empty( $type ) ) {
            $meta_query[] = array(
                'key'   => 'questoes_question_type',
                'value' => $type,
            );
        }

        if ( ! empty( $year ) ) {
            $meta_query[] = array(
                'key'     => 'questoes_year',
                'value'   => $year,
                'compare' => '=',
                'type'    => 'NUMERIC',
            );
        }

        if ( ! empty( $meta_query ) ) {
            $query_args['meta_query'] = $meta_query;
        }

        $results = $this->questions->query_questions(
            $query_args,
            array(
                'record_views' => true,
            )
        );

        $config = array(
            'perPage'    => $per_page,
            'category'   => $category,
            'banca'      => $banca,
            'difficulty' => $difficulty,
            'subject'    => $subject,
            'year'       => $year,
            'type'       => $type,
            'showFilter' => 'sim' === strtolower( $atts['mostrar_filtros'] ) || 'yes' === strtolower( $atts['mostrar_filtros'] ),
            'restUrl'    => esc_url_raw( rest_url( 'questoes/v1/questions' ) ),
        );

        $categories = get_terms(
            array(
                'taxonomy'   => $this->questions->get_category_taxonomy(),
                'hide_empty' => false,
            )
        );

        $bancas = get_terms(
            array(
                'taxonomy'   => $this->questions->get_banca_taxonomy(),
                'hide_empty' => false,
            )
        );

        $subjects = get_terms(
            array(
                'taxonomy'   => $this->questions->get_subject_taxonomy(),
                'hide_empty' => false,
            )
        );

        if ( is_wp_error( $categories ) ) {
            $categories = array();
        }

        if ( is_wp_error( $bancas ) ) {
            $bancas = array();
        }

        if ( is_wp_error( $subjects ) ) {
            $subjects = array();
        }

        $title   = sanitize_text_field( $atts['titulo'] );
        $palette = $this->settings->get( 'palette' );

        ob_start();
        include QUESTOES_PLUGIN_DIR . 'views/question-bank.php';

        return ob_get_clean();
    }

    public function render_question_search_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'titulo'          => '',
                'categoria'       => '',
                'banca'           => '',
                'assunto'         => '',
                'dificuldade'     => '',
                'ano'             => '',
                'tipo'            => '',
                'por_pagina'      => 10,
                'mostrar_filtros' => 'sim',
                'busca'           => '',
                'placeholder'     => '',
            ),
            $atts,
            'questoes_busca'
        );

        $per_page   = max( 1, min( 50, absint( $atts['por_pagina'] ) ) );
        $category   = sanitize_text_field( $atts['categoria'] );
        $banca      = sanitize_text_field( $atts['banca'] );
        $subject    = sanitize_text_field( $atts['assunto'] );
        $difficulty = $this->questions->sanitize_difficulty( sanitize_key( $atts['dificuldade'] ) );
        $year       = $this->questions->sanitize_year( absint( $atts['ano'] ) );
        $type       = $this->questions->sanitize_question_type( sanitize_key( $atts['tipo'] ) );
        $search     = sanitize_text_field( $atts['busca'] );

        $initial_notice = esc_html__( 'Use a busca e os filtros para encontrar questões.', 'questoes' );

        $config = array(
            'perPage'           => $per_page,
            'category'          => $category,
            'banca'             => $banca,
            'subject'           => $subject,
            'difficulty'        => $difficulty,
            'year'              => $year,
            'type'              => $type,
            'search'            => $search,
            'showFilter'        => 'sim' === strtolower( $atts['mostrar_filtros'] ) || 'yes' === strtolower( $atts['mostrar_filtros'] ),
            'restUrl'           => esc_url_raw( rest_url( 'questoes/v1/questions' ) ),
            'delayInitialFetch' => true,
            'initialNotice'     => $initial_notice,
        );

        $categories = get_terms(
            array(
                'taxonomy'   => $this->questions->get_category_taxonomy(),
                'hide_empty' => false,
            )
        );

        $bancas = get_terms(
            array(
                'taxonomy'   => $this->questions->get_banca_taxonomy(),
                'hide_empty' => false,
            )
        );

        $subjects = get_terms(
            array(
                'taxonomy'   => $this->questions->get_subject_taxonomy(),
                'hide_empty' => false,
            )
        );

        if ( is_wp_error( $categories ) ) {
            $categories = array();
        }

        if ( is_wp_error( $bancas ) ) {
            $bancas = array();
        }

        if ( is_wp_error( $subjects ) ) {
            $subjects = array();
        }

        $title              = sanitize_text_field( $atts['titulo'] );
        $palette            = $this->settings->get( 'palette' );
        $search_placeholder = ! empty( $atts['placeholder'] ) ? sanitize_text_field( $atts['placeholder'] ) : esc_html__( 'Digite um termo para buscar…', 'questoes' );

        $results = array(
            'items' => array(),
            'pages' => 0,
        );

        ob_start();
        include QUESTOES_PLUGIN_DIR . 'views/question-search.php';

        return ob_get_clean();
    }
}
