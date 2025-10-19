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
     * Constructor.
     *
     * @param Questoes_Settings $settings Settings handler.
     * @param Questoes_Renderer $renderer Renderer.
     */
    public function __construct( Questoes_Settings $settings, Questoes_Renderer $renderer ) {
        $this->settings = $settings;
        $this->renderer = $renderer;

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
}
