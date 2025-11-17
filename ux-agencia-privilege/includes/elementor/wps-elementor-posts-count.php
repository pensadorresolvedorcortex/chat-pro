<?php
/*
 * WPSHAPERE
 * @author   AcmeeDesign
 * @url     https://acmeedesign.com
*/

namespace Elementor;

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;
use \Elementor\Core\Kits\Documents\Tabs\Global_Typography;

defined('ABSPATH') || die;

/**
 * Register Elementor Widget
 */
if ( did_action( 'elementor/loaded' ) ) {

    class WPS_Count_Widget extends Widget_Base {

        public function get_name() {
            return 'wps_count_widget';
        }

        public function get_title() {
            return esc_html__( 'Admin Post Counts', 'wps' );
        }

        public function get_icon() {
            return 'eicon-number-field';
        }

        public function get_categories() {
            return [ 'general' ];
        }

        protected function register_controls() {

            $this->start_controls_section(
                'acm_heading_styles',
                [
                    'label'         => esc_html__('Heading', 'wps'),
                    'tab'           => Controls_Manager::TAB_STYLE,
                ]
            );

            $this->add_responsive_control(
                'acm_text_alignment',
                [
                    'label' 	  => esc_html__('Alignment', 'wps'),
                    'type'		  => Controls_Manager::CHOOSE,
                    'options' => [
                        'left' => [
                            'title' => __( 'Left', 'wps' ),
                            'icon' => 'eicon-text-align-left',
                        ],
                        'center' => [
                            'title' => __( 'Center', 'wps' ),
                            'icon' => 'eicon-text-align-center',
                        ],
                        'right' => [
                            'title' => __( 'Right', 'wps' ),
                            'icon' => 'eicon-text-align-right',
                        ],

                    ],
                    'default' => 'center',
                    'separator' => 'before',
                    'toggle' => true,
                    'selectors' => [
                        '{{WRAPPER}} .wpshapere-count-widget .wps-count-content' => 'text-align: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'acm_heading_text_color',
                [
                    'label'     => esc_html__( 'Heading Text color', 'wps' ),
                    'type'      => Controls_Manager::COLOR,
            'default' 		=> '#1b202a',
                    'selectors' => [
                        '{{WRAPPER}} .wpshapere-count-widget .acm-title' => 'color: {{VALUE}}',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'acm_heading_typography',
                    'label' => __( 'Heading Typography', 'wps' ),
                    'global' => [
                        'default' => Global_Typography::TYPOGRAPHY_PRIMARY
                    ],
                    'selector' => '{{WRAPPER}} .wpshapere-count-widget .acm-title',
                ]
            );

            $this->add_control(
                'acm_count_color',
                [
                    'label'     => esc_html__( 'Count color', 'wps' ),
                    'type'      => Controls_Manager::COLOR,
                    'default' 		=> '#1b202a',
                    'selectors' => [
                        '{{WRAPPER}} .wpshapere-count-widget .wps-count' => 'color: {{VALUE}}',
                    ],
                    'separator'    => 'before',
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'acm_count_typography',
                    'label' => __( 'Count Typography', 'wps' ),
                    'global' => [
                        'default' => Global_Typography::TYPOGRAPHY_PRIMARY
                    ],
                    'selector' => '{{WRAPPER}} .wpshapere-count-widget .wps-count',
                    'separator' => 'after',
                ]
            );

            $this->add_control(
                'acm_link_bg_color',
                [
                    'label'     => esc_html__( 'Link background color', 'wps' ),
                    'type'      => Controls_Manager::COLOR,
                    'default' 		=> '#ffffff',
                    'selectors' => [
                        '{{WRAPPER}} .wpshapere-count-widget a' => 'background-color: {{VALUE}}',
                    ],
                    'separator'    => 'before',
                ]
            );

            $this->add_control(
                'acm_link_bg_hover_color',
                [
                    'label'     => esc_html__( 'Link hover background color', 'wps' ),
                    'type'      => Controls_Manager::COLOR,
                    'default' 		=> '#ffffff',
                    'selectors' => [
                        '{{WRAPPER}} .wpshapere-count-widget a:hover' => 'background-color: {{VALUE}}',
                    ],
                ]
            );

            $this->add_control(
                'acm_link_text_color',
                [
                    'label'     => esc_html__( 'Link color', 'wps' ),
                    'type'      => Controls_Manager::COLOR,
                    'default' 		=> '#214992',
                    'selectors' => [
                        '{{WRAPPER}} .wpshapere-count-widget a' => 'color: {{VALUE}}',
                    ],
                ]
            );

            $this->add_control(
                'acm_link_hover_color',
                [
                    'label'     => esc_html__( 'Link hover color', 'wps' ),
                    'type'      => Controls_Manager::COLOR,
                    'default' 		=> '#1c2432',
                    'selectors' => [
                        '{{WRAPPER}} .wpshapere-count-widget a:hover' => 'color: {{VALUE}}',
                    ],
                ]
            );

            $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                    'name' => 'acm_link_typography',
                    'label' => __( 'Link Typography', 'wps' ),
                    'global' => [
                        'default' => Global_Typography::TYPOGRAPHY_PRIMARY
                    ],
                    'selector' => '{{WRAPPER}} .wpshapere-count-widget a',
                ]
            );

            $this->add_control(
                'acm_enable_border',
                    [
                        'label'        => esc_html__( 'Show borders', 'wps' ),
                        'type'         => Controls_Manager::SWITCHER,
                        'label_on'     => esc_html__( 'Yes', 'wps' ),
                        'label_off'    => esc_html__( 'No', 'wps' ),
                        'return_value' => 'true',
                        'default'      => 'true',
                        'separator'    => 'before',
                    ]
            );

            $this->add_control(
                'acm_border_color',
                [
                    'label'     => esc_html__( 'Border color', 'wps' ),
                    'type'      => Controls_Manager::COLOR,
                    'default' 		=> '#d5d5d5',
                    'selectors' => [
                        '{{WRAPPER}} .wpshapere-count-widget .wps-count-content.count-border' => 'border-color: {{VALUE}}',
                    ],
                ]
            );

            $this->end_controls_section();

        }

        protected function render() {

            $settings = $this->get_settings_for_display();

            $posts_count   = wp_count_posts( 'post' )->publish;
            $pages_count   = wp_count_posts( 'page' )->publish;
            $products_count = class_exists( 'WooCommerce' ) ? wp_count_posts( 'product' )->publish : 0;
            ?>

            <div class="wpshapere-count-widget" style="display:flex; gap:20px; flex-wrap:nowrap;">
                <div class="wps-count-content count-border wps-post-count" style="min-width:180px;">
                    <span class="wps-count"><?php echo esc_html( $posts_count ); ?></span>
                    <h3 style="margin: 5px; 0" class="acm-title"><?php echo esc_html__( 'Total blog posts', 'wps' ); ?></h3>
                    <a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?> '"><?php echo esc_html__( 'Add new', 'wps' ); ?></a>
                </div>
                <div class="wps-count-content count-border wps-page-count" style="min-width:180px;">
                    <span class="wps-count"><?php echo esc_html( $pages_count ); ?></span>
                    <h3 style="margin: 5px; 0" class="acm-title"><?php echo esc_html__( 'Total pages', 'wps' ); ?></h3> 
                    <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?> '"><?php echo esc_html__( 'Add new page', 'wps' ); ?></a>
                </div>

            <?php if ( class_exists( 'WooCommerce' ) ) { ?>
                <div class="wps-count-content wps-woo-product-count" style="min-width:180px;">
                    <span class="wps-count"><?php echo esc_html( $products_count ); ?></span>
                    <h3 style="margin: 5px; 0" class="acm-title"><?php echo esc_html__( 'Total Products', 'wps' ); ?></h3> 
                    <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product' ) ); ?>"><?php echo esc_html__( 'Add new product', 'wps' ); ?></a>
                </div>
           <?php } ?>
            </div>
            <?php if( !empty( $settings['acm_enable_border'] ) ) : ?>
                <style>.wpshapere-count-widget .wps-count-content:not(:last-child) {border-right:1px solid #f5f5f5}
                    .wpshapere-count-widget a {padding:3px 7px;border-radius:3px;transition: background-color 0.2s ease-in-out;}
                    .wps-count{text-align:center;border: 2px solid;border-radius: 100%;    width: 50px;height: 50px;line-height:50px;display: inline-block;}
                </style>
            <?php endif; ?>
        <?php 
        }
    }

    Plugin::instance()->widgets_manager->register_widget_type( new WPS_Count_Widget() );

}
