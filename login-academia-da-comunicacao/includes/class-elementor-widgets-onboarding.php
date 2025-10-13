<?php
/**
 * Elementor widget for the onboarding flow.
 *
 * @package ADC\Login\Elementor
 */

namespace ADC\Login\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

use function ADC\Login\get_asset_url;
use function do_shortcode;
use function sanitize_text_field;
use function wp_strip_all_tags;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Onboarding widget definition.
 */
class Onboarding extends Base {

    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name() {
        return 'adc-onboarding';
    }

    /**
     * Visible title inside Elementor.
     *
     * @return string
     */
    public function get_title() {
        return __( 'Onboarding ADC', 'login-academia-da-comunicacao' );
    }

    /**
     * Icon shown in Elementor panel.
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-slides';
    }

    /**
     * Categories assigned to the widget.
     *
     * @return array
     */
    public function get_categories() {
        return array( 'adc-login' );
    }

    /**
     * Helpful search keywords.
     *
     * @return array
     */
    public function get_keywords() {
        return array( 'login', 'onboarding', 'carousel', 'academia' );
    }

    /**
     * Register widget controls.
     */
    protected function register_controls() {
        $this->start_controls_section(
            'section_slides',
            array(
                'label' => __( 'Slides', 'login-academia-da-comunicacao' ),
            )
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'slide_title',
            array(
                'label'       => __( 'Título', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Novo slide', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $repeater->add_control(
            'slide_text',
            array(
                'label'       => __( 'Descrição', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXTAREA,
                'default'     => __( 'Adicione uma descrição envolvente para o slide.', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $repeater->add_control(
            'slide_cta',
            array(
                'label'       => __( 'Rótulo do CTA', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => '',
                'label_block' => true,
                'description' => __( 'Opcional — utilize apenas no último slide.', 'login-academia-da-comunicacao' ),
            )
        );

        $repeater->add_control(
            'slide_image',
            array(
                'label'   => __( 'Ilustração', 'login-academia-da-comunicacao' ),
                'type'    => Controls_Manager::MEDIA,
                'default' => array(
                    'url' => get_asset_url( 'assets/img/onboarding-quiz.svg' ),
                ),
            )
        );

        $repeater->add_control(
            'slide_alt',
            array(
                'label'       => __( 'Texto alternativo', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Descrição da ilustração do slide.', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'slides',
            array(
                'label'       => __( 'Slides', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => array(
                    array(
                        'slide_title' => __( 'Quiz On the Go', 'login-academia-da-comunicacao' ),
                        'slide_text'  => __( 'Responda quizzes rápidos onde estiver e mantenha sua mente afiada.', 'login-academia-da-comunicacao' ),
                        'slide_image' => array( 'url' => get_asset_url( 'assets/img/onboarding-quiz.svg' ) ),
                        'slide_alt'   => __( 'Ilustração de uma pessoa respondendo a um quiz no celular.', 'login-academia-da-comunicacao' ),
                    ),
                    array(
                        'slide_title' => __( 'Knowledge Boosting', 'login-academia-da-comunicacao' ),
                        'slide_text'  => __( 'Descubra conteúdos envolventes para turbinar seus estudos diariamente.', 'login-academia-da-comunicacao' ),
                        'slide_image' => array( 'url' => get_asset_url( 'assets/img/onboarding-knowledge.svg' ) ),
                        'slide_alt'   => __( 'Ilustração abstrata representando crescimento de conhecimento.', 'login-academia-da-comunicacao' ),
                    ),
                    array(
                        'slide_title' => __( 'Win Rewards Galore', 'login-academia-da-comunicacao' ),
                        'slide_text'  => __( 'Ganhe recompensas enquanto aprende com desafios pensados para você.', 'login-academia-da-comunicacao' ),
                        'slide_cta'   => __( 'Get Started', 'login-academia-da-comunicacao' ),
                        'slide_image' => array( 'url' => get_asset_url( 'assets/img/onboarding-rewards.svg' ) ),
                        'slide_alt'   => __( 'Ilustração com medalhas e troféus simbolizando recompensas.', 'login-academia-da-comunicacao' ),
                    ),
                ),
                'title_field' => '{{{ slide_title }}}',
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_labels',
            array(
                'label' => __( 'Textos complementares', 'login-academia-da-comunicacao' ),
            )
        );

        $this->add_control(
            'brand_label',
            array(
                'label'       => __( 'Nome da marca', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Academia da Comunicação', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'skip_label',
            array(
                'label'       => __( 'Rótulo do botão Pular', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Pular', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'login_link_label',
            array(
                'label'       => __( 'Texto do link de login', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Já tem conta? Faça login', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'signup_link_label',
            array(
                'label'       => __( 'Texto do link de cadastro', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Nova por aqui? Crie sua conta', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output.
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        $slides   = array();

        if ( ! empty( $settings['slides'] ) && is_array( $settings['slides'] ) ) {
            foreach ( $settings['slides'] as $slide ) {
                $title = isset( $slide['slide_title'] ) ? sanitize_text_field( $slide['slide_title'] ) : '';
                $text  = isset( $slide['slide_text'] ) ? wp_strip_all_tags( $slide['slide_text'] ) : '';
                $cta   = isset( $slide['slide_cta'] ) ? sanitize_text_field( $slide['slide_cta'] ) : '';
                $alt   = isset( $slide['slide_alt'] ) ? sanitize_text_field( $slide['slide_alt'] ) : '';
                $url   = $this->parse_media_url( isset( $slide['slide_image'] ) ? $slide['slide_image'] : array(), get_asset_url( 'assets/img/onboarding-quiz.svg' ) );

                $slide_data = array(
                    'title' => $title,
                    'text'  => $text,
                );

                if ( ! empty( $cta ) ) {
                    $slide_data['cta'] = $cta;
                }

                if ( ! empty( $url ) ) {
                    $slide_data['image'] = array(
                        'src' => $url,
                        'alt' => $alt,
                    );
                }

                $slides[] = $slide_data;
            }
        }

        $brand_label       = isset( $settings['brand_label'] ) ? sanitize_text_field( $settings['brand_label'] ) : '';
        $skip_label        = isset( $settings['skip_label'] ) ? sanitize_text_field( $settings['skip_label'] ) : '';
        $login_link_label  = isset( $settings['login_link_label'] ) ? sanitize_text_field( $settings['login_link_label'] ) : '';
        $signup_link_label = isset( $settings['signup_link_label'] ) ? sanitize_text_field( $settings['signup_link_label'] ) : '';

        $filters = array();

        if ( ! empty( $slides ) ) {
            $filters['adc_login_onboarding_slides'] = function () use ( $slides ) {
                return $slides;
            };
        }

        $filters['adc_login_onboarding_brand_label']       = function () use ( $brand_label ) {
            return $brand_label;
        };
        $filters['adc_login_onboarding_skip_label']        = function () use ( $skip_label ) {
            return $skip_label;
        };
        $filters['adc_login_onboarding_login_link_label']  = function () use ( $login_link_label ) {
            return $login_link_label;
        };
        $filters['adc_login_onboarding_signup_link_label'] = function () use ( $signup_link_label ) {
            return $signup_link_label;
        };

        $this->render_with_filters(
            'adc_onboarding',
            function () {
                return do_shortcode( '[adc_onboarding]' );
            },
            $filters
        );
    }
}
