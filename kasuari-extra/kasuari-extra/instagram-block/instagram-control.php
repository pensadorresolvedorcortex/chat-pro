<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include ( plugin_dir_path(__FILE__).'config/instagram-style-setting.php' );

#[\AllowDynamicProperties]
class kasuari_instagram_block extends Widget_Base {

	public function get_name() {
		return 'kasuari-instagram-block';
	}

	public function get_title() {
		return __( 'Instagram', 'kasuari' );
	}

	public function get_icon() {
		return 'eicon-apps';
	}

	public function get_categories() {
		return [ 'kasuari-general-category' ];
	}

	protected function _register_controls() {

		/*-----------------------------------------------------------------------------------
			end of instagram block element setting
		-----------------------------------------------------------------------------------*/

		$this->start_controls_section(
			'section_kasuari_instagram_block_general_control',
			[
				'label' => __( 'Instagram Setting', 'kasuari' ),
			]
		);

		$this->add_control(
			'kasuari_instafeed_access_token',
			[
				'label' => esc_html__( 'Access Token', 'kasuari' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
				'default' => esc_html__( '6271285231.ba4c844.b73a97e4f9334781a4219b2ea2d1dcf0', 'kasuari' ),
				'description' => '<a href="http://instagramwordpress.rafsegat.com/docs/get-access-token/" class="kasuari-btn" target="_blank">Get Access Token</a> Please change it with yours.', 'kasuari',
			]
		);
		
		$this->add_control(
			'kasuari_instafeed_user_id',
			[
				'label' => esc_html__( 'User ID', 'kasuari' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
				'default' => esc_html__( '6271285231', 'kasuari' ),
				'description' => '<a href="https://smashballoon.com/instagram-feed/find-instagram-user-id/" class="kasuari-btn" target="_blank">Get User ID</a> Please change it with yours.', 'kasuari',
			]
		);

		
		$this->add_control(
			'kasuari_instafeed_client_id',
			[
				'label' => esc_html__( 'Client ID', 'kasuari' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
				'default' => esc_html__( 'ca8ed4414e4e49779113a90fdf9aca2e', 'kasuari' ),
				'description' => '<a href="https://www.instagram.com/developer/clients/manage/" class="kasuari-btn" target="_blank">Get Client ID</a> Please change it with yours.', 'kasuari',
			]
		);

		$this->add_control(
			'kasuari_instafeed_source',
			[
				'label' => esc_html__( 'Instagram Feed Source', 'kasuari-extra' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'user',
				'options' => [
					'user' => esc_html__( 'User', 'kasuari-extra' ),
					'tagged' => esc_html__( 'Hashtag', 'kasuari-extra' ),
				],
			]
		);

		$this->add_control(
			'kasuari_instafeed_hashtag',
			[
				'label' => esc_html__( 'Hashtag', 'kasuari-extra' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
				'default' => esc_html__( 'cars', 'kasuari-extra' ),
				'condition' => [
					'kasuari_instafeed_source' => 'tagged',
				],
				'description' => 'Place the hashtag', 'kasuari-extra',
			]
		);

		$this->add_control(
			'kasuari_instafeed_image_count',
			[
				'label' => esc_html__( 'Max Visible Images', 'kasuari-extra' ),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 12,
				],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 100,
					],
				],
			]
		);

		$this->end_controls_section();

		/*-----------------------------------------------------------------------------------*/
		/*  3. IMAGE SETTING
		/*-----------------------------------------------------------------------------------*/
		$this->start_controls_section(
			'section_kasuari_instagram_block_image_setting',
			[
				'label' => __( 'Image Setting', 'kasuari' ),
			]
		);

		$gallery_columns = range( 1, 10 );
		$gallery_columns = array_combine( $gallery_columns, $gallery_columns );
		$this->add_control(
			'kasuari_instafeed_columns',
			[
				'label' => esc_html__( 'Number of Columns', 'kasuari-extra' ),
				'type' => Controls_Manager::SELECT,
				'default' => 3,
				'options' => $gallery_columns,
			]
		);

		$this->add_control(
			'tablet_choose_column',
			[
				'label' => __( 'Column Tablet', 'raung' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'tablet-column-2',
				'options' => [
					'tablet-column-1' => __( '1', 'raung' ),
					'tablet-column-2' => __( '2', 'raung' ),
					'tablet-column-3' => __( '3', 'raung' ),
					'tablet-column-4' => __( '4', 'raung' ),
					'tablet-column-5' => __( '5', 'raung' ),
				],
			]
		);

		$this->add_control(
			'mobile_choose_column',
			[
				'label' => __( 'Column Mobile', 'raung' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'mobile-column-1',
				'options' => [
					'mobile-column-1' => __( '1', 'raung' ),
					'mobile-column-2' => __( '2', 'raung' ),
					'mobile-column-3' => __( '3', 'raung' ),
					'mobile-column-4' => __( '4', 'raung' ),
					'mobile-column-5' => __( '5', 'raung' ),
				],
			]
		);


		$this->add_control(
			'kasuari_instafeed_image_resolution',
			[
				'label' => esc_html__( 'Image Resolution', 'kasuari-extra' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'low_resolution',
				'options' => [
					'thumbnail' => esc_html__( 'Thumbnail (150x150)', 'kasuari-extra' ),
					'low_resolution' => esc_html__( 'Low Res (306x306)',   'kasuari-extra' ),
					'standard_resolution' => esc_html__( 'Standard (612x612)', 'kasuari-extra' ),
				],
			]
		);

		$this->add_control(
			'kasuari_instafeed_sort_by',
			[
				'label' => esc_html__( 'Sort By', 'kasuari-extra' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'none',
				'options' => [
					'none' => esc_html__( 'None', 'kasuari-extra' ),
					'most-recent' => esc_html__( 'Most Recent',   'kasuari-extra' ),
					'least-recent' => esc_html__( 'Least Recent', 'kasuari-extra' ),
					'most-liked' => esc_html__( 'Most Likes', 'kasuari-extra' ),
					'least-liked' => esc_html__( 'Least Likes', 'kasuari-extra' ),
					'most-commented' => esc_html__( 'Most Commented', 'kasuari-extra' ),
					'least-commented' => esc_html__( 'Least Commented', 'kasuari-extra' ),
					'random' => esc_html__( 'Random', 'kasuari-extra' ),
				],
			]
		);

		$this->add_control(
			'kasuari_instafeed_caption_heading',
			[
				'label' => __( 'Caption & Link', 'kasuari-extra' ),
				'type' => Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'kasuari_instafeed_caption',
			[
				'label' => esc_html__( 'Display Caption', 'kasuari-extra' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'show-caption',
				'default' => 'no-caption',
			]
		);

		$this->add_control(
			'kasuari_instafeed_link',
			[
				'label' => esc_html__( 'Enable Link', 'kasuari-extra' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'kasuari_instafeed_link_target',
			[
				'label' => esc_html__( 'Open in new window?', 'kasuari-extra' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => '_blank',
				'default' => '_blank',
				'condition' => [
					'kasuari_instafeed_link' => 'yes',
				],
			]
		);

		$this->end_controls_section();
		/*-----------------------------------------------------------------------------------
			end of portfolio block image setting
		-----------------------------------------------------------------------------------*/

		$this->start_controls_section(
			'kasuari_section_instafeed_styles_general',
			[
				'label' => esc_html__( 'Instagram Feed Styles', 'kasuari-extra' ),
				'tab' => Controls_Manager::TAB_STYLE
			]
		);
		
		$this->add_responsive_control(
			'kasuari_instafeed_spacing',
			[
				'label' => esc_html__( 'Padding Between Images', 'kasuari-extra' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .kasuari-insta-feed-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'kasuari_instafeed_box_border',
				'label' => esc_html__( 'Border', 'kasuari-extra' ),
				'selector' => '{{WRAPPER}} .kasuari-insta-feed-wrap',
			]
		);

		$this->add_control(
			'kasuari_instafeed_box_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'kasuari-extra' ),
				'type' => Controls_Manager::DIMENSIONS,
				'selectors' => [
					'{{WRAPPER}} .kasuari-insta-feed-wrap' => 'border-radius: {{TOP}}px {{RIGHT}}px {{BOTTOM}}px {{LEFT}}px;',
				],
			]
		);
		
		$this->end_controls_section();
		
		
		$this->start_controls_section(
			'kasuari_section_instafeed_styles_content',
			[
				'label' => esc_html__( 'Color &amp; Typography', 'kasuari-extra' ),
				'tab' => Controls_Manager::TAB_STYLE
			]
		);


		$this->add_control(
			'kasuari_instafeed_overlay_color',
			[
				'label' => esc_html__( 'Hover Overlay Color', 'kasuari-extra' ),
				'type' => Controls_Manager::COLOR,
				'default' => 'rgba(0,0,0, .75)',
				'selectors' => [
					'{{WRAPPER}} .kasuari-insta-feed-wrap::after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'kasuari_instafeed_like_comments_heading',
			[
				'label' => __( 'Like & Comments Styles', 'kasuari-extra' ),
				'type' => Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'kasuari_instafeed_like_comments_color',
			[
				'label' => esc_html__( 'Like &amp; Comments Color', 'kasuari-extra' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .kasuari-insta-likes-comments > p' => 'color: {{VALUE}};',
				],
			]
		);
		
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
             'name' => 'kasuari_instafeed_like_comments_typography',
				'scheme' => Scheme_Typography::TYPOGRAPHY_2,
				'selector' => '{{WRAPPER}} .kasuari-insta-likes-comments > p',
			]
		);

		$this->add_control(
			'kasuari_instafeed_caption_style_heading',
			[
				'label' => __( 'Caption Styles', 'kasuari-extra' ),
				'type' => Controls_Manager::HEADING,
			]
		);

		$this->add_control(
			'kasuari_instafeed_caption_color',
			[
				'label' => esc_html__( 'Caption Color', 'kasuari-extra' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .kasuari-insta-info-wrap' => 'color: {{VALUE}};',
				],
			]
		);
		
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
             'name' => 'kasuari_instafeed_caption_typography',
				'scheme' => Scheme_Typography::TYPOGRAPHY_2,
				'selector' => '{{WRAPPER}} .kasuari-insta-info-wrap',
			]
		);		


		$this->end_controls_section();

	}

	protected function render() {

	$settings = $this->get_settings();

	$image_limit 	= $this->get_settings( 'kasuari_instafeed_image_count' ); 
	$link_target  = ( ($settings['kasuari_instafeed_link_target'] == 'yes') ? "_blank" : "_self" );
	$enable_link  = ( ($settings['kasuari_instafeed_link'] == 'yes') ? "<a href=\"{{link}}\" target=\"$link_target\"></a>" : "" );
	$no_caption   = ( ($settings['kasuari_instafeed_caption'] == 'show-caption') ? "show-caption" : "no-caption" );
	$show_caption = ( ($settings['kasuari_instafeed_caption'] == 'show-caption') ? '<p class="insta-caption">{{caption}}</p>' : "" );

	$tablet_choose_column 	= ! empty( $settings['tablet_choose_column'] ) ? $settings['tablet_choose_column'] : 'tablet-column-2';
	$mobile_choose_column 	= ! empty( $settings['mobile_choose_column'] ) ? $settings['mobile_choose_column'] : 'mobile-column-1';

	//wp_enqueue_script('kasuari-instafeed-js', NIKAH_EXTRA_URL.'inc/js/kasuari-instafeed.min.js', array('jquery', 'masonry'), true);	
	?>
	<div class="kasuari-instagram-feed instagram-builder <?php echo $no_caption; ?> column-<?php echo esc_attr($settings['kasuari_instafeed_columns'] ); ?>">
		<div id="kasuari-instagram-feed-<?php echo esc_attr($this->get_id()); ?>" class="kasuari-insta-grid">
		</div>
	</div>


	<script type="text/javascript">

	jQuery(document).ready(function($) {
	  var feed = new Instafeed({
	    get: '<?php echo esc_attr($settings['kasuari_instafeed_source'] ); ?>',
	    tagName: '<?php echo esc_attr($settings['kasuari_instafeed_hashtag'] ); ?>',
	    userId: <?php echo esc_attr($settings['kasuari_instafeed_user_id'] ); ?>,
	    clientId: '<?php echo esc_attr($settings['kasuari_instafeed_client_id'] ); ?>',
	    accessToken: '<?php echo esc_attr($settings['kasuari_instafeed_access_token'] ); ?>',
	    limit: '<?php echo $image_limit['size']; ?>',
	    resolution: '<?php echo esc_attr($settings['kasuari_instafeed_image_resolution'] ); ?>',
	    sortBy: '<?php echo esc_attr($settings['kasuari_instafeed_sort_by'] ); ?>',
	    target: 'kasuari-instagram-feed-<?php echo esc_attr($this->get_id()); ?>',
	    template: '<div class="instagram-item kasuari-insta-feed kasuari-insta-box <?php echo esc_attr($mobile_choose_column); ?> <?php echo esc_attr($tablet_choose_column); ?>"><div class="kasuari-insta-feed-inner"><div class="kasuari-insta-feed-wrap"><div class="kasuari-insta-img-wrap"><img src="{{image}}" /></div><div class="kasuari-insta-info-wrap"><div class="kasuari-insta-likes-comments"><p> <i class="fa fa-heart-o" aria-hidden="true"></i> {{likes}}</p> <p><i class="fa fa-comment-o" aria-hidden="true"></i> {{comments}}</p> </div><?php echo $show_caption; ?></div><?php echo $enable_link; ?></div></div></div>',
	    after: function() {
	      var el = document.getElementById('kasuari-instagram-feed-<?php echo esc_attr($this->get_id()); ?>');
	      if (el.classList)
	        el.classList.add('show');
	      else
	        el.className += ' ' + 'show';
	    }
	  });
	  feed.run();
	  });

	</script>

	<script type="text/javascript">
	jQuery(document).ready(function($) {
		'use strict';
		  $(window).load(function(){

		    $('.kasuari-insta-grid').masonry({
		      itemSelector: '.kasuari-insta-feed',
		      percentPosition: true,
		      columnWidth: '.kasuari-insta-box'
		    });

		  });
	});
	</script>
	
	<?php
	
	}

	protected function content_template() {}

	public function render_plain_content( $instance = [] ) {

	}

}

Plugin::instance()->widgets_manager->register_widget_type( new kasuari_instagram_block() );