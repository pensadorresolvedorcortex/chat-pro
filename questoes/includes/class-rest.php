<?php
/**
 * REST endpoints.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST class.
 */
class Questoes_REST {

    /**
     * Settings handler.
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
     * Question manager.
     *
     * @var Questoes_Questions
     */
    protected $questions;

    /**
     * Constructor.
     *
     * @param Questoes_Settings $settings  Settings handler.
     * @param Questoes_Renderer $renderer  Renderer.
     * @param Questoes_Questions $questions Question manager.
     */
    public function __construct( Questoes_Settings $settings, Questoes_Renderer $renderer, Questoes_Questions $questions ) {
        $this->settings  = $settings;
        $this->renderer  = $renderer;
        $this->questions = $questions;

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register routes.
     */
    public function register_routes() {
        register_rest_route(
            'questoes/v1',
            '/data',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'permission_callback' => '__return_true',
                    'callback'            => array( $this, 'get_data' ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'permission_callback' => array( $this, 'can_manage' ),
                    'callback'            => array( $this, 'save_data' ),
                    'args'                => array(
                        'data' => array(
                            'required' => true,
                            'type'     => 'array',
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            'questoes/v1',
            '/questions',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'permission_callback' => '__return_true',
                    'callback'            => array( $this, 'get_questions' ),
                    'args'                => array(
                        'search'    => array(
                            'type' => 'string',
                        ),
                        'per_page'  => array(
                            'type'              => 'integer',
                            'default'           => 20,
                            'sanitize_callback' => 'absint',
                        ),
                        'page'      => array(
                            'type'              => 'integer',
                            'default'           => 1,
                            'sanitize_callback' => 'absint',
                        ),
                        'category'  => array(
                            'type' => 'string',
                        ),
                        'banca'     => array(
                            'type' => 'string',
                        ),
                        'difficulty'=> array(
                            'type' => 'string',
                        ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'permission_callback' => array( $this, 'can_manage' ),
                    'callback'            => array( $this, 'create_question' ),
                ),
            )
        );
    }

    /**
     * Permission callback.
     *
     * @return bool
     */
    public function can_manage() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Get data callback.
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response
     */
    public function get_data( WP_REST_Request $request ) {
        $mode = $request->get_param( 'mode' );
        $mode = $mode ? sanitize_text_field( $mode ) : 'both';

        $data = $this->renderer->get_data( $mode );

        return rest_ensure_response(
            array(
                'data' => $data,
            )
        );
    }

    /**
     * Save data callback.
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function save_data( WP_REST_Request $request ) {
        $data = $request->get_param( 'data' );

        if ( empty( $data ) || ! is_array( $data ) ) {
            return new WP_Error( 'questoes_invalid_data', __( 'JSON inválido: verifique vírgulas e chaves.', 'questoes' ), array( 'status' => 400 ) );
        }

        $validation = Questoes_Schema::validate( $data );
        if ( is_wp_error( $validation ) ) {
            $validation->add_data( array( 'status' => 400 ) );
            return $validation;
        }

        $this->settings->update( 'data', wp_json_encode( $data ) );

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __( 'Dados salvos com sucesso.', 'questoes' ),
            )
        );
    }

    /**
     * Retrieve questions.
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response
     */
    public function get_questions( WP_REST_Request $request ) {
        $per_page = (int) $request->get_param( 'per_page' );
        if ( $per_page < 1 ) {
            $per_page = 1;
        } elseif ( $per_page > 100 ) {
            $per_page = 100;
        }

        $page = (int) $request->get_param( 'page' );
        if ( $page < 1 ) {
            $page = 1;
        }

        $args = array(
            'posts_per_page' => $per_page,
            'paged'          => $page,
        );

        $search = $request->get_param( 'search' );
        if ( $search ) {
            $args['s'] = sanitize_text_field( $search );
        }

        $tax_query = array();

        $category = $request->get_param( 'category' );
        if ( $category ) {
            $category_terms = array_filter( array_map( 'sanitize_title', explode( ',', $category ) ) );
            if ( ! empty( $category_terms ) ) {
                $tax_query[] = array(
                    'taxonomy' => $this->questions->get_category_taxonomy(),
                    'field'    => 'slug',
                    'terms'    => $category_terms,
                );
            }
        }

        $banca = $request->get_param( 'banca' );
        if ( $banca ) {
            $banca_terms = array_filter( array_map( 'sanitize_title', explode( ',', $banca ) ) );
            if ( ! empty( $banca_terms ) ) {
                $tax_query[] = array(
                    'taxonomy' => $this->questions->get_banca_taxonomy(),
                    'field'    => 'slug',
                    'terms'    => $banca_terms,
                );
            }
        }

        if ( ! empty( $tax_query ) ) {
            $args['tax_query'] = $tax_query;
        }

        $difficulty = $request->get_param( 'difficulty' );
        if ( $difficulty ) {
            $args['meta_query'] = array(
                array(
                    'key'   => 'questoes_difficulty',
                    'value' => sanitize_key( $difficulty ),
                ),
            );
        }

        $results = $this->questions->query_questions( $args );

        return rest_ensure_response( $results );
    }

    /**
     * Create or update a question.
     *
     * @param WP_REST_Request $request Request object.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function create_question( WP_REST_Request $request ) {
        $payload = $request->get_json_params();

        if ( empty( $payload ) ) {
            $payload = $request->get_body_params();
        }

        if ( empty( $payload ) || empty( $payload['title'] ) ) {
            return new WP_Error( 'questoes_missing_title', __( 'O título da questão é obrigatório.', 'questoes' ), array( 'status' => 400 ) );
        }

        $question_id = $this->questions->upsert_question_from_array(
            array(
                'ID'            => isset( $payload['id'] ) ? absint( $payload['id'] ) : 0,
                'post_title'    => $payload['title'],
                'post_content'  => isset( $payload['content'] ) ? $payload['content'] : '',
                'post_excerpt'  => isset( $payload['excerpt'] ) ? $payload['excerpt'] : '',
                'post_status'   => isset( $payload['status'] ) ? $payload['status'] : 'publish',
                'post_author'   => isset( $payload['author'] ) ? absint( $payload['author'] ) : get_current_user_id(),
                'answers'       => isset( $payload['answers'] ) ? $payload['answers'] : array(),
                'difficulty'    => isset( $payload['difficulty'] ) ? $payload['difficulty'] : '',
                'reference'     => isset( $payload['reference'] ) ? $payload['reference'] : '',
                'source'        => isset( $payload['source'] ) ? $payload['source'] : '',
                'estimated_time'=> isset( $payload['estimated_time'] ) ? $payload['estimated_time'] : 0,
                'explanation'   => isset( $payload['explanation'] ) ? $payload['explanation'] : '',
                'categories'    => isset( $payload['categories'] ) ? $payload['categories'] : array(),
                'bancas'        => isset( $payload['bancas'] ) ? $payload['bancas'] : array(),
            )
        );

        if ( is_wp_error( $question_id ) ) {
            $status = 'questoes_forbidden' === $question_id->get_error_code() ? 403 : 400;
            $question_id->add_data( array( 'status' => $status ) );
            return $question_id;
        }

        $question = get_post( $question_id );

        if ( ! $question ) {
            return new WP_Error( 'questoes_not_found', __( 'Questão não encontrada.', 'questoes' ), array( 'status' => 404 ) );
        }

        $response = $this->questions->prepare_question_for_response( $question );

        return rest_ensure_response(
            array(
                'success'  => true,
                'question' => $response,
            )
        );
    }
}
