<?php
/**
 * Elementor widget for the password recovery form.
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
 * Forgot password widget definition.
 */
class Forgot extends Base {

    /**
     * Widget slug.
     *
     * @return string
     */
    public function get_name() {
        return 'adc-forgot-form';
    }

    /**
     * Visible title inside Elementor.
     *
     * @return string
     */
    public function get_title() {
        return __( 'Formulário de Recuperação ADC', 'login-academia-da-comunicacao' );
    }

    /**
     * Icon displayed in Elementor sidebar.
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-mail';
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
                'default'     => __( 'Tudo sob controle', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'headline',
            array(
                'label'       => __( 'Título principal', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Vamos recuperar seu acesso.', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'subtitle',
            array(
                'label'       => __( 'Descrição', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXTAREA,
                'default'     => __( 'Não se preocupe, enviaremos um link seguro para você redefinir a senha em instantes.', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $repeater = new Repeater();
        $repeater->add_control(
            'feature_text',
            array(
                'label'       => __( 'Benefício', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Receba o passo a passo por e-mail em poucos segundos.', 'login-academia-da-comunicacao' ),
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
                    array( 'feature_text' => __( 'Receba o passo a passo por e-mail em poucos segundos.', 'login-academia-da-comunicacao' ) ),
                    array( 'feature_text' => __( 'Proteção reforçada com 2FA e verificações inteligentes.', 'login-academia-da-comunicacao' ) ),
                    array( 'feature_text' => __( 'Equipe de suporte pronta para ajudar quando precisar.', 'login-academia-da-comunicacao' ) ),
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
                'default'     => __( 'Esqueceu a senha?', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'card_title',
            array(
                'label'       => __( 'Título do cartão', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Recuperar senha', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'card_description',
            array(
                'label'       => __( 'Descrição do cartão', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXTAREA,
                'default'     => __( 'Informe seu e-mail para receber um link de redefinição.', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'submit_label',
            array(
                'label'       => __( 'Texto do botão principal', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Enviar instruções', 'login-academia-da-comunicacao' ),
                'label_block' => true,
            )
        );

        $this->add_control(
            'footer_link',
            array(
                'label'       => __( 'Texto do link de retorno', 'login-academia-da-comunicacao' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Voltar ao login', 'login-academia-da-comunicacao' ),
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
            'adc_login_forgot_badge'            => function () use ( $settings ) {
                return sanitize_text_field( isset( $settings['badge'] ) ? $settings['badge'] : '' );
            },
            'adc_login_forgot_headline'         => function () use ( $settings ) {
                return sanitize_text_field( isset( $settings['headline'] ) ? $settings['headline'] : '' );
            },
            'adc_login_forgot_subtitle'         => function () use ( $settings ) {
                return wp_strip_all_tags( isset( $settings['subtitle'] ) ? $settings['subtitle'] : '' );
            },
            'adc_login_forgot_card_kicker'      => function () use ( $settings ) {
                return sanitize_text_field( isset( $settings['card_kicker'] ) ? $settings['card_kicker'] : '' );
            },
            'adc_login_forgot_card_title'       => function () use ( $settings ) {
                return sanitize_text_field( isset( $settings['card_title'] ) ? $settings['card_title'] : '' );
            },
            'adc_login_forgot_card_description' => function () use ( $settings ) {
                return wp_strip_all_tags( isset( $settings['card_description'] ) ? $settings['card_description'] : '' );
            },
            'adc_login_forgot_submit_label'     => function () use ( $settings ) {
                return sanitize_text_field( isset( $settings['submit_label'] ) ? $settings['submit_label'] : '' );
            },
            'adc_login_forgot_footer_link_label' => function () use ( $settings ) {
                return sanitize_text_field( isset( $settings['footer_link'] ) ? $settings['footer_link'] : '' );
            },
            'adc_login_forgot_illustration'     => function () use ( $illustration_url, $illustration_alt ) {
                return array(
                    'src' => $illustration_url,
                    'alt' => $illustration_alt,
                );
            },
        );

        $filters['adc_login_forgot_features'] = function () use ( $features ) {
            return $features;
        };

        $this->render_with_filters(
            'adc_forgot',
            function () {
                return do_shortcode( '[adc_forgot]' );
            },
            $filters
        );
    }
}
