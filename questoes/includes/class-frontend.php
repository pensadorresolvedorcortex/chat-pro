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

    public function __construct( Questoes_Settings $settings, Questoes_Renderer $renderer, Questoes_Accessibility $accessibility ) {
        $this->settings      = $settings;
        $this->renderer      = $renderer;
        $this->accessibility = $accessibility;

        add_shortcode( 'questoes', array( $this, 'render_shortcode' ) );
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
}
