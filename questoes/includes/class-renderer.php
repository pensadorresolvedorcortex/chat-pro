<?php
/**
 * Render data helpers.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Questoes_Renderer {

    protected $settings;

    public function __construct( Questoes_Settings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Prepare data using configured source.
     *
     * @param string $mode Mode.
     *
     * @return array
     */
    public function get_data( $mode = 'both' ) {
        $raw = $this->load_data();

        if ( empty( $raw ) ) {
            return array();
        }

        return $this->prepare_from_array( $raw, $mode );
    }

    /**
     * Prepare structured data from array.
     *
     * @param array  $raw Raw array.
     * @param string $mode Mode.
     *
     * @return array
     */
    public function prepare_from_array( $raw, $mode = 'both' ) {
        $data = array();

        if ( in_array( $mode, array( 'mindmap', 'mapa', 'both', 'ambos' ), true ) && isset( $raw['mindmap'] ) ) {
            $data['mindmap'] = $this->normalize_nodes( $raw['mindmap'] );
        }

        if ( in_array( $mode, array( 'orgchart', 'organograma', 'both', 'ambos' ), true ) && isset( $raw['orgchart'] ) ) {
            $data['orgchart'] = $this->normalize_nodes( $raw['orgchart'] );
        }

        $data['title'] = isset( $raw['title'] ) ? sanitize_text_field( $raw['title'] ) : $this->settings->get( 'title' );

        return $data;
    }

    protected function normalize_nodes( $node, $level = 0 ) {
        $normalized = array(
            'id'       => sanitize_key( $node['id'] ),
            'label'    => sanitize_text_field( $node['label'] ),
            'meta'     => isset( $node['meta'] ) ? array_map( 'sanitize_text_field', (array) $node['meta'] ) : array(),
            'level'    => $level,
            'children' => array(),
        );

        if ( ! empty( $node['children'] ) && is_array( $node['children'] ) ) {
            foreach ( $node['children'] as $child ) {
                $normalized['children'][] = $this->normalize_nodes( $child, $level + 1 );
            }
        }

        return $normalized;
    }

    protected function load_data() {
        $source = $this->settings->get( 'data_source' );

        if ( 'endpoint' === $source ) {
            $endpoint = $this->settings->get( 'data_endpoint' );
            if ( empty( $endpoint ) ) {
                return array();
            }

            $response = wp_remote_get( $endpoint, array( 'timeout' => 10 ) );
            if ( is_wp_error( $response ) ) {
                return array();
            }

            $body = wp_remote_retrieve_body( $response );
            $body = Questoes_Schema::sanitize_json( $body );
            $data = json_decode( $body, true );
        } else {
            $stored = $this->settings->get( 'data' );
            $data   = json_decode( $stored, true );
        }

        if ( empty( $data ) ) {
            return array();
        }

        $validation = Questoes_Schema::validate( $data );
        if ( is_wp_error( $validation ) ) {
            return array();
        }

        return $data;
    }
}
