<?php
/**
 * Accessibility helpers.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Accessibility class.
 */
class Questoes_Accessibility {

    /**
     * Build aria label for node.
     *
     * @param array $node Node data.
     *
     * @return string
     */
    public function get_node_label( $node ) {
        $level = isset( $node['level'] ) ? intval( $node['level'] ) + 1 : 1;
        $label = isset( $node['label'] ) ? $node['label'] : '';

        return sprintf(
            /* translators: 1: node label 2: hierarchy level */
            __( 'Nó: %1$s – nível %2$d', 'questoes' ),
            $label,
            $level
        );
    }

    /**
     * Get aria attributes for container.
     *
     * @param string $type Component type.
     *
     * @return array
     */
    public function get_container_attrs( $type ) {
        $labels = array(
            'mindmap'   => __( 'Mapa Mental Questões', 'questoes' ),
            'orgchart'  => __( 'Organograma Questões', 'questoes' ),
            'tabs'      => __( 'Alternância de visualizações', 'questoes' ),
        );

        return array(
            'role'        => 'region',
            'tabindex'    => '0',
            'aria-label'  => isset( $labels[ $type ] ) ? $labels[ $type ] : __( 'Mapa Questões', 'questoes' ),
        );
    }
}
