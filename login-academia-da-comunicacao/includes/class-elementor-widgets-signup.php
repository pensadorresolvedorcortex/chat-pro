<?php
/**
 * Elementor widget for the signup form.
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
 * Signup widget definition.
 */
class Signup extends Base {

    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name() {
        return 'adc-signup-form';
    }

    /**
     * Visible title inside Elementor.
     *
     * @return string
     */
    public function get_title() {
        return __( 'Formulário de Cadastro ADC', 'login-academia-da-comunicacao' );
    }

    /**
     * Icon displayed in Elementor sidebar.
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-user-circle-o';
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
     * Register editable controls.
     */
    protected function register_controls() {
        $this->start_controls_section(
            'section_hero',
            array(
                'label' => __( 'Hero', 'login-academia-da-comunicacao' ),
            )
        );

        $this->add_control(
            'badge',
            array(
                'label'       => __( 'Selo', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Jornada personalizada', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'headline',
            array(
                'label'       => __( 'Título principal', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Crie sua conta e desbloqueie o melhor da comunicação.', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'subtitle',
            array(
                'label'       => __( 'Descrição', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXTAREA,
                'default'     => __( 'Domine apresentações, storytelling e técnicas de influência com roteiros feitos para você.', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $repeater = new Repeater();
        $repeater->add_control(
            'feature_text',
            array(
                'label'       => __( 'Benefício', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Aulas ao vivo e gravadas em um só lugar.', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'features',
            array(
                'label'       => __( 'Benefícios', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => array(
                    array( 'feature_text' => __( 'Aulas ao vivo e gravadas em um só lugar.', 'login-academia-da-comunicacao' ) ),
                    array( 'feature_text' => __( 'Metas semanais para manter sua motivação.', 'login-academia-da-comunicacao' ) ),
                    array( 'feature_text' => __( 'Suporte da comunidade e mentores certificados.', 'login-academia-da-comunicacao' ) ),
                ),
                'title_field' => '{{{ feature_text }}}',
            )
        );

        $this->add_control(
            'illustration',
            array(
                'label'   => __( 'Ilustração', 'login-academia-da-comunicacao' ),
                'type'    => Controls_Manager::MEDIA,
                'default' => array(
                    'url' => get_asset_url( 'assets/img/auth-illustration.svg' ),
                ),
            )
        );

        $this->add_control(
            'illustration_alt',
            array(
                'label'       => __( 'Texto alternativo da imagem', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Pessoas conectadas celebrando conquistas de aprendizagem.', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_form',
            array(
                'label' => __( 'Formulário', 'login-academia-da-comunicacao' ),
            )
        );

        $this->add_control(
            'card_kicker',
            array(
                'label'       => __( 'Chamada superior', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Vamos começar', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'card_title',
            array(
                'label'       => __( 'Título do cartão', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Criar conta', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'card_description',
            array(
                'label'       => __( 'Descrição do cartão', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXTAREA,
                'default'     => __( 'Complete seus dados para personalizarmos sua experiência.', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'submit_label',
            array(
                'label'       => __( 'Texto do botão principal', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Criar conta', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'footer_prompt',
            array(
                'label'       => __( 'Texto auxiliar do rodapé', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Já tem conta?', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'footer_link',
            array(
                'label'       => __( 'Texto do link de login', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Entrar', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'terms_text',
            array(
                'label'       => __( 'Texto do aviso de termos', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Li e aceito os Termos, Política e eventuais taxas.', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'terms_link_label',
            array(
                'label'       => __( 'Texto do link de termos', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Saiba mais', 'login-academia-da-comunicacao' ),
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

        $features = array();
        if ( ! empty( $settings['features'] ) && is_array( $settings['features'] ) ) {
            foreach ( $settings['features'] as $feature ) {
                $features[] = sanitize_text_field( isset( $feature['feature_text'] ) ? $feature['feature_text'] : '' );
            }
            $features = array_filter( $features );
        }

        $illustration_url = $this->parse_media_url( isset( $settings['illustration'] ) ? $settings['illustration'] : array(), get_asset_url( 'assets/img/auth-illustration.svg' ) );
        $illustration_alt = isset( $settings['illustration_alt'] ) ? sanitize_text_field( $settings['illustration_alt'] ) : '';

        $filters = array(
            'adc_login_signup_badge'            => function () use ( $settings ) {
                return sanitize_text_field( isset( $settings['badge'] ) ? $settings['badge'] : '' );
            },
            'adc_login_signup_headline'         => function () use ( $settings ) {
                return sanitize_text_field( isset( $settings['headline'] ) ? $settings['headline'] : '' );
            },
            'adc_login_signup_subtitle'         => function () use ( $settings ) {
                return wp_strip_all_tags( isset( $settings['subtitle'] ) ? $settings['subtitle'] : '' );
            },
            'adc_login_signup_card_kicker'      => function () use ( $settings ) {
                return sanitize_text_field( isset( $settings['card_kicker'] ) ? $settings['card_kicker'] : '' );
            },
            'adc_login_signup_card_title'       => function () use ( $settings ) {
                return sanitize_text_field( isset( $settings['card_title'] ) ? $settings['card_title'] : '' );
            },
            'adc_login_signup_card_description' => function () use ( $settings ) {
                return wp_strip_all_tags( isset( $settings['card_description'] ) ? $settings['card_description'] : '' );
            },
            'adc_login_signup_submit_label'     => function () use ( $settings ) {
                return sanitize_text_field( isset( $settings['submit_label'] ) ? $settings['submit_label'] : '' );
            },
            'adc_login_signup_footer_prompt'    => function () use ( $settings ) {
                return sanitize_text_field( isset( $settings['footer_prompt'] ) ? $settings['footer_prompt'] : '' );
            },
            'adc_login_signup_footer_link_label' => function () use ( $settings ) {
                return sanitize_text_field( isset( $settings['footer_link'] ) ? $settings['footer_link'] : '' );
            },
            'adc_login_signup_terms_text'       => function () use ( $settings ) {
                return sanitize_text_field( isset( $settings['terms_text'] ) ? $settings['terms_text'] : '' );
            },
            'adc_login_signup_terms_link_label' => function () use ( $settings ) {
                return sanitize_text_field( isset( $settings['terms_link_label'] ) ? $settings['terms_link_label'] : '' );
            },
            'adc_login_signup_illustration'     => function () use ( $illustration_url, $illustration_alt ) {
                return array(
                    'src' => $illustration_url,
                    'alt' => $illustration_alt,
                );
            },
        );

        $filters['adc_login_signup_features'] = function () use ( $features ) {
            return $features;
        };

        $this->render_with_filters(
            'adc_signup',
            function () {
                return do_shortcode( '[adc_signup]' );
            },
            $filters
        );
    }
}
