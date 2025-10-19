<?php
/**
 * Gutenberg block registration.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Block class.
 */
class Questoes_Block {

    protected $settings;
    protected $renderer;
    protected $accessibility;

    /**
     * Constructor.
     */
    public function __construct( Questoes_Settings $settings, Questoes_Renderer $renderer, Questoes_Accessibility $accessibility ) {
        $this->settings      = $settings;
        $this->renderer      = $renderer;
        $this->accessibility = $accessibility;

        add_action( 'init', array( $this, 'register_block' ) );
    }

    /**
     * Register block type and assets.
     */
    public function register_block() {
        wp_register_script(
            'questoes-block',
            QUESTOES_PLUGIN_URL . 'assets/js/block.js',
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' ),
            questoes_asset_version( 'assets/js/block.js' ),
            true
        );

        wp_register_style(
            'questoes-block-editor',
            QUESTOES_PLUGIN_URL . 'assets/css/block-editor.css',
            array( 'wp-edit-blocks' ),
            questoes_asset_version( 'assets/css/block-editor.css' )
        );

        register_block_type(
            'questoes/mapa-organograma',
            array(
                'editor_script'   => 'questoes-block',
                'editor_style'    => 'questoes-block-editor',
                'render_callback' => array( $this, 'render_block' ),
                'attributes'      => array(
                    'mode'  => array(
                        'type'    => 'string',
                        'default' => 'ambos',
                    ),
                    'title' => array(
                        'type'    => 'string',
                        'default' => '',
                    ),
                    'data'  => array(
                        'type' => 'object',
                    ),
                ),
            )
        );
    }

    /**
     * Render block.
     *
     * @param array $attributes Attributes.
     *
     * @return string
     */
    public function render_block( $attributes ) {
        $mode  = isset( $attributes['mode'] ) ? $attributes['mode'] : 'ambos';
        $title = isset( $attributes['title'] ) ? $attributes['title'] : '';
        $data  = ! empty( $attributes['data'] ) ? wp_json_encode( $attributes['data'] ) : '';

        $shortcode = sprintf(
            '[questoes modo="%1$s" titulo="%2$s"]%3$s[/questoes]',
            esc_attr( $mode ),
            esc_attr( $title ),
            $data
        );

        return do_shortcode( $shortcode );
    }
}
