<?php
use Etn\Utils\Helper;


if ( is_array($attendee_extra_fields) && !empty($attendee_extra_fields) ){
    foreach( $attendee_extra_fields as $index => $attendee_extra_field ){
        
        $label_content  = $attendee_extra_field['label'];
        $etn_field_type = '';
        $required_span  = '';
        if ( !empty($attendee_extra_field['required'])   ) {
            $etn_field_type = 'required';
            $required_span  = '<span class="etn-input-field-required">*</span>';
        }

        $field_type = ! empty( $attendee_extra_field['field_type'] ) ? $attendee_extra_field['field_type'] : '';
        $field_type = ! empty( $attendee_extra_field['type'] ) ? $attendee_extra_field['type'] : '';

        if ( ! empty( $attendee_extra_field['field_type'] ) ) {
            $field_type = $attendee_extra_field['field_type'];
        } elseif( ! empty( $attendee_extra_field['type'] ) ) {
            $field_type = $attendee_extra_field['type'];
        }
        
        if( !empty($label_content) && $field_type ){
            $name_from_label       = \Etn\Utils\Helper::generate_name_from_label( "etn_attendee_extra_field_" , $label_content);
            $class_name_from_label = \Etn\Utils\Helper::get_name_structure_from_label($label_content);
            ?>

            <div class="etn-<?php echo esc_attr( $class_name_from_label ); ?>-field etn-group-field">
                <label for="etn_attendee_extra_field_<?php echo esc_attr( $key ) . "_attendee_" . intval( $i ) ?>">
                    <?php echo esc_html( $label_content );  echo  Helper::kses( $required_span ) ?>
                </label>

                <?php
                    if( $field_type == 'radio' ) {
                        $radio_arr = isset( $attendee_extra_field['field_options'] ) ? $attendee_extra_field['field_options'] : [];


                        if( is_array($radio_arr) && !empty($radio_arr) ) {
                            $special_radio_index = $key .'_'. ( $i-1 );
                            if ( !in_array( $special_radio_index, $radio_generated_indexes ) ) {
                                $radio_generated_indexes[] = $special_radio_index;
                                ?>
                                <input type="hidden" name="radio_track_index[]" value="<?php echo esc_attr( $special_radio_index );?>"/>
                                <?php
                            }
                            ?>
                            <div class="etn-radio-field-wrap">
                            <?php
                            foreach( $radio_arr as $radio_index => $radio_val ) {
                                $id = 'etn_attendee_extra_field_'.$key.'_attendee_'.$i.'_input_'.$index.'_radio_'.$radio_index.'';
                                $radio_input_value = is_array( $radio_val ) ? $radio_val['value'] : $radio_val;
                                ?>
                                <div class="etn-radio-field">
                                    <input type="radio" name="<?php echo esc_attr( $name_from_label ) . '_' . $key .'_'. ( $i-1 ); ?>[]" value="<?php echo esc_attr( $radio_index ); ?>"
                                        class="etn-attendee-extra-fields" id="<?php echo esc_attr( $id );?>" data-etn_required="<?php echo esc_attr($etn_field_type);?>" <?php echo esc_attr( $etn_field_type ); ?> />
                                    <label for="<?php echo esc_attr( $id );?>"><?php echo esc_html( $radio_input_value ); ?></label>
                                </div>
                                <?php
                            }
                            ?>
                            <div class="etn-error <?php echo esc_attr( 'etn_attendee_extra_field_'.$key.'_attendee_'.$i.'_input_'.$index ); ?>"></div>
                            </div>
                            <?php
                        }

                    } else if( $field_type == 'checkbox' ){
                        $checkbox_arr = isset( $attendee_extra_field['field_options'] ) ? $attendee_extra_field['field_options'] : [];

                        if( is_array( $checkbox_arr ) && ! empty( $checkbox_arr ) ) {
                            $special_checkbox_index = $key .'_'. ( $i-1 );
                            if ( !in_array( $special_checkbox_index, $checkbox_generated_indexes ) ) {
                                $checkbox_generated_indexes[] = $special_checkbox_index;
                                ?>
                                <input type="hidden" name="checkbox_track_index[]" value="<?php echo esc_attr( $special_checkbox_index );?>" />
                                <?php
                            }
                            ?>
                            <div class="etn-checkbox-field-wrap">
                                <?php
                                    foreach( $checkbox_arr as $checkbox_index => $checkbox_val ) {
                                        $id = 'etn_attendee_extra_field_'.$key.'_attendee_'.$i.'_input_'.$index.'_checkbox_'.$checkbox_index.'';
                                        $checbox_input_value = is_array($checkbox_val) ? $checkbox_val['value'] : $checkbox_val;
                                        ?>
                                            <div class="etn-checkbox-field">
                                                <input type="checkbox" name="<?php echo esc_attr( $name_from_label ) . '_' . $key .'_'. ( $i-1 ); ?>[]" value="<?php echo esc_attr( strtolower($checbox_input_value) ); ?>"
                                                    class="etn-attendee-extra-fields" id="<?php esc_attr_e( $id, 'eventin' );?>" data-etn_required="<?php esc_attr_e($etn_field_type, 'eventin');?>" />
                                                <label for="<?php esc_attr_e( $id, 'eventin' );?>"><?php echo html_entity_decode( $checbox_input_value );?></label>
                                            </div>
                                        <?php
                                    }
                                ?>
                                <div class="etn-error <?php echo esc_attr( 'etn_attendee_extra_field_'.$key.'_attendee_'.$i.'_input_'.$index ); ?>"></div>
                            </div>
                            <?php
                        }
                    } else if ( $field_type == 'select' ) {

                        $select_box_arr = isset( $attendee_extra_field['field_options'] ) ? $attendee_extra_field['field_options'] : [];

                        if( is_array( $select_box_arr ) && ! empty( $select_box_arr ) ) {
                            $special_checkbox_index = $key .'_'. ( $i-1 );
                            if ( !in_array( $special_checkbox_index, $checkbox_generated_indexes ) ) {
                                $checkbox_generated_indexes[] = $special_checkbox_index;
                                ?>
                                <input type="hidden" name="checkbox_track_index[]" value="<?php echo esc_attr( $special_checkbox_index );?>" />
                                <?php
                            }
                            ?>
                            <div class="etn-checkbox-field-wrap">
                                <select name="<?php echo esc_attr( $name_from_label ); ?>[]" id="" class="attr-form-control etn-attendee-extra-fields">
                                <?php
                                    foreach( $select_box_arr as $checkbox_index => $select_box_value ) {
                                        ?>
                                        <option value="<?php echo esc_attr( $select_box_value['value'] ) ?>"><?php echo esc_html( $select_box_value['value'] ) ?></option>
                                        <?php
                                    }
                                ?>
                                </select>
                                <div class="etn-error <?php echo esc_attr( 'etn_attendee_extra_field_'.$key.'_attendee_'.$i.'_input_'.$index ); ?>"></div>
                            </div>
                            <?php
                        }

                    } else {
                        $id = 'etn_attendee_extra_field_'.$key.'_attendee_'.$i.'_input_'.$index.'';
                        ?>
                        <input type="<?php echo esc_html( $field_type ); ?>"
                            name="<?php echo esc_attr( $name_from_label ); ?>[]"
                            class="attr-form-control etn-attendee-extra-fields"
                            id="<?php echo esc_attr($id); ?>"
                            placeholder="<?php echo !empty($attendee_extra_field['place_holder']) ? esc_attr( $attendee_extra_field['place_holder'] ) : ''; ?>"
                            <?php echo ($field_type == 'number') ? "pattern='\d+'" : ''; ?> <?php echo esc_attr( $etn_field_type ); ?> />
                        <?php
                    }
                ?>

                <div class="etn-error <?php echo esc_attr($id); ?>"></div>
            </div>
            <?php
        } else { ?>
            <p class="error-text"><?php echo esc_html__( 'Please Select input type & label name from admin', 'eventin' ); ?></p>
        <?php
        }

    }
}