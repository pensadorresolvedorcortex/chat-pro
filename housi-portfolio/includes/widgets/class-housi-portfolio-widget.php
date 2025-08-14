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
            echo '<div class="housi-portfolio-grid columns-' . esc_attr( $settings['columns'] ) . '">';
            while ( $query->have_posts() ) {
                $query->the_post();
                echo '<div class="housi-portfolio-item">';
                if ( has_post_thumbnail() ) {
                    echo '<div class="housi-portfolio-thumb"><a href="' . esc_url( get_permalink() ) . '">';
                    the_post_thumbnail( 'medium' );
                    echo '</a></div>';
                }
                echo '<h3 class="housi-portfolio-title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';
                echo '</div>';
            }
            echo '</div>';
            wp_reset_postdata();
        }
    }
}

