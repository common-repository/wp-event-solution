<?php
use Etn\Utils\Helper as Helper;
global $post;
?>

<div class="etn-label-item etn-label-extra-field etn-label-top">
    <div class="etn-label">
        <label for="attendee_extra_field"
               class="etn-settings-label"><?php echo esc_html__('Extra Field', "eventin"); ?></label>
        <p class="etn-desc"> <?php echo esc_html__("Extra field will be added in attendee form", "eventin"); ?> </p>
    </div>
	<?php 
		if(!class_exists( 'Wpeventin_Pro' )){ 
			echo Helper::get_pro();
		} else {
	?>
    <div class="etn-meta">
        <div class="attendee_extra_main_block">
			<?php
			$input_type_array = [
				'text'     => esc_html__('Text', 'eventin'),
				'number'   => esc_html__('Number', 'eventin'),
				'date'     => esc_html__('Date', 'eventin'),
				'radio'    => esc_html__('Radio', 'eventin'),
				'checkbox' => esc_html__('Checkbox', 'eventin'),
			];

			$special_types = [
				'date',
				'radio',
				'checkbox',
			];

			$attendee_extra_fields = get_post_meta( $post->ID, 'attendee_extra_fields', true );

			$next_add_time_index = 1;
			if( is_array( $attendee_extra_fields ) && !empty( $attendee_extra_fields ) ) {
				$next_add_time_index = count($attendee_extra_fields);

				foreach($attendee_extra_fields as $index => $attendee_extra_field) {
					$selection = !empty($attendee_extra_field['etn_field_type']) ? $attendee_extra_field['etn_field_type'] : "";
					?>
                    <div class="etn-attendee-field attendee_block">
                        <select name='attendee_extra_fields[<?php echo esc_attr($index); ?>][etn_field_type]'>
                            <option value="optional" <?php echo esc_attr($selection == 'optional' ? 'selected' : ''); ?> > <?php echo esc_html__("Optional", "eventin") ?></option>
                            <option value="required" <?php echo esc_attr($selection == 'required' ? 'selected' : ''); ?>><?php echo esc_html__("Required", "eventin") ?></option>
                        </select>
                        <div class="attendee_extra_field_wrapper">
                            <input type="text" name="attendee_extra_fields[<?php echo esc_attr($index); ?>][label]"
								value="<?php echo esc_attr($attendee_extra_field['label']); ?>"
								id="attendee_extra_label_<?php echo esc_attr($index); ?>"
								class="attendee_extra_label mr-1 etn-settings-input etn-form-control"
								placeholder="<?php esc_html_e('Input Label', 'eventin'); ?>" <?php echo ($index != 0) ? 'required' : '' ?> />
                        </div>
						<select name="attendee_extra_fields[<?php echo esc_attr($index); ?>][type]"
							id="attendee_extra_type_<?php echo esc_attr($index); ?>"
							class="attendee_extra_type mr-1 etn-settings-input etn-form-control"
							data-current_extra_block_index="<?php echo esc_attr($index); ?>" <?php echo ($index != 0) ? 'required' : '' ?>>
							<option value="" selected><?php echo esc_html__('Select Input Type', 'eventin'); ?></option>
							<?php foreach($input_type_array as $key => $value) { ?>
										<option value="<?php echo esc_attr($key); ?>" <?php selected($attendee_extra_field['type'], $key, true) ?>>
										<?php echo esc_html($value); ?></option>
							<?php } ?>
						</select>
                        <div class="attendee_extra_field_wrapper">
                        <input type="text" name="attendee_extra_fields[<?php echo esc_attr($index); ?>][place_holder]"
                               value="<?php echo esc_attr($attendee_extra_field['place_holder']); ?>"
                               id="attendee_extra_placeholder_<?php echo esc_attr($index); ?>"
                               class="attendee_extra_placeholder mr-1 etn-settings-input etn-form-control"
                               style="display: <?php echo in_array($attendee_extra_field['type'], $special_types) ? 'none' : 'block'; ?>;"
                               placeholder="<?php esc_html_e('Input Placeholder', 'eventin'); ?>"/>
                        </div>

                        <!-- attendee extra type radio section -->
						<?php
							if($attendee_extra_field['type'] == 'radio' && isset($attendee_extra_field['radio'])) {
								if(is_array($attendee_extra_field['radio']) && count($attendee_extra_field['radio']) >= 2) {

									$next_add_time_radio_index = count($attendee_extra_field['radio']);
									?>

									<div class="attendee_extra_type_radio_main_block">
											<div class="attendee_extra_type_radio_note"
													style="display: none;"><?php echo esc_html__('Note: At least 2 radio label are required for working. 1st two are mandatory.', "eventin"); ?></div>

										<?php
										// generating each radio field.
										foreach($attendee_extra_field['radio'] as $radio_index => $radio_val) {
											?>
											<div class="etn-attendee-field attendee_extra_type_radio_block mb-2">
												<input type="text"
															name="attendee_extra_fields[<?php echo esc_attr($index); ?>][radio][<?php echo esc_attr($radio_index); ?>]"
															value="<?php esc_html_e($radio_val, 'eventin') ?>"
															id="attendee_extra_type_<?php echo esc_attr($index); ?>_radio_<?php echo esc_attr($radio_index); ?>"
															class="attendee_extra_type_radio mr-1 etn-settings-input etn-form-control <?php echo ($radio_index <= 1) ? 'attendee_extra_type_radio_' . esc_attr($radio_index) : ''; ?>"
															placeholder="<?php esc_html_e('Radio text', 'eventin'); ?>" />

												<?php
												if($radio_index > 1) {
													?>
														<span class="dashicons etn-btn dashicons dashicons-no-alt remove_attendee_extra_type_radio_field pl-1"></span>
													<?php
												}
												?>
											</div>
											<?php
										}
										?>

										<!-- add more radio portion -->
										<div class="etn_flex_reverse attendee_extra_type_radio_section">
											<span class="add_attendee_extra_type_radio_block etn-btn-text"
													data-radio_placeholder_text="<?php echo esc_html__("Radio text", "eventin"); ?>"
													data-next_add_time_radio_parent_index="<?php echo esc_attr($index); ?>"
													data-next_add_time_radio_index="<?php echo esc_attr($next_add_time_radio_index); ?>">
													<?php echo esc_html__('Add Radio', 'eventin'); ?>
											</span>
										</div>
								</div>
									<?php

								}
							}
						?>

                        <!-- attendee extra type checkbox section -->
						<?php

						if($attendee_extra_field['type'] == 'checkbox' && isset($attendee_extra_field['checkbox'])) {
							if(is_array($attendee_extra_field['checkbox']) && count($attendee_extra_field['checkbox']) >= 1) {

								$next_add_time_checkbox_index = count($attendee_extra_field['checkbox']);
								?>
								<div class="attendee_extra_type_checkbox_main_block">
										<?php
										// generating each checkbox field.
										foreach($attendee_extra_field['checkbox'] as $checkbox_index => $checkbox_val) {
											?>
											<div class="etn-attendee-field attendee_extra_type_checkbox_block mb-2">
												<input type="text"
													name="attendee_extra_fields[<?php echo esc_attr($index); ?>][checkbox][<?php echo esc_attr($checkbox_index); ?>]"
													value="<?php esc_html_e($checkbox_val, 'eventin') ?>"
													id="attendee_extra_type_<?php echo esc_attr($index); ?>_checkbox_<?php echo esc_attr($checkbox_index); ?>"
													class="attendee_extra_type_checkbox mr-1 etn-settings-input etn-form-control"
													placeholder="<?php esc_html_e('Checkbox text', 'eventin'); ?>" <?php echo ($index != 0) ? 'required1' : '' ?> />

												<?php
												if($checkbox_index > 0) {
													?>
													<span class="dashicons etn-btn dashicons dashicons-no-alt remove_attendee_extra_type_checkbox_field pl-1"></span>
													<?php
												}
												?>
											</div>
											<?php
										}
										?>

										<!-- add more checkbox portion -->
										<div class="etn_flex_reverse attendee_extra_type_checkbox_section">
												<span class="add_attendee_extra_type_checkbox_block etn-btn-text"
													data-checkbox_placeholder_text="<?php echo esc_html__("Checkbox text", "eventin"); ?>"
													data-next_add_time_checkbox_parent_index="<?php echo esc_attr($index); ?>"
													data-next_add_time_checkbox_index="<?php echo esc_attr($next_add_time_checkbox_index); ?>">
													<?php echo esc_html__('Add Checkbox', 'eventin'); ?>
												</span>
										</div>
								</div>
								<?php
							}
						}
						?>
						<?php
						if($index != 0) {
							?>
                            <span class="dashicons etn-btn dashicons dashicons-no-alt remove_attendee_extra_field pl-1"></span>
							<?php
						}
						?>
                    </div>
					<?php
				}
			}
			?>
        </div>

        <div class="etn_flex_reverse attendee_extra_section">
            <span class="add_attendee_extra_block etn-btn-text"
                  data-label_text="<?php echo esc_html__("Label text", "eventin"); ?>"
                  data-placeholder_text="<?php echo esc_html__("Placeholder text", "eventin"); ?>"
                  data-select_input_type_text="<?php echo esc_html__("Select Input Type", "eventin"); ?>"
                  data-input_type_text="<?php echo esc_html__("Text", "eventin"); ?>"
                  data-input_type_number="<?php echo esc_html__('Number', "eventin"); ?>"
                  data-input_type_date="<?php echo esc_html__('Date', "eventin"); ?>"
                  data-input_type_radio="<?php echo esc_html__('Radio', "eventin"); ?>"
                  data-input_type_checkbox="<?php echo esc_html__('Checkbox', "eventin"); ?>"
                  data-radio_placeholder_text="<?php echo esc_html__('Radio text', "eventin"); ?>"
                  data-radio_add_btn_text="<?php echo esc_html__('Add Radio', "eventin"); ?>"
                  data-radio_note="<?php echo esc_html__('Note: At least 2 radio label are required for working. 1st two are mandatory.', "eventin"); ?>"
                  data-checkbox_placeholder_text="<?php echo esc_html__('Checkbox text', "eventin"); ?>"
                  data-checkbox_add_btn_text="<?php echo esc_html__(' Add Checkbox', "eventin"); ?>"
                  data-show_in_dashboard_text="<?php echo esc_html__('Show in Attendee Dashboard', "eventin"); ?>"
				  data-attendee-extra-scope="etn-extra-field-single-event"
                  data-next_add_time_index="<?php echo esc_attr($next_add_time_index); ?>">

                <?php echo esc_html__('Add Field', 'eventin'); ?>
            </span>
        </div>
    </div>
	<?php } ?>
</div>
