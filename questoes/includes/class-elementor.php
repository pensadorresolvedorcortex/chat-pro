<?php
/**
 * Elementor integration.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

/**
 * Elementor hooks for Questões.
 */
class Questoes_Elementor {

    /**
     * Settings handler.
     *
     * @var Questoes_Settings
     */
    protected $settings;

    /**
     * Renderer handler.
     *
     * @var Questoes_Renderer
     */
    protected $renderer;

    /**
     * Accessibility helper.
     *
     * @var Questoes_Accessibility
     */
    protected $accessibility;

    /**
     * Constructor.
     *
     * @param Questoes_Settings      $settings Settings instance.
     * @param Questoes_Renderer      $renderer Renderer instance.
     * @param Questoes_Accessibility $accessibility Accessibility helper.
     */
    public function __construct( Questoes_Settings $settings, Questoes_Renderer $renderer, Questoes_Accessibility $accessibility ) {
        $this->settings      = $settings;
        $this->renderer      = $renderer;
        $this->accessibility = $accessibility;

        add_action( 'init', array( $this, 'register_frontend_assets' ) );
        add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
        add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
        add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'enqueue_preview_assets' ) );
    }

    /**
     * Register Elementor category.
     *
     * @param \Elementor\Elements_Manager $elements_manager Manager instance.
     */
    public function register_category( $elements_manager ) {
        $elements_manager->add_category(
            'questoes',
            array(
                'title' => __( 'Questões', 'questoes' ),
                'icon'  => 'eicon-navigator',
            )
        );
    }

    /**
     * Register Elementor widget.
     *
     * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
     */
    public function register_widget( $widgets_manager ) {
        $visual_widget = new Questoes_Elementor_Widget();
        $visual_widget->set_dependencies( $this->settings, $this->renderer, $this->accessibility );

        $bank_widget    = new Questoes_Elementor_Question_Bank();
        $courses_widget = new Questoes_Elementor_Courses();

        if ( method_exists( $widgets_manager, 'register' ) ) {
            $widgets_manager->register( $visual_widget );
            $widgets_manager->register( $bank_widget );
            $widgets_manager->register( $courses_widget );
        } else {
            $widgets_manager->register_widget_type( $visual_widget );
            $widgets_manager->register_widget_type( $bank_widget );
            $widgets_manager->register_widget_type( $courses_widget );
        }
    }

    /**
     * Ensure assets are registered for Elementor preview.
     */
    public function register_frontend_assets() {
        if ( ! wp_style_is( 'questoes-frontend', 'registered' ) ) {
            wp_register_style(
                'questoes-frontend',
                QUESTOES_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                questoes_asset_version( 'assets/css/frontend.css' )
            );
        }

        if ( ! wp_script_is( 'questoes-frontend', 'registered' ) ) {
            wp_register_script(
                'questoes-frontend',
                QUESTOES_PLUGIN_URL . 'assets/js/frontend.js',
                array(),
                questoes_asset_version( 'assets/js/frontend.js' ),
                true
            );
        }
    }

    /**
     * Enqueue assets for Elementor editor preview.
     */
    public function enqueue_preview_assets() {
        wp_enqueue_style( 'questoes-frontend' );
        wp_enqueue_script( 'questoes-frontend' );
    }
}

/**
 * Elementor widget implementation.
 */
class Questoes_Elementor_Widget extends Widget_Base {

    /**
     * Settings handler.
     *
     * @var Questoes_Settings
     */
    protected $settings_handler;

    /**
     * Renderer instance.
     *
     * @var Questoes_Renderer
     */
    protected $renderer;

    /**
     * Accessibility helper.
     *
     * @var Questoes_Accessibility
     */
    protected $accessibility;

    /**
     * Assign dependencies after instantiation.
     *
     * @param Questoes_Settings      $settings Settings instance.
     * @param Questoes_Renderer      $renderer Renderer instance.
     * @param Questoes_Accessibility $accessibility Accessibility helper.
     */
    public function set_dependencies( Questoes_Settings $settings, Questoes_Renderer $renderer, Questoes_Accessibility $accessibility ) {
        $this->settings_handler = $settings;
        $this->renderer         = $renderer;
        $this->accessibility    = $accessibility;
    }

    /**
     * Widget slug.
     */
    public function get_name() {
        return 'questoes_visualizador';
    }

    /**
     * Widget title.
     */
    public function get_title() {
        return __( 'Questões – Mapa/Organograma', 'questoes' );
    }

    /**
     * Widget icon.
     */
    public function get_icon() {
        return 'eicon-navigator';
    }

    /**
     * Categories used by widget.
     */
    public function get_categories() {
        return array( 'questoes' );
    }

    /**
     * Related keywords.
     */
    public function get_keywords() {
        return array( 'questoes', 'mapa mental', 'organograma', 'mindmap', 'orgchart' );
    }

    /**
     * Ensure assets are loaded.
     */
    public function get_style_depends() {
        return array( 'questoes-frontend' );
    }

    /**
     * Ensure scripts are loaded.
     */
    public function get_script_depends() {
        return array( 'questoes-frontend' );
    }

    /**
     * Register widget controls.
     */
    protected function register_controls() {
        $code_control_type = defined( 'Elementor\\Controls_Manager::CODE' ) ? Controls_Manager::CODE : Controls_Manager::TEXTAREA;

        $this->start_controls_section(
            'section_content',
            array(
                'label' => __( 'Conteúdo', 'questoes' ),
            )
        );

        $this->add_control(
            'mode',
            array(
                'label'   => __( 'Modo', 'questoes' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'ambos',
                'options' => array(
                    'ambos'       => __( 'Mapa e Organograma', 'questoes' ),
                    'mapa'        => __( 'Apenas Mapa Mental', 'questoes' ),
                    'organograma' => __( 'Apenas Organograma', 'questoes' ),
                ),
            )
        );

        $this->add_control(
            'title',
            array(
                'label'       => __( 'Título', 'questoes' ),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => __( 'Opcional', 'questoes' ),
            )
        );

        $this->add_control(
            'use_custom_data',
            array(
                'label'        => __( 'Dados personalizados', 'questoes' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Sim', 'questoes' ),
                'label_off'    => __( 'Não', 'questoes' ),
                'return_value' => 'yes',
                'default'      => '',
            )
        );

        $this->add_control(
            'custom_data',
            array(
                'label'       => __( 'JSON personalizado', 'questoes' ),
                'type'        => $code_control_type,
                'language'    => 'json',
                'rows'        => 16,
                'placeholder' => '{\n  "title": "Questões — Academia da Comunicação"\n}',
                'description' => __( 'Cole um JSON válido seguindo o esquema do plugin.', 'questoes' ),
                'condition'   => array( 'use_custom_data' => 'yes' ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output.
     */
    protected function render() {
        if ( ! $this->renderer || ! $this->settings_handler ) {
            echo '<div class="questoes-empty">' . esc_html__( 'Nenhum dado disponível.', 'questoes' ) . '</div>';
            return;
        }

        $settings = $this->get_settings_for_display();
        $mode     = isset( $settings['mode'] ) ? $settings['mode'] : 'ambos';
        $data     = array();

        if ( isset( $settings['use_custom_data'] ) && 'yes' === $settings['use_custom_data'] ) {
            $json = isset( $settings['custom_data'] ) ? $settings['custom_data'] : '';
            $json = Questoes_Schema::sanitize_json( $json );

            if ( empty( $json ) ) {
                echo '<div class="questoes-empty">' . esc_html__( 'Cole um JSON válido para pré-visualizar.', 'questoes' ) . '</div>';
                return;
            }

            $decoded = json_decode( $json, true );
            $valid   = Questoes_Schema::validate( $decoded );

            if ( is_wp_error( $valid ) ) {
                echo '<div class="questoes-empty">' . esc_html( $valid->get_error_message() ) . '</div>';
                return;
            }

            $data = $this->renderer->prepare_from_array( $decoded, $mode );
        } else {
            $data = $this->renderer->get_data( $mode );
        }

        if ( empty( $data ) ) {
            echo '<div class="questoes-empty">' . esc_html__( 'Nenhum dado disponível.', 'questoes' ) . '</div>';
            return;
        }

        if ( ! empty( $settings['title'] ) ) {
            $data['title'] = sanitize_text_field( $settings['title'] );
        }

        $palette       = $this->settings_handler->get( 'palette' );
        $accessibility = $this->accessibility;
        $mode          = $mode;

        include QUESTOES_PLUGIN_DIR . 'views/tabs.php';
    }

    /**
     * Render plain content (used for copy/paste).
     */
    public function render_plain_content() {
        $settings = $this->get_settings_for_display();
        $shortcode = '[questoes';

        if ( ! empty( $settings['mode'] ) ) {
            $shortcode .= sprintf( ' modo="%s"', esc_attr( $settings['mode'] ) );
        }

        if ( ! empty( $settings['title'] ) ) {
            $shortcode .= sprintf( ' titulo="%s"', esc_attr( $settings['title'] ) );
        }

        $shortcode .= ']';

        if ( isset( $settings['use_custom_data'] ) && 'yes' === $settings['use_custom_data'] && ! empty( $settings['custom_data'] ) ) {
            $shortcode .= $settings['custom_data'];
        }

        $shortcode .= '[/questoes]';

        echo $shortcode;
    }
}

class Questoes_Elementor_Question_Bank extends Widget_Base {

    public function get_name() {
        return 'questoes_banco';
    }

    public function get_title() {
        return __( 'Questões – Banco', 'questoes' );
    }

    public function get_icon() {
        return 'eicon-table';
    }

    public function get_categories() {
        return array( 'questoes' );
    }

    public function get_keywords() {
        return array( 'questoes', 'questões', 'banco', 'simulado', 'prova' );
    }

    public function get_style_depends() {
        return array( 'questoes-frontend' );
    }

    public function get_script_depends() {
        return array( 'questoes-frontend' );
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            array(
                'label' => __( 'Configurações', 'questoes' ),
            )
        );

        $this->add_control(
            'title',
            array(
                'label'       => __( 'Título', 'questoes' ),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => __( 'Opcional', 'questoes' ),
            )
        );

        $this->add_control(
            'show_filters',
            array(
                'label'        => __( 'Exibir filtros', 'questoes' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Sim', 'questoes' ),
                'label_off'    => __( 'Não', 'questoes' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'category',
            array(
                'label'       => __( 'Categoria (slug)', 'questoes' ),
                'type'        => Controls_Manager::TEXT,
                'description' => __( 'Opcional. Use o slug da categoria. Para múltiplas categorias, separe por vírgulas.', 'questoes' ),
            )
        );

        $this->add_control(
            'banca',
            array(
                'label'       => __( 'Banca (slug)', 'questoes' ),
                'type'        => Controls_Manager::TEXT,
                'description' => __( 'Opcional. Use o slug da banca. Para múltiplas bancas, separe por vírgulas.', 'questoes' ),
            )
        );

        $this->add_control(
            'difficulty',
            array(
                'label'   => __( 'Dificuldade', 'questoes' ),
                'type'    => Controls_Manager::SELECT,
                'default' => '',
                'options' => array(
                    ''       => __( 'Todas', 'questoes' ),
                    'easy'   => __( 'Fácil', 'questoes' ),
                    'medium' => __( 'Média', 'questoes' ),
                    'hard'   => __( 'Difícil', 'questoes' ),
                ),
            )
        );

        $this->add_control(
            'per_page',
            array(
                'label'   => __( 'Questões por página', 'questoes' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 10,
                'min'     => 1,
                'max'     => 50,
            )
        );

        $this->end_controls_section();
    }

    protected function build_shortcode( $settings ) {
        $shortcode = '[questoes_banco';

        if ( ! empty( $settings['title'] ) ) {
            $shortcode .= sprintf( ' titulo="%s"', esc_attr( $settings['title'] ) );
        }

        if ( ! empty( $settings['category'] ) ) {
            $shortcode .= sprintf( ' categoria="%s"', esc_attr( $settings['category'] ) );
        }

        if ( ! empty( $settings['banca'] ) ) {
            $shortcode .= sprintf( ' banca="%s"', esc_attr( $settings['banca'] ) );
        }

        if ( ! empty( $settings['difficulty'] ) ) {
            $shortcode .= sprintf( ' dificuldade="%s"', esc_attr( $settings['difficulty'] ) );
        }

        if ( isset( $settings['per_page'] ) && $settings['per_page'] ) {
            $shortcode .= sprintf( ' por_pagina="%d"', absint( $settings['per_page'] ) );
        }

        if ( isset( $settings['show_filters'] ) && 'yes' !== $settings['show_filters'] ) {
            $shortcode .= ' mostrar_filtros="nao"';
        }

        $shortcode .= ']';

        return $shortcode;
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $shortcode = $this->build_shortcode( $settings );
        echo do_shortcode( $shortcode );
    }

    public function render_plain_content() {
        $settings = $this->get_settings_for_display();
        echo $this->build_shortcode( $settings );
    }
}

class Questoes_Elementor_Courses extends Widget_Base {

    public function get_name() {
        return 'questoes_cursos';
    }

    public function get_title() {
        return __( 'Questões – Cursos', 'questoes' );
    }

    public function get_icon() {
        return 'eicon-library-opened';
    }

    public function get_categories() {
        return array( 'questoes' );
    }

    public function get_keywords() {
        return array( 'questoes', 'cursos', 'concursos', 'disciplinas' );
    }

    public function get_style_depends() {
        return array( 'questoes-frontend' );
    }

    public function get_script_depends() {
        return array( 'questoes-frontend' );
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            array(
                'label' => __( 'Conteúdo', 'questoes' ),
            )
        );

        $this->add_control(
            'highlight_title',
            array(
                'label'       => __( 'Título dos destaques', 'questoes' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'Cursos em alta', 'questoes' ),
                'placeholder' => __( 'Cursos em alta', 'questoes' ),
            )
        );

        $this->add_control(
            'highlight_description',
            array(
                'label'       => __( 'Descrição dos destaques', 'questoes' ),
                'type'        => Controls_Manager::TEXTAREA,
                'placeholder' => __( 'Principais concursos do momento.', 'questoes' ),
            )
        );

        $this->add_control(
            'featured_limit',
            array(
                'label'       => __( 'Quantidade de destaques', 'questoes' ),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 1,
                'max'         => 12,
                'step'        => 1,
                'default'     => 6,
            )
        );

        $this->add_control(
            'extra_content',
            array(
                'label'       => __( 'Bloco adicional (HTML opcional)', 'questoes' ),
                'type'        => Controls_Manager::TEXTAREA,
                'placeholder' => __( '<p>Destaque personalizado para os cursos.</p>', 'questoes' ),
                'rows'        => 6,
            )
        );

        $this->end_controls_section();
    }

    protected function build_shortcode( $settings ) {
        $attributes = array();

        if ( ! empty( $settings['highlight_title'] ) ) {
            $attributes[] = sprintf( 'titulo_destaques="%s"', esc_attr( $settings['highlight_title'] ) );
        }

        if ( ! empty( $settings['highlight_description'] ) ) {
            $attributes[] = sprintf( 'descricao_destaques="%s"', esc_attr( $settings['highlight_description'] ) );
        }

        if ( ! empty( $settings['featured_limit'] ) ) {
            $attributes[] = sprintf( 'limite_destaques="%d"', absint( $settings['featured_limit'] ) );
        }

        $shortcode = '[questoes_cursos';

        if ( ! empty( $attributes ) ) {
            $shortcode .= ' ' . implode( ' ', $attributes );
        }

        $shortcode .= ']';

        if ( ! empty( $settings['extra_content'] ) ) {
            $shortcode .= $settings['extra_content'];
        }

        $shortcode .= '[/questoes_cursos]';

        return $shortcode;
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        echo do_shortcode( $this->build_shortcode( $settings ) );
    }

    public function render_plain_content() {
        $settings = $this->get_settings_for_display();
        echo $this->build_shortcode( $settings );
    }
}
