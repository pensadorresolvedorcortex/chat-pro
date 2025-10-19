<?php
/**
 * JSON schema validation for Questões plugin.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Schema validator.
 */
class Questoes_Schema {

    /**
     * Sanitize raw JSON string.
     *
     * @param string $raw Raw JSON.
     *
     * @return string
     */
    public static function sanitize_json( $raw ) {
        if ( empty( $raw ) ) {
            return '';
        }

        $raw = wp_check_invalid_utf8( $raw );
        $raw = wp_strip_all_tags( $raw );

        return trim( $raw );
    }

    /**
     * Validate decoded data against schema.
     *
     * @param array $data Decoded data.
     *
     * @return WP_Error|true
     */
    public static function validate( $data ) {
        if ( empty( $data ) || ! is_array( $data ) ) {
            return new WP_Error( 'questoes_invalid_data', __( 'JSON inválido: verifique vírgulas e chaves.', 'questoes' ) );
        }

        if ( isset( $data['mindmap'] ) ) {
            $mind = self::validate_node( $data['mindmap'], 'mindmap', 0 );
            if ( is_wp_error( $mind ) ) {
                return $mind;
            }
        }

        if ( isset( $data['orgchart'] ) ) {
            $org = self::validate_node( $data['orgchart'], 'orgchart', 0 );
            if ( is_wp_error( $org ) ) {
                return $org;
            }
        }

        return true;
    }

    /**
     * Validate node recursively.
     *
     * @param array  $node Node data.
     * @param string $root Root key.
     * @param int    $depth Depth level.
     *
     * @return WP_Error|true
     */
    protected static function validate_node( $node, $root, $depth ) {
        if ( $depth > 6 ) {
            return new WP_Error( 'questoes_invalid_depth', __( 'Profundidade máxima recomendada excedida.', 'questoes' ) );
        }

        if ( empty( $node['id'] ) || ! is_string( $node['id'] ) ) {
            return new WP_Error( 'questoes_missing_id', __( 'Cada nó deve ter um ID exclusivo.', 'questoes' ) );
        }

        if ( empty( $node['label'] ) || ! is_string( $node['label'] ) ) {
            return new WP_Error( 'questoes_missing_label', __( 'Cada nó deve ter um rótulo.', 'questoes' ) );
        }

        if ( mb_strlen( $node['label'] ) > 60 ) {
            return new WP_Error( 'questoes_label_too_long', __( 'Os rótulos devem ter no máximo 60 caracteres.', 'questoes' ) );
        }

        if ( ! empty( $node['children'] ) && is_array( $node['children'] ) ) {
            foreach ( $node['children'] as $child ) {
                $child_validation = self::validate_node( $child, $root, $depth + 1 );
                if ( is_wp_error( $child_validation ) ) {
                    return $child_validation;
                }
            }
        }

        return true;
    }
}
