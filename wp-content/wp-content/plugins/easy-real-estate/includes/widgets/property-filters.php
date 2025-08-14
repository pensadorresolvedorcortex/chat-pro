<?php
/**
 * Adds Properties Filter Widget
 *
 * @since 2.0.3
 */

if ( ! class_exists( 'Properties_Filter_Widget' ) ) {

	class Properties_Filter_Widget extends WP_Widget {
		/**
		 * Registering widget with WordPress.
		 */
		public function __construct() {
			parent::__construct(
				'properties_filter_widget',
				esc_html__( 'RealHomes - Properties Filter Widget', ERE_TEXT_DOMAIN ),
				array(
					'description' => esc_html__( 'This widget provides users with filter options to refine their search criteria for property listings templates.', ERE_TEXT_DOMAIN )
				)
			);
		}

		/**
		 * Front-end display of widget.
		 *
		 * @param array $args     Widget arguments.
		 * @param array $instance Saved values from database.
		 *
		 * @see WP_Widget::widget()
		 *
		 */
		public function widget( $args, $instance ) {
			extract( $args );
			$title                 = apply_filters( 'widget_title', $instance['title'] );
                        $property_types        = $instance['property-types'];
                        $property_location     = $instance['property-location'];
                        $location_terms        = ! empty( $instance['location-terms'] ) ? (array) $instance['location-terms'] : array();
                        $property_categories   = $instance['property-categories'];
                        $category_terms        = ! empty( $instance['category-terms'] ) ? (array) $instance['category-terms'] : array();
                        $property_status       = $instance['property-status'];
                        $property_features     = $instance['property-features'];
			$checkboxes_view_limit = $instance['checkboxes-view-limit'];
			$hide_empty            = $instance['hide_empty'];
			$price_range           = $instance['price-ranges'];
			$price_range_type      = $instance['price-range-type'];
			$custom_price_ranges   = $instance['custom-price-ranges'];
			$price_slider_min      = $instance['price-slider-min'];
			$price_slider_max      = $instance['price-slider-max'];
			$area_range            = $instance['area-ranges'];
			$area_range_type       = $instance['area-range-type'];
			$custom_area_ranges    = $instance['custom-area-ranges'];
			$area_unit             = $instance['area-unit'];
			$area_slider_min       = $instance['area-slider-min'];
			$area_slider_max       = $instance['area-slider-max'];
			$bedroom_options       = $instance['bedroom-options'];
			$bedrooms_max_value    = $instance['bedrooms-max-value'];
			$bathroom_options      = $instance['bathroom-options'];
			$bathrooms_max_value   = $instance['bathrooms-max-value'];
			$garage_options        = $instance['garage-options'];
			$garages_max_value     = $instance['garages-max-value'];
			$agent_options         = $instance['agent-options'];
			$agent_display_type    = ! empty( $instance['agent-display-type'] ) ? $instance['agent-display-type'] : 'thumbnail';
			$agency_options        = $instance['agency-options'];
			$agency_display_type   = ! empty( $instance['agency-display-type'] ) ? $instance['agency-display-type'] : 'thumbnail';
			$property_id           = $instance['property-id'];
			$additional_fields     = $instance['additional-fields'];
			$hide_empty            = filter_var( $hide_empty, FILTER_VALIDATE_BOOLEAN ); // making sure if provided val is bool
			echo $before_widget;

			if ( ! empty( $title ) ) {
				echo '<h4 class="title filters-heading">' . esc_html( $title ) . '<i class="fa-duotone fa-filters"></i></h4>';
			}

			if ( ! is_page_template( 'templates/properties.php' ) ) {

				echo '<p class="alert alert-error filters-wrong-template"><strong>' . esc_html__( 'Note:', ERE_TEXT_DOMAIN ) . '</strong> ' . esc_html__( 'The properties filters widget is specifically designed to work with properties listing templates and cannot be used on other types of pages.', ERE_TEXT_DOMAIN ) . '</p>';

			} else if ( function_exists( 'realhomes_is_header_search_form_configured' ) && realhomes_is_header_search_form_configured() ) {

				echo '<p class="alert alert-error filters-wrong-template"><strong>' . esc_html__( 'Note:', ERE_TEXT_DOMAIN ) . '</strong> ' . esc_html__( 'Advance Search is already enabled in the header. For the filters to work you need to disable the header search form as they do not work simultaneously.', ERE_TEXT_DOMAIN ) . '</p>';

			} else {
				?>
                <div class="filters-widget-wrap">
                    <div class="property-filters">
                        <div class="collapse-button">
                            <span class="pop-open-all hidden">
                                <span class="button-text"><?php esc_html_e( 'Open All', ERE_TEXT_DOMAIN ); ?></span>&nbsp; <i class="fas fa-angle-double-down"></i>
                            </span>
                            <span class="pop-collapse-all">
                                <span class="button-text"><?php esc_html_e( 'Collapse All', ERE_TEXT_DOMAIN ); ?></span>&nbsp; <i class="fas fa-angle-double-up"></i>
                            </span>
                        </div>
						<?php
						// Adding property type taxonomy filter
						if ( $property_types === 'true' && ere_taxonomy_has_terms( 'property-type', $hide_empty ) ) {
							?>
                            <div class="filter-wrapper property-types">
                                <h4><?php esc_html_e( 'Property Types', ERE_TEXT_DOMAIN ); ?></h4>
                                <div class="filter-section terms-list" data-taxonomy="types" data-display-title="<?php esc_html_e( 'Type', ERE_TEXT_DOMAIN ); ?>">
									<?php
									ere_process_filter_widget_taxonomies( 'property-type', $checkboxes_view_limit, $hide_empty );
									?>
                                </div>
                            </div>
							<?php
						}

						// Adding property city taxonomy filter
                                                if ( $property_location === 'true' && ere_taxonomy_has_terms( 'property-city', $hide_empty ) ) {
                                                        ?>
                            <div class="filter-wrapper property-locations">
                                <h4><?php esc_html_e( 'Property Locations', ERE_TEXT_DOMAIN ); ?></h4>
                                <div class="filter-section terms-list" data-taxonomy="locations" data-display-title="<?php esc_html_e( 'Location', ERE_TEXT_DOMAIN ); ?>">
                                                                        <?php
                                                                        ere_process_filter_widget_taxonomies( 'property-city', $checkboxes_view_limit, $hide_empty, $location_terms );
                                                                        ?>
                                </div>
                            </div>
                                                        <?php
                                                }

                                                // Adding property category taxonomy filter
                                                if ( $property_categories === 'true' && ere_taxonomy_has_terms( 'property-category', $hide_empty ) ) {
                                                        ?>
                            <div class="filter-wrapper property-categories">
                                <h4><?php esc_html_e( 'Property Categories', ERE_TEXT_DOMAIN ); ?></h4>
                                <div class="filter-section terms-list" data-taxonomy="categories" data-display-title="<?php esc_html_e( 'Category', ERE_TEXT_DOMAIN ); ?>">
                                                                        <?php
                                                                        ere_process_filter_widget_taxonomies( 'property-category', $checkboxes_view_limit, $hide_empty, $category_terms );
                                                                        ?>
                                </div>
                            </div>
                                                        <?php
                                                }

                                                // Adding property status taxonomy filter
                                                if ( $property_status === 'true' && ere_taxonomy_has_terms( 'property-status', $hide_empty ) ) {
                                                        ?>
                            <div class="filter-wrapper property-statuses">
                                <h4><?php esc_html_e( 'Property Status', ERE_TEXT_DOMAIN ); ?></h4>
                                <div class="filter-section terms-list" data-taxonomy="statuses" data-display-title="<?php esc_html_e( 'Status', ERE_TEXT_DOMAIN ); ?>">
                                                                        <?php
                                                                        ere_process_filter_widget_taxonomies( 'property-status', $checkboxes_view_limit, $hide_empty );
                                                                        ?>
                                </div>
                            </div>
                                                        <?php
                                                }

						// Adding property features filter
						if ( $property_features === 'true' && ere_taxonomy_has_terms( 'property-feature', $hide_empty ) ) {
							?>
                            <div class="filter-wrapper property-features">
                                <h4><?php esc_html_e( 'Property Features', ERE_TEXT_DOMAIN ); ?></h4>
                                <div class="filter-section terms-list" data-taxonomy="features" data-display-title="<?php esc_html_e( 'Feature', ERE_TEXT_DOMAIN ); ?>">
									<?php
									ere_process_filter_widget_taxonomies( 'property-feature', $checkboxes_view_limit, $hide_empty );
									?>
                                </div>
                            </div>
							<?php
						}

						// Adding price options
						if ( $price_range === 'true' ) {
							?>
                            <div class="filter-wrapper price-ranges" data-price-range-type="<?php echo esc_attr( $price_range_type ); ?>">
								<?php
								if ( 'radio' == $price_range_type ) {
									$price_ranges = array(
										'1000 - 5000',
										'5001 - 10000',
										'10001 - 50000',
										'50001 - 100000',
										'100000 - 150000',
										'150001 - 200000',
										'200001 - 250000'
									);

									// If custom price ranges area set in widget settings
									if ( ! empty( $custom_price_ranges ) ) {
										$custom_prices_array = preg_split( '/\r\n|\r|\n/', $custom_price_ranges );
										$price_ranges        = $custom_prices_array;
									}

									array_unshift( $price_ranges, esc_html__( 'All', ERE_TEXT_DOMAIN ) );
									?>
                                    <h4><?php esc_html_e( 'Price Ranges', ERE_TEXT_DOMAIN ); ?></h4>
                                    <div class="filter-section range-list">
										<?php
										if ( is_array( $price_ranges ) ) {
											foreach ( $price_ranges as $p_range ) {
												$prices   = '';
												$range_id = str_replace( ' ', '', $p_range );
												if ( $p_range === 'All' ) {
													$prices = $p_range;
												} else {
													$range_array = explode( ' - ', $p_range );
													if ( 1 < count( $range_array ) && is_numeric( $range_array[0] ) && is_numeric( $range_array[1] ) ) {
														$prices = ere_format_amount( $range_array[0] ) . ' - ' . ere_format_amount( $range_array[1] );
													}
												}
												if ( ! empty( $prices ) ) {
													?>
                                                    <p class="radio-wrap" data-meta-name="price" data-display-title="<?php esc_html_e( 'Price', ERE_TEXT_DOMAIN ); ?>">
                                                        <input type="radio" id="price-<?php echo esc_attr( $range_id ); ?>" data-display-value="<?php echo esc_attr( $prices ); ?>" name="price-range" value="<?php echo esc_attr( $p_range ); ?>">
                                                        <label for="price-<?php echo esc_attr( $range_id ); ?>"><span class="radio-fancy"></span><?php echo esc_html( $prices ); ?></label>
                                                    </p>
													<?php
												}
											}
										}
										?>
                                    </div>
									<?php
								} else {
									?>
                                    <h4><?php esc_html_e( 'Price Filter', ERE_TEXT_DOMAIN ); ?></h4>
                                    <div class="filter-section range-slider price-range-slider" data-meta-name="price" data-display-title="<?php esc_html_e( 'Price', ERE_TEXT_DOMAIN ); ?>" data-values-sign="<?php echo ere_get_currency_sign(); ?>" data-sign-position="before">
                                        <div class="ranges">
                                            <span class="min-value price" data-range="<?php echo esc_attr( $price_slider_min ); ?>"><?php echo ere_format_amount( $price_slider_min ); ?></span>
                                            <span class="max-value price" data-range="<?php echo esc_attr( $price_slider_max ); ?>"><?php echo ere_format_amount( $price_slider_max ); ?></span>
                                        </div>
                                        <div class="range-slider-trigger price-slider"></div>
                                        <div class="current-values">
                                            <span class="min-value"></span> - <span class="max-value"></span>
                                        </div>
                                    </div>
									<?php
								}
								?>
                            </div>
							<?php
						}

						// Adding area options
						if ( $area_range === 'true' ) {
							?>
                            <div class="filter-wrapper area-ranges">
								<?php

								if ( 'radio' == $area_range_type ) {
									$area_ranges = array(
										'50 - 100',
										'101 - 500',
										'501 - 1000',
										'1001 - 5000',
										'5001 - 10000'
									);

									// If custom area ranges area set in widget settings
									if ( ! empty( $custom_area_ranges ) ) {
										$custom_areas_array = preg_split( '/\r\n|\r|\n/', $custom_area_ranges );
										$area_ranges        = $custom_areas_array;
									}

									array_unshift( $area_ranges, esc_html__( 'All', ERE_TEXT_DOMAIN ) );
									?>
                                    <h4><?php esc_html_e( 'Area Ranges', ERE_TEXT_DOMAIN ); ?></h4>
                                    <div class="filter-section range-list">
										<?php
										if ( is_array( $area_ranges ) ) {
											foreach ( $area_ranges as $a_range ) {
												$areas    = '';
												$range_id = str_replace( ' ', '', $a_range );
												if ( $a_range === 'All' ) {
													$areas = $a_range;
												} else {
													$range_array = explode( ' - ', $a_range );
													if ( 1 < count( $range_array ) && is_numeric( $range_array[0] ) && is_numeric( $range_array[1] ) ) {
														$areas = $range_array[0] . ' <sub>' . $area_unit . '</sub>' . ' - ' . $range_array[1] . ' <sub>' . $area_unit . '</sub>';
													}
												}

												if ( ! empty( $areas ) ) {
													?>
                                                    <p class="radio-wrap" data-meta-name="area" data-display-title="<?php esc_html_e( 'Area', ERE_TEXT_DOMAIN ); ?>">
                                                        <input type="radio" id="area-<?php echo esc_attr( $range_id ); ?>" data-display-value="<?php echo wp_kses_post( $areas ); ?>" name="area-range" value="<?php echo esc_attr( $a_range ); ?>">
                                                        <label for="area-<?php echo esc_attr( $range_id ); ?>"><span class="radio-fancy"></span><?php echo wp_kses_post( $areas ); ?></label>
                                                    </p>
													<?php
												}
											}
										}
										?>
                                    </div>
									<?php
								} else {
									?>
                                    <h4><?php esc_html_e( 'Area Filter', ERE_TEXT_DOMAIN ); ?></h4>
                                    <div class="filter-section range-slider area-range-slider" data-meta-name="area" data-display-title="<?php esc_html_e( 'Area', ERE_TEXT_DOMAIN ); ?>" data-values-sign=" <?php echo esc_attr( $area_unit ); ?>" data-sign-position="after" data-min-trigger-key="minArea" data-max-trigger-key="maxArea">
                                        <div class="ranges">
                                            <span class="min-value area" data-range="<?php echo esc_attr( $area_slider_min ); ?>"><?php echo esc_attr( $area_slider_min . ' ' . $area_unit ); ?></span>
                                            <span class="max-value area" data-range="<?php echo esc_attr( $area_slider_max ); ?>"><?php echo esc_attr( $area_slider_max . ' ' . $area_unit ); ?></span>
                                        </div>
                                        <div class="range-slider-trigger area-slider"></div>
                                        <div class="current-values">
                                            <span class="min-value"></span> - <span class="max-value"></span>
                                        </div>
                                    </div>
									<?php
								}
								?>
                            </div>
							<?php
						}

						if ( post_type_exists( 'agent' ) ) {
							$agent_posts = wp_count_posts( 'agent' );
							if ( $agent_options === 'true' && intval( $agent_posts->publish ) > 0 ) {
								$agents_arguments = array(
									'post_type'       => 'agent',
									'view_limit'      => $checkboxes_view_limit,
									'wrapper_classes' => 'agent-options',
									'section_title'   => esc_html__( 'Agents', ERE_TEXT_DOMAIN ),
									'display_type'    => $agent_display_type,
									'display_title'   => esc_html__( 'Agent', ERE_TEXT_DOMAIN ),
									'target_id'       => 'agent'
								);
								ere_process_filter_widget_post_types( $agents_arguments );
							}
						}

						if ( post_type_exists( 'agency' ) ) {
							$agency_posts = wp_count_posts( 'agency' );
							if ( $agency_options === 'true' && intval( $agency_posts->publish ) > 0 ) {
								$agency_arguments = array(
									'post_type'       => 'agency',
									'view_limit'      => $checkboxes_view_limit,
									'wrapper_classes' => 'agency-options',
									'section_title'   => esc_html__( 'Agencies', ERE_TEXT_DOMAIN ),
									'display_type'    => $agency_display_type,
									'display_title'   => esc_html__( 'Agency', ERE_TEXT_DOMAIN ),
									'target_id'       => 'agencies',
									'thumb_placeholder' => get_template_directory_uri() . '/common/images/agency-placeholder.png'
								);
								ere_process_filter_widget_post_types( $agency_arguments );
							}
						}

						if ( $bedroom_options === 'true' ) {
							?>
                            <div class="filter-wrapper radio-buttons">
                                <h4><?php esc_html_e( 'Min Bedrooms', ERE_TEXT_DOMAIN ); ?></h4>
                                <div class="filter-section buttons-list" data-meta-name="bedrooms" data-display-title="<?php esc_html_e( 'Min Beds', ERE_TEXT_DOMAIN ); ?>">
                                    <div class="number-option-wrap bedroom-options">
                                        <span class="option-num">
                                            <input type="radio" name="min-bedrooms" id="min-bedroom-all" value="0" checked>
                                            <label for="min-bedroom-all"><?php esc_html_e( 'All', ERE_TEXT_DOMAIN ) ?></label>
                                        </span>
										<?php
										if ( empty( $bedrooms_max_value ) || 0 > intval( $bedrooms_max_value ) ) {
											$bedrooms_max_value = 9;
										}
										for ( $bed = 1; $bed <= $bedrooms_max_value; $bed++ ) {
											?>
                                            <span class="option-num">
                                                <input type="radio" name="min-bedrooms" id="min-bedroom-<?php echo esc_attr( $bed ); ?>" value="<?php echo esc_html( $bed ); ?>">
                                                <label for="min-bedroom-<?php echo esc_attr( $bed ); ?>"><?php echo esc_html( $bed ); ?></label>
                                            </span>
											<?php
										}
										?>
                                    </div>
                                </div>
                            </div>
							<?php
						}

						if ( $bathroom_options === 'true' ) {
							?>
                            <div class="filter-wrapper radio-buttons">
                                <h4><?php esc_html_e( 'Min Bathrooms', ERE_TEXT_DOMAIN ); ?></h4>
                                <div class="filter-section buttons-list" data-meta-name="bathrooms" data-display-title="<?php esc_html_e( 'Min Baths', ERE_TEXT_DOMAIN ); ?>">
                                    <div class="number-option-wrap bathroom-options">
                                        <span class="option-num">
                                            <input type="radio" name="min-bathrooms" id="min-bathroom-all" value="0" checked>
                                            <label for="min-bathroom-all"><?php esc_html_e( 'All', ERE_TEXT_DOMAIN ) ?></label>
                                        </span>
										<?php
										if ( empty( $bathrooms_max_value ) || 0 > intval( $bathrooms_max_value ) ) {
											$bathrooms_max_value = 12;
										}
										for ( $bath = 1; $bath <= $bathrooms_max_value; $bath++ ) {
											?>
                                            <span class="option-num">
                                                <input type="radio" name="min-bathrooms" id="min-bathroom-<?php echo esc_attr( $bath ); ?>" value="<?php echo esc_html( $bath ); ?>">
                                                <label for="min-bathroom-<?php echo esc_attr( $bath ); ?>"><?php echo esc_html( $bath ); ?></label>
                                            </span>
											<?php
										}
										?>
                                    </div>
                                </div>
                            </div>
							<?php
						}

						if ( $garage_options === 'true' ) {
							?>
                            <div class="filter-wrapper radio-buttons">
                                <h4><?php esc_html_e( 'Min Garages', ERE_TEXT_DOMAIN ); ?></h4>
                                <div class="filter-section buttons-list" data-meta-name="garages" data-display-title="<?php esc_html_e( 'Min Garages', ERE_TEXT_DOMAIN ); ?>">
                                    <div class="number-option-wrap garage-options">
                                        <span class="option-num">
                                            <input type="radio" name="min-garages" id="min-garage-all" value="0" checked>
                                            <label for="min-garage-all"><?php esc_html_e( 'All', ERE_TEXT_DOMAIN ) ?></label>
                                        </span>
										<?php
										if ( empty( $garages_max_value ) || 0 > intval( $garages_max_value ) ) {
											$garages_max_value = 5;
										}
										for ( $garage = 1; $garage <= $garages_max_value; $garage++ ) {
											?>
                                            <span class="option-num">
                                                <input type="radio" name="min-garages" id="min-garage-<?php echo esc_attr( $garage ); ?>" value="<?php echo esc_html( $garage ); ?>">
                                                <label for="min-garage-<?php echo esc_attr( $garage ); ?>"><?php echo esc_html( $garage ); ?></label>
                                            </span>
											<?php
										}
										?>
                                    </div>
                                </div>
                            </div>
							<?php
						}

						if ( $property_id === 'true' ) {
							?>
                            <div class="filter-wrapper input-wrapper">
                                <h4 for="property-id"><?php esc_html_e( 'Property ID', ERE_TEXT_DOMAIN ); ?></h4>
                                <div class="filter-section input-filter" data-meta-name="propertyID" data-display-title="<?php esc_html_e( 'Property ID', ERE_TEXT_DOMAIN ); ?>">
                                    <p class="input-wrap" data-meta-name="propertyID">
                                        <input type="text" id="property-id" name="property-id" placeholder="<?php esc_html_e( 'Any', ERE_TEXT_DOMAIN ); ?>">
                                    </p>
                                </div>
                            </div>
							<?php
						}

						// Adding additional meta details
						if ( $additional_fields === 'true' ) {
							$additional_fields = get_option( 'inspiry_property_additional_fields' );

							if ( isset( $additional_fields['inspiry_additional_fields_list'] ) && 0 < count( $additional_fields['inspiry_additional_fields_list'] ) ) {
								$additional_fields = $additional_fields['inspiry_additional_fields_list'];
								if ( is_array( $additional_fields ) && 0 < count( $additional_fields ) && ere_new_field_for_section( 'filters_widget', $additional_fields ) ) {
									?>
                                    <div class="filter-wrapper additional-fields">
                                        <h4><?php esc_html_e( 'Additional Details', ERE_TEXT_DOMAIN ); ?></h4>
                                        <div class="filter-section additional-items" data-meta-name="additional-fields">
											<?php
											foreach ( $additional_fields as $field ) {
												$field_name    = $field['field_name'] ?? '';
												$field_type    = $field['field_type'] ?? '';
												$field_display = $field['field_display'] ?? '';

												if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
													$field_reg_name  = $field_name . ' Label';
													$field_reg_value = $field_name . ' Value';
													$field_name      = apply_filters( 'wpml_translate_single_string', $field_name, 'Additional Fields', $field_reg_name, ICL_LANGUAGE_CODE );
												}

												// Checking if this field is allowed to be displayed here
												if ( is_array( $field_display ) && in_array( 'filters_widget', $field_display ) ) {
													$field_slug = 'inspiry_' . strtolower( str_replace( ' ', '_', $field_name ) );
													?>
                                                    <div class="additional-item <?php echo esc_attr( $field_slug );
													echo ' ad-' . esc_attr( $field_type ) . '-wrap'; ?>">
														<?php
														if ( $field_type === 'text' ) {
															?>
                                                            <p class="input-wrap input-filter" data-field-name="<?php echo esc_attr( $field_name ); ?>">
                                                                <label for="<?php echo esc_attr( $field_slug ); ?>"><?php echo esc_html( $field_name ); ?></label>
                                                                <input type="text" id="<?php echo esc_attr( $field_slug ); ?>" name="<?php echo esc_attr( $field_slug ); ?>" placeholder="<?php esc_html_e( 'Any', ERE_TEXT_DOMAIN ); ?>" value="">
                                                            </p>
															<?php
														} else if ( $field_type === 'select' ) {
															$field_options = $field['field_options'];
															if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
																$field_options = apply_filters( 'wpml_translate_single_string', $field_options, 'Additional Fields', $field_reg_value, ICL_LANGUAGE_CODE );
															}
															$field_options = explode( ', ', $field_options );

															if ( ! empty( $field['multi_select'] ) && 'yes' === $field['multi_select'] ) {
																ere_process_filter_widget_tiles(
																	array(
																		'items_array'     => $field_options,
																		'selection_type'  => 'multiselect',
																		'view_limit'      => $checkboxes_view_limit,
																		'wrapper_classes' => 'items-list multi-select-wrap',
																		'section_title'   => $field_name,
																		'meta_key'        => $field_slug,
																		'display_type'    => $agency_display_type,
																		'target_id'       => 'check-list'
																	)
																);
															} else {
																if ( is_array( $field_options ) && 0 < count( $field_options ) ) {
																	?>
                                                                    <p class="select-wrap select-filter" data-field-name="<?php echo esc_attr( $field_name ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>">
                                                                        <label for="<?php echo esc_attr( $field_slug ); ?>"><?php echo esc_html( $field_name ); ?></label>
                                                                        <select name="<?php echo esc_attr( $field_slug ); ?>" id="<?php echo esc_attr( $field_slug ); ?>">
                                                                            <option value=""><?php esc_html_e( 'None', ERE_TEXT_DOMAIN ); ?></option>
																			<?php
																			foreach ( $field_options as $option ) {
																				$option_slug = strtolower( str_replace( ' ', '-', $option ) );
																				?>
                                                                                <option value="<?php echo esc_attr( $option_slug ); ?>" id="<?php echo esc_attr( $field_slug ) . '-' . esc_attr( $option_slug ); ?>"><?php echo esc_html( $option ); ?></option>
																				<?php
																			}
																			?>
                                                                        </select>
                                                                    </p>
																	<?php
																}
															}
														} else if ( $field_type === 'radio' ) {
															$field_options = $field['field_options'];

															if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
																$field_options = apply_filters( 'wpml_translate_single_string', $field_options, 'Additional Fields', $field_reg_value, ICL_LANGUAGE_CODE );
															}

															$field_options = explode( ', ', $field_options );
															?>
                                                            <div class="radio-filter" data-field-name="<?php echo esc_attr( $field_name ); ?>" data-field-slug="<?php echo esc_attr( $field_slug ); ?>">
                                                                <h5><?php echo esc_html( $field_name ); ?></h5>
                                                                <p class="radio-wrap">
                                                                    <input type="radio" id="<?php echo esc_attr( $field_slug ); ?>-none" name="<?php echo esc_attr( $field_slug ); ?>" value="">
                                                                    <label for="<?php echo esc_attr( $field_slug ); ?>-none"><span class="radio-fancy"></span> <?php esc_html_e( 'None', ERE_TEXT_DOMAIN ); ?></label>
                                                                </p>
																<?php
																foreach ( $field_options as $option ) {
																	$option_slug = strtolower( str_replace( ' ', '-', $option ) );
																	?>
                                                                    <p class="radio-wrap">
                                                                        <input type="radio" id="<?php echo esc_attr( $option_slug ); ?>" name="<?php echo esc_attr( $field_slug ); ?>" value="<?php echo esc_attr( $option ); ?>">
                                                                        <label for="<?php echo esc_attr( $option_slug ); ?>"><span class="radio-fancy"></span> <?php echo esc_attr( $option ); ?></label>
                                                                    </p>
																	<?php
																}
																?>
                                                            </div>
															<?php
														} else if ( $field_type === 'checkbox_list' ) {
															$field_options = $field['field_options'];

															if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
																$field_options = apply_filters( 'wpml_translate_single_string', $field_options, 'Additional Fields', $field_reg_value, ICL_LANGUAGE_CODE );
															}

															$field_options = explode( ', ', $field_options );
															?>
                                                            <div class="checkbox-wrap checkbox-filter" data-field-name="<?php echo esc_attr( $field_name ); ?>" data-field-slug="<?php echo esc_attr( $field_slug ); ?>">
                                                                <h5><?php echo esc_html( $field_name ); ?></h5>
																<?php
																foreach ( $field_options as $option ) {
																	$option_slug = strtolower( str_replace( ' ', '-', $option ) );
																	?>
                                                                    <p class="cb-wrap" data-field-name="<?php echo esc_attr( $field_name ); ?>">
                                                                        <input type="checkbox" id="<?php echo esc_attr( $option_slug ); ?>" name="<?php echo esc_attr( $field_slug ); ?>" value="<?php echo esc_attr( $option ); ?>">
                                                                        <label for="<?php echo esc_attr( $option_slug ); ?>"><?php echo esc_attr( $option ); ?><i>&#10003;</i></label>
                                                                    </p>
																	<?php
																}
																?>
                                                            </div>
															<?php
														} else if ( $field_type === 'textarea' ) {
															?>
                                                            <p class="input-wrap input-filter" data-field-name="<?php echo esc_attr( $field_name ); ?>">
                                                                <label for="<?php echo esc_attr( $field_slug ); ?>"><?php echo esc_html( $field_name ); ?></label>
                                                                <input type="text" id="<?php echo esc_attr( $field_slug ); ?>" name="<?php echo esc_attr( $field_slug ); ?>" placeholder="<?php esc_html_e( 'Any', ERE_TEXT_DOMAIN ); ?>">
                                                            </p>
															<?php
														}
														?>
                                                    </div>
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
						?>
                    </div> <!-- .property-filters -->
                </div>

				<?php
				do_action( 'realhomes_after_filter_properties_widget' );
			}

			echo $after_widget;
		}

		/**
		 * Back-end widget form.
		 *
		 * @param array $instance Previously saved values from database.
		 *
		 * @see WP_Widget::form()
		 *
		 */
		public function form( $instance ) {

			$title                 = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Filter Properties', ERE_TEXT_DOMAIN );
			$hide_empty            = $instance['hide_empty'] ?? 'false';
			$property_types        = $instance['property-types'] ?? 'true';
                        $property_location     = $instance['property-location'] ?? 'true';
                        $location_terms        = $instance['location-terms'] ?? array();
                        $property_categories   = $instance['property-categories'] ?? 'true';
                        $category_terms        = $instance['category-terms'] ?? array();
                        $property_status       = $instance['property-status'] ?? 'true';
                        $property_features     = $instance['property-features'] ?? 'true';
			$checkboxes_view_limit = $instance['checkboxes-view-limit'] ?? 6;
			$price_ranges          = $instance['price-ranges'] ?? 'true';
			$price_range_type      = $instance['price-range-type'] ?? 'radio';
			$custom_price_ranges   = $instance['custom-price-ranges'] ?? '';
			$price_slider_min      = $instance['price-slider-min'] ?? '';
			$price_slider_max      = $instance['price-slider-max'] ?? '';
			$area_ranges           = $instance['area-ranges'] ?? 'true';
			$area_range_type       = $instance['area-range-type'] ?? 'radio';
			$custom_area_ranges    = $instance['custom-area-ranges'] ?? '';
			$area_slider_min       = $instance['area-slider-min'] ?? '';
			$area_slider_max       = $instance['area-slider-max'] ?? '';
			$area_unit             = $instance['area-unit'] ?? 'sq ft';
			$bedrooms_options      = $instance['bedroom-options'] ?? 'true';
			$bedrooms_max_value    = $instance['bedrooms-max-value'] ?? 9;
			$bathrooms_options     = $instance['bathroom-options'] ?? 'true';
			$bathrooms_max_value   = $instance['bathrooms-max-value'] ?? 9;
			$garages_options       = $instance['garage-options'] ?? 'true';
			$garages_max_value     = $instance['garages-max-value'] ?? 4;
			$agent_options         = $instance['agent-options'] ?? 'true';
			$agent_display_type    = $instance['agent-display-type'] ?? 'thumbnail';
			$agency_options        = $instance['agency-options'] ?? 'true';
			$agency_display_type   = $instance['agency-display-type'] ?? 'thumbnail';
			$property_id           = $instance['property-id'] ?? 'true';
			$additional_fields     = $instance['additional-fields'] ?? 'true';
			?>
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title' ); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
            </p>

            <hr>

            <h4><?php esc_html_e( 'Show/Hide Taxonomies', ERE_TEXT_DOMAIN ); ?></h4>

            <p class="fancy-checkbox-option-button property-type-wrapper">
                <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Property Type', ERE_TEXT_DOMAIN ); ?></strong>
                <input type="checkbox" id="<?php echo $this->get_field_id( 'property-types' ); ?>" name="<?php echo $this->get_field_name( 'property-types' ); ?>" value="true" <?php echo checked( $property_types, 'true' ); ?>>
                <label class="fancy-button-label widget-field-half option-align-right" for="<?php echo $this->get_field_id( 'property-types' ); ?>">
                    <span class="cb-btn-wrapper">
                        <span class="cb-btn-trigger"></span>
                    </span>
                </label>
            </p>

            <p class="fancy-checkbox-option-button property-location-wrapper">
                <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Property Location', ERE_TEXT_DOMAIN ); ?></strong>
                <input type="checkbox" id="<?php echo $this->get_field_id( 'property-location' ); ?>" name="<?php echo $this->get_field_name( 'property-location' ); ?>" value="true" <?php echo checked( $property_location, 'true' ); ?>>
                <label class="fancy-button-label widget-field-half option-align-right" for="<?php echo $this->get_field_id( 'property-location' ); ?>">
                    <span class="cb-btn-wrapper">
                        <span class="cb-btn-trigger"></span>
                    </span>
                </label>
            </p>

            <p class="location-terms-wrapper">
                <label for="<?php echo $this->get_field_id( 'location-terms' ); ?>"><?php esc_html_e( 'Select Locations', ERE_TEXT_DOMAIN ); ?></label>
                <select class="widefat" multiple id="<?php echo $this->get_field_id( 'location-terms' ); ?>" name="<?php echo $this->get_field_name( 'location-terms' ); ?>[]">
                                        <?php
                                        $locations = get_terms( array( 'taxonomy' => 'property-city', 'hide_empty' => false ) );
                                        if ( ! is_wp_error( $locations ) ) {
                                                foreach ( $locations as $loc ) {
                                                        $selected = in_array( $loc->term_id, (array) $location_terms ) ? 'selected' : '';
                                                        echo '<option value="' . esc_attr( $loc->term_id ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $loc->name ) . '</option>';
                                                }
                                        }
                                        ?>
                </select>
                <span class="description"><?php esc_html_e( 'Choose which locations appear in filters.', ERE_TEXT_DOMAIN ); ?></span>
            </p>

            <p class="fancy-checkbox-option-button property-categories-wrapper">
                <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Property Categories', ERE_TEXT_DOMAIN ); ?></strong>
                <input type="checkbox" id="<?php echo $this->get_field_id( 'property-categories' ); ?>" name="<?php echo $this->get_field_name( 'property-categories' ); ?>" value="true" <?php echo checked( $property_categories, 'true' ); ?>>
                <label class="fancy-button-label widget-field-half option-align-right" for="<?php echo $this->get_field_id( 'property-categories' ); ?>">
                    <span class="cb-btn-wrapper">
                        <span class="cb-btn-trigger"></span>
                    </span>
                </label>
            </p>

            <p class="category-terms-wrapper">
                <label for="<?php echo $this->get_field_id( 'category-terms' ); ?>"><?php esc_html_e( 'Select Categories', ERE_TEXT_DOMAIN ); ?></label>
                <select class="widefat" multiple id="<?php echo $this->get_field_id( 'category-terms' ); ?>" name="<?php echo $this->get_field_name( 'category-terms' ); ?>[]">
                                        <?php
                                        $categories = get_terms( array( 'taxonomy' => 'property-category', 'hide_empty' => false ) );
                                        if ( ! is_wp_error( $categories ) ) {
                                                foreach ( $categories as $cat ) {
                                                        $selected = in_array( $cat->term_id, (array) $category_terms ) ? 'selected' : '';
                                                        echo '<option value="' . esc_attr( $cat->term_id ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $cat->name ) . '</option>';
                                                }
                                        }
                                        ?>
                </select>
                <span class="description"><?php esc_html_e( 'Choose which categories appear in filters.', ERE_TEXT_DOMAIN ); ?></span>
            </p>

            <p class="fancy-checkbox-option-button property-status-wrapper">
                <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Property Status', ERE_TEXT_DOMAIN ); ?></strong>
                <input type="checkbox" id="<?php echo $this->get_field_id( 'property-status' ); ?>" name="<?php echo $this->get_field_name( 'property-status' ); ?>" value="true" <?php echo checked( $property_status, 'true' ); ?>>
                <label class="fancy-button-label widget-field-half option-align-right" for="<?php echo $this->get_field_id( 'property-status' ); ?>">
                    <span class="cb-btn-wrapper">
                        <span class="cb-btn-trigger"></span>
                    </span>
                </label>
            </p>

            <p class="fancy-checkbox-option-button property-features-wrapper">
                <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Property Features', ERE_TEXT_DOMAIN ); ?></strong>
                <input type="checkbox" id="<?php echo $this->get_field_id( 'property-features' ); ?>" name="<?php echo $this->get_field_name( 'property-features' ); ?>" value="true" <?php echo checked( $property_features, 'true' ); ?>>
                <label class="fancy-button-label widget-field-half option-align-right" for="<?php echo $this->get_field_id( 'property-features' ); ?>">
                    <span class="cb-btn-wrapper">
                        <span class="cb-btn-trigger"></span>
                    </span>
                </label>
            </p>

            <p>
                <label for="<?php echo esc_attr( $this->get_field_id( 'hide_empty' ) ); ?>"><?php esc_html_e( 'Hide Empty Taxonomies', ERE_TEXT_DOMAIN ); ?></label><br>
                <select class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'hide_empty' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'hide_empty' ) ); ?>">
                    <option value="true" <?php echo selected( 'true', $hide_empty ); ?>><?php esc_html_e( 'True', ERE_TEXT_DOMAIN ) ?></option>
                    <option value="false" <?php echo selected( 'false', $hide_empty ); ?>><?php esc_html_e( 'False', ERE_TEXT_DOMAIN ) ?></option>
                </select>
            </p>

            <p>
                <label for="<?php echo $this->get_field_id( 'checkboxes-view-limit' ); ?>"><?php esc_html_e( 'Checkboxes View Limit', ERE_TEXT_DOMAIN ); ?></label><br>
                <input class="widefat" type="number" id="<?php echo $this->get_field_id( 'checkboxes-view-limit' ); ?>" name="<?php echo $this->get_field_name( 'checkboxes-view-limit' ); ?>" value="<?php echo esc_attr( $checkboxes_view_limit ); ?>">
            </p>

            <hr>

            <p class="fancy-checkbox-option-button price-ranges-wrapper">
                <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Price Ranges', ERE_TEXT_DOMAIN ); ?></strong>
                <input type="checkbox" id="<?php echo $this->get_field_id( 'price-ranges' ); ?>" name="<?php echo $this->get_field_name( 'price-ranges' ); ?>" value="true" <?php echo checked( $price_ranges, 'true' ); ?>>
                <label class="fancy-button-label widget-field-half option-align-right" for="<?php echo $this->get_field_id( 'price-ranges' ); ?>">
                    <span class="cb-btn-wrapper">
                        <span class="cb-btn-trigger"></span>
                    </span>
                </label>
            </p>

            <div class="fancy-radio-options price-range-type-wrapper">
                <br>
                <strong class="widget-field-40 fancy-field-title"><?php esc_html_e( 'Price Range Type', ERE_TEXT_DOMAIN ); ?></strong>
                <div class="option-align-right">
                    <span class="buttons-wrap">
                        <span class="inner-buttons">
                            <input type="radio" name="<?php echo $this->get_field_name( 'price-range-type' ); ?>" id="<?php echo $this->get_field_id( 'price-range-type-radio' ); ?>" value="radio" <?php echo checked( $price_range_type, 'radio' ); ?>>
                            <label for="<?php echo $this->get_field_id( 'price-range-type-radio' ); ?>"><?php esc_html_e( 'Radio Options', ERE_TEXT_DOMAIN ); ?></label>
                        </span>
                        <span class="inner-buttons">
                            <input type="radio" name="<?php echo $this->get_field_name( 'price-range-type' ); ?>" id="<?php echo $this->get_field_id( 'price-range-type-slider' ); ?>" value="slider" <?php echo checked( $price_range_type, 'slider' ); ?>>
                            <label for="<?php echo $this->get_field_id( 'price-range-type-slider' ); ?>"><?php esc_html_e( 'Slider Controls', ERE_TEXT_DOMAIN ); ?></label>
                        </span>
                    </span>
                </div>
            </div>

            <div id="price-ranges" class="clearfix custom-price-ranges-wrapper option-dependent-control">
                <p class="widget-fat">
                    <label for="<?php echo $this->get_field_id( 'custom-price-ranges' ); ?>-default"><?php esc_html_e( 'Custom Price Ranges (optional)', ERE_TEXT_DOMAIN ); ?></label>
                    <textarea class="widefat" id="<?php echo $this->get_field_id( 'custom-price-ranges' ); ?>" name="<?php echo $this->get_field_name( 'custom-price-ranges' ); ?>" type="radio"><?php echo esc_html( $custom_price_ranges ); ?></textarea>
                    <span class="description"><?php esc_html_e( 'Leave this area empty for default values. Put each range in new line in the given format. ( i.e. 5000 - 10000 )', ERE_TEXT_DOMAIN ); ?></span>
                </p>
            </div>

            <div class="price-min-max-wrap option-dependent-control">
                <p class="widget-field-half">
                    <label for="<?php echo $this->get_field_id( 'price-slider-min' ); ?>"><?php esc_html_e( 'Price Slider Min', ERE_TEXT_DOMAIN ); ?></label><br>
                    <input type="text" id="<?php echo $this->get_field_id( 'price-slider-min' ); ?>" name="<?php echo $this->get_field_name( 'price-slider-min' ); ?>" value="<?php echo esc_attr( $price_slider_min ); ?>"><br>
                </p>
                <p class="widget-field-half">
                    <label for="<?php echo $this->get_field_id( 'price-slider-min' ); ?>"><?php esc_html_e( 'Price Slider Max', ERE_TEXT_DOMAIN ); ?></label><br>
                    <input type="text" id="<?php echo $this->get_field_id( 'price-slider-max' ); ?>" name="<?php echo $this->get_field_name( 'price-slider-max' ); ?>" value="<?php echo esc_attr( $price_slider_max ); ?>">
                </p>
            </div>

            <hr>

            <p class="fancy-checkbox-option-button area-ranges-wrapper">
                <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Area Ranges', ERE_TEXT_DOMAIN ); ?></strong>
                <input type="checkbox" id="<?php echo $this->get_field_name( 'area-ranges' ); ?>" name="<?php echo $this->get_field_name( 'area-ranges' ); ?>" value="true" <?php echo checked( $area_ranges, 'true' ); ?>>
                <label class="fancy-button-label widget-field-half option-align-right" for="<?php echo $this->get_field_name( 'area-ranges' ); ?>">
                    <span class="cb-btn-wrapper">
                        <span class="cb-btn-trigger"></span>
                    </span>
                </label>
            </p>

            <p class="area-unit-wrapper">
                <label for="<?php echo $this->get_field_id( 'area-unit' ); ?>"><?php esc_html_e( 'Area Unit', ERE_TEXT_DOMAIN ); ?></label><br>
                <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'area-unit' ); ?>" name="<?php echo $this->get_field_name( 'area-unit' ); ?>" value="<?php echo esc_attr( $area_unit ); ?>">
            </p>

            <div class="fancy-radio-options area-range-type-wrapper">
                <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Area Range Type', ERE_TEXT_DOMAIN ); ?></strong>
                <div class="option-align-right">
                    <span class="buttons-wrap">
                        <span class="inner-buttons">
                            <input type="radio" name="<?php echo $this->get_field_name( 'area-range-type' ); ?>" id="<?php echo $this->get_field_id( 'area-range-type-radio' ); ?>" value="radio" <?php echo checked( $area_range_type, 'radio' ); ?>>
                            <label for="<?php echo $this->get_field_id( 'area-range-type-radio' ); ?>"><?php esc_html_e( 'Radio Options', ERE_TEXT_DOMAIN ); ?></label>
                        </span>
                        <span class="inner-buttons">
                            <input type="radio" name="<?php echo $this->get_field_name( 'area-range-type' ); ?>" id="<?php echo $this->get_field_id( 'area-range-type-slider' ); ?>" value="slider" <?php echo checked( $area_range_type, 'slider' ); ?>>
                            <label for="<?php echo $this->get_field_id( 'area-range-type-slider' ); ?>"><?php esc_html_e( 'Slider Controls', ERE_TEXT_DOMAIN ); ?></label>
                        </span>
                    </span>
                </div>
            </div>

            <div id="area-ranges" class="clearfix custom-area-ranges-wrapper option-dependent-control">
                <p class="widget-fat">
                    <label for="<?php echo $this->get_field_id( 'custom-area-ranges' ); ?>-default"><?php esc_html_e( 'Custom Area Ranges (optional)', ERE_TEXT_DOMAIN ); ?></label>
                    <textarea class="widefat" id="<?php echo $this->get_field_id( 'custom-area-ranges' ); ?>" name="<?php echo $this->get_field_name( 'custom-area-ranges' ); ?>" type="radio"><?php echo esc_html( $custom_area_ranges ); ?></textarea>
                    <span class="description"><?php esc_html_e( 'Leave this area empty for default values. Put each range in new line in the given format. ( i.e. 200 - 400 )', ERE_TEXT_DOMAIN ); ?></span>
                </p>
            </div>

            <div class="area-min-max-wrap option-dependent-control <?php echo $area_range_type == 'radio' ? 'hidden' : ''; ?>">
                <p class="widget-field-half">
                    <label for="<?php echo $this->get_field_id( 'area-slider-min' ); ?>"><?php esc_html_e( 'Area Slider Min', ERE_TEXT_DOMAIN ); ?></label><br>
                    <input type="text" id="<?php echo $this->get_field_id( 'area-slider-min' ); ?>" name="<?php echo $this->get_field_name( 'area-slider-min' ); ?>" value="<?php echo esc_attr( $area_slider_min ); ?>"><br>
                </p>
                <p class="widget-field-half">
                    <label for="<?php echo $this->get_field_id( 'area-slider-max' ); ?>"><?php esc_html_e( 'Area Slider Max', ERE_TEXT_DOMAIN ); ?></label><br>
                    <input type="text" id="<?php echo $this->get_field_id( 'area-slider-max' ); ?>" name="<?php echo $this->get_field_name( 'area-slider-max' ); ?>" value="<?php echo esc_attr( $area_slider_max ); ?>">
                </p>
            </div>

            <hr>

			<?php
			if ( post_type_exists( 'agent' ) ) {
				?>
                <p class="fancy-checkbox-option-button agent-options-wrapper">
                    <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Agent Options', ERE_TEXT_DOMAIN ); ?></strong>
                    <input type="checkbox" id="<?php echo $this->get_field_id( 'agent-options' ); ?>" name="<?php echo $this->get_field_name( 'agent-options' ); ?>" value="true" <?php echo checked( $agent_options, 'true' ); ?>>
                    <label class="fancy-button-label widget-field-half option-align-right" for="<?php echo $this->get_field_id( 'agent-options' ); ?>">
                        <span class="cb-btn-wrapper">
                            <span class="cb-btn-trigger"></span>
                        </span>
                    </label>
                </p>

                <div class="fancy-radio-options agent-display-type-wrapper">
                    <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Display Type', ERE_TEXT_DOMAIN ); ?></strong>
                    <div class="option-align-right">
                        <span class="buttons-wrap">
                            <span class="inner-buttons">
                                <input type="radio" name="<?php echo $this->get_field_name( 'agent-display-type' ); ?>" id="<?php echo $this->get_field_id( 'agent-display-type-thumbnails' ); ?>" value="thumbnail" <?php echo checked( $agent_display_type, 'thumbnail' ); ?>>
                                <label for="<?php echo $this->get_field_id( 'agent-display-type-thumbnails' ); ?>"><?php esc_html_e( 'Thumbnails', ERE_TEXT_DOMAIN ); ?></label>
                            </span>
                            <span class="inner-buttons">
                                <input type="radio" name="<?php echo $this->get_field_name( 'agent-display-type' ); ?>" id="<?php echo $this->get_field_id( 'agent-display-type-checkboxes' ); ?>" value="checkbox" <?php echo checked( $agent_display_type, 'checkbox' ); ?>>
                                <label for="<?php echo $this->get_field_id( 'agent-display-type-checkboxes' ); ?>"><?php esc_html_e( 'Checkboxes', ERE_TEXT_DOMAIN ); ?></label>
                            </span>
                        </span>
                    </div>
                </div>

                <hr>
				<?php
			}

			if ( post_type_exists( 'agency' ) ) {
				?>
                <p class="fancy-checkbox-option-button agency-options-wrapper">
                    <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Agency Options', ERE_TEXT_DOMAIN ); ?></strong>
                    <input type="checkbox" id="<?php echo $this->get_field_id( 'agency-options' ); ?>" name="<?php echo $this->get_field_name( 'agency-options' ); ?>" value="true" <?php echo checked( $agency_options, 'true' ); ?>>
                    <label class="fancy-button-label widget-field-half option-align-right" for="<?php echo $this->get_field_id( 'agency-options' ); ?>">
                        <span class="cb-btn-wrapper">
                            <span class="cb-btn-trigger"></span>
                        </span>
                    </label>
                </p>

                <div class="fancy-radio-options agency-display-type-wrapper">
                    <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Display Type', ERE_TEXT_DOMAIN ); ?></strong>
                    <div class="option-align-right">
                    <span class="buttons-wrap">
                        <span class="inner-buttons">
                            <input type="radio" name="<?php echo $this->get_field_name( 'agency-display-type' ); ?>" id="<?php echo $this->get_field_id( 'agency-display-type-thumbnails' ); ?>" value="thumbnail" <?php echo checked( $agency_display_type, 'thumbnail' ); ?>>
                            <label for="<?php echo $this->get_field_id( 'agency-display-type-thumbnails' ); ?>"><?php esc_html_e( 'Thumbnails', ERE_TEXT_DOMAIN ); ?></label>
                        </span>
                        <span class="inner-buttons">
                            <input type="radio" name="<?php echo $this->get_field_name( 'agency-display-type' ); ?>" id="<?php echo $this->get_field_id( 'agency-display-type-checkboxes' ); ?>" value="checkbox" <?php echo checked( $agency_display_type, 'checkbox' ); ?>>
                            <label for="<?php echo $this->get_field_id( 'agency-display-type-checkboxes' ); ?>"><?php esc_html_e( 'Checkboxes', ERE_TEXT_DOMAIN ); ?></label>
                        </span>
                    </span>
                    </div>
                </div>

                <hr>
				<?php
			}
			?>

            <p class="fancy-checkbox-option-button bedroom-options-wrapper">
                <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Bedroom Options', ERE_TEXT_DOMAIN ); ?></strong>
                <input type="checkbox" id="<?php echo $this->get_field_id( 'bedroom-options' ); ?>" name="<?php echo $this->get_field_name( 'bedroom-options' ); ?>" value="true" <?php echo checked( $bedrooms_options, 'true' ); ?>>
                <label class="fancy-button-label widget-field-half option-align-right" for="<?php echo $this->get_field_id( 'bedroom-options' ); ?>">
                    <span class="cb-btn-wrapper">
                        <span class="cb-btn-trigger"></span>
                    </span>
                </label>
            </p>

            <p class="mini-option bedrooms-max-value option-align-right bedrooms-max-value-wrapper">
                <label for="<?php echo $this->get_field_id( 'bedrooms-max-value' ); ?>"><?php esc_html_e( 'Maximum Bedrooms', ERE_TEXT_DOMAIN ); ?></label>
                <input class="" type="number" id="<?php echo $this->get_field_id( 'bedrooms-max-value' ); ?>" name="<?php echo $this->get_field_name( 'bedrooms-max-value' ); ?>" value="<?php echo esc_attr( $bedrooms_max_value ); ?>">
            </p>

            <hr>

            <p class="fancy-checkbox-option-button bathroom-options-wrapper">
                <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Bathroom Options', ERE_TEXT_DOMAIN ); ?></strong>
                <input type="checkbox" id="<?php echo $this->get_field_id( 'bathroom-options' ); ?>" name="<?php echo $this->get_field_name( 'bathroom-options' ); ?>" value="true" <?php echo checked( $bathrooms_options, 'true' ); ?>>
                <label class="fancy-button-label widget-field-half option-align-right" for="<?php echo $this->get_field_id( 'bathroom-options' ); ?>">
                    <span class="cb-btn-wrapper">
                        <span class="cb-btn-trigger"></span>
                    </span>
                </label>
            </p>

            <p class="mini-option bathrooms-max-value option-align-right bathrooms-max-value-wrapper">
                <label for="<?php echo $this->get_field_id( 'bathrooms-max-value' ); ?>"><?php esc_html_e( 'Maximum Bathrooms', ERE_TEXT_DOMAIN ); ?></label>
                <input class="" type="number" id="<?php echo $this->get_field_id( 'bathrooms-max-value' ); ?>" name="<?php echo $this->get_field_name( 'bathrooms-max-value' ); ?>" value="<?php echo esc_attr( $bathrooms_max_value ); ?>">
            </p>

            <hr>

            <p class="fancy-checkbox-option-button garage-options-wrapper">
                <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Garage Options', ERE_TEXT_DOMAIN ); ?></strong>
                <input type="checkbox" id="<?php echo $this->get_field_id( 'garage-options' ); ?>" name="<?php echo $this->get_field_name( 'garage-options' ); ?>" value="true" <?php echo checked( $garages_options, 'true' ); ?>>
                <label class="fancy-button-label widget-field-half option-align-right" for="<?php echo $this->get_field_id( 'garage-options' ); ?>">
                    <span class="cb-btn-wrapper">
                        <span class="cb-btn-trigger"></span>
                    </span>
                </label>
            </p>

            <p class="mini-option garages-max-value option-align-right garages-max-value-wrapper">
                <label for="<?php echo $this->get_field_id( 'garages-max-value' ); ?>"><?php esc_html_e( 'Maximum Garages', ERE_TEXT_DOMAIN ); ?></label>
                <input class="" type="number" id="<?php echo $this->get_field_id( 'garages-max-value' ); ?>" name="<?php echo $this->get_field_name( 'garages-max-value' ); ?>" value="<?php echo esc_attr( $garages_max_value ); ?>">
            </p>

            <hr>

            <p class="fancy-checkbox-option-button property-id-options-wrapper">
                <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Property ID', ERE_TEXT_DOMAIN ); ?></strong>
                <input type="checkbox" id="<?php echo $this->get_field_id( 'property-id' ); ?>" name="<?php echo $this->get_field_name( 'property-id' ); ?>" value="true" <?php echo checked( $property_id, 'true' ); ?>>
                <label class="fancy-button-label widget-field-half option-align-right" for="<?php echo $this->get_field_id( 'property-id' ); ?>">
                    <span class="cb-btn-wrapper">
                        <span class="cb-btn-trigger"></span>
                    </span>
                </label>
            </p>

            <p class="fancy-checkbox-option-button property-id-options-wrapper">
                <strong class="widget-field-half fancy-field-title"><?php esc_html_e( 'Additional Fields', ERE_TEXT_DOMAIN ); ?></strong>
                <input type="checkbox" id="<?php echo $this->get_field_id( 'additional-fields' ); ?>" name="<?php echo $this->get_field_name( 'additional-fields' ); ?>" value="true" <?php echo checked( $additional_fields, 'true' ); ?>>
                <label class="fancy-button-label widget-field-half option-align-right" for="<?php echo $this->get_field_id( 'additional-fields' ); ?>">
                    <span class="cb-btn-wrapper">
                        <span class="cb-btn-trigger"></span>
                    </span>
                </label>
            </p>

            <hr><br>
			<?php
		}

		/**
		 * Sanitize widget form values as they are saved.
		 *
		 * @param array $new_instance Values just sent to be saved.
		 * @param array $old_instance Previously saved values from database.
		 *
		 * @return array Updated safe values to be saved.
		 * @see WP_Widget::update()
		 *
		 */
		public function update( $new_instance, $old_instance ) {
			$instance                          = array();
			$instance['title']                 = ! empty( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '';
			$instance['hide_empty']            = ! empty( $new_instance['hide_empty'] ) ? $new_instance['hide_empty'] : '';
			$instance['property-types']        = ! empty( $new_instance['property-types'] ) ? $new_instance['property-types'] : '';
                        $instance['property-location']     = ! empty( $new_instance['property-location'] ) ? $new_instance['property-location'] : '';
                        $instance['location-terms']        = ! empty( $new_instance['location-terms'] ) ? (array) $new_instance['location-terms'] : array();
                        $instance['property-categories']   = ! empty( $new_instance['property-categories'] ) ? $new_instance['property-categories'] : '';
                        $instance['category-terms']        = ! empty( $new_instance['category-terms'] ) ? (array) $new_instance['category-terms'] : array();
                        $instance['property-status']       = ! empty( $new_instance['property-status'] ) ? $new_instance['property-status'] : '';
                        $instance['property-features']     = ! empty( $new_instance['property-features'] ) ? $new_instance['property-features'] : '';
			$instance['checkboxes-view-limit'] = ! empty( $new_instance['checkboxes-view-limit'] ) ? $new_instance['checkboxes-view-limit'] : '';
			$instance['price-ranges']          = ! empty( $new_instance['price-ranges'] ) ? $new_instance['price-ranges'] : '';
			$instance['price-range-type']      = ! empty( $new_instance['price-range-type'] ) ? $new_instance['price-range-type'] : '';
			$instance['custom-price-ranges']   = ! empty( $new_instance['custom-price-ranges'] ) ? $new_instance['custom-price-ranges'] : '';
			$instance['price-slider-min']      = ! empty( $new_instance['price-slider-min'] ) ? $new_instance['price-slider-min'] : '';
			$instance['price-slider-max']      = ! empty( $new_instance['price-slider-max'] ) ? $new_instance['price-slider-max'] : '';
			$instance['area-ranges']           = ! empty( $new_instance['area-ranges'] ) ? $new_instance['area-ranges'] : '';
			$instance['area-range-type']       = ! empty( $new_instance['area-range-type'] ) ? $new_instance['area-range-type'] : '';
			$instance['area-unit']             = ! empty( $new_instance['area-unit'] ) ? $new_instance['area-unit'] : '';
			$instance['custom-area-ranges']    = ! empty( $new_instance['custom-area-ranges'] ) ? $new_instance['custom-area-ranges'] : '';
			$instance['area-slider-min']       = ! empty( $new_instance['area-slider-min'] ) ? $new_instance['area-slider-min'] : '';
			$instance['area-slider-max']       = ! empty( $new_instance['area-slider-max'] ) ? $new_instance['area-slider-max'] : '';
			$instance['bedroom-options']       = ! empty( $new_instance['bedroom-options'] ) ? $new_instance['bedroom-options'] : '';
			$instance['bedrooms-max-value']    = ! empty( $new_instance['bedrooms-max-value'] ) ? $new_instance['bedrooms-max-value'] : '';
			$instance['bathroom-options']      = ! empty( $new_instance['bathroom-options'] ) ? $new_instance['bathroom-options'] : '';
			$instance['bathrooms-max-value']   = ! empty( $new_instance['bathrooms-max-value'] ) ? $new_instance['bathrooms-max-value'] : '';
			$instance['garage-options']        = ! empty( $new_instance['garage-options'] ) ? $new_instance['garage-options'] : '';
			$instance['garages-max-value']     = ! empty( $new_instance['garages-max-value'] ) ? $new_instance['garages-max-value'] : '';
			$instance['agent-options']         = ! empty( $new_instance['agent-options'] ) ? $new_instance['agent-options'] : '';
			$instance['agent-display-type']    = ! empty( $new_instance['agent-display-type'] ) ? $new_instance['agent-display-type'] : '';
			$instance['agency-options']        = ! empty( $new_instance['agency-options'] ) ? $new_instance['agency-options'] : '';
			$instance['agency-display-type']   = ! empty( $new_instance['agency-display-type'] ) ? $new_instance['agency-display-type'] : '';
			$instance['property-id']           = ! empty( $new_instance['property-id'] ) ? $new_instance['property-id'] : '';
			$instance['additional-fields']     = ! empty( $new_instance['additional-fields'] ) ? $new_instance['additional-fields'] : '';

			return $instance;
		}
	}
}

if ( ! function_exists( 'register_properties_filter_widget' ) ) {

	// Register Properties_Filter_Widget widget
	function register_properties_filter_widget() {
		register_widget( 'Properties_Filter_Widget' );
	}

	add_action( 'widgets_init', 'register_properties_filter_widget' );

}