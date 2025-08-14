<?php
/**
 * Contains Basic Functions for Easy Real Estate plugin.
 */

/**
 * Get template part for ERE plugin.
 *
 * @access public
 *
 * @param mixed  $slug Template slug.
 * @param string $name Template name (default: '').
 */
function ere_get_template_part( $slug, string $name = '' ) {
	$template = '';

	// Get slug-name.php.
	if ( $name && file_exists( ERE_PLUGIN_DIR . "/{$slug}-{$name}.php" ) ) {
		$template = ERE_PLUGIN_DIR . "/{$slug}-{$name}.php";
	}

	// Get slug.php.
	if ( ! $template && file_exists( ERE_PLUGIN_DIR . "/{$slug}.php" ) ) {
		$template = ERE_PLUGIN_DIR . "/{$slug}.php";
	}

	// Allow 3rd party plugins to filter template file from their plugin.
	$template = apply_filters( 'ere_get_template_part', $template, $slug, $name );

	if ( $template ) {
		load_template( $template, false );
	}
}

if ( ! function_exists( 'ere_log' ) ) {
	/**
	 * Function to help in debugging
	 *
	 * @param $message
	 */
	function ere_log( $message ) {
		if ( WP_DEBUG === true ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				error_log( print_r( $message, true ) );
			} else {
				error_log( $message );
			}
		}
	}
}

if ( ! function_exists( 'ere_exclude_CPT_meta_keys_from_rest_api' ) ) {

	add_filter( 'ere_property_rest_api_meta', 'ere_exclude_CPT_meta_keys_from_rest_api' );
	add_filter( 'ere_agency_rest_api_meta', 'ere_exclude_CPT_meta_keys_from_rest_api' );
	add_filter( 'ere_agent_rest_api_meta', 'ere_exclude_CPT_meta_keys_from_rest_api' );

	function ere_exclude_CPT_meta_keys_from_rest_api( $post_meta ) {

		$exclude_keys = array(
			//'_thumbnail_id',
			'_vc_post_settings',
			'_dp_original',
			'_edit_lock',
			'_wp_old_slug',
			'slide_template',
			'REAL_HOMES_banner_sub_title',
		);

		// excluding keys
		foreach ( $exclude_keys as $key ) {
			if ( key_exists( $key, $post_meta ) ) {
				unset( $post_meta[ $key ] );
			}
		}

		// return the post meta
		return $post_meta;
	}
}

if ( ! function_exists( 'ere_get_current_user_ip' ) ) {
	/**
	 * Return current user IP
	 *
	 * @return string|string[]|null
	 */
	function ere_get_current_user_ip() {
		return preg_replace( '/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR'] );
	}
}

if ( ! function_exists( 'ere_generate_posts_list' ) ) {
	/**
	 * Generates options list for given post arguments
	 *
	 * @param     $post_args
	 * @param int $selected
	 */
	function ere_generate_posts_list( $post_args, $selected = 0 ) {

		$defaults = array( 'posts_per_page' => -1, 'suppress_filters' => true );

		if ( is_array( $post_args ) ) {
			$post_args = wp_parse_args( $post_args, $defaults );
		} else {
			$post_args = wp_parse_args( array( 'post_type' => $post_args ), $defaults );
		}

		$posts = get_posts( $post_args );

		if ( isset( $selected ) && is_array( $selected ) ) {
			foreach ( $posts as $post ) :
				?>
                <option value="<?php echo esc_attr( $post->ID ); ?>" <?php if ( in_array( $post->ID, $selected ) ) {
				echo "selected";
			} ?>><?php echo esc_html( $post->post_title ); ?></option><?php
			endforeach;
		} else {
			foreach ( $posts as $post ) :
				?>
                <option value="<?php echo esc_attr( $post->ID ); ?>" <?php if ( isset( $selected ) && ( $selected == $post->ID ) ) {
				echo "selected";
			} ?>><?php echo esc_html( $post->post_title ); ?></option><?php
			endforeach;
		}
	}
}

if ( ! function_exists( 'ere_display_property_label' ) ) {
	/**
	 * Display property label
	 *
	 * @param $post_id
	 */
	function ere_display_property_label( $post_id ) {

		$label_text = get_post_meta( $post_id, 'inspiry_property_label', true );
		$color      = get_post_meta( $post_id, 'inspiry_property_label_color', true );
		if ( ! empty ( $label_text ) ) {
			?>
            <span <?php if ( ! empty( $color ) ){ ?>style="background: <?php echo esc_attr( $color ); ?>"<?php } ?>
                  class='property-label'><?php echo esc_html( $label_text ); ?></span>
			<?php

		}
	}
}

if ( ! function_exists( 'ere_include_compare_icon' ) ) {
	/**
	 * Include compare icon
	 */
	function ere_include_compare_icon() {

		if ( defined( 'INSPIRY_THEME_DIR' ) ) {
			include( INSPIRY_THEME_DIR . '/images/icons/icon-compare.svg' );
		} else {
			include( ERE_PLUGIN_DIR . '/images/icons/icon-compare.svg' );
		}

	}
}

if ( ! function_exists( 'ere_framework_excerpt' ) ) {
	/**
	 * Output custom excerpt of required length
	 *
	 * @param int    $len  number of words
	 * @param string $trim string to appear after excerpt
	 */
	function ere_framework_excerpt( $len = 15, $trim = "&hellip;" ) {
		echo ere_get_framework_excerpt( $len, $trim );
	}
}

if ( ! function_exists( 'ere_get_framework_excerpt' ) ) {
	/**
	 * Returns custom excerpt of required length
	 *
	 * @param int    $len  number of words
	 * @param string $trim string after excerpt
	 *
	 * @return array|string
	 */
	function ere_get_framework_excerpt( $len = 15, $trim = "&hellip;" ) {
		$limit     = $len + 1;
		$excerpt   = explode( ' ', get_the_excerpt(), $limit );
		$num_words = count( $excerpt );
		if ( $num_words >= $len ) {
			array_pop( $excerpt );
		} else {
			$trim = "";
		}
		$excerpt = implode( " ", $excerpt ) . "$trim";

		return $excerpt;
	}
}

if ( ! function_exists( 'ere_social_networks' ) ) {
	/**
	 * Print social networks
	 *
	 * @param array $args Optional. Arguments to change container and icon classes.
	 *
	 * @return string
	 */
	function ere_social_networks( $args = array() ) {

		// Return if social menu display options is disabled.
		if ( 'true' !== get_option( 'theme_show_social_menu', 'false' ) ) {
			return false;
		}

		$defaults = array(
			'echo'            => 1,
			'container'       => 'ul',
			'container_class' => 'social_networks clearfix',
			'icon_size_class' => 'fa-lg',
			'replace_icons'   => array(),
			'link_target'     => '_blank',
		);

		$args = wp_parse_args( $args, $defaults );

		$default_social_networks = array(
			'facebook'  => array(
				'url'  => get_option( 'theme_facebook_link' ),
				'icon' => 'fab fa-facebook-square'
			),
			'twitter'   => array(
				'url'  => get_option( 'theme_twitter_link' ),
				'icon' => 'fab fa-twitter'
			),
			'linkedin'  => array(
				'url'  => get_option( 'theme_linkedin_link' ),
				'icon' => 'fab fa-linkedin'
			),
			'instagram' => array(
				'url'  => get_option( 'theme_instagram_link' ),
				'icon' => 'fab fa-instagram'
			),
			'pinterest' => array(
				'url'  => get_option( 'theme_pinterest_link' ),
				'icon' => 'fab fa-pinterest'
			),
			'youtube'   => array(
				'url'  => get_option( 'theme_youtube_link' ),
				'icon' => 'fab fa-youtube'
			),
			'skype'     => array(
				'url'  => get_option( 'theme_skype_username' ),
				'icon' => 'fab fa-skype'
			),
			'rss'       => array(
				'url'  => get_option( 'theme_rss_link' ),
				'icon' => 'fas fa-rss'
			),
			'line'      => array(
				'url'  => get_option( 'theme_line_id' ),
				'icon' => 'fab fa-line'
			),
		);

		$additional_social_networks = get_option( 'theme_social_networks', array() );
		$social_networks            = apply_filters( 'inspiry_header_social_networks', $default_social_networks + $additional_social_networks );

		$html = '<' . $args['container'] . ' class="' . esc_attr( $args['container_class'] ) . '">';

		foreach ( $social_networks as $title => $social_network ) {

			$social_network_title = $title;
			$social_network_url   = $social_network['url'];
			$social_network_icon  = $social_network['icon'];

			if ( isset( $social_network['title'] ) && ! empty( $social_network['title'] ) ) {
				$social_network_title = strtolower( str_replace( ' ', '-', $social_network['title'] ) );
			}

			if ( ! empty( $social_network_title ) && ! empty( $social_network_url ) && ! empty( $social_network_icon ) ) {

				if ( 'skype' === $social_network_title ) {
					$social_network_url = esc_attr( 'skype:' . $social_network_url );
				} else if ( 'line' === $social_network_title ) {
					$social_network_url = esc_url( 'https://line.me/ti/p/' . $social_network_url );
				} else {
					$social_network_url = esc_url( $social_network_url );
				}

				if ( ! empty( $args['replace_icons'] ) ) {

					if ( array_key_exists( $social_network_title, $args['replace_icons'] ) ) {
						$social_network_icon = $args['replace_icons'][ $social_network_title ];
					}
				}

				$social_network_icon = array(
					$social_network_icon,
					$args['icon_size_class']
				);
				$icon_classes        = implode( " ", $social_network_icon );

				if ( 'ul' === $args['container'] ) {
					$format = '<li class="%s"><a href="%s" target="%s"><i class="%s"></i></a></li>';
				} else {
					$format = '<a class="%s" href="%s" target="%s"><i class="%s"></i></a>';
				}

				$html .= sprintf( $format, esc_attr( $social_network_title ), $social_network_url, esc_attr( $args['link_target'] ), esc_attr( $icon_classes ) );
			}
		}

		$html .= '</' . $args['container'] . '>';

		if ( $args['echo'] ) {
			echo $html;
		} else {
			return $html;
		}
	}
}

if ( is_admin() && ! function_exists( 'inspiry_remove_revolution_slider_meta_boxes' ) ) {
	/**
	 * Remove Rev Slider Metabox
	 */
	function inspiry_remove_revolution_slider_meta_boxes() {

		$post_types = apply_filters( 'inspiry_remove_revolution_slider_meta_boxes',
			array(
				'page',
				'post',
				'property',
				'agency',
				'agent',
				'partners',
				'slide',
			)
		);

		remove_meta_box( 'mymetabox_revslider_0', $post_types, 'normal' );
	}

	add_action( 'do_meta_boxes', 'inspiry_remove_revolution_slider_meta_boxes' );
}

if ( ! function_exists( 'inspiry_is_property_analytics_enabled' ) ) {
	/**
	 * Check property analytics feature is enabled or disabled.
	 */
	function inspiry_is_property_analytics_enabled() {
		return 'enabled' === get_option( 'inspiry_property_analytics_status', 'disabled' );
	}
}

if ( ! function_exists( 'ere_epc_default_fields' ) ) {
	/**
	 * Return Enenergy Performance Certificate default fields.
	 */
	function ere_epc_default_fields() {

		return apply_filters(
			'ere_epc_default_fields',
			array(
				array(
					'name'  => 'A+',
					'color' => '#00845a',
				),
				array(
					'name'  => 'A',
					'color' => '#18b058',
				),
				array(
					'name'  => 'B',
					'color' => '#8dc643',
				),
				array(
					'name'  => 'C',
					'color' => '#ffcc01',
				),
				array(
					'name'  => 'D',
					'color' => '#f6ac63',
				),
				array(
					'name'  => 'E',
					'color' => '#f78622',
				),
				array(
					'name'  => 'F',
					'color' => '#ef1d3a',
				),
				array(
					'name'  => 'G',
					'color' => '#d10400',
				),
			)
		);
	}
}

if ( ! function_exists( 'ere_safe_include_svg' ) ) {
	/**
	 * Includes svg file in the ERE Plugin.
	 *
	 * @param string $file
	 *
	 */
	function ere_safe_include_svg( $file ) {
		$file = ERE_PLUGIN_DIR . $file;
		if ( file_exists( $file ) ) {
			include( $file );
		}
	}
}

if ( ! function_exists( 'ere_property_meta_icon' ) ) {
	/**
	 * Displays property meta icon based on field name.
	 *
	 * @since 2.2.0
	 *
	 * @param string $field_name The name of the property field associated with the icon.
	 * @param string $icon_name The name of the SVG icon to be displayed.
	 *
	 * @return void
	 */
	function ere_property_meta_icon( $field_name, $icon_name ) {
		if ( function_exists( 'realhomes_property_meta_icon_custom' ) && ! realhomes_property_meta_icon_custom( $field_name ) ) {
			ere_safe_include_svg( '/images/icons/' . $icon_name . '.svg' );
		}
	}
}

if ( ! function_exists( 'ere_stylish_meta' ) ) {
	function ere_stylish_meta( $label, $post_meta_key, $icon, $postfix = '' ) {
		$property_id = get_the_ID();
		$post_meta   = get_post_meta( $property_id, $post_meta_key, true );

		if ( isset( $post_meta ) && ! empty( $post_meta ) ) {
			?>
            <div class="rh_prop_card__meta">
				<?php
				if ( $label ) {
					?>
                    <span class="rh_meta_titles"><?php echo esc_html( $label ); ?></span>
					<?php
				}
				?>
                <div class="rh_meta_icon_wrapper">
	                <?php ere_property_meta_icon( $post_meta_key, $icon ); ?>
                    <span class="figure"><?php echo esc_html( $post_meta ); ?></span>
	                <?php
	                if ( ! empty( $postfix ) ) {
		                if ( 'REAL_HOMES_property_size_postfix' === $postfix ) {
			                $get_postfix = realhomes_get_area_unit( $property_id );
		                } else if ( 'REAL_HOMES_property_lot_size_postfix' === $postfix ) {
			                $get_postfix = realhomes_get_lot_unit( $property_id );
		                } else {
			                $get_postfix = get_post_meta( $property_id, $postfix, true );
		                }

		                if ( ! empty( $get_postfix ) ) {
			                ?>
                            <span class="label"><?php echo esc_html( $get_postfix ); ?></span>
			                <?php
		                }
	                }
	                ?>
                </div>
            </div>
			<?php
		}
	}
}

if ( ! function_exists( 'ere_stylish_meta_smart' ) ) {
	function ere_stylish_meta_smart( $label, $post_meta_key, $icon, $postfix = '' ) {
		$property_id = get_the_ID();
		$post_meta   = get_post_meta( $property_id, $post_meta_key, true );

		if ( isset( $post_meta ) && ! empty( $post_meta ) ) {
			?>
            <div class="rh_prop_card__meta">
				<?php
				if ( $label ) {
					?>
                    <span class="rhea_meta_titles"><?php echo esc_html( $label ); ?></span>
					<?php
				}
				?>
                <div class="rhea_meta_icon_wrapper">
                    <span data-tooltip="<?php echo esc_html( $label ) ?>">
					<?php ere_property_meta_icon( $post_meta_key, $icon ); ?>
                    </span>
                    <span class="rhea_meta_smart_box">
                    <span class="figure"><?php echo esc_html( $post_meta ); ?></span>
	                    <?php
	                    if ( ! empty( $postfix ) ) {
		                    if ( 'REAL_HOMES_property_size_postfix' === $postfix ) {
			                    $get_postfix = realhomes_get_area_unit( $property_id );
		                    } else if ( 'REAL_HOMES_property_lot_size_postfix' === $postfix ) {
			                    $get_postfix = realhomes_get_lot_unit( $property_id );
		                    } else {
			                    $get_postfix = get_post_meta( $property_id, $postfix, true );
		                    }

		                    if ( ! empty( $get_postfix ) ) {
			                    ?>
                                <span class="label"><?php echo esc_html( $get_postfix ); ?></span>
			                    <?php
		                    }
	                    }
	                    ?>
                    </span>
                </div>
            </div>
			<?php
		}
	}
}

if ( ! function_exists( 'ere_ultra_meta' ) ) {
	function ere_ultra_meta( $label, $post_meta_key, $icon, $postfix = '' ) {
		$property_id = get_the_ID();
		$post_meta   = get_post_meta( $property_id, $post_meta_key, true );

		if ( isset( $post_meta ) && ! empty( $post_meta ) ) {
			?>
            <div class="rh-ultra-prop-card-meta">
                <div class="rh-ultra-meta-icon-wrapper">
                    <span class="rh-ultra-meta-icon" data-tooltip="<?php echo esc_html( $label ) ?>">
					<?php ere_property_meta_icon( $post_meta_key, $icon ); ?>
                    </span>
                    <span class="rh-ultra-meta-box">
                    <span class="figure"><?php echo esc_html( $post_meta ); ?></span>
	                    <?php
	                    if ( ! empty( $postfix ) ) {
		                    if ( 'REAL_HOMES_property_size_postfix' === $postfix ) {
			                    $get_postfix = realhomes_get_area_unit( $property_id );
		                    } else if ( 'REAL_HOMES_property_lot_size_postfix' === $postfix ) {
			                    $get_postfix = realhomes_get_lot_unit( $property_id );
		                    } else {
			                    $get_postfix = get_post_meta( $property_id, $postfix, true );
		                    }

		                    if ( ! empty( $get_postfix ) ) {
			                    ?>
                                <span class="label"><?php echo esc_html( $get_postfix ); ?></span>
			                    <?php
		                    }
	                    }
	                    ?>
                    </span>
                </div>
            </div>
			<?php
		}
	}
}

if ( ! function_exists( 'ere_display_property_status_html' ) ) {
	/**
	 * Display property status.
	 *
	 * @param $post_id
	 *
	 */
	function ere_display_property_status_html( $post_id ) {
		$status_terms = get_the_terms( $post_id, 'property-status' );

		if ( ! empty( $status_terms ) && ! is_wp_error( $status_terms ) ) {


			foreach ( $status_terms as $term ) {

				$inspiry_property_status_bg = get_term_meta( $term->term_id, 'inspiry_property_status_bg', true );
				$status_bg                  = '';
				if ( ! empty( $inspiry_property_status_bg ) ) {
					$status_bg = " background:" . "$inspiry_property_status_bg; ";
				}
				$inspiry_property_status_text = get_term_meta( $term->term_id, 'inspiry_property_status_text', true );

				$status_text = '';
				if ( ! empty( $inspiry_property_status_text ) ) {
					$status_text = " color:" . "$inspiry_property_status_text; ";
				}

				?>
                <span class="rh_prop_status_sty" style="<?php echo esc_attr( $status_bg . $status_text ) ?>">
                <?php
                echo esc_html( $term->name );
                ?>
				</span>
				<?php
			}
		}

	}
}

if ( ! function_exists( 'ere_get_scheduled_times' ) ) {
	/**
	 * Get scheduled times for the current property
	 *
	 * @since  2.0.0
	 *
	 * @return string 
	 */
	function ere_get_scheduled_times( $current_schedules ) {

		if ( ! empty( $current_schedules ) ) {
			$table = '<table class="schedule-time-list">';
			$table .= '<tr>';
			$table .= '<th>' . esc_html__( 'Date/Time', ERE_TEXT_DOMAIN ) . '</th>';
			$table .= '<th>' . esc_html__( 'Tour Type', ERE_TEXT_DOMAIN ) . '</th>';
			$table .= '<th>' . esc_html__( 'Name', ERE_TEXT_DOMAIN ) . '</th>';
			$table .= '<th>' . esc_html__( 'Phone', ERE_TEXT_DOMAIN ) . '</th>';
			$table .= '<th>' . esc_html__( 'Email', ERE_TEXT_DOMAIN ) . '</th>';
			$table .= '</tr>';

			// reversing the existing array to display the new requests on the top
			$current_schedules = array_reverse( $current_schedules );

			foreach ( $current_schedules as $schedule ) {
				$table .= '<tr>';
				$table .= ( isset( $schedule['selected_date'] ) || isset( $schedule['selected_time'] ) ) ? '<td>' . esc_html( $schedule['selected_date'] ) . ', ' . esc_html( $schedule['selected_time'] ) . '</td>' : '<td></td>';
				$table .= isset( $schedule['schedule_type'] ) ? '<td>' . esc_html( $schedule['schedule_type'] ) . '</td>' : '<td></td>';
				$table .= isset( $schedule['user_name'] ) ? '<td>' . esc_html( $schedule['user_name'] ) . '</td>' : '<td></td>';
				$table .= isset( $schedule['user_phone'] ) ? '<td>' . esc_html( $schedule['user_phone'] ) . '</td>' : '<td></td>';
				$table .= isset( $schedule['user_email'] ) ? '<td>' . esc_html( $schedule['user_email'] ) . '</td>' : '<td></td>';
				$table .= '</tr>';
			}

			$table .= '</table>';

			return $table;
		}

		return '<div class="sat-no-tours">' . esc_html__( 'There are no tour requests for this property.', ERE_TEXT_DOMAIN ) . '</div>';
	}
}
if ( ! function_exists( 'ere_share_post' ) ) {
	/**
	 * Displays post social sharing links.
	 *
	 * @since 1.*.*
	 *
	 * @param int|WP_Post $post_id Optional. Post ID or WP_Post object. Default is the global `$post`.
	 *
	 * @return void
	 */
	function ere_share_post( $post_id = 0 ) {
		$post = get_post( $post_id );

		// Holds HTML structure of social sharing buttons
		$output = '';

		// Get current page URL
		$post_link = get_permalink();

		// Get current page title
		$post_title = str_replace( ' ', '%20', get_the_title() );

		// Social Sharing Links data array
		$social_sharing_links = array(
			'facebook'  => array(
				'icon'          => '<i class="fab fa-facebook"></i>',
				'url'           => 'https://www.facebook.com/sharer/sharer.php?u=' . $post_link,
				'new_window'    => true,
				'window_width'  => 600,
				'window_height' => 300,
			),
			'twitter'   => array(
				'icon'          => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z"></path></svg>',
				'url'           => 'https://twitter.com/intent/tweet?text=' . esc_html( $post_title ) . '&amp;url=' . $post_link,
				'new_window'    => true,
				'window_width'  => 600,
				'window_height' => 300,
			),
			'mail'      => array(
				'icon'       => '<i class="fa fa-envelope"></i>',
				'url'        => 'mailto:?body=' . $post_link,
				'new_window' => false,
			),
		);

		// Get Post Thumbnail for pinterest
		$post_thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );
		if ( $post_thumbnail ) {
			$social_sharing_links['pinterest'] = array(
				'icon'          => '<i class="fab fa-pinterest"></i>',
				'url'           => 'https://pinterest.com/pin/create/button/?url=' . $post_link . '&amp;media=' . $post_thumbnail[0] . '&amp;description=' . esc_html( $post_title ),
				'new_window'    => true,
				'window_width'  => 600,
				'window_height' => 600,
			);
		}

		$title = esc_html__( 'Share', ERE_TEXT_DOMAIN );

		$output .= '<div class="post-share">';
		$output .= '<h3 class="post-share-title">' . $title . '</h3>';
		$output .= '<div class="post-share-links">';

		foreach ( $social_sharing_links as $social_media_title => $social_sharing_data ) {

			$icon                    = $social_sharing_data['icon'];
			$url                     = $social_sharing_data['url'];
			$new_window              = $social_sharing_data['new_window'];
			$open_in_separate_window = '';

			if ( $new_window ) {
				$window_width            = $social_sharing_data['window_width'];
				$window_height           = $social_sharing_data['window_height'];
				$open_in_separate_window = sprintf( 'onclick="window.open(this.href, \'%s\', \'width=%d,height=%d\'); return false;"', $social_media_title, $window_width, $window_height );
			}

			$output .= sprintf( '<a rel="nofollow" href="%s" ' . $open_in_separate_window . '" title="Share on %s">%s</a>', $url, $social_media_title, $icon );
		}

		$output .= '</div>';
		$output .= '</div>';

		echo wp_kses( $output, array(
			'div' => array(
				'class' => true,
			),
			'h3' => array(
				'class' => true,
			),
			'a' => array(
				'href'   => true,
				'title'  => true,
				'rel'    => true,
				'class'  => true,
				'onclick'=> true,
			),
			'i' => array(
				'class' => true,
			),
			'svg' => array(
				'xmlns'       => true,
				'viewBox'     => true,
				'width'       => true,
				'height'      => true,
				'fill'        => true,
				'aria-hidden' => true,
			),
			'path' => array(
				'd' => true,
			),
		) );
	}
}


if ( ! function_exists( 'ere_process_filter_widget_taxonomies' ) ) {
	/**
	 * Process taxonomies listings for filter properties widget
	 *
	 * @since  2.0.3
	 *
	 * @param $taxonomy
	 * @param $view_limit
	 * @param $hide_empty
	 */
        function ere_process_filter_widget_taxonomies( $taxonomy, $view_limit, $hide_empty, $include_terms = array() ) {

		// to ensure that view limit is not set under 1
		if ( 1 > intval( $view_limit ) ) {
			$view_limit = 6;
		}

		// fetching the taxonomies object
                $args = array(
                        'taxonomy'   => $taxonomy,
                        'hide_empty' => $hide_empty
                );
                if ( ! empty( $include_terms ) ) {
                        $args['include'] = $include_terms;
                }
                $taxonomies_obj = get_terms( $args );

		// checking if taxonomy is throwing an error
		if ( ! is_wp_error( $taxonomies_obj ) && ! empty( $taxonomies_obj ) ) {
			?>
            <div class="items-visible">
				<?php
				$tax_count = count( $taxonomies_obj );
				if ( $tax_count < $view_limit ) {
					$view_limit = $tax_count;
				}

				// generating taxonomies before the max view limit
				for ( $tax_num = 0; $tax_num < $view_limit; $tax_num++ ) {
					if ( ! empty( $taxonomies_obj[ $tax_num ] ) ) {
						echo '<span data-term-slug="' . esc_attr( $taxonomies_obj[ $tax_num ]->slug ) . '">' . esc_html( $taxonomies_obj[ $tax_num ]->name ) . '<i>&#10003;</i></span>';
					}
				}
				?>
            </div>

			<?php
			// generating taxonomies after the max view limit
			if ( $tax_count > $view_limit ) {
				?>
                <div class="items-view-more">
					<?php
					for ( $tax_num = $view_limit; $tax_num < $tax_count; $tax_num++ ) {
						if ( ! empty( $taxonomies_obj[ $tax_num ] ) ) {
							echo '<span data-term-slug="' . esc_attr( $taxonomies_obj[ $tax_num ]->slug ) . '">' . esc_html( $taxonomies_obj[ $tax_num ]->name ) . '<i>&#10003;</i></span>';
						}
					}
					?>
                </div>

                <a class="view-more"><?php esc_html_e( 'View More', ERE_TEXT_DOMAIN ); ?></a>
                <a class="view-less"><?php esc_html_e( 'View Less', ERE_TEXT_DOMAIN ); ?></a>
				<?php
			}
		}
	}
}


if ( ! function_exists( 'ere_taxonomy_has_terms' ) ) {
	/**
	 * Check if given taxonomy has any terms
	 *
	 * @since  2.0.3
	 *
	 * @param string $taxonomy
	 * @param boolean $hide_empty
     *
     * @return boolean
	 */
    function ere_taxonomy_has_terms( $taxonomy, $hide_empty = false ) {

        if( taxonomy_exists( $taxonomy ) ) {
	        $terms = get_terms(
                    array(
                        'taxonomy' => $taxonomy,
                        'hide_empty' => $hide_empty
                    )
            );

	        if( 0 < count( $terms ) ) {
		        return true;
	        }
        }

        return false;
    }
}


if ( ! function_exists( 'ere_new_field_for_section' ) ) {
	/**
	 * Check if current section can show any of the additional fields
	 * It is being checked against the section key in the given fields array
	 * It is required to assess that if the given section can be displayed at all
	 *
	 * @since  2.0.3
	 *
	 * @param string $section_key
	 * @param array  $fields_array
	 *
	 * @return boolean
	 */
	function ere_new_field_for_section( $section_key, $fields_array ) {

		if ( is_array( $fields_array ) ) {
			foreach ( $fields_array as $field ) {
				if ( ! empty( $field['field_display'] ) && in_array( $section_key, $field['field_display'] ) ) {
					return true;
				}
			}
		}

		return false;
	}
}


if ( ! function_exists( 'ere_process_filter_widget_post_types' ) ) {
	/**
	 * Process taxonomies listings for filter properties widget
	 *
	 * @since  2.0.3
	 *
	 * @param array $args arguments to manage lists
	 *                    - post_type           (post, page, {custom post type name} etc.)
	 *                    - display_type        (thumbnail, radio)
	 *                    - view_limit          The number of items after which the 'view more' will be displayed
	 *                    - wrapper_classes     Post type specific classes for filter section
	 *                    - section_title       Section display title
	 *                    - display_title       Display title tag to show selection above posts listings
	 *                    - target_id           JS target ID which will be used to target this section
	 *                    - thumb_placeholder   Placeholder if not thumbnail exists (Default is user image)
	 */
	function ere_process_filter_widget_post_types( $args = array() ) {

		$post_type         = ! empty( $args['post_type'] ) ? $args['post_type'] : 'post';
		$display_type      = ! empty( $args['display_type'] ) ? $args['display_type'] : 'radio';
		$view_limit        = ! empty( $args['view_limit'] ) ? $args['view_limit'] : 6;
		$wrapper_classes   = ! empty( $args['wrapper_classes'] ) ? $args['wrapper_classes'] : 'post-options';
		$section_title     = ! empty( $args['section_title'] ) ? $args['section_title'] : esc_html__( 'Posts', ERE_TEXT_DOMAIN );
		$display_tag_title = ! empty( $args['display_title'] ) ? $args['display_title'] : esc_html__( 'Post', ERE_TEXT_DOMAIN );
		$js_target_id      = ! empty( $args['target_id'] ) ? $args['target_id'] : 'posts';
		$image_size        = ! empty( $args['image_size'] ) ? $args['image_size'] : 'thumbnail';
		$thumb_placeholder = ! empty( $args['thumb_placeholder'] ) ? $args['thumb_placeholder'] : get_template_directory_uri() . '/common/images/profile-img-placeholder.png';


		// to ensure that view limit is not set under 1
		if ( 1 > intval( $view_limit ) ) {
			$view_limit = 6;
		}

		// fetching the posts
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => -1
			)
		);

		if ( ! is_wp_error( $posts ) && is_array( $posts ) ) {

			// if sitepress WPML is enabled
			if ( class_exists( 'SitePress' ) ) {
				$current_posts = array();

				// Filter IDs to get content in current language only
				foreach ( $posts as $post ) {
					if ( ! in_array( $post->ID, $current_posts ) ) {
						if ( apply_filters( 'wpml_object_id', $post->ID, $post_type, false ) !== null ) {
							$current_posts[] = apply_filters( 'wpml_object_id', $post->ID, $post_type, false );
						}
					}
				}

				if ( 0 < count( $current_posts ) ) {
					$posts = get_posts(
						array(
							'post_type'      => $post_type,
							'posts_per_page' => -1,
							'post__in'       => $current_posts
						)
					);
				} else {
					$posts = array();
				}
			}

			// Counter variable to honor view limit of the checkboxes
			$counter = 1;

			// if there are posts available
			if ( 0 < count( $posts ) ) {
				?>
                <div class="filter-wrapper <?php echo esc_attr( $wrapper_classes ); ?>">
                    <h4><?php echo esc_html( $section_title ); ?></h4>
                    <div class="filter-section posts-list display-type-<?php echo esc_attr( $display_type ); ?>" data-meta-name="<?php echo esc_attr( $js_target_id ); ?>" data-display-title="<?php echo esc_attr( $display_tag_title ); ?>">
                        <div class="items-visible">
							<?php
							foreach ( $posts as $post ) {
								// foreach will break after generating checkboxes under the selected limit
								if ( $counter <= $view_limit ) {
									$post_id = $post->ID;
									?>
                                    <div class="pt-item <?php echo esc_attr( $display_type ); ?>" data-post-id="<?php echo esc_attr( $post_id ) . '|' . get_the_title( $post_id ); ?>">
										<?php
										if ( $display_type === 'thumbnail' ) {
											if ( has_post_thumbnail( $post_id ) ) {
												?>
                                                <figure><?php echo get_the_post_thumbnail( $post_id, $image_size ); ?></figure>
												<?php
											} else {
												?>
                                                <figure>
                                                    <img src="<?php echo esc_url( $thumb_placeholder ); ?>" alt="<?php echo get_the_title( $post_id ); ?>">
                                                </figure>
												<?php
											}
											?>
                                            <div class="item-content">
                                                <h5 class="pt-title"><?php echo get_the_title( $post_id ); ?></h5>
                                            </div>
											<?php
										} else {
											?>
                                            <span><?php echo get_the_title( $post_id ); ?><i>&#10003;</i></span>
											<?php
										}
										?>
                                    </div>
									<?php
								} else {
									break;
								}
								$counter++;
							}
							?>
                        </div>
						<?php
						// if we still have items after the limit
						if ( $view_limit < count( $posts ) ) {

							// slicing the array to skip already generated checkboxes
							$more_posts = array_slice( $posts, $view_limit );
							if ( 0 < count( $more_posts ) ) {
								?>
                                <div class="items-view-more">
									<?php
									foreach ( $more_posts as $post ) {
										$post_id = $post->ID;
										?>
                                        <div class="pt-item <?php echo esc_attr( $display_type ); ?>" data-post-id="<?php echo esc_attr( $post_id ) . '|' . get_the_title( $post_id ); ?>">
											<?php
											if ( $display_type === 'thumbnail' ) {
												if ( has_post_thumbnail( $post_id ) ) {
													?>
                                                    <figure><?php echo get_the_post_thumbnail( $post_id, 'thumbnail' ); ?></figure>
													<?php
												}
												?>
                                                <div class="item-content">
                                                    <h5 class="pt-title"><?php echo get_the_title( $post_id ); ?></h5>
                                                </div>
												<?php
											} else {
												?>
                                                <span><?php echo get_the_title( $post_id ); ?><i>&#10003;</i></span>
												<?php
											}
											?>
                                        </div>
										<?php
									}
									?>
                                </div>

                                <a class="view-more"><?php esc_html_e( 'View More', ERE_TEXT_DOMAIN ); ?></a>
                                <a class="view-less"><?php esc_html_e( 'View Less', ERE_TEXT_DOMAIN ); ?></a>
								<?php
							}
						}
						?>
                    </div>
                </div>
				<?php
			}

		}
	}
}

if ( ! function_exists( 'ere_process_filter_widget_tiles' ) ) {
	/**
	 * Process given data into single or multi-select tiles
	 *
	 * @since  2.3.0
	 *
	 * @param array $args arguments to manage lists
	 *                    - list_array          (post, page, {custom post type name} etc.)
	 *                    - display_type        (thumbnail, radio)
	 *                    - view_limit          The number of items after which the 'view more' will be displayed
	 *                    - wrapper_classes     Post type specific classes for filter section
	 *                    - section_title       Section display title
	 *                    - display_title       Display title tag to show selection above posts listings
	 *                    - target_id           JS target ID which will be used to target this section
	 */
	function ere_process_filter_widget_tiles( $args = array() ) {

		$items_array       = isset( $args['items_array'] ) ? $args['items_array'] : false;
		$selection_type    = isset( $args['selection_type'] ) ? $args['selection_type'] : 'single';
		$view_limit        = isset( $args['view_limit'] ) ? $args['view_limit'] : 6;
		$wrapper_classes   = isset( $args['wrapper_classes'] ) ? $args['wrapper_classes'] : 'items-list';
		$section_title     = isset( $args['section_title'] ) ? $args['section_title'] : esc_html__( 'Items', ERE_TEXT_DOMAIN );
		$meta_key          = isset( $args['meta_key'] ) ? $args['meta_key'] : '';
		$display_tag_title = isset( $args['display_title'] ) ? $args['display_title'] : esc_html__( 'Check List', ERE_TEXT_DOMAIN );

		// Counter variable to honor view limit of the checkboxes
		$counter = 1;

        // if is array and have items
        if ( is_array( $items_array ) && 0 < count( $items_array ) ) {
            ?>
            <div class="filter-wrapper <?php echo esc_attr( $wrapper_classes ); ?>">
                <h4><?php echo esc_html( $section_title ); ?></h4>
                <div class="filter-section ad-select-list selection-type-<?php echo esc_attr( $selection_type ); ?>" data-meta-key="<?php echo esc_attr( $meta_key ); ?>" data-display-title="<?php echo esc_attr( $display_tag_title ); ?>">
                    <div class="items-visible">
                        <?php
                        foreach ( $items_array as $item ) {
                            // foreach will break after generating checkboxes under the selected limit
                            if ( $counter <= $view_limit ) {
	                            $item_slug = strtolower( str_replace( ' ', '-', $item ) );
                                ?>
                                <div id="<?php echo esc_attr( $meta_key ) . '-' . esc_attr( $item_slug ); ?>" class="ad-select-item <?php echo esc_attr( $selection_type ); ?>" data-filter-value="<?php echo esc_attr( $item_slug ); ?>">
                                    <span class="selection"></span>
                                    <div class="item-content">
                                        <h5 class="ad-select-title"><?php echo esc_html( $item ); ?></h5>
                                    </div>
                                </div>
                                <?php
                            } else {
                                break;
                            }
                            $counter++;
                        }
                        ?>
                    </div>
                    <?php
                    // if we still have items after the limit
                    if ( $view_limit < count( $items_array ) ) {

                        // slicing the array to skip already generated checkboxes
	                    $more_list_items = array_slice( $items_array, $view_limit );
                        if ( 0 < count( $more_list_items ) ) {
                            ?>
                            <div class="items-view-more">
                                <?php
                                foreach ( $more_list_items as $item ) {
	                                $item_slug = strtolower( str_replace( ' ', '-', $item ) );
	                                ?>
                                    <div id="<?php echo esc_attr( $meta_key ) . '-' . esc_attr( $item_slug ); ?>" class="ad-select-item <?php echo esc_attr( $selection_type ); ?>" data-filter-value="<?php echo esc_attr( $item_slug ); ?>">
                                        <span class="selection"></span>
                                        <div class="item-content">
                                            <h5 class="ad-select-title"><?php echo esc_html( $item ); ?></h5>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>

                            <a class="view-more"><?php esc_html_e( 'View More', ERE_TEXT_DOMAIN ); ?></a>
                            <a class="view-less"><?php esc_html_e( 'View Less', ERE_TEXT_DOMAIN ); ?></a>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
            <?php
        }
	}
}


if ( ! function_exists( 'ere_add_media_string' ) ) {
	/**
	 * Modify + Add Media string for gallery images and other metabox settings
	 *
	 * @since 2.0.3
	 *
	 * @return string
	 */
	function ere_add_media_string() {
		return esc_html__( '+ Add Media', ERE_TEXT_DOMAIN );
	}

	add_filter( 'rwmb_media_add_string', 'ere_add_media_string' );

}

if ( ! function_exists( 'ere_email_template_styles' ) ) {
	/**
	 * RealHomes Email Templates Styles
	 *
	 * @since 2.1.1
	 *
	 * @return array
	 */
	function ere_email_template_styles() {

		$email_styles                   = array();
		$email_styles['header_content'] = get_option( 'ere_email_template_header_content', 'image' );
		$email_styles['footer_text']    = get_option( 'ere_email_template_footer_text', '' );

		// Getting Email Template Color Scheme
		$email_color_scheme = get_option( 'ere_email_color_scheme', 'default' );

		if ( 'default' == $email_color_scheme ) {

			if ( 'ultra' === INSPIRY_DESIGN_VARIATION ) {

				$email_styles['header_title_color'] = '#0ba3f2';
				$email_styles['background_color']   = '#e7f6fd';
				$email_styles['body_link_color']    = '#1db2ff';
				$email_styles['footer_text_color']  = '#808080';
				$email_styles['footer_link_color']  = '#1a1a1a';

			} else if ( 'modern' === INSPIRY_DESIGN_VARIATION ) {

				$email_styles['header_title_color'] = '#333333';
				$email_styles['background_color']   = '#e9eaec';
				$email_styles['body_link_color']    = '#0b8278';
				$email_styles['footer_text_color']  = '#808080';
				$email_styles['footer_link_color']  = '#1a1a1a';

			} else if ( 'classic' === INSPIRY_DESIGN_VARIATION ) {

				$email_styles['header_title_color'] = '#333333';
				$email_styles['background_color']   = '#e9eaec';
				$email_styles['body_link_color']    = '#ff7f50';
				$email_styles['footer_text_color']  = '#aaaaaa';
				$email_styles['footer_link_color']  = '#949494';

			}

		} else {

			$email_styles['header_title_color'] = get_option( 'ere_email_template_header_title_color', '#333333' );
			$email_styles['background_color']   = get_option( 'ere_email_template_background_color', '#e9eaec' );
			$email_styles['body_link_color']    = get_option( 'ere_email_template_body_link_color', '#ff7f50' );
			$email_styles['footer_text_color']  = get_option( 'ere_email_template_footer_text_color', '#aaaaaa' );
			$email_styles['footer_link_color']  = get_option( 'ere_email_template_footer_link_color', '#949494' );

		}

		return $email_styles;
	}

}


if ( ! function_exists( 'ere_get_users_by_approval_status' ) ) {
	/**
	 * This function is used to get the list of users with specific status or all
     * By default the administrators are skipped unless $show_admin is stated as true
	 *
	 * @since 2.2.0
	 *
	 * @param string  $status
	 * @param boolean $show_admin
	 *
	 * @return array|boolean
	 */
	function ere_get_users_by_approval_status( $status = 'all', $show_admin = false ) {

		$user_args = array();

		// Setting up meta query
		if ( $status != 'all' ) {
			$user_args['meta_query'] = array(
				array(
					'key'     => 'ere_user_approval_status',
					'value'   => $status,
					'compare' => '='
				)
			);
		}

		// skipping the administrators by default
		if ( ! $show_admin ) {
			$user_args['role__not_in'] = 'Administrator';
		}

		$users = get_users( $user_args );

		if ( ! is_wp_error( $users ) ) {
			return $users;
		}

		return false;
	}
}


if ( ! function_exists( 'ere_get_user_approval_status' ) ) {
	/**
	 * Get the status of given user ID
	 *
	 * @since 2.2.0
	 *
     * @param int $user_id
     *
	 * @return string
	 */
	function ere_get_user_approval_status( $user_id ) {
		$user_status = get_user_meta( $user_id, 'ere_user_approval_status', true );

		if ( empty( $user_status ) ) {
			return 'approved';
		}

		return $user_status;
	}
}

if ( ! function_exists( 'ere_current_class' ) ) {
	/**
	 * Print or return given class in case the given target and needle are matched
     * This is a helper function just like 'selected' or 'checked' php functions
     * It is created to enter 'current' class based on matching needle and target strings
     * It shortens the code and help when need multiple occurrences of this type of code
	 *
	 * @since 2.2.0
	 *
	 * @param string  $needle
	 * @param string  $target
	 * @param boolean $echo
	 * @param string  $class_name
	 *
	 * @return mixed
	 */
	function ere_current_class( $needle, $target, $echo = true, $class_name = 'current' ) {

		if ( $needle === $target ) {
			if ( $echo ) {
				echo $class_name;
			} else {
				return $class_name;
			}
		}

		return null;
	}
}

if ( ! function_exists( 'ere_admin_taxonomy_terms' ) ) {

	/**
	 * Comma separated taxonomy terms with admin side links.
	 *
	 * @since  1.0.0
	 *
	 * @param string $taxonomy  - Taxonomy name.
	 * @param string $post_type - Post type.
	 *
	 * @param int    $post_id   - Post ID.
	 *
	 * @return string|bool
	 */
	function ere_admin_taxonomy_terms( $post_id, $taxonomy, $post_type ) {

		$terms = get_the_terms( $post_id, $taxonomy );

		if ( ! empty( $terms ) ) {
			$out = array();
			/* Loop through each term, linking to the 'edit posts' page for the specific term. */
			foreach ( $terms as $term ) {
				$out[] = sprintf(
					'<a href="%s">%s</a>',
					esc_url(
						add_query_arg(
							array(
								'post_type' => $post_type,
								$taxonomy   => $term->slug,
							), 'edit.php'
						)
					),
					esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, $taxonomy, 'display' ) )
				);
			}

			/* Join the terms, separating them with a comma. */

			return join( ', ', $out );
		}

		return false;
	}
}


/**
 * Adds Easy Real Estate settings link to the admin bar.
 *
 * @since 2.2.2
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin Bar instance.
 */
function ere_add_settings_link_to_admin_bar( $wp_admin_bar ) {
	// Only show to users who can manage options
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$wp_admin_bar->add_node( array(
		'id'    => 'ere-settings',
		'title' => esc_html__( 'ERE Settings', ERE_TEXT_DOMAIN ),
		'href'  => admin_url( 'admin.php?page=ere-settings' ),
		'meta'  => array(
			'class' => 'ere-settings-toolbar',
			'title' => esc_html__( 'Easy Real Estate Settings', ERE_TEXT_DOMAIN )
		)
	) );

}

add_action( 'admin_bar_menu', 'ere_add_settings_link_to_admin_bar', 100 );