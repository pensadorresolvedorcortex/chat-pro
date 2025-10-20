<?php
/**
 * Settings handler.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings class.
 */
class Questoes_Settings {

    /**
     * Option name.
     *
     * @var string
     */
    protected $option_name = 'questoes_settings';

    /**
     * Cached settings.
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Constructor.
     */
    public function __construct() {
        $this->settings = get_option( $this->option_name, $this->get_defaults() );
        add_action( 'admin_init', array( $this, 'register' ) );
    }

    /**
     * Register settings.
     */
    public function register() {
        register_setting( 'questoes_settings', $this->option_name, array( $this, 'sanitize' ) );
    }

    /**
     * Sanitize and validate settings.
     *
     * @param array $input Raw input.
     *
     * @return array
     */
    public function sanitize( $input ) {
        $defaults = $this->get_defaults();
        $output   = wp_parse_args( $input, $defaults );

        $output['title']           = sanitize_text_field( $output['title'] );
        $output['allow_palette']   = ! empty( $output['allow_palette'] ) ? 1 : 0;
        $output['data_source']     = sanitize_text_field( $output['data_source'] );
        if ( ! in_array( $output['data_source'], array( 'stored', 'endpoint' ), true ) ) {
            $output['data_source'] = 'stored';
        }
        $output['data_endpoint']   = esc_url_raw( $output['data_endpoint'] );
        $output['comments_enabled'] = ! empty( $output['comments_enabled'] ) ? 1 : 0;

        if ( ! $output['allow_palette'] ) {
            $output['palette'] = questoes_get_default_palette();
        } elseif ( empty( $output['palette'] ) || ! is_array( $output['palette'] ) ) {
            $output['palette'] = questoes_get_default_palette();
        } else {
            $palette = array();
            foreach ( questoes_get_default_palette() as $key => $default_color ) {
                $palette[ $key ] = isset( $output['palette'][ $key ] ) ? sanitize_hex_color( $output['palette'][ $key ] ) : $default_color;
            }
            $output['palette'] = $palette;
        }

        $raw_json = isset( $output['data'] ) ? $output['data'] : '';
        $clean    = Questoes_Schema::sanitize_json( $raw_json );
        $decoded  = json_decode( $clean, true );

        if ( ! empty( $clean ) ) {
            $validation = Questoes_Schema::validate( $decoded );
            if ( is_wp_error( $validation ) ) {
                add_settings_error( 'questoes_settings', 'questoes_settings_error', $validation->get_error_message(), 'error' );
                $output['data'] = $this->get( 'data' );
            } else {
                $output['data'] = wp_json_encode( $decoded );
            }
        } else {
            $output['data'] = '';
        }

        return $output;
    }

    /**
     * Get option.
     *
     * @param string $key Option key.
     *
     * @return mixed
     */
    public function get( $key ) {
        if ( isset( $this->settings[ $key ] ) ) {
            return $this->settings[ $key ];
        }

        $defaults = $this->get_defaults();

        return isset( $defaults[ $key ] ) ? $defaults[ $key ] : null;
    }

    /**
     * Update option value.
     *
     * @param string $key Option key.
     * @param mixed  $value Value.
     */
    public function update( $key, $value ) {
        $this->settings[ $key ] = $value;
        update_option( $this->option_name, $this->settings );
    }

    /**
     * Retrieve all settings.
     *
     * @return array
     */
    public function all() {
        return $this->settings;
    }

    /**
     * Defaults.
     *
     * @return array
     */
    protected function get_defaults() {
        return array(
            'title'             => __( 'Questões — Academia da Comunicação', 'questoes' ),
            'palette'           => questoes_get_default_palette(),
            'allow_palette'     => 0,
            'data_source'       => 'stored',
            'data'              => '',
            'data_endpoint'     => '',
            'comments_enabled'  => 1,
        );
    }
}
