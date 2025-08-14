<?php
/**
 * Elementor widget to display portfolio items.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Housi_Portfolio_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'housi_portfolio';
    }

    public function get_title() {
        return __( 'Housi Portfolio', 'housi-portfolio' );
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    protected function register_controls() {
        $this->start_controls_section( 'content_section', [
            'label' => __( 'Content', 'housi-portfolio' ),
        ] );

        $this->add_control( 'posts_per_page', [
            'label' => __( 'Posts Per Page', 'housi-portfolio' ),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 6,
        ] );

        $this->add_control( 'columns', [
            'label' => __( 'Columns', 'housi-portfolio' ),
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => [
                '2' => 2,
                '3' => 3,
                '4' => 4,
            ],
            'default' => '3',
        ] );

        $this->add_control( 'project_cat', [
            'label' => __( 'Categories', 'housi-portfolio' ),
            'type' => \Elementor\Controls_Manager::SELECT2,
            'options' => $this->get_categories_options(),
            'multiple' => true,
            'label_block' => true,
        ] );

        $this->end_controls_section();
    }

    /**
     * Retrieve portfolio categories for select options.
     */
    private function get_categories_options() {
        $terms = get_terms( [
            'taxonomy'   => 'project-cat',
            'hide_empty' => false,
        ] );

        $options = [];
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $options[ $term->slug ] = $term->name;
            }
        }

        return $options;
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $args = [
            'post_type'      => 'portfolio',
            'posts_per_page' => $settings['posts_per_page'],
        ];

        if ( ! empty( $settings['project_cat'] ) ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'project-cat',
                    'field'    => 'slug',
                    'terms'    => $settings['project_cat'],
                ],
            ];
        }

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            wp_enqueue_style( 'housi-portfolio' );
            wp_enqueue_script( 'housi-portfolio' );

            $filter_terms = empty( $settings['project_cat'] ) ? get_terms( [
                'taxonomy'   => 'project-cat',
                'hide_empty' => true,
            ] ) : get_terms( [
                'taxonomy'   => 'project-cat',
                'hide_empty' => true,
                'slug'       => $settings['project_cat'],
            ] );

            if ( ! is_wp_error( $filter_terms ) && ! empty( $filter_terms ) ) {
                echo '<div class="housi-portfolio-filters">';
                echo '<button class="active" data-term="all">' . esc_html__( 'Mostrar Todos', 'housi-portfolio' ) . '</button>';
                foreach ( $filter_terms as $term ) {
                    echo '<button data-term="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</button>';
                }
                echo '</div>';
            }

            echo '<div class="housi-portfolio-grid columns-' . esc_attr( $settings['columns'] ) . '">';
            while ( $query->have_posts() ) {
                $query->the_post();
                $terms        = get_the_terms( get_the_ID(), 'project-cat' );
                $term_classes = '';
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                    foreach ( $terms as $term ) {
                        $term_classes .= ' term-' . $term->slug;
                    }
                }
                echo '<div class="housi-portfolio-item' . esc_attr( $term_classes ) . '">';
                if ( has_post_thumbnail() ) {
                    echo '<div class="housi-portfolio-thumb"><a href="' . esc_url( get_permalink() ) . '">';
                    the_post_thumbnail( 'large' );
                    echo '</a></div>';
                    echo '<h3 class="housi-portfolio-title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';
                } else {
                    echo '<h3 class="housi-portfolio-title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';
                }
                echo '</div>';
            }
            echo '</div>';
            wp_reset_postdata();
        }
    }
}

