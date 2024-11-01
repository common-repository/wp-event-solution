<?php

    use Etn\Utils\Helper;
?>
<div class="wrap etn-settings-dashboard etn-settings-shortcode">
	<div class="etn-admin-container stuffbox">
		<div class="etn-admin-container--body">
			<!-- hooks -->
			<div class="attr-tab-content etn-settings-section etn-shortcode-settings" data-id="tab6" id="etn-hooks_options">
				<div class="etn-settings-single-section">
					<div class="shortcode-generator-wrap">
						<div class="shortcode-generator-main-wrap">
							<div class="shortcode-generator-inner">
								<div class="shortcode-popup-close"><span>❌</span></div>
									<div class="etn-row">
										<div class="etn-col-lg-6">
											<div class="etn-field-wrap">
												<h3><?php echo esc_html__('Select event', 'eventin'); ?></h3>
												<?php
												$event_list = [
																																			'events' => esc_html__('Event List', 'eventin'),
																																			'events_tab' => esc_html__('Event Tab', 'eventin'),
																						];
																				echo Helper::get_option_range($event_list);
																				?>
																		</div>
																</div>
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Select Style', 'eventin'); ?></h3>
																				<?php Helper::get_option_style(2,'style', 'event-', 'style ' ); ?>
																		</div>
																</div>
														</div>
														<div class="etn-row">
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Select Category', 'eventin'); ?></h3>
																				<?php
																				echo Helper::get_etn_taxonomy_ids('etn_category', 'event_cat_ids');
																				?>
																		</div>
																</div>
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Select Tags', 'eventin'); ?></h3>
																				<?php
																				echo Helper::get_etn_taxonomy_ids('etn_tags', 'event_tag_ids');
																				?>
																		</div>
																</div>
														</div>
														
														<div class="etn-row">
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Select Order', 'eventin'); ?></h3>
																				<?php echo Helper::get_order('order'); ?>
																		</div>
																</div>
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Select orderby', 'eventin'); ?></h3>
																				<?php
																						$orderby = [
																								"orderby='title'" => esc_html__('Title', 'eventin'),
																								"orderby='ID'" => esc_html__('ID', 'eventin'),
																								"orderby='post_date'" => esc_html__('Post Date', 'eventin'),
																								"orderby='etn_start_date'" => esc_html__('Event Start Date', 'eventin'),
																								"orderby='etn_end_date'" => esc_html__('Event End Date', 'eventin'),
																						];
																				echo Helper::get_option_range($orderby);
																				?>
																		</div>
																</div>
														</div>
														
														<div class="etn-row">
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Filter by status', 'eventin'); ?></h3>
																				<?php echo Helper::get_event_status('filter_with_status'); ?>
																		</div>
																</div>
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Event Column', 'eventin'); ?></h3>
																				<?php echo Helper::get_option_style(4, 'etn_event_col', '', 'Column '); ?>
																		</div>
																</div>
														</div>
														<div class="etn-row">
															<div class="etn-col-lg-6">
															<div class="etn-field-wrap">
																<h3><?php echo esc_html__('Event Limit', 'eventin'); ?></h3>
																<input type="number" data-count ="<?php echo esc_attr('limit') ?>" class="post_count etn-setting-input" value="20">
															</div>
															</div>
															<div class="etn-col-lg-6">
																<div class="etn-field-wrap">
																	<h3><?php echo esc_html__('Show End Date', 'eventin'); ?></h3>
																	<?php
																		$show_end_date = [
																			"show_end_date='yes'" => esc_html__('Yes', 'eventin'),
																			"show_end_date='no'" => esc_html__('No', 'eventin')
																		];
																	echo Helper::get_option_range($show_end_date);
																	?>
																</div>
															</div>
														</div>
														<div class="etn-row">
															<div class="etn-col-lg-6">
																<div class="etn-field-wrap">
																		<h3><?php echo esc_html__('Show Recurring Child Events', 'eventin'); ?></h3>
																		<?php echo Helper::get_show_hide('show_child_event'); ?>
																</div>
															</div>
															<div class="etn-col-lg-6">
																<div class="etn-field-wrap">
																	<h3><?php echo esc_html__('Show Recurring Parent Events', 'eventin'); ?></h3>
																	<?php echo Helper::get_show_hide_recurring('show_parent_event', 1 ); ?>
																</div>
															</div>
														</div>
														<div class="etn-row">
															<div class="etn-col-lg-6">
																<div class="etn-field-wrap">
																		<h3><?php echo esc_html__('Show Event Location', 'eventin'); ?></h3>
																		<?php echo Helper::get_show_hide('show_event_location'); ?>
																</div>
															</div>
															<div class="etn-col-lg-6">
																<div class="etn-field-wrap">
																	<h3><?php echo esc_html__('Show Event Desc', 'eventin'); ?></h3>
																	<?php echo Helper::get_show_hide('etn_desc_show'); ?>
																</div>
															</div>
														</div>
						
														<div class="etn-row">
															<div class="etn-col-lg-6">
															
															<button type="button" class="etn-btn shortcode-generate-btn"><?php echo esc_html__('Generate', 'eventin'); ?></button>
															
															<?php if ( function_exists( 'wpeventin_pro' ) ): ?>
															<button type="button" class="etn-btn shortcode-script-btn"><?php echo esc_html__('Get Script', 'eventin'); ?></button>
															<input type="hidden" class="script-name" value="event">
															<?php endif; ?>
															</div>
														</div>

														<div class="attr-form-group etn-label-item copy_shortcodes">
																<div class="etn-meta">
																		<input type="text" readonly name="etn_event_label" id="events-shortcode" value="[events]" class="etn-setting-input etn_include_shortcode" placeholder="<?php esc_attr_e('Label Text', 'eventin'); ?>">
																		<button type="button" onclick="copyTextData('events-shortcode');" class="etn_copy_button etn-btn"><span class="dashicons dashicons-category"></span></button>
																</div>
														</div>
												</div>
										</div>
										<div class="attr-form-group etn-label-item">
												<div class="etn-label">
														<label><?php esc_html_e('Event', 'eventin'); ?> </label>
														<div class="etn-desc"> <?php esc_html_e('Show "event details" in any specific location.', 'eventin'); ?> </div>
												</div>
												<div class="etn-meta">
														<button type="button" class="etn-btn s-generate-btn"><?php echo esc_html__('Generate Shortcode', 'eventin'); ?></button>
												</div>
										</div>
								</div>
								<!-- calendar with event -->
								<div class="shortcode-generator-wrap">
										<div class="shortcode-generator-main-wrap">
												<div class="shortcode-generator-inner">
														<div class="shortcode-popup-close"><span>❌</span></div>
														<div class="etn-row">
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Select Calendar Event', 'eventin'); ?></h3>
																				<?php
																						$event_list = [
																								'events_calendar' => esc_html__('Event With Calendar', 'eventin'),
																						];
																				echo Helper::get_option_range($event_list);
																				?>
																		</div>
																</div>
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap calendar-style">
																				<h3><?php echo esc_html__('Select Style', 'eventin'); ?></h3>
																				<?php Helper::get_option_style(2,'style', 'style-', 'style ' ); ?>
																		</div>
																</div>
														</div>
														<div class="etn-row">
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Select Category', 'eventin'); ?></h3>
																				<?php
																				echo Helper::get_etn_taxonomy_ids('etn_category', 'event_cat_ids');
																				?>
																		</div>
																</div>
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap s-display-calendar">
																						<h3><?php echo esc_html__('Display Calendar', 'eventin'); ?></h3>
																						<?php 
																						$orderby = [
																								"calendar_show='left'" => esc_html__('Left', 'eventin'),
																								"calendar_show='full_width'" => esc_html__('Full Width', 'eventin'),
																								"calendar_show='right'" => esc_html__('Right', 'eventin'),
																						];
																						echo Helper::get_option_range($orderby);
																				?>
																				</div>
																</div>
														</div>
														
														<div class="etn-row">
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Show Description?', 'eventin'); ?></h3>
																				<?php echo Helper::get_show_hide('show_desc'); ?>
																		</div>
																</div>
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Event Limit', 'eventin'); ?></h3>
																				<input type="number" data-count ="<?php echo esc_attr('limit') ?>" class="post_count etn-setting-input" value="5">
																		</div>
																</div>
														</div>

														<div class="etn-row">
															<div class="etn-col-lg-6">
																<div class="etn-field-wrap">
																		<h3><?php echo esc_html__('Show Recurring Child Events', 'eventin'); ?></h3>
																		<?php echo Helper::get_show_hide('show_child_event'); ?>
																</div>
															</div>
															<div class="etn-col-lg-6">
																<div class="etn-field-wrap">
																	<h3><?php echo esc_html__('Show Recurring Parent Events', 'eventin'); ?></h3>
																	<?php echo Helper::get_show_hide_recurring('show_parent_event', 1 ); ?>
																</div>
															</div>
														</div>
														<div class="etn-row">
															<div class="etn-col-lg-6">
															<button type="button" class="etn-btn shortcode-generate-btn"><?php echo esc_html__('Generate', 'eventin'); ?></button>

															<?php if ( function_exists( 'wpeventin_pro' ) ): ?>
															<button type="button" class="etn-btn shortcode-script-btn"><?php echo esc_html__('Get Script', 'eventin'); ?></button>
															<input type="hidden" class="script-name" value="events-with-calendar">
															<?php endif; ?>
															</div>
														</div>
														<div class="attr-form-group etn-label-item copy_shortcodes">
																<div class="etn-meta">
																		<input type="text" readonly name="etn_event_label" id="events-calendar-shortcode" value="[events]" class="etn-setting-input etn_include_shortcode" placeholder="<?php esc_attr_e('Label Text', 'eventin'); ?>">
																		<button type="button" onclick="copyTextData('events-calendar-shortcode');" class="etn_copy_button etn-btn"><span class="dashicons dashicons-category"></span></button>
																</div>
														</div>
												</div>
										</div>
										<div class="attr-form-group etn-label-item">
												<div class="etn-label">
														<label><?php esc_html_e('Events With Calendar', 'eventin'); ?> </label>
														<div class="etn-desc"> <?php esc_html_e('Show "events in calendar view" in any specific location.', 'eventin'); ?> </div>
												</div>
												<div class="etn-meta">
														<button type="button" class="etn-btn s-generate-btn"><?php echo esc_html__('Generate Shortcode', 'eventin'); ?></button>
												</div>
										</div>
								</div>

								<!-- speakers start -->
								<div class="shortcode-generator-wrap">
										<div class="shortcode-generator-main-wrap">
												<div class="shortcode-generator-inner">
														<div class="shortcode-popup-close"><span>❌</span></div>

														<div class="etn-row">
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Select speakers', 'eventin'); ?></h3>
																				<select  class="get_template etn-setting-input">
																						<option value="speakers"> <?php echo esc_html__('Speakers', 'eventin'); ?> </option>
																				</select>
																		</div>
																</div>
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Select Style', 'eventin'); ?></h3>
																				<?php Helper::get_option_style(2,'style', 'speaker-', 'style ' ); ?>
																		</div>
																</div>
														</div>
														<div class="etn-row">
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Select Category', 'eventin'); ?></h3>
																				<?php
																				echo Helper::get_etn_taxonomy_ids('etn_speaker_category', 'cat_id');
																				?>
																		</div>
																</div>
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Column', 'eventin'); ?></h3>
																				<?php echo Helper::get_option_style(4, 'etn_speaker_col', '', 'Column '); ?>
																		</div>
																</div>
														</div>
														
														<div class="etn-row">
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Select Order', 'eventin'); ?></h3>
																				<?php echo Helper::get_order('order'); ?>
																		</div>
																</div>
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Select orderby', 'eventin'); ?></h3>
																						<?php
																						$orderby = [
																								"orderby='title'" => esc_html__('Title', 'eventin'),
																								"orderby='ID'" => esc_html__('ID', 'eventin'),
																								"orderby='post_date'" => esc_html__('Post Date', 'eventin'),
																								"orderby='name'" => esc_html__('Name', 'eventin'),
																						];
																				echo Helper::get_option_range($orderby);
																				?>
																		</div>
																</div>
														</div>
														<div class="etn-field-wrap">
																<h3><?php echo esc_html__('post Limit', 'eventin'); ?></h3>
																<input type="number" data-count = "<?php echo esc_attr('limit') ?>" class="post_count etn-setting-input" value="20">
														</div>
														<div class="etn-row">
															<div class="etn-col-lg-6">
															<button type="button" class="etn-btn shortcode-generate-btn"><?php echo esc_html__('Generate', 'eventin'); ?></button>
															<?php if ( function_exists( 'wpeventin_pro' ) ): ?>
															
															<button type="button" class="etn-btn shortcode-script-btn"><?php echo esc_html__('Get Script', 'eventin'); ?></button>
															<input type="hidden" class="script-name" value="speakers">
															<?php endif; ?>
															</div>
														</div>
												
												
														<div class="attr-form-group etn-label-item copy_shortcodes">
																<div class="etn-meta">
																		<input type="text" readonly name="etn_event_label" id="speakers-shortcode" value="[speakers]" class="etn-setting-input etn_include_shortcode etn_include_shortcode attr-form-control etn_include_shortcode" placeholder="<?php esc_attr_e('Label Text', 'eventin'); ?>">
																		<button type="button" onclick="copyTextData('speakers-shortcode');" class="etn_copy_button etn-btn"><span class="dashicons dashicons-category"></span></button>
																</div>
														</div>
												</div>
										</div>
										<div class="attr-form-group etn-label-item">
												<div class="etn-label">
														<label><?php esc_html_e('Speakers', 'eventin'); ?> </label>
														<div class="etn-desc"> <?php esc_html_e('Add "speakers profile" to show it in any specific location.', 'eventin'); ?> </div>
												</div>
												<div class="etn-meta">
														<button type="button" class="etn-btn s-generate-btn"><?php echo esc_html__('Generate Shortcode', 'eventin'); ?></button>
												</div>
										</div>
								</div>

								<!-- schedule start -->
								<div class="shortcode-generator-wrap">
										<div class="shortcode-generator-main-wrap">
												<div class="shortcode-generator-inner">
														<div class="shortcode-popup-close"><span>❌</span></div>
														<div class="etn-row">
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Select Schedule', 'eventin'); ?></h3>
																				<?php
																				$schedules = [
																						"schedules_list" => esc_html__('Schedules List', 'eventin'),
																						"schedules" => esc_html__('Schedule Tab', 'eventin'),
																				];
																				echo Helper::get_option_range($schedules, 'get_template get_schedule_template');
																				?>
																		</div>
																</div>
																<div class="etn-col-lg-6">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Select Order', 'eventin'); ?></h3>
																				<?php echo Helper::get_order('order'); ?>
																		</div>
																</div>
														</div>
														<div class="etn-row">
																<div class="etn-col-lg-12">
																		<div class="etn-field-wrap">
																				<h3><?php echo esc_html__('Select Schedule', 'eventin'); ?></h3>
																				<?php
																				echo Helper::get_posts_ids('etn-schedule', 'ids',' ');
																				?>
																		</div>
																</div>
												
														</div>
														<div class="etn-row">
															<div class="etn-col-lg-6">
															<button type="button" class="etn-btn shortcode-generate-btn"><?php echo esc_html__('Generate', 'eventin'); ?></button>
															<?php if ( function_exists( 'wpeventin_pro' ) ): ?>
															<button type="button" class="etn-btn shortcode-script-btn"><?php echo esc_html__('Get Script', 'eventin'); ?></button>
															<input type="hidden" class="script-name" value="schedules">
															<?php endif; ?>
															</div>
														</div>
												
														<div class="attr-form-group etn-label-item copy_shortcodes">
																<div class="etn-meta">
																		<input type="text" readonly name="etn_event_label" id="schedules-shortcode" value="[schedules]" class="etn-setting-input attr-form-control etn_include_shortcode" placeholder="<?php esc_attr_e('Label Text', 'eventin'); ?>">
																		<button type="button" onclick="copyTextData('schedules-shortcode');" class="etn_copy_button etn-btn"><span class="dashicons dashicons-category"></span></button>
																</div>
														</div>
												</div>
										</div>
										<div class="attr-form-group etn-label-item">
												<div class="etn-label">
														<label><?php esc_html_e( 'Schedules', 'eventin' ); ?> </label>
														<div class="etn-desc"> <?php esc_html_e('Use "schedules" to show it in any specific location.', 'eventin'); ?> </div>
												</div>
												<div class="etn-meta">
														<button type="button" class="etn-btn s-generate-btn"><?php echo esc_html__('Generate Shortcode', 'eventin'); ?></button>
												</div>
										</div>
								</div>


								<!-- advance search -->
								<div class="shortcode-generator-wrap">
										<div class="shortcode-generator-main-wrap">
												<div class="shortcode-generator-inner">
														<div class="shortcode-popup-close"><span>❌</span></div>

																<div class="etn-field-wrap">
																		<h3><?php echo esc_html__('Select Template', 'eventin'); ?></h3>
																		<select  class="get_template etn-setting-input">
																				<option value="event_search_filter"> <?php echo esc_html__('Advanced Search', 'eventin'); ?> </option>
																		</select>
																</div>
												
																<div class="etn-row">
																	<div class="etn-col-lg-6">
																	<button type="button" class="etn-btn shortcode-generate-btn"><?php echo esc_html__('Generate', 'eventin'); ?></button>
																	<?php if ( function_exists( 'wpeventin_pro' ) ): ?>
																	<button type="button" class="etn-btn shortcode-script-btn"><?php echo esc_html__('Get Script', 'eventin'); ?></button>
																	<input type="hidden" class="script-name" value="advanced-search-filter">
																	<?php endif; ?>
																	</div>
																</div>
												
												
														<div class="attr-form-group etn-label-item copy_shortcodes">
																<div class="etn-meta">
																		<input type="text" readonly name="etn_event_label" id="event_search_filter-shortcode" value="[event_search_filter]" class="etn-setting-input attr-form-control etn_include_shortcode" placeholder="<?php esc_attr_e('Label Text', 'eventin'); ?>">
																		<button type="button" onclick="copyTextData('event_search_filter-shortcode');" class="etn_copy_button etn-btn"><span class="dashicons dashicons-category"></span></button>
																</div>
														</div>
												</div>
										</div>
										<div class="attr-form-group etn-label-item">
												<div class="etn-label">
														<label><?php esc_html_e('Advanced Search Filter', 'eventin'); ?> </label>
														<div class="etn-desc"> <?php esc_html_e('Add the "advanced search filter option" in any specific location.', 'eventin'); ?> </div>
												</div>
												<div class="etn-meta">
												<button type="button" class="etn-btn s-generate-btn"><?php echo esc_html__('Generate Shortcode', 'eventin'); ?></button>
												</div>
										</div>
								</div>

								<?php
								$is_zoom_on = etn_get_option( 'etn_zoom_api' );
								if ( $is_zoom_on == 'on') {
										?>
										<!-- zoom meeting -->
										<div class="shortcode-generator-wrap">
											<div class="shortcode-generator-main-wrap">
												<div class="shortcode-generator-inner">
												<div class="shortcode-popup-close"><span>❌</span></div>

												<div class="etn-row">
													<div class="etn-col-lg-6">
														<div class="etn-field-wrap">
														<h3><?php echo esc_html__('Select Template', 'eventin'); ?></h3>
													<select  class="get_template etn-setting-input">
																								<option value="etn_zoom_api_link"> <?php echo esc_html__('Template 1', 'eventin'); ?> </option>
																						</select>
																				</div>
																		</div>
																		<div class="etn-col-lg-6">
																				<div class="etn-field-wrap">
																						<h3><?php echo esc_html__('link only?', 'eventin'); ?></h3>
																						<?php echo Helper::get_show_hide('link_only'); ?>
																				</div>
																		</div>
																</div>
																<div class="etn-row">
																		<div class="etn-col-lg-12">
																				<div class="etn-field-wrap">
																						<h3><?php echo esc_html__('Select Zoom', 'eventin'); ?></h3>
																						<?php
																						echo Helper::get_posts_ids('etn','meeting_id', ' ');
																						?>
																				</div>
																		</div>
														
																</div>
															<div class="etn-row">
																<div class="etn-col-lg-6">
																<button type="button" class="etn-btn shortcode-generate-btn"><?php echo esc_html__('Generate', 'eventin'); ?></button>
																<?php if ( function_exists( 'wpeventin_pro' ) ): ?>
																<button type="button" class="etn-btn shortcode-script-btn"><?php echo esc_html__('Get Script', 'eventin'); ?></button>
																<input type="hidden" class="script-name" value="zoom-meeting">
																<?php endif; ?>
																</div>
															</div>
														
														
																<div class="attr-form-group etn-label-item copy_shortcodes">
																		<div class="etn-meta">
																				<input type="text" readonly name="etn_event_label" id="etn_zoom_api_link-shortcode" value="[etn_zoom_api_link meeting_id ='123456789' link_only='no']" class="etn-setting-input attr-form-control etn_include_shortcode" placeholder="<?php esc_attr_e('Label Text', 'eventin'); ?>">
																				<button type="button" onclick="copyTextData('etn_zoom_api_link-shortcode');" class="etn_copy_button etn-btn"><span class="dashicons dashicons-category"></span></button>
																		</div>
																</div>
														</div>
												</div>
												<div class="attr-form-group etn-label-item">
														<div class="etn-label">
																<label><?php esc_html_e('Zoom Meeting', 'eventin'); ?> </label>
																<div class="etn-desc"> <?php esc_html_e("You can use generate shortcode", 'eventin'); ?> </div>
														</div>
														<div class="etn-meta">
														<button type="button" class="etn-btn s-generate-btn"><?php echo esc_html__('Generate Shortcode', 'eventin'); ?></button>

														</div>
												</div>
										</div>
										<?php
								}
								
								$shortcode_arr   =  apply_filters( 'eventin/shortcode/pro_shortcode', [] );
								if( is_array( $shortcode_arr ) && isset( $shortcode_arr['pro_shortcode'] )  && file_exists($shortcode_arr['pro_shortcode'])){
										include $shortcode_arr['pro_shortcode'];
								}
								?>

								<div class="shortcode-generator-wrap">
									<div class="shortcode-generator-main-wrap">
										<div class="shortcode-generator-inner">
											<div class="shortcode-popup-close">x</div>
											<div class="etn-row">
												<div class="etn-col-lg-6">
													<div class="etn-field-wrap">
														<h3><?php echo esc_html__('Select Event Meta Info', 'eventin'); ?></h3>
														<select  class="get_template  etn-setting-input">
															<option value="etn_event_meta_info"> <?php echo esc_html__('Event Meta Info', 'eventin'); ?> </option>
														</select>
													</div>
												</div>
												<div class="etn-col-lg-6">
													<div class="etn-field-wrap">
														<h3><?php echo esc_html__('Select Event', 'eventin'); ?></h3>
														<?php
														echo Helper::get_posts_ids('etn', 'event_id', ' ');
														?>
													</div>
												</div>
											</div>
										
											<div class="etn-row">
												<div class="etn-col-lg-6">
													<button type="button" class="etn-btn shortcode-generate-btn"><?php echo esc_html__('Generate', 'eventin'); ?></button>
												</div>
											</div>
											
											<div class="attr-form-group etn-label-item copy_shortcodes">
												<div class="etn-meta">
													<input type="text" readonly name="etn_event_label" id="etn_event_meta_info-shortcode" value="[etn_event_meta_info]" class="etn-setting-input etn_include_shortcode" placeholder="<?php esc_attr_e('Label Text', 'eventin'); ?>">
													<button type="button" onclick="copyTextData('etn_event_meta_info-shortcode');" class="etn_copy_button etn-btn"><span class="dashicons dashicons-category"></span></button>
												</div>
											</div>
										</div>
									</div>
									<div class="attr-form-group etn-label-item">
										<div class="etn-label">
											<label><?php esc_html_e('Event Meta Info', 'eventin'); ?> </label>
											<div class="etn-desc"> <?php esc_html_e('The "events meta info" is for the showing event meta details in widget.', 'eventin'); ?> </div>
										</div>
										<div class="etn-meta">
											<button type="button" class="etn-btn s-generate-btn"><?php echo esc_html__('Generate Shortcode', 'eventin'); ?></button>
										</div>
									</div>
								</div>

						</div>
				</div>
		</div>
	</div>
</div>
