<?php
/**
 * Register portfolio post type and taxonomy.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Housi_Portfolio_CPT {

    public function __construct() {
        add_action( 'init', [ __CLASS__, 'register_cpt' ] );
        add_action( 'init', [ __CLASS__, 'register_taxonomy' ] );
    }

    /**
     * Register the portfolio custom post type.
     */
    public static function register_cpt() {
        $labels = [
            'name'               => __( 'Portfolios', 'housi-portfolio' ),
            'singular_name'      => __( 'Portfolio', 'housi-portfolio' ),
            'add_new'            => __( 'Add New', 'housi-portfolio' ),
            'add_new_item'       => __( 'Add New Portfolio', 'housi-portfolio' ),
            'edit_item'          => __( 'Edit Portfolio', 'housi-portfolio' ),
            'new_item'           => __( 'New Portfolio', 'housi-portfolio' ),
            'view_item'          => __( 'View Portfolio', 'housi-portfolio' ),
            'search_items'       => __( 'Search Portfolios', 'housi-portfolio' ),
            'not_found'          => __( 'No portfolios found', 'housi-portfolio' ),
            'not_found_in_trash' => __( 'No portfolios found in Trash', 'housi-portfolio' ),
        ];

        $args = [
            'labels'       => $labels,
            'public'       => true,
            'has_archive'  => true,
            'show_in_rest' => true,
            'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
            'rewrite'      => [ 'slug' => 'portfolio' ],
        ];

        register_post_type( 'portfolio', $args );
    }

    /**
     * Register the project-cat taxonomy.
     */
    public static function register_taxonomy() {
        $labels = [
            'name'          => __( 'Project Categories', 'housi-portfolio' ),
            'singular_name' => __( 'Project Category', 'housi-portfolio' ),
        ];

        $args = [
            'labels'       => $labels,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite'      => [ 'slug' => 'project-cat' ],
        ];

        register_taxonomy( 'project-cat', 'portfolio', $args );
    }
}

new Housi_Portfolio_CPT();

