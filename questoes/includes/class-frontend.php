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

    public function __construct( Questoes_Settings $settings, Questoes_Renderer $renderer, Questoes_Accessibility $accessibility, Questoes_Questions $questions ) {
        $this->settings      = $settings;
        $this->renderer      = $renderer;
        $this->accessibility = $accessibility;
        $this->questions     = $questions;

        add_shortcode( 'questoes', array( $this, 'render_shortcode' ) );
        add_shortcode( 'questoes_banco', array( $this, 'render_question_bank_shortcode' ) );
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
                'texts'   => array(
                    'loading'  => __( 'Carregando questões…', 'questoes' ),
                    'empty'    => __( 'Nenhuma questão encontrada.', 'questoes' ),
                    'error'    => __( 'Não foi possível carregar as questões. Tente novamente.', 'questoes' ),
                    'showOptions' => __( 'Mostrar alternativas', 'questoes' ),
                    'hideOptions' => __( 'Ocultar alternativas', 'questoes' ),
                    'correct'     => __( 'Resposta correta!', 'questoes' ),
                    'incorrect'   => __( 'Resposta incorreta. Tente novamente.', 'questoes' ),
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

        $results = $this->questions->query_questions( $query_args );

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
}
