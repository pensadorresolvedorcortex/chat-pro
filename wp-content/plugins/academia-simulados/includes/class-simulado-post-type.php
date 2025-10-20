<?php
/**
 * Register custom post type for simulados.
 *
 * @package Academia_Simulados
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Academia_Simulados_Post_Type {
    /**
     * Initialise hooks.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
    }

    /**
     * Register the Simulado post type.
     */
    public static function register_post_type() {
        $labels = array(
            'name'               => __( 'Simulados', 'academia-simulados' ),
            'singular_name'      => __( 'Simulado', 'academia-simulados' ),
            'menu_name'          => __( 'Simulados', 'academia-simulados' ),
            'add_new'            => __( 'Adicionar novo', 'academia-simulados' ),
            'add_new_item'       => __( 'Adicionar novo simulado', 'academia-simulados' ),
            'edit_item'          => __( 'Editar simulado', 'academia-simulados' ),
            'new_item'           => __( 'Novo simulado', 'academia-simulados' ),
            'view_item'          => __( 'Ver simulado', 'academia-simulados' ),
            'search_items'       => __( 'Pesquisar simulados', 'academia-simulados' ),
            'not_found'          => __( 'Nenhum simulado encontrado', 'academia-simulados' ),
            'not_found_in_trash' => __( 'Nenhum simulado na lixeira', 'academia-simulados' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'show_in_menu'       => true,
            'menu_icon'          => 'dashicons-welcome-learn-more',
            'supports'           => array( 'title', 'editor', 'comments' ),
            'has_archive'        => true,
            'rewrite'            => array(
                'slug'       => 'simulados',
                'with_front' => false,
            ),
            'show_in_rest'       => true,
        );

        register_post_type( 'simulado', $args );
    }
}
