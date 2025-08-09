<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

#[\AllowDynamicProperties]
class kasuari_team_block extends Widget_Base {

	public function get_name() {
		return 'kasuari-team-block';
	}

	public function get_title() {
		return __( 'Team', 'kasuari' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_categories() {
		return [ 'kasuari-general-category' ];
	}

	protected function _register_controls() {

		/*-----------------------------------------------------------------------------------
			TEAM BLOCK INDEX
			1. ELEMENT SETTING
			2. TEAM SETTING
			3. IMAGE SETTING
			4. ITEM SETTING
			5. LAYOUT SETTING
			6. TITLE SETTING
			7. JOB SETTING
			8. SOCIAL SETTING
		-----------------------------------------------------------------------------------*/

		/*-----------------------------------------------------------------------------------*/
		/*  1. ELEMENT SETTING
		/*-----------------------------------------------------------------------------------*/
		$this->start_controls_section(
			'section_kasuari_team_block_element_setting',
			[
				'label' => __( 'Element Setting', 'kasuari' ),
			]
		);

		$this->add_control(
			'team_pilih_layout',
			[
				'label' => __( 'Team Layouts', 'kasuari' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'team_grid_layout',
				'options' => [
					'team_grid_layout' => __( 'Grid Layout', 'kasuari' ),
				],
			]
		);

		/*if portfolio layout grid*/
		$this->add_control(
			'team_grid_style',
			[
				'label' => __( 'Team Grid Styles', 'kasuari' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'team_grid_style1',
				'options' => [
					'team_grid_style1' => __( 'Grid 1', 'kasuari' ),
					'team_grid_style2' => __( 'Grid 2', 'kasuari' ),
				],
				'condition' => [
					'team_pilih_layout' => 'team_grid_layout',
				],
			]
		);

		$this->end_controls_section();
		/*-----------------------------------------------------------------------------------
			end of team block element setting
		-----------------------------------------------------------------------------------*/

		/*-----------------------------------------------------------------------------------*/
		/*  2. TEAM SETTING
		/*-----------------------------------------------------------------------------------*/
		$this->start_controls_section(
			'section_kasuari_team_block_team_setting',
			[
				'label' => __( 'Team Setting', 'kasuari' ),
			]
		);

		$this->add_control(
			'team_item',
			[
				'label' => '',
				'type' => Controls_Manager::REPEATER,
				'default' => [
					[
						'text' => __( 'Team Item #1', 'kasuari' ),
					],
					[
						'text' => __( 'Team Item #2', 'kasuari' ),
					],
					[
						'text' => __( 'Team Item #3', 'kasuari' ),
					],
				],
				'fields' => [
					[
						'name' => 'team_name',
						'label' => __( 'Team Name', 'kasuari' ),
						'type' => Controls_Manager::TEXT,
						'label_block' => true,
						'placeholder' => __( 'Your client name.', 'kasuari' ),
						'default' => __( 'Your client name.', 'kasuari' ),
					],
					[
						'name' => 'team_job',
						'label' => __( 'Team Job', 'kasuari' ),
						'type' => Controls_Manager::TEXT,
						'label_block' => true,
						'placeholder' => __( 'Your client job.', 'kasuari' ),
						'default' => __( 'Your client job.', 'kasuari' ),
					],
					[
						'name' => 'team_bio',
						'label' => __( 'Team Bio', 'kasuari' ),
						'type' => Controls_Manager::WYSIWYG,
						'label_block' => true,
						'placeholder' => __( 'Your team biography text.', 'kasuari' ),
						'default' => __( 'Your team biography text.', 'kasuari' ),
						//repeater item teu bisa di kondisional
					],
					[
						'name' => 'team_image',
						'label' => __( 'Team Image', 'kasuari' ),
						'type' => Controls_Manager::MEDIA,
						'label_block' => true,
					],
					[
						'name' => 'team_facebook',
						'label' => __( 'Team Facebook', 'kasuari' ),
						'type' => Controls_Manager::URL,
						'label_block' => true,
						'placeholder' => __( 'http://your-link.com', 'kasuari' ),
					],
					[
						'name' => 'team_twitter',
						'label' => __( 'Team Twitter', 'kasuari' ),
						'type' => Controls_Manager::URL,
						'label_block' => true,
						'placeholder' => __( 'http://your-link.com', 'kasuari' ),
					],
					[
						'name' => 'team_instagram',
						'label' => __( 'Team Instagram', 'kasuari' ),
						'type' => Controls_Manager::URL,
						'label_block' => true,
						'placeholder' => __( 'http://your-link.com', 'kasuari' ),
					],
					[
						'name' => 'team_googleplus',
						'label' => __( 'Team Google+', 'kasuari' ),
						'type' => Controls_Manager::URL,
						'label_block' => true,
						'placeholder' => __( 'http://your-link.com', 'kasuari' ),
					],
				],
				'title_field' => '{{{ team_name }}}',
			]
		);

		$this->end_controls_section();
		/*-----------------------------------------------------------------------------------
			end of team block team setting
		-----------------------------------------------------------------------------------*/

		/*-----------------------------------------------------------------------------------*/
		/*  3. IMAGE SETTING
		/*-----------------------------------------------------------------------------------*/
		$this->start_controls_section(
		'section_kasuari_team_block_image_setting',
			[
				'label' => __( 'Image Setting', 'kasuari' ),
			]
		);

		$this->add_control(
			'width',
			[
				'label' => __( 'Width', 'kasuari' ),
				'type' => Controls_Manager::TEXT,
				'default' => '',
				'title' => __( 'Enter some text', 'kasuari' ),
				'description' => __( 'Crop your image width.', 'kasuari' ),
			]
		);

		$this->add_control(
			'height',
			[
				'label' => __( 'Height', 'kasuari' ),
				'type' => Controls_Manager::TEXT,
				'default' => '',
				'title' => __( 'Enter some text', 'kasuari' ),
				'description' => __( 'Crop your image height and also your post height.', 'kasuari' ),
			]
		);

		$this->add_control(
			'team_image_crop',
			[
				'label' => __( 'Force to Crop Image', 'kasuari' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => 'Use',
				'label_off' => 'No',
				'return_value' => 'on',
				'default' => 'off',
			]
		);

		$this->end_controls_section();
		/*-----------------------------------------------------------------------------------
			end of team block image setting
		-----------------------------------------------------------------------------------*/

		/*-----------------------------------------------------------------------------------*/
		/*  4. ITEM SETTING
		/*-----------------------------------------------------------------------------------*/
		$this->start_controls_section(
		'section_kasuari_team_block_item_setting',
			[
				'label' => __( 'Item Setting', 'kasuari' ),
			]
		);

		$this->add_responsive_control(
			'text_align',
			[
				'label' => __( 'Text Align', 'kasuari' ),
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
				],
				'selectors' => [
					'{{WRAPPER}} .team-inner-block' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'grid_item_bg',
			[
				'label' => __( 'Item Background Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#efefef',
				'selectors' => [
					'{{WRAPPER}} .team-inner-block' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'team_inner_bg',
			[
				'label' => __( 'Detail Background Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#000000',
				'selectors' => [
					'{{WRAPPER}} .team-details' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
		/*-----------------------------------------------------------------------------------
			end of team block image setting
		-----------------------------------------------------------------------------------*/

		/*-----------------------------------------------------------------------------------*/
		/*  5. LAYOUT SETTING
		/*-----------------------------------------------------------------------------------*/
		$this->start_controls_section(
			'section_kasuari_team_block_layout_setting',
			[
				'label' => __( 'Layout Setting', 'kasuari' ),
			]
		);

		$this->add_control(
			'choose_column',
			[
				'label' => __( 'Column', 'kasuari' ),
				'type' => Controls_Manager::SELECT,
				'default' => 3,
				'options' => [
					1 => __( '1', 'kasuari' ),
					2 => __( '2', 'kasuari' ),
					3 => __( '3', 'kasuari' ),
					4 => __( '4', 'kasuari' ),
					5 => __( '5', 'kasuari' ),
				]
			]
		);

		$this->add_control(
			'tablet_choose_column',
			[
				'label' => __( 'Tablet Column', 'kasuari' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'tablet-column-2',
				'options' => [
					'tablet-column-1' => __( '1', 'kasuari' ),
					'tablet-column-2' => __( '2', 'kasuari' ),
					'tablet-column-3' => __( '3', 'kasuari' ),
					'tablet-column-4' => __( '4', 'kasuari' ),
					'tablet-column-5' => __( '5', 'kasuari' ),
				],
			]
		);

		$this->add_control(
			'mobile_choose_column',
			[
				'label' => __( 'Mobile Column', 'kasuari' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'mobile-column-1',
				'options' => [
					'mobile-column-1' => __( '1', 'kasuari' ),
					'mobile-column-2' => __( '2', 'kasuari' ),
					'mobile-column-3' => __( '3', 'kasuari' ),
					'mobile-column-4' => __( '4', 'kasuari' ),
					'mobile-column-5' => __( '5', 'kasuari' ),
				],
			]
		);

		$this->add_control(
			'margin_bottom',
			[
				'label' => __( 'Margin Bottom Post', 'kasuari' ),
				'description' => __( 'Margin bottom for each item inside this block.', 'kasuari' ),
				'type' => Controls_Manager::NUMBER,
				'default' => '20',
				'selectors' => [
					'{{WRAPPER}} .team-block' => 'margin-bottom: {{VALUE}}px;',
				],	
			]
		);

		/*=========== padding ===========*/
		$this->add_control(
			'use_padding',
			[
				'label' => __( 'Use Padding', 'kasuari' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => '',
				'prefix_class' => 'extra-padding-',
				'label_on' => 'Use',
				'label_off' => 'No',
				'return_value' => 'use',
				'description' => __( 'Add a gap for each post item with padding.', 'kasuari' ),
			]
		);

		$this->add_responsive_control(
			'padding_size',
			[
				'label' => __( 'Padding Size', 'kasuari' ),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 20,
					'unit' => 'px',
				],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
				],
				'size_units' => [ 'px', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .team-block' => 'padding-right: calc( {{SIZE}}{{UNIT}}/2 ); padding-left: calc( {{SIZE}}{{UNIT}}/2 );',
				],
				'condition' => [
					'use_padding' => 'use',
				],
			]
		);

		$this->end_controls_section();
		/*-----------------------------------------------------------------------------------
			end of team block layout setting
		-----------------------------------------------------------------------------------*/

		/*-----------------------------------------------------------------------------------*/
		/*  6. TITLE SETTING
		/*-----------------------------------------------------------------------------------*/
		$this->start_controls_section(
		'section_kasuari_team_block_title_setting',
			[
				'label' => __( 'Title Setting', 'kasuari' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'use_title',
			[
				'label' => __( 'Use Title', 'kasuari' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => 'Use',
				'label_off' => 'No',
				'return_value' => 'on',
				'default' => 'on',
			]
		);

		$this->add_control(
			'typhography_title_color',
			[
				'label' => __( 'Title Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,	
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .team-name' => 'color: {{VALUE}};',
				],
				'condition' => [
					'use_title' => 'on',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'team_name_typography',
				'label' => __( 'Name Font Setting', 'kasuari' ),
				'scheme' => Scheme_Typography::TYPOGRAPHY_3,
				'selector' => '{{WRAPPER}} .team-name',
				'condition' => [
					'use_title' => 'on',
				],
			]
		);

		$this->end_controls_section();
		/*-----------------------------------------------------------------------------------
			end of team block title setting
		-----------------------------------------------------------------------------------*/

		/*-----------------------------------------------------------------------------------*/
		/*  7. JOB SETTING
		/*-----------------------------------------------------------------------------------*/
		$this->start_controls_section(
		'section_kasuari_team_block_job_setting',
			[
				'label' => __( 'Job Setting', 'kasuari' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'use_job',
			[
				'label' => __( 'Use Team Job', 'kasuari' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => 'Use',
				'label_off' => 'No',
				'return_value' => 'on',
				'default' => 'on',
			]
		);

		$this->add_control(
			'typhography_job_color',
			[
				'label' => __( 'Job Title Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,	
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .team-job' => 'color: {{VALUE}};',
				],
				'condition' => [
					'use_job' => 'on',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'team_job_typography',
				'label' => __( 'Job Font Setting', 'kasuari' ),
				'scheme' => Scheme_Typography::TYPOGRAPHY_3,
				'selector' => '{{WRAPPER}} .team-job',
				'condition' => [
					'use_job' => 'on',
				],
			]
		);

		$this->end_controls_section();
		/*-----------------------------------------------------------------------------------
			end of team block job setting
		-----------------------------------------------------------------------------------*/

		/*-----------------------------------------------------------------------------------*/
		/*  8. JOB SETTING
		/*-----------------------------------------------------------------------------------*/
		$this->start_controls_section(
		'section_kasuari_team_block_bio_setting',
			[
				'label' => __( 'Biography Setting', 'kasuari' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'team_grid_style' => 'team_grid_style2'
				],
			]
		);

		$this->add_control(
			'use_bio',
			[
				'label' => __( 'Use Team Bio', 'kasuari' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => 'Use',
				'label_off' => 'No',
				'return_value' => 'on',
				'default' => 'on',
				'condition' => [
					'team_grid_style' => 'team_grid_style2'
				],
			]
		);

		$this->add_control(
			'typhography_bio_color',
			[
				'label' => __( 'Bio Title Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,	
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .team-bio, {{WRAPPER}} .team-bio p' => 'color: {{VALUE}};',
				],
				'condition' => [
					'use_bio' => 'on',
					'team_grid_style' => 'team_grid_style2'
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'team_bio_typography',
				'label' => __( 'Bio Font Setting', 'kasuari' ),
				'scheme' => Scheme_Typography::TYPOGRAPHY_3,
				'selector' => '{{WRAPPER}} .team-bio, {{WRAPPER}} .team-bio p',
				'condition' => [
					'use_bio' => 'on',
					'team_grid_style' => 'team_grid_style2'
				],
			]
		);

		$this->end_controls_section();
		/*-----------------------------------------------------------------------------------
			end of team block job setting
		-----------------------------------------------------------------------------------*/

		/*-----------------------------------------------------------------------------------*/
		/*  7. SOCIAL SETTING
		/*-----------------------------------------------------------------------------------*/
		$this->start_controls_section(
		'section_kasuari_team_block_social_setting',
			[
				'label' => __( 'Social Link Setting', 'kasuari' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'use_social',
			[
				'label' => __( 'Use Social Links', 'kasuari' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => 'Use',
				'label_off' => 'No',
				'return_value' => 'on',
				'default' => 'on',
			]
		);

		$this->add_control(
			'typhography_social_color',
			[
				'label' => __( 'Social Icon Color', 'kasuari' ),
				'type' => Controls_Manager::COLOR,	
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .team-socials a' => 'color: {{VALUE}};',
				],
				'condition' => [
					'use_social' => 'on',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'team_social_typography',
				'label' => __( 'Social Link Setting', 'kasuari' ),
				'scheme' => Scheme_Typography::TYPOGRAPHY_3,
				'selector' => '{{WRAPPER}} .team-socials a',
				'condition' => [
					'use_social' => 'on',
				],
			]
		);

		$this->end_controls_section();
		/*-----------------------------------------------------------------------------------
			end of team block social setting
		-----------------------------------------------------------------------------------*/

	}

	protected function render() {

		$instance = $this->get_settings();

		$team_grid_style 		= ! empty( $instance['team_grid_style'] ) ? $instance['team_grid_style'] : 'team_grid_style1';

		$team_item 		= ! empty( $instance['team_item'] ) ? $instance['team_item'] : '';

		// Style Setting
		$width 					= ! empty( $instance['width'] ) ? (int)$instance['width'] : '';
		$height 				= ! empty( $instance['height'] ) ? (int)$instance['height'] : '';
		$team_image_crop 		= $instance['team_image_crop'];

		$use_title 				= $instance['use_title'];
		$use_job 				= $instance['use_job'];
		$use_bio 				= $instance['use_bio'];
		$use_social 			= $instance['use_social'];

		// Layout Setting
		$choose_column 			= ! empty( $instance['choose_column'] ) ? $instance['choose_column'] : 3;
		$tablet_choose_column 	= ! empty( $instance['tablet_choose_column'] ) ? $instance['tablet_choose_column'] : 'tablet-column-2';	
		$mobile_choose_column 	= ! empty( $instance['mobile_choose_column'] ) ? $instance['mobile_choose_column'] : 'mobile-column-1';	
		
		if($team_grid_style == 'team_grid_style1') {
			include ( plugin_dir_path(__FILE__).'tpl/team-block.php' );
		}
		elseif($team_grid_style == 'team_grid_style2') {
			include ( plugin_dir_path(__FILE__).'tpl/team-grid2-block.php' );
		}



		?>

		<?php

	}

	protected function content_template() {}

	public function render_plain_content( $instance = [] ) {}

}

Plugin::instance()->widgets_manager->register_widget_type( new kasuari_team_block() );