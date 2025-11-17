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

    class WPS_Welcome_Widget extends Widget_Base {

        public function get_name() {
            return 'wps_welcome_widget';
        }

        public function get_title() {
            return esc_html__( 'Welcome user message', 'wps' );
        }

        public function get_icon() {
            return 'eicon-person';
        }

        public function get_categories() {
            return [ 'general' ];
        }

        protected function register_controls() {

            $this->start_controls_section(
                'acm_content',
                [
                    'label' => esc_html__('Content', 'wps'),
                    'tab' => Controls_Manager::TAB_CONTENT,
                ]
            );

            $this->add_control(
                'before_text',
                [
                    'label'       => esc_html__( 'Text Before Name', 'wps' ),
                    'type'        => Controls_Manager::TEXT,
                    'default'     => esc_html__( 'Hello, ', 'wps' ),
                    'placeholder' => esc_html__( 'Enter text before name', 'wps' ),
                ]
            );

            $this->add_control(
                'after_text',
                [
                    'label'       => esc_html__( 'Text After Name', 'wps' ),
                    'type'        => Controls_Manager::TEXT,
                    'default'     => esc_html__( ' — Welcome back!', 'wps' ),
                    'placeholder' => esc_html__( 'Enter text after name', 'wps' ),
                ]
            );

            $this->end_controls_section();

            $this->start_controls_section(
                'acm_heading_styles',
                [
                    'label'         => esc_html__('Heading', 'wps'),
                    'tab'           => Controls_Manager::TAB_STYLE,
                ]
            );

            $this->add_control(
            'text_color',
            [
                'label'     => __( 'Text Color', 'wps' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wps-current-user' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .wps-current-user a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'typography',
                'selector' => '{{WRAPPER}} .wps-current-user',
            ]
        );

            $this->end_controls_section();

        }

        protected function render() {

            $settings = $this->get_settings_for_display();

            $current_user = wp_get_current_user();

            if ( $current_user->exists() ) {
                $first_name = get_user_meta( $current_user->ID, 'first_name', true );
                $last_name  = get_user_meta( $current_user->ID, 'last_name', true );
                // Fallback if first and last name are empty
                if ( empty( $first_name ) && empty( $last_name ) ) {
                    $full_name = $current_user->display_name;
                } else {
                    $full_name = trim( $first_name . ' ' . $last_name );
                }

                $before_text = !empty($settings['before_text']) ? $settings['before_text'] : '';
                $after_text = !empty($settings['after_text']) ? $settings['after_text'] : '';

                echo '<div class="wps-current-user">';
                echo esc_html( $before_text ) . esc_html( $full_name ) . esc_html( $after_text );
                echo '</div>';

            }

        }
    }

    Plugin::instance()->widgets_manager->register_widget_type( new WPS_Welcome_Widget() );

}
