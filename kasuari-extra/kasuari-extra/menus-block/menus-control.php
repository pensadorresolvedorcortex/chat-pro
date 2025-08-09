<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

#[\AllowDynamicProperties]
class kasuari_restaurant_menu extends Widget_Base {

	public function get_name() {
		return 'kasuari-restaurant-menu';
	}

	public function get_title() {
		return __( 'Restaurant Menu', 'kasuari' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return [ 'kasuari-general-category' ];
	}

	protected function _register_controls() {

		/*-----------------------------------------------------------------------------------*/
		/*  1. TITLE STYLE SETTING
		/*-----------------------------------------------------------------------------------*/
		$this->start_controls_section(
		'section_kasuari_menus_block_title_style_setting',
			[
				'label' => __( 'Title Setting', 'kasuari' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'menu_text_vertical',
			[
				'label' => __( 'Vertical Align Text', 'kasuari' ),
				'type' => Controls_Manager::TEXT,
				'default' => '0',
				'selectors' => [
					'{{WRAPPER}} .kasuari-menus .texted-menu' => 'top: {{VALUE}}px;',
				],
			]
		);

		$this->add_control(
			'menu_name',
			[
				'label' => __( 'Menu Name', 'kasuari' ),
				'type' => Controls_Manager::TEXT,
				'default' => 'Menu Name',
			]
		);

		$this->add_control(
			'typhography_title_color',
			[
				'label' => __( 'Title Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#000000',
				'selectors' => [
					'{{WRAPPER}} .menu-list__item-title .item_title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'typhography_title_bg_color',
			[
				'label' => __( 'Title Background Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .menu-list__item-title .item_title' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'typhography_title_bord_color',
			[
				'label' => __( 'Border Dots Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#000000',
				'selectors' => [
					'{{WRAPPER}} .menu-list__item span.dots' => 'background-image: radial-gradient(circle closest-side, {{VALUE}} 99%, transparent 1%);',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'typography_menus_title',
				'label' => __( 'Title Font Setting', 'kasuari' ),
				'scheme' => Scheme_Typography::TYPOGRAPHY_3,
				'selector' => '{{WRAPPER}} .menu-list__item-title .item_title',
			]
		);

		$this->end_controls_section();

		/*-----------------------------------------------------------------------------------*/
		/*  2. PRICE STYLE SETTING
		/*-----------------------------------------------------------------------------------*/
		$this->start_controls_section(
		'section_kasuari_menus_block_price_style_setting',
			[
				'label' => __( 'Price Setting', 'kasuari' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'menu_price',
			[
				'label' => __( 'Menu Price', 'kasuari' ),
				'type' => Controls_Manager::TEXT,
				'default' => 'Rp. 100000',
				'title' => __( 'Enter your menu price', 'kasuari' ),
			]
		);

		$this->add_control(
			'typhography_price_color',
			[
				'label' => __( 'Price Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#000000',
				'selectors' => [
					'{{WRAPPER}} .menu-list__item-price' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'typhography_price_bg_color',
			[
				'label' => __( 'Price Background Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .menu-list__item-price' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'typography_menus_price',
				'label' => __( 'Price Font Setting', 'kasuari' ),
				'scheme' => Scheme_Typography::TYPOGRAPHY_3,
				'selector' => '{{WRAPPER}} .menu-list__item-price',
			]
		);

		$this->end_controls_section();
		/*-----------------------------------------------------------------------------------
			end of tour block title style setting
		-----------------------------------------------------------------------------------*/

		/*-----------------------------------------------------------------------------------*/
		/*  3. DESCRIPTION STYLE SETTING
		/*-----------------------------------------------------------------------------------*/
		$this->start_controls_section(
		'section_kasuari_menus_block_desc_style_setting',
			[
				'label' => __( 'Description Setting', 'kasuari' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'menu_desc',
			[
				'label' => __( 'Menu Description', 'kasuari' ),
				'type' => Controls_Manager::TEXTAREA,
				'default' => '',
				'title' => __( 'Enter your menu description', 'kasuari' ),
			]
		);

		$this->add_control(
			'typhography_desc_color',
			[
				'label' => __( 'Text Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#000000',
				'selectors' => [
					'{{WRAPPER}} .resto-menus-desc' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'typography_menus_desc',
				'label' => __( 'Text Font Setting', 'kasuari' ),
				'scheme' => Scheme_Typography::TYPOGRAPHY_3,
				'selector' => '{{WRAPPER}} .resto-menus-desc',
			]
		);

		$this->add_responsive_control(
			'menu_text_margin',
			[
				'label' => __( 'Margin Top Description', 'kasuari' ),
				'type' => Controls_Manager::TEXT,
				'default' => '0',
				'selectors' => [
					'{{WRAPPER}} .resto-menus-desc' => 'margin-top: {{VALUE}}px;',
				],
			]
		);

		$this->end_controls_section();
		/*-----------------------------------------------------------------------------------
			end of DESCRIPTION style setting
		-----------------------------------------------------------------------------------*/

		/*-----------------------------------------------------------------------------------*/
		/*  3. MENU IMAGE STYLE SETTING
		/*-----------------------------------------------------------------------------------*/
		$this->start_controls_section(
		'section_kasuari_menus_block_iamge_style_setting',
			[
				'label' => __( 'Image Setting', 'kasuari' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'use_image',
			[
				'label' => __( 'Use Menu Image', 'kasuari' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => 'Use',
				'label_off' => 'No',
				'return_value' => 'on',
				'default' => 'on',
			]
		);

		$this->add_control(
			'menu_img',
			[
				'label' => __( 'Menu Image', 'kasuari' ),
				'type' => Controls_Manager::MEDIA,
				'default' => [
					'url' => Utils::get_placeholder_image_src(),
				],
				'condition' => [
					'use_image' => 'on',
				],
			]
		);

		$this->add_control(
			'width',
			[
				'label' => __( 'Width', 'kasuari' ),
				'type' => Controls_Manager::TEXT,
				'default' => '100',
				'title' => __( 'Enter some text', 'kasuari' ),
				'description' => __( 'Crop your image width.', 'kasuari' ),
				'condition' => [
					'use_image' => 'on',
				],
			]
		);

		$this->add_control(
			'height',
			[
				'label' => __( 'Height', 'kasuari' ),
				'type' => Controls_Manager::TEXT,
				'default' => '100',
				'title' => __( 'Enter some text', 'kasuari' ),
				'description' => __( 'Crop your image height and also your post height.', 'kasuari' ),
				'condition' => [
					'use_image' => 'on',
				],
			]
		);

		$this->add_control(
			'menu_image_crop',
			[
				'label' => __( 'Force to Crop Image', 'kasuari' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => 'Use',
				'label_off' => 'No',
				'return_value' => 'on',
				'default' => 'off',
				'condition' => [
					'use_image' => 'on',
				],
			]
		);

		$this->add_control(
			'menu_image_radius',
			[
				'label' => __( 'Round or Square Image', 'kasuari' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => 'Use',
				'label_off' => 'No',
				'return_value' => 'on',
				'default' => 'off',
				'condition' => [
					'use_image' => 'on',
				],
			]
		);

		$this->end_controls_section();
		/*-----------------------------------------------------------------------------------
			end of menu image style setting
		-----------------------------------------------------------------------------------*/

	}

	protected function render() {

		$instance = $this->get_settings();

		$menu_name 		= ! empty( $instance['menu_name'] ) ? $instance['menu_name'] : 'Menu Name';
		$menu_price 	= ! empty( $instance['menu_price'] ) ? $instance['menu_price'] : 'Rp. 100000';
		$menu_desc 		= ! empty( $instance['menu_desc'] ) ? $instance['menu_desc'] : '';
		$menu_desc 		= ! empty( $instance['menu_desc'] ) ? $instance['menu_desc'] : '';

		$use_image 			= $instance['use_image'];
		$menu_img 			= ! empty( $instance['menu_img'] ) ? $instance['menu_img'] : '';
		$width 				= ! empty( $instance['width'] ) ? (int)$instance['width'] : 100;
		$height 			= ! empty( $instance['height'] ) ? (int)$instance['height'] : 100;
		$menu_image_crop 	= $instance['menu_image_crop'];
		$menu_image_radius 	= $instance['menu_image_radius'];

		include ( plugin_dir_path(__FILE__).'tpl/menus-block.php' );

	}

	protected function content_template() {}

	public function render_plain_content( $instance = [] ) {

	}

}

Plugin::instance()->widgets_manager->register_widget_type( new kasuari_restaurant_menu() );