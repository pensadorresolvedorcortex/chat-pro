<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

#[\AllowDynamicProperties]
class kasuari_head_title extends Widget_Base {

	public function get_name() {
		return 'kasuari-head-title';
	}

	public function get_title() {
		return __( 'Heading', 'kasuari' );
	}

	public function get_icon() {
		return 'eicon-banner';
	}

	public function get_categories() {
		return [ 'kasuari-general-category' ];
	}

	protected function _register_controls() {

		/*----- Style Control -----*/

		$this->start_controls_section(
			'section_kasuari_head_title',
			[
				'label' => __( 'General Settings', 'kasuari' ),
			]
		);

		$this->add_control(
			'choose_style',
			[
				'label' => __( 'Style', 'kasuari' ),
				'type' => Controls_Manager::SELECT,
				'default' => '1',
				'options' => [
					1 => __( 'Style 1', 'kasuari' ),
					2 => __( 'Style 2', 'kasuari' ),
					3 => __( 'Style 3', 'kasuari' ),
					4 => __( 'Style 4', 'kasuari' ),
					5 => __( 'Style 5', 'kasuari' ),
					6 => __( 'Style 6', 'kasuari' ),
					7 => __( 'Style 7', 'kasuari' ),
					8 => __( 'Style 8', 'kasuari' ),
				]
			]
		);

		$this->add_control(
			'title_size',
			[
				'label' => __( 'Title Size', 'kasuari' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'h1' => __( 'H1', 'kasuari' ),
					'h2' => __( 'H2', 'kasuari' ),
					'h3' => __( 'H3', 'kasuari' ),
					'h4' => __( 'H4', 'kasuari' ),
					'h5' => __( 'H5', 'kasuari' ),
					'h6' => __( 'H6', 'kasuari' ),
					'div' => __( 'div', 'kasuari' ),
					'span' => __( 'span', 'kasuari' ),
					'p' => __( 'p', 'kasuari' ),
				],
				'default' => 'h2',
			]
		);

		$this->add_control(
			'the_title',
			[
				'label' => __( 'Title Text', 'kasuari' ),
				'type' => Controls_Manager::TEXT,
				'default' => 'Kasuari Head Title',
				'title' => __( 'Enter some text', 'kasuari' ),
			]
		);

		$this->add_control(
			'title_end',
			[
				'label' => __( 'Title Separator', 'kasuari' ),
				'type' => Controls_Manager::TEXT,
				'default' => '',
				'title' => __( 'Enter some text', 'kasuari' ),
				'condition' => [
					'choose_style' => '8',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'typography_title',
				'scheme' => \Elementor\Core\Schemes\Typography::TYPOGRAPHY_1,
				'selector' => '{{WRAPPER}} .head-title .the-title',
			]
		);

		$this->add_control(
			'head_use_subtitle',
			[
				'label' => __( 'Use Subtitle', 'kasuari' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => 'Use',
				'label_off' => 'No',
				'return_value' => 'on',
				'default' => 'on',
				'condition' => [
					'choose_style' => ['1', '2', '4', '5', '6', '7', '8'],
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'the_subtitle',
			[
				'label' => __( 'Subtitle Text', 'kasuari' ),
				'type' => Controls_Manager::TEXTAREA,
				'default' => 'Kasuari Subtitle',
				'title' => __( 'Enter some text', 'kasuari' ),
				'condition' => [
					'choose_style' => ['1', '2', '4', '5', '6', '7', '8'],
					'head_use_subtitle' => 'on',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'typography_subtitle',
				'scheme' => \Elementor\Core\Schemes\Typography::TYPOGRAPHY_1,
				'label' => __( 'Subtitle Text', 'kasuari' ),
				'selector' => '{{WRAPPER}} .head-title .subtitle',
				'condition' => [
					'choose_style' => ['1', '2', '4', '5', '6', '7', '8'],
					'head_use_subtitle' => 'on',
				],
			]
		);

		$this->add_responsive_control(
			'align',
			[
				'label' => __( 'Alignment', 'kasuari' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => __( 'Left', 'kasuari' ),
						'icon' => 'fa fa-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'kasuari' ),
						'icon' => 'fa fa-align-center',
					],
					'right' => [
						'title' => __( 'Right', 'kasuari' ),
						'icon' => 'fa fa-align-right',
					],
					'justify' => [
						'title' => __( 'Justified', 'kasuari' ),
						'icon' => 'fa fa-align-justify',
					],
				],
				'default' => 'left',
				'selectors' => [
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				],
				'prefix_class' => 'the-title-',
				'separator' => 'before',
			]
		);

		$this->end_controls_section();

		/* title dimensions*/
		$this->start_controls_section(
			'section_kasuari_head_title_dimensions',
			[
				'label' => __( 'Title Dimensions', 'kasuari' ),
			]
		);

		$this->add_responsive_control(
			'title_margin_top',
			[
				'label' => __( 'Margin Top Title', 'kasuari' ),
				'description' => __( 'Margin top for each head title.', 'kasuari' ),
				'type' => Controls_Manager::NUMBER,
				'default' => '0',
				'selectors' => [
					'{{WRAPPER}} .head-title .the-title' => 'margin-top: {{VALUE}}px;',
				],			
			]
		);

		$this->add_responsive_control(
			'title_margin_bottom',
			[
				'label' => __( 'Margin Bottom Title', 'kasuari' ),
				'description' => __( 'Margin bottom for each head title.', 'kasuari' ),
				'type' => Controls_Manager::NUMBER,
				'default' => '0',
				'selectors' => [
					'{{WRAPPER}} .head-title .the-title' => 'margin-bottom: {{VALUE}}px;',
				],			
			]
		);

		$this->end_controls_section();

		/*----- Style 1 Settings -----*/
		$this->start_controls_section(
			'section_kasuari_head_title_style_1',
			[
				'label' => __( 'Style 1 Settings', 'kasuari' ),
				'condition' => [
					'choose_style' => '1',
				],
			]
		);

		$this->add_control(
			'title_color_1',
			[
				'label' => __( 'Title Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-1 .the-title' => 'color: {{VALUE}};',
				],
				'default' => '#000000',
			]
		);

		$this->add_control(
			'separator_type_1',
			[
				'label' => __( 'Separator Type', 'kasuari' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'border' => __( 'Border', 'kasuari' ),
					'image'=> __( 'Image', 'kasuari' ),
				],
				'default' => 'border',
				'condition' => [
					'head_use_subtitle' => 'on',
				],
			]
		);

		$this->add_control(
			'subtitle_color_1',
			[
				'label' => __( 'Subtitle Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-1 .subtitle' => 'color: {{VALUE}};',
				],
				'default' => '#000000',
				'condition' => [
					'head_use_subtitle' => 'on',
				],
			]
		);

		$this->add_control(
			'border_style_1',
			[
				'label' => __( 'Separator Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-1.bg-image .the-title:after' => 'background-color: {{VALUE}};',
				],
				'default' => '#000000',
				'condition' => [
					'head_use_subtitle' => 'on',
					'separator_type_1' => 'border',
				],
			]
		);

		$this->add_control(
			'border_img_style_1',
			[
				'label' => __( 'Separator Image', 'kasuari' ),
				'type' => Controls_Manager::MEDIA,
				'selectors' => [
					'{{WRAPPER}} .head-title-1.bg-image .the-title:after' => 'background-image: url({{URL}});',
				],
				'condition' => [
					'head_use_subtitle' => 'on',
					'separator_type_1' => 'image',
				],
			]
		);

		$this->end_controls_section();
		/*----- Style 1 Settings end -----*/

		/*----- Style 2 Settings -----*/
		$this->start_controls_section(
			'section_kasuari_head_title_style_2',
			[
				'label' => __( 'Style 2 Settings', 'kasuari' ),
				'condition' => [
					'choose_style' => '2',
				],
			]
		);

		$this->add_control(
			'title_color_2',
			[
				'label' => __( 'Title Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-2 .the-title' => 'color: {{VALUE}};',
				],
				'default' => '#000000',
			]
		);

		$this->add_control(
			'subtitle_color_2',
			[
				'label' => __( 'Subtitle Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-2 .subtitle' => 'color: {{VALUE}};',
				],
				'default' => '#000000',
				'condition' => [
					'head_use_subtitle' => 'on',
				],
			]
		);

		$this->end_controls_section();
		/*----- Style 2 Settings end -----*/

		/*----- Style 3 Settings -----*/
		$this->start_controls_section(
			'section_kasuari_head_title_style_3',
			[
				'label' => __( 'Style 3 Settings', 'kasuari' ),
				'condition' => [
					'choose_style' => '3',
				],
			]
		);

		$this->add_control(
			'title_color_3',
			[
				'label' => __( 'Title Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-3 .the-title' => 'color: {{VALUE}};',
				],
				'default' => '#000000',
			]
		);

		$this->add_control(
			'span_border_style_3',
			[
				'label' => __( 'Separator Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-3 span' => 'border-color: {{VALUE}};',
				],
				'default' => '#213875',
			]
		);

		$this->add_control(
			'border_style_3',
			[
				'label' => __( 'Separator Background Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-3' => 'border-color: {{VALUE}};',
				],
				'default' => '#e1e1e1',
			]
		);

		$this->end_controls_section();
		/*----- Style 3 Settings end -----*/

		/*----- Style 4 Settings -----*/
		$this->start_controls_section(
			'section_kasuari_head_title_style_4',
			[
				'label' => __( 'Style 4 Settings', 'kasuari' ),
				'condition' => [
					'choose_style' => '4',
				],
			]
		);

		$this->add_control(
			'title_color_4',
			[
				'label' => __( 'Title Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-4 .the-title' => 'color: {{VALUE}};',
				],
				'default' => '#000000',
			]
		);

		$this->add_control(
			'subtitle_color_4',
			[
				'label' => __( 'Subtitle Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-4 .subtitle' => 'color: {{VALUE}};',
				],
				'default' => '#000000',
				'condition' => [
					'head_use_subtitle' => 'on',
				],
			]
		);

		$this->add_control(
			'title_bg_style_4',
			[
				'label' => __( 'Title Background Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-4 h2 span.inner-title' => 'background-color: {{VALUE}};',
				],
				'default' => '#ffffff',
			]
		);

		$this->add_control(
			'border_style_4',
			[
				'label' => __( 'Separator Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-4 h2:before' => 'background-color: {{VALUE}};',
				],
				'default' => '#000000',
			]
		);

		$this->end_controls_section();
		/*----- Style 4 Settings end -----*/

		/*----- Style 5 Settings -----*/
		$this->start_controls_section(
			'section_kasuari_head_title_style_5',
			[
				'label' => __( 'Style 5 Settings', 'kasuari' ),
				'condition' => [
					'choose_style' => '5',
				],
			]
		);

		$this->add_control(
			'title_color_5',
			[
				'label' => __( 'Title Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-5 .the-title' => 'color: {{VALUE}};',
				],
				'default' => '#000000',
			]
		);

		$this->add_control(
			'subtitle_color_5',
			[
				'label' => __( 'Subtitle Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-5 .subtitle' => 'color: {{VALUE}};',
				],
				'default' => '#000000',
				'condition' => [
					'head_use_subtitle' => 'on',
				],
			]
		);

		$this->add_control(
			'border_style_5',
			[
				'label' => __( 'Separator Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-5 h2 span:before, .head-title-5 h2 span:after' => 'background-color: {{VALUE}};',
				],
				'default' => '#000000',
			]
		);

		$this->end_controls_section();
		/*----- Style 5 Settings end -----*/

		/*----- Style 6 Settings -----*/
		$this->start_controls_section(
			'section_kasuari_head_title_style_6',
			[
				'label' => __( 'Style 6 Settings', 'kasuari' ),
				'condition' => [
					'choose_style' => '6',
				],
			]
		);

		$this->add_control(
			'title_color_6',
			[
				'label' => __( 'Title Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-6 h2 span' => 'color: {{VALUE}};',
				],
				'default' => '#ffffff',
			]
		);

		$this->add_control(
			'title_bg_style_6',
			[
				'label' => __( 'Title Background Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-6 h2 span' => 'background-color: {{VALUE}};',
				],
				'default' => '#000000',
			]
		);

		$this->add_control(
			'subtitle_color_6',
			[
				'label' => __( 'Subtitle Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .head-title-6 .subtitle' => 'color: {{VALUE}};',
				],
				'default' => '#000000',
				'condition' => [
					'head_use_subtitle' => 'on',
				],
			]
		);

		$this->end_controls_section();
		/*----- Style 6 Settings end -----*/

		/*----- Style 7 Settings -----*/
		$this->start_controls_section(
			'section_kasuari_head_title_7',
			[
				'label' => __( 'Style 7 Settings', 'kasuari' ),
				'condition' => [
					'choose_style' => '7',
				],
			]
		);

		$this->add_control(
			'title_color_7',
			[
				'label' => __( 'Text Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'scheme' => [
				     'type' => \Elementor\Core\Schemes\Color::get_type() ,
                    'valor' => \Elementor\Core\Schemes\Color::COLOR_1,
				],
				'selectors' => [
					'{{WRAPPER}} .head-title.head-title-7 .the-title' => 'color: {{VALUE}};',
				],
				'default' => '#111111',
			]
		);

		$this->add_control(
			'big_title_color_7',
			[
				'label' => __( 'Big Title Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'scheme' => [
				    'type' => \Elementor\Core\Schemes\Color::get_type() ,
                    'valor' => \Elementor\Core\Schemes\Color::COLOR_1,
				],
				'selectors' => [
					'{{WRAPPER}} .head-title.head-title-7 .the-title span.big-title' => 'color: {{VALUE}};',
				],
				'default' => 'rgba(0, 0, 0, 0.1)',
			]
		);

		$this->add_responsive_control(
			'vertical_position_big_title_7',
			[
				'label' => __( 'Vertical Position', 'kasuari' ),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => -65,
					'unit' => '%',
				],
				'range' => [
					'%' => [
						'min' => -100,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .head-title.head-title-7 .the-title span.big-title' => 'top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'horizontal_position_big_title_7',
			[
				'label' => __( 'Horizontal Position', 'kasuari' ),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => -2,
					'unit' => '%',
				],
				'range' => [
					'%' => [
						'min' => -100,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .head-title.head-title-7 .the-title span.big-title' => 'left: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
		/*----- Style 7 Settings end -----*/

		/*----- Style 7 Settings -----*/
		$this->start_controls_section(
			'section_kasuari_head_title_8',
			[
				'label' => __( 'Style 8 Settings', 'kasuari' ),
				'condition' => [
					'choose_style' => '8',
				],
			]
		);

		$this->add_control(
			'title_color_8',
			[
				'label' => __( 'Text Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'scheme' => [
				     'type' => \Elementor\Core\Schemes\Color::get_type() ,
                    'valor' => \Elementor\Core\Schemes\Color::COLOR_1,
				],
				'selectors' => [
					'{{WRAPPER}} .head-title.head-title-8 .the-title' => 'color: {{VALUE}};',
				],
				'default' => '#111111',
			]
		);

		$this->add_control(
			'subtitle_color_8',
			[
				'label' => __( 'Subtitle Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'scheme' => [
				     'type' => \Elementor\Core\Schemes\Color::get_type() ,
                    'valor' => \Elementor\Core\Schemes\Color::COLOR_1,
				],
				'selectors' => [
					'{{WRAPPER}} .head-title.head-title-8 span.subtitle' => 'color: {{VALUE}};',
				],
				'default' => '#111111',
			]
		);

		$this->add_control(
			'separator_8_color',
			[
				'label' => __( 'Title Separator Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'scheme' => [
				     'type' => \Elementor\Core\Schemes\Color::get_type() ,
                    'valor' => \Elementor\Core\Schemes\Color::COLOR_1,
				],
				'selectors' => [
					'{{WRAPPER}} .head-title.head-title-8 span.title-end' => 'color: {{VALUE}};',
				],
				'default' => '#111111',
				'condition' => [
					'choose_style' => '8',
				],
			]
		);

		$this->add_responsive_control(
			'separator_8_typo',
			[
				'label' => __( 'Separator Title Size', 'kasuari' ),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 32,
					'unit' => 'px',
				],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 200,
					],
				],
				'size_units' => [ 'px', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .head-title.head-title-8 .the-title span.title-end' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'choose_style' => '8',
				],
			]
		);

		$this->end_controls_section();
		/*----- Style 7 Settings end -----*/

	}

	protected function render() {

		$instance = $this->get_settings();

		/* General Controls */
		$choose_style 		= ! empty( $instance['choose_style'] ) ? $instance['choose_style'] : 1;
		$the_title 			= ! empty( $instance['the_title'] ) ? $instance['the_title'] : 'Kasuari Head Title';
		$title_end 			= ! empty( $instance['title_end'] ) ? $instance['title_end'] : '';
		$align 				= ! empty( $instance['align'] ) ? $instance['align'] : '';
		$head_use_subtitle	=  $instance['head_use_subtitle'];
		$title_size 		= ! empty( $instance['title_size'] ) ? $instance['title_size'] : 'h2';
		$the_subtitle 		= ! empty( $instance['the_subtitle'] ) ? $instance['the_subtitle'] : 'Kasuari Subtitle';

		/*style 1*/
		$separator_type_1 	= ! empty( $instance['separator_type_1'] ) ? $instance['separator_type_1'] : '';
		$border_img_style_1 = ! empty( $instance['border_img_style_1'] ) ? $instance['border_img_style_1'] : '';
		

		/* Choose Style */
		if($choose_style == 1) {
			include ( plugin_dir_path(__FILE__).'tpl/header-title-1.php' );
		}
		elseif($choose_style == 2) {
			include ( plugin_dir_path(__FILE__).'tpl/header-title-2.php' );
		}
		elseif($choose_style == 3) {
			include ( plugin_dir_path(__FILE__).'tpl/header-title-3.php' );
		}
		elseif($choose_style == 4) {
			include ( plugin_dir_path(__FILE__).'tpl/header-title-4.php' );
		}
		elseif($choose_style == 5) {
			include ( plugin_dir_path(__FILE__).'tpl/header-title-5.php' );
		}
		elseif($choose_style == 6) {
			include ( plugin_dir_path(__FILE__).'tpl/header-title-6.php' );
		}
		elseif($choose_style == 7) {
			include ( plugin_dir_path(__FILE__).'tpl/header-title-7.php' );
		}
		elseif($choose_style == 8) {
			include ( plugin_dir_path(__FILE__).'tpl/header-title-8.php' );
		}

		?>

		<?php

	}

	protected function content_template() {}

	public function render_plain_content( $instance = [] ) {

	}
}

Plugin::instance()->widgets_manager->register_widget_type( new kasuari_head_title() );