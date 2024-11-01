<?php

namespace Etn\Core\Metaboxs;

use Etn\Core\Metaboxs\Event_manager_repeater_metabox as Event_manager_repeater_metabox;
use Etn\Utils\Helper as Helper;
use Exception;
use WP_Error;

defined( 'ABSPATH' ) || exit;

abstract class Event_manager_metabox extends Event_manager_repeater_metabox {

    /**
     *  Meta data display type
     */
    public function get_display_type( $post_id ){
        $display_type        = "list";
        $meta_field_function = $this->get_meta_field_function_name_by_post_id( $post_id );
        $instance            = $this->get_cpt_instance( $post_id );
        if ( !empty( $instance->$meta_field_function()['display'] ) ) {
            $display_type = $instance->$meta_field_function()['display'];
        }

        return $display_type;
    }

	public function dis_allow_cpt() {
		return array( "shop_order","etn-stripe-order","shop_order_placehold" );
	}

    public function display_callback( $post ) {
		if ( in_array($post->type,$this->dis_allow_cpt()) ) {
			return;
		}
        $meta_field_function_name = $this->get_meta_field_function_name_by_post_id( $post->ID );
        $display_type             = $this->get_display_type( $post->ID );

        if ( "tab" == $display_type ) {
            // tab
            ?>
            <div class="etn-tab-wrapper">
                <ul class="nav-tab-wrapper etn-tab">
                    <?php
                        foreach ( $this->$meta_field_function_name()['tab_items'] as $key => $item ) {
                            $active_class = $key == 0 ? 'attr-active' : '';
                            ?>
                            <li class="<?php echo esc_attr( $active_class ); ?>">
                                <a href="#<?php echo esc_attr( $item['id'] );?>" class="etn-nav-tab"  data-id="<?php echo esc_attr( $item['id'] );?>">
                                    <?php esc_html_e($item['name'], 'eventin');?>
                                    <?php echo \Etn\Utils\Helper::render($item['icon']);?>
                                </a>
                            </li>
                            <?php
                        }
                    ?>
                </ul>
                <div class="attr-tab-content">
                    <?php 
                    foreach ( $this->$meta_field_function_name()['tab_items'] as $i => $tab_item ) { 
                        $active_class = $i == 0 ? 'attr-active' : '';
                        ?>
                        <div class="etn-settings-section attr-tab-pane <?php echo esc_attr( $active_class ); ?>" data-id="<?php echo esc_attr($tab_item['id'] );?>" id="<?php echo esc_attr( $tab_item['id'] );?>">
                            <?php
                                foreach( $this->$meta_field_function_name()['fields'] as $key => $item ) {
                                    if(isset($item['attr']['tab']) && $item['attr']['tab'] == $tab_item['id'] ){
                                        $this->get_markup( $item, $key );
                                    }
                                }
                            ?>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <?php
        } else {
            // list
            $group_array = array();$un_group_array = array();
            foreach ($this->$meta_field_function_name() as $key => $element ) {
                if ( !empty($element['group']) ) {
                    $group_array[$element['group']][$key] = $element;
                }else {
                    $un_group_array[$key] = $element;
                }
            }
            // group
            foreach ( $group_array as $group_name => $item ) {
                ?>
                    <div class="<?php echo esc_attr( $group_name );?>">
                        <?php
                            foreach ( $item as $key => $value ) {
                                $this->get_markup( $value, $key );
                            }
                        ?>
                    </div>
                <?php
            }
            //un-group
            foreach ($un_group_array as $key => $element ) {
                $this->get_markup( $element, $key );
            }
        }

        wp_nonce_field( 'etn_event_data', 'etn_event_n_fields' );

    }

    /**
     * Get instance
     */
    public function get_cpt_instance( $post_id ){
        $instance = null;
		
        switch ( get_post_type( $post_id ) ) {
            case 'etn':
				if( class_exists('Wpeventin_Pro') && !empty($this->report_box_id) &&  'etn-rsvp' == $this->report_box_id){
					$instance = new \Etn_Pro\Core\Modules\Rsvp\Admin\Metaboxs\Metabox();
				}else{
					$instance = new \Etn\Core\Metaboxs\Event_meta();
				}
                break;
            case 'etn-speaker':
                $instance = new \Etn\Core\Metaboxs\Speaker_meta();
                break;
            case 'etn-schedule':
                $instance = new \Etn\Core\Metaboxs\Schedule_meta();
                break;
            case 'etn-attendee':
                $instance = new \Etn\Core\Metaboxs\Attendee_Meta();
                break;
        }
       
        return $instance;
    }

    /**
     * Undocumented function
     *
     * @param [type] $post_id
     * @return void
     */
    private function get_meta_field_function_name_by_post_id( $post_id ) {

        $post_type                = get_post_type( $post_id );
        $post_type                = str_replace( '-', '_', $post_type );
        $meta_field_function_name = $post_type . '_meta_fields';
         return $meta_field_function_name;
    }

    public function save_meta_box_data( $post_id ) {
		if ( in_array(get_post_type($post_id),$this->dis_allow_cpt()) ) {
			return;
		}
		
        $post_arr = Helper::render( $_POST );

        if ( !Helper::is_secured( 'etn_event_n_fields', 'etn_event_data', $post_id, $post_arr ) ) {
            return $post_id;
        }
        $instance = $this->get_cpt_instance( $post_id );
        try {
            $meta_field_function_name = $this->get_meta_field_function_name_by_post_id( $post_id );
            $display_type             = $this->get_display_type( $post_id );

            if ( "tab" == $display_type ) {
                $meta_fields = $instance->$meta_field_function_name()['fields'];
            }else {
                $meta_fields = $instance->$meta_field_function_name();
            }
            
            $this->update( $meta_fields, $post_arr );

        } catch ( Exception $e ) {
            $error = new WP_Error( $e->getCode(), $e->getMessage() );
        }

    }

    protected function update( $fields = null, $post = []) {

        if ( !is_array( $fields ) || !count( $fields ) ) {
            throw new Exception( esc_html__( "Meta data field not found", 'eventin' ) );
        }
        
        foreach ( $fields as $field_key => $field ) {

            $field_type = ! empty( $field['type'] ) ? $field['type'] : '';

            if($field_type == 'recurrence_block'){
                if ( isset( $post[$field_key] ) ) {

                    $recurrence_data = !empty($post[$field_key]) ? $post[$field_key] : '';

                    if ( is_array( $recurrence_data ) && !empty( $recurrence_data['recurrence_freq'] ) ) {
                        switch($recurrence_data['recurrence_freq']){
                            case 'day':
                                unset($recurrence_data['recurrence_weekly_day']);
                                unset($recurrence_data['recurrence_monthly_date']);
                                unset($recurrence_data['recurrence_yearly_month']);
                                unset($recurrence_data['recurrence_yearly_date']);
                                unset($recurrence_data['recurrence_monthly_advanced_interval']);
                                unset($recurrence_data['recurrence_monthly_advanced_week_no']);
                                unset($recurrence_data['recurrence_monthly_advanced_weekday_no']);
                                break;
                            case 'week':
                                unset($recurrence_data['recurrence_day']);
                                unset($recurrence_data['recurrence_monthly_date']);
                                unset($recurrence_data['recurrence_yearly_month']);
                                unset($recurrence_data['recurrence_yearly_date']);
                                unset($recurrence_data['recurrence_monthly_advanced_interval']);
                                unset($recurrence_data['recurrence_monthly_advanced_week_no']);
                                unset($recurrence_data['recurrence_monthly_advanced_weekday_no']);
                                break;
                            case 'month':
                                unset($recurrence_data['recurrence_day']);
                                unset($recurrence_data['recurrence_weekly_day']);
                                unset($recurrence_data['recurrence_yearly_month']);
                                unset($recurrence_data['recurrence_yearly_date']);
                                unset($recurrence_data['recurrence_monthly_advanced_interval']);
                                unset($recurrence_data['recurrence_monthly_advanced_week_no']);
                                unset($recurrence_data['recurrence_monthly_advanced_weekday_no']);
                                break;
                            case 'year':
                                unset($recurrence_data['recurrence_day']);
                                unset($recurrence_data['recurrence_weekly_day']);
                                unset($recurrence_data['recurrence_monthly_date']);
                                unset($recurrence_data['recurrence_monthly_advanced_interval']);
                                unset($recurrence_data['recurrence_monthly_advanced_week_no']);
                                unset($recurrence_data['recurrence_monthly_advanced_weekday_no']);
                                break;
                            case 'month-advanced':
                                unset($recurrence_data['recurrence_day']);
                                unset($recurrence_data['recurrence_weekly_day']);
                                unset($recurrence_data['recurrence_monthly_date']);
                                unset($recurrence_data['recurrence_yearly_month']);
                                unset($recurrence_data['recurrence_yearly_date']);
                                break;
                            default:
                                unset($recurrence_data['recurrence_day']);
                                unset($recurrence_data['recurrence_weekly_day']);
                                unset($recurrence_data['recurrence_monthly_date']);
                                unset($recurrence_data['recurrence_yearly_month']);
                                unset($recurrence_data['recurrence_yearly_date']);
                                unset($recurrence_data['recurrence_monthly_advanced_interval']);
                                unset($recurrence_data['recurrence_monthly_advanced_week_no']);
                                unset($recurrence_data['recurrence_monthly_advanced_weekday_no']);
                        }
                        update_post_meta( get_the_ID(), $field_key, $recurrence_data );
                    }

                } else {
                    update_post_meta( get_the_ID(), $field_key, '' );
                }
            } elseif ( $field_type == 'radio' || $field_type == 'select2' ) {
                if ( isset( $post[$field_key] ) ) {
                	// for event location update taxonomy term
					if ( 'etn_event_location_list' == $field_key ) {
						if( taxonomy_exists('etn_location') && !empty( $post[$field_key]) ) {
							wp_set_object_terms( get_the_ID() , $post[$field_key]  , 'etn_location' );
						}
					}

                    $upload_key = isset( $post[$field_key] ) ? $post[$field_key] : '';
                    $rv         = $upload_key;
                    update_post_meta( get_the_ID(), $field_key, $rv );

                    if ( $field_key == "_tax_status" ) {
                        if ( $post[$field_key] == 'taxable' ) {
                            update_post_meta( get_the_ID(), "_tax_class", "standard" );
                        } else {
                            delete_post_meta( get_the_ID(), "_tax_class" );
                        }
                    }
                } else {
                    update_post_meta( get_the_ID(), $field_key, '' );
                }

            } elseif ( $field_type == 'upload' ) {

                if ( isset( $post[$field_key] ) ) {
                    $upload_key = isset( $post[$field_key] ) ? sanitize_text_field( $post[$field_key] ) : '';
                    update_post_meta( get_the_ID(), $field_key, $upload_key );
                }

            } elseif ( $field_type == 'multi_checkbox' ) {
                $selected_values = isset( $post[$field_key] ) ? maybe_serialize( $post[$field_key] ) : '';
                update_post_meta( get_the_ID(), $field_key, $selected_values );
            } elseif ( $field_type == 'wp_editor' ) {

                if ( isset( $post[$field_key] ) ) {
                    $upload_key = isset( $post[$field_key] ) ? stripslashes( $post[$field_key] ) : '';
                    update_post_meta( get_the_ID(), $field_key, $upload_key );
                }

            } elseif ( $field_type == 'social_reapeater' ) {

                if ( isset( $post[$field_key] ) ) {
                    $social_key = isset( $post[$field_key] ) ? $post[$field_key] : '';

                    if ( is_array( $social_key ) ) {

                        if ( count( $social_key ) == 1 ) {

                            if ( $social_key[0]['icon'] == '' ) {
                                update_post_meta( get_the_ID(), $field_key, "" );
                            } else {
                                update_post_meta( get_the_ID(), $field_key, $social_key );
                            }

                        } else {
                            update_post_meta( get_the_ID(), $field_key, $social_key );
                        }

                    }

                } else {
                    update_post_meta( get_the_ID(), $field_key, '' );
                }

            } elseif ( $field_type == 'repeater' ) {
                if ( isset( $post[$field_key] ) ) {

                    $etn_rep_key = isset( $post[$field_key] ) ? $post[$field_key] : '';
                    if ( $field_key == "etn_ticket_variations" && is_array( $etn_rep_key ) ) {
                        // This block is responsible for creating multiple ticket slug

                        $ticket_variations_info    = Helper::get_ticket_variations_info( get_the_ID(), $etn_rep_key );
                        $etn_rep_key               = $ticket_variations_info['ticket_variations'];
                        $etn_total_created_tickets = $ticket_variations_info['etn_total_created_tickets'];

                        update_post_meta( get_the_ID(), "etn_total_avaiilable_tickets", $etn_total_created_tickets );

                        update_post_meta( get_the_ID(), "_price", 0 );
                        update_post_meta( get_the_ID(), "_regular_price", 0 );
                        update_post_meta( get_the_ID(), "_sale_price", 0 );
                        update_post_meta( get_the_ID(), "_stock", 0 );
                    }

                    if ( is_array( $etn_rep_key ) ) {

                        if ( count( $etn_rep_key ) == 1 ) {
                            //only one item in repeater field
                            if ( strlen( trim( join( "", Helper::array_flatten( $etn_rep_key[0] ) ) ) ) == 0 ) {
                                update_post_meta( get_the_ID(), $field_key, "" );
                            } else {
                                update_post_meta( get_the_ID(), $field_key, $etn_rep_key );
                            }
                        } else {
                            // multiple items in repeater field. sort repeater data and update value
                            if ( !empty( $_POST['etn_schedule_sorting'] )){
                                \Etn\Utils\Helper::sort_schedule_items( get_the_ID(), $etn_rep_key );
                            } else {
                                update_post_meta( get_the_ID(), $field_key, $etn_rep_key );
                            }
                        }
                    }
                } else {
                    update_post_meta( get_the_ID(), $field_key, '' );
                }

            } elseif ( $field_type == 'email' ) {

                if ( isset( $post[$field_key] ) ) {
                    $email_value = isset( $post[$field_key] ) ? sanitize_email( $post[$field_key] ) : '';
                    update_post_meta( get_the_ID(), $field_key, $email_value );
                }

            } elseif ( $field_type == 'date_range_picker'){
                if ( isset( $post['etn_start_date'] ) ) {
                    $date_array = explode(" to ", $post['etn_start_date']);
                    $start_date = isset( $date_array[0] ) ? $date_array[0] : '';
                    $end_date = isset( $date_array[1] ) ? $date_array[1] : '';
                    update_post_meta( get_the_ID(), 'etn_start_date', $start_date );
                    update_post_meta( get_the_ID(), 'etn_end_date', $end_date );
                }
            } else {
                if ( isset( $post[$field_key] ) ) {
                    $text_value = isset( $post[$field_key] ) ? $post[$field_key] : '';
                    update_post_meta( get_the_ID(), $field_key, $text_value );

                    // add the event ticket price as an extra meta so woocommerce extensions can use it
                    if ( "etn_ticket_price" === $field_key ) {
                        update_post_meta( get_the_ID(), "_price", $text_value );
                        update_post_meta( get_the_ID(), "_regular_price", $text_value );
                        update_post_meta( get_the_ID(), "_sale_price", $text_value );
                    }
                }

            }
        }

        // Attendee Extra Fields.
        $attendee_extra_fields = ! empty( $post['attendee_extra_fields'] ) ? $post['attendee_extra_fields'] : [];

        update_post_meta( get_the_ID(), 'attendee_extra_fields', $attendee_extra_fields );
    }

    protected function get_markup( $item = null, $key = '' ) {

        if ( is_null( $item ) ) {
            return;
        }

        if ( isset( $item['type'] ) ) {
            switch ( $item['type'] ) {
                case "text":
                    return $this->get_textinput( $item, $key );
                    break;
                case "number":
                    return $this->get_number_input( $item, $key );
                    break;
                case "date":
                    return $this->get_textinput( $item, $key );
                    break;
                case "time":
                    return $this->get_textinput( $item, $key );
                    break;
                case "textarea":
                    return $this->get_textarea( $item, $key );
                    break;
                case "url":
                    return $this->get_url_input( $item, $key );
                    break;
                case "email":
                    return $this->get_email_input( $item, $key );
                    break;
                case "radio":
                    return $this->get_radio_input( $item, $key );
                    break;
                case "select2":
                    return $this->get_select2( $item, $key );
                    break;
                case "select_single":
                    return $this->get_select_single( $item, $key );
                    break;
                case "upload":
                    return $this->get_upload( $item, $key );
                    break;
                case "wp_editor":
                    return $this->get_wp_editor( $item, $key );
                    break;
                case "map":
                    return $this->get_wp_map( $item, $key );
                    break;
                case "social_reapeater":
                    return $this->get_wp_social_repeater( $item, $key );
                    break;
                case "repeater":
                    return $this->get_wp_repeater( $item, $key );
                    break;
                case "heading":
                    return $this->get_heading( $item, $key );
                    break;
                case "separator":
                    return $this->get_separator( $item, $key );
                    break;
                case "checkbox":
                    return $this->get_checkbox( $item, $key );
                case "multi_checkbox":
                    return $this->get_multi_checkbox( $item, $key );
                    break;
                case "color_picker":
                    return $this->get_color_picker( $item, $key );
                    break;
                case "hidden":
                    return $this->get_hidden_input( $item, $key );
                    break;
                case "timezone":
                    return $this->get_timezone_input( $item, $key );
                    break;
                case "recurrence_block":
                    return $this->get_recurrence_block_input( $item, $key );
                    break;
                case "date_range_picker":
                    return $this->get_date_range_picker_block_input( $item, $key );
                    break;
                case "button":
                    return $this->get_button( $item, $key );
				case "markup":
					return $this->get_desc_markup( $item, $key );
                default:
                    return;
            }
        }
        return;
    }

	public function get_desc_markup( $item, $key ){
		$class = $key;
		$text = '';
        if ( isset( $item['attr'] ) ) {
            $class = !empty( $item['attr']['class'] ) ? $item['attr']['class'] . ' etn_event_meta_field' : 'etn_event_meta_field';
        }
		if ( isset( $item['text'] ) ) {
            $text = !empty( $item['text'] ) ? $item['text'] : '';
        }

        $file = ! empty( $item['file'] ) ? $item['file'] : '';
        if ( file_exists( $file ) ) {
            include_once $file;
        }

        ?>
        <?php if ( $text ): ?>
        <div class="<?php echo esc_html( $class ); ?>">
			<div class='etn-meta'>
            <?php echo Helper::kses(__( $text, 'eventin' ),'eventin'); ?>
            </div>
        </div>
        <?php endif; ?>
        <?php
    }

    public function get_date_range_picker_block_input( $item, $key ){
        $class          = $key;
        $icon           = '';
        $placeholder    = '';
        $readonly       = ( !empty( $item['readonly'] ) ) ? 'readonly' : "";
        $disabled       = ( !empty( $item['disabled'] ) ) ? 'disabled' : "";
        if ( isset( $item['attr'] ) ) {
            $class = !empty( $item['attr']['class'] ) ? $item['attr']['class'] . ' etn_event_meta_field' : 'etn_event_meta_field';
            $icon  = !empty( $item['attr']['icon'] )  ? $item['attr']['icon']  : '';
        }

        $start_date = !empty(  get_post_meta( get_the_ID(), 'etn_start_date', true ) ) ?   get_post_meta( get_the_ID(), 'etn_start_date', true ) : '';
        $end_date   = !empty(  get_post_meta( get_the_ID(), 'etn_end_date', true ) ) ?   get_post_meta( get_the_ID(), 'etn_end_date', true ) : '';
        $date_formats        = Helper::get_option("date_format");
        $date_options        = Helper::get_date_formats(); 
        $get_date_format     = ! empty( $date_formats ) ? $date_options[$date_formats] : get_option("date_format");
        $saved_val  = !empty($start_date) ?  sprintf( esc_attr__( '%1$s to %2$s', 'eventin' ), $start_date, $end_date ) : '';
        ?>
        <div class="<?php echo esc_html( $class ); ?>">
            <div class="etn-label">
                <label for="<?php echo esc_html( $key ); ?>">
                    <?php echo esc_html( $item['label'] ); ?>
                    <?php if( !empty($item['tooltip_title'])): ?>
                        <span class="tooltip-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M506.3 417l-213.3-364c-16.33-28-57.54-28-73.98 0l-213.2 364C-10.59 444.9 9.849 480 42.74 480h426.6C502.1 480 522.6 445 506.3 417zM232 168c0-13.25 10.75-24 24-24S280 154.8 280 168v128c0 13.25-10.75 24-23.1 24S232 309.3 232 296V168zM256 416c-17.36 0-31.44-14.08-31.44-31.44c0-17.36 14.07-31.44 31.44-31.44s31.44 14.08 31.44 31.44C287.4 401.9 273.4 416 256 416z"/></svg>
                        </span>
                        <div class="tooltip-wrap">
                            <h3>
                                <?php echo esc_html( $item['tooltip_title'] ); ?> <br>
                            </h3>    
                            <div class="tooltip-content">
                                <p>
                                    <?php echo Helper::kses( $item['tooltip_desc'] ); ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </label>
                <div class="etn-desc">  <?php echo esc_html( $item['desc'] ); ?>  </div>
            </div>
            <div class="etn-meta datepicker">
                <div>
                    <input type="text" id="etn_start_date" class="etn-setting-input" date-format="<?php echo esc_attr($get_date_format); ?>" required name="etn_start_date" placeholder="<?php echo !empty( $item['placeholder'] ) ? esc_attr( $item['placeholder'] ) : esc_html__('Start and End date must be provided', 'eventin'); ?>" value="<?php echo esc_html( $saved_val ); ?>" />
                </div>
            </div>
        </div>
        <?php
    }

    public function get_button( $item, $key ){
		$class     = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] : 'etn_event_meta_field';
		$btn_class = isset( $item['attr']['button_class'] ) && $item['attr']['button_class'] != '' ? $item['attr']['button_class'] : 'etn-btn-text';

        ?>
		<div class='<?php echo esc_attr( $class );?> etn_event_meta_field view-order-button'>
			<div class='etn-label'>
				<label> <?php echo esc_html( $item['label'] ); ?></label>
			</div>
			<div class='etn-meta'>
				<a href='<?php echo $item['url'] ? esc_url($item['url']) : '' ?>' target='_blank' class='etn-btn-text <?php echo esc_attr( $btn_class );?>'>
					<?php echo esc_html( $item['text'] ); ?>
				</a>
			</div>
		</div>
		<?php
    }

    public function get_recurrence_block_input( $item, $key ){
        $saved_recurring_settings       = !empty(  get_post_meta( get_the_ID(), $key, true ) ) ?   get_post_meta( get_the_ID(), $key, true ) : '';
        $event_span_days                = !empty($saved_recurring_settings['recurrence_span']) ? intval($saved_recurring_settings['recurrence_span']) : 1;
        $span_type                      = !empty($saved_recurring_settings['span_type']) ? $saved_recurring_settings['span_type'] : 'multiple' ;
        $saved_recurrence_monthly_date  = !empty($saved_recurring_settings['recurrence_monthly_date']) ? intval($saved_recurring_settings['recurrence_monthly_date']) : 1;
        $saved_recurrence_yearly_date   = !empty($saved_recurring_settings['recurrence_yearly_date']) ? intval($saved_recurring_settings['recurrence_yearly_date']) : 1;
        $saved_recurrence_yearly_month  = !empty($saved_recurring_settings['recurrence_yearly_month']) ? intval($saved_recurring_settings['recurrence_yearly_month']) : 1;
        $recurrence_frequency           = !empty($saved_recurring_settings['recurrence_freq']) ? $saved_recurring_settings['recurrence_freq'] : '';
        $selected_recurrence_ends_on    = !empty($saved_recurring_settings['recurrence_ends_on']) ? $saved_recurring_settings['recurrence_ends_on'] : '';
        $saved_recurrence_daily_interval= !empty($saved_recurring_settings['recurrence_daily_interval']) ? intval( $saved_recurring_settings['recurrence_daily_interval'] ) : 1;
        $selected_recurrence_weekly_day = !empty($saved_recurring_settings['recurrence_weekly_day']) ? $saved_recurring_settings['recurrence_weekly_day'] : [] ;
        $saved_recurrence_monthly_advanced_interval     = !empty($saved_recurring_settings['recurrence_monthly_advanced_interval']) ? intval($saved_recurring_settings['recurrence_monthly_advanced_interval']) : 1;
        $saved_recurrence_monthly_advanced_week_no      = !empty($saved_recurring_settings['recurrence_monthly_advanced_week_no']) ? intval($saved_recurring_settings['recurrence_monthly_advanced_week_no']) : 1;
        $saved_recurrence_monthly_advanced_weekday_no   = !empty($saved_recurring_settings['recurrence_monthly_advanced_weekday_no']) ? intval($saved_recurring_settings['recurrence_monthly_advanced_weekday_no']) : 0;
        $check_val           = !empty($saved_recurring_settings['recurring_thumb']) ? $saved_recurring_settings['recurring_thumb'] : '';
        $class  = $key;

        $checked                = isset( $check_val ) && $check_val !== '' && $check_val !== 'no' ? 'checked' : '';
        $left_choice            = isset( $item['left_choice'] ) ? $item['left_choice'] : 'yes';
        $right_choice           = isset( $item['right_choice'] ) ? $item['right_choice'] : 'no';
       
        if ( isset( $item['attr'] ) ) {
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field' : 'etn_event_meta_field';
        }
        ?>
        <div class="<?php echo esc_attr( $class ); ?>">
            <div class="etn-label">
                <label> 
                    <?php echo esc_html( $item['label'] ); ?> 
                    <span class="tooltip-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M506.3 417l-213.3-364c-16.33-28-57.54-28-73.98 0l-213.2 364C-10.59 444.9 9.849 480 42.74 480h426.6C502.1 480 522.6 445 506.3 417zM232 168c0-13.25 10.75-24 24-24S280 154.8 280 168v128c0 13.25-10.75 24-23.1 24S232 309.3 232 296V168zM256 416c-17.36 0-31.44-14.08-31.44-31.44c0-17.36 14.07-31.44 31.44-31.44s31.44 14.08 31.44 31.44C287.4 401.9 273.4 416 256 416z"/></svg>
                    </span>
                    <div class="tooltip-wrap">
                        <h3><?php echo esc_html__('Recurring Event', 'eventin');?></h3>  
                        <div class="tooltip-content">
                            <p>
                            <?php
                                echo Helper::kses(__('Any kind of modification to event <strong>Start Date, End Date or Recurrence Rules </strong> will cause all <strong>upcoming</strong> recurrences of this event to be deleted and recreated, their corresponded bookings will also be deleted. But the expired / past recurrences that already have occurred will be preserved.
                                    <br>
                                    <br>
                                    You can edit individual recurrences and disassociate them with this recurring event if you want.
                                ','eventin'));
                                ?>
                            </p>
                        </div>
                    </div>
                </label>
                <div class="etn-desc"> <?php echo esc_html( $item['desc'] ); ?> </div>
            </div>
            <div class="etn-meta">                
                <div class="etn-recurring-single-item etn-recurring-event-every-parent">
                    <div class="floatleft etn-recurring-event-every-label">
                        <?php echo esc_html__('Event will repeat ', 'eventin');?>
                    </div>
                    <div class="floatleft etn-recurring-event-every-options">
                        <select class='event-recurrence-freq etn-form-control' name="<?php echo esc_attr( $key . "[recurrence_freq]" ) ;?>" id='recurrence_freq'>
                            <option value='no' <?php selected( $recurrence_frequency, '' ); ?>><?php echo esc_html__('-- Select --', 'eventin');?></option>
                            <option value='day' <?php selected( $recurrence_frequency, 'day' ); ?>><?php echo esc_html__('Daily', 'eventin');?></option>
                            <option value='week' <?php selected( $recurrence_frequency, 'week' ); ?>><?php echo esc_html__('Weekly', 'eventin');?></option>
                            <option value='month' <?php selected( $recurrence_frequency, 'month' ); ?>><?php echo esc_html__('Monthly', 'eventin');?></option>
                            <option value='month-advanced' <?php selected( $recurrence_frequency, 'month-advanced' ); ?>><?php echo esc_html__('Monthly - Advanced', 'eventin');?></option>
                            <option value='year' <?php selected( $recurrence_frequency, 'year' ); ?>><?php echo esc_html__('Yearly', 'eventin');?></option>
                        </select>
                    </div>
                </div>

                <div class="etn-recurring-single-item etn-recurring-event-interval-day" id="event-interval-day">
                    <div class="floatleft etn-recurring-event-every-label">
                        <?php echo esc_html__('Recurrence Interval', 'eventin');?>
                    </div>
                    <div class='floatleft event-interval-monthly-date'>
                        <input class="etn-form-control" type="number" name="<?php echo esc_attr( $key . "[recurrence_daily_interval]" ) ;?>" min='1' max='31' placeholder="1-31" value="<?php echo esc_attr( $saved_recurrence_daily_interval ); ?>"/>
                        <span><?php echo esc_html__(' day(s)', 'eventin');?></span>
                    </div>
                </div>

                <div class="etn-recurring-single-item etn-recurring-event-interval-week etn-checkbox-field" id="event-interval-week">
                    <div class="floatleft etn-recurring-event-every-label">
                        <?php echo esc_html__('On', 'eventin');?>
                    </div>
                    <ul class="floatleft event-interval-week-name-container">
                        <li class='floatleft event-interval-week-name'>
                            <input id="0<?php echo esc_attr($key);?>" type="checkbox" name="<?php echo esc_attr($key . "[recurrence_weekly_day][]") ;?>" value="0" <?php echo in_array(0, $selected_recurrence_weekly_day) ? "checked='checked'" : ''; ?> />
                            <label for="0<?php echo esc_attr($key);?>"><?php echo esc_html__('Sun', 'eventin');?></label>
                        </li>
                        <li class='floatleft event-interval-week-name'>
                            <input id="1<?php echo esc_attr($key);?>" type="checkbox" name="<?php echo esc_attr($key . "[recurrence_weekly_day][]") ;?>" value="1" <?php echo in_array(1, $selected_recurrence_weekly_day) ? "checked='checked'" : ''; ?> />
                            <label for="1<?php echo esc_attr($key);?>"><?php echo esc_html__('Mon', 'eventin');?></label>
                        </li>
                        <li class='floatleft event-interval-week-name'>
                            <input id="2<?php echo esc_attr($key);?>" type="checkbox" name="<?php echo esc_attr($key . "[recurrence_weekly_day][]") ;?>" value="2" <?php echo in_array(2, $selected_recurrence_weekly_day) ? "checked='checked'" : ''; ?> />
                            <label for="2<?php echo esc_attr($key);?>"><?php echo esc_html__('Tue', 'eventin');?></label>

                        </li>
                        <li class='floatleft event-interval-week-name'>
                            <input id="3<?php echo esc_attr($key);?>" type="checkbox" name="<?php echo esc_attr( $key . "[recurrence_weekly_day][]" ) ;?>" value="3" <?php echo in_array(3, $selected_recurrence_weekly_day) ? "checked='checked'" : ''; ?> />
                            <label for="3<?php echo esc_attr($key);?>"><?php echo esc_html__('Wed', 'eventin');?></label>

                        </li>
                        <li class='floatleft event-interval-week-name'>
                            <input id="4<?php echo esc_attr($key);?>" type="checkbox" name="<?php echo esc_attr($key . "[recurrence_weekly_day][]") ;?>" value="4" <?php echo in_array(4, $selected_recurrence_weekly_day) ? "checked='checked'" : ''; ?> />
                            <label for="4<?php echo esc_attr($key);?>"><?php echo esc_html__('Thu', 'eventin');?></label>

                        </li>
                        <li class='floatleft event-interval-week-name'>
                            <input id="5<?php echo esc_attr($key);?>" type="checkbox" name="<?php echo esc_attr( $key . "[recurrence_weekly_day][]"  );?>" value="5" <?php echo in_array(5, $selected_recurrence_weekly_day) ? "checked='checked'" : ''; ?> />
                            <label for="5<?php echo esc_attr($key);?>"><?php echo esc_html__('Fri', 'eventin');?></label>

                        </li>
                        <li class='floatleft event-interval-week-name'>
                            <input id="6<?php echo esc_attr($key);?>" type="checkbox" name="<?php echo esc_attr($key . "[recurrence_weekly_day][]") ;?>" value="6" <?php echo in_array(6, $selected_recurrence_weekly_day) ? "checked='checked'" : ''; ?> />
                            <label for="6<?php echo esc_attr($key);?>"><?php echo esc_html__('Sat', 'eventin');?></label>

                        </li>
                    </ul>
                </div>

                <div class="etn-recurring-single-item etn-recurring-event-interval-month" id="event-interval-month">
                    <div class="floatleft etn-recurring-event-every-label">
                        <?php echo esc_html__('On the date of', 'eventin');?>
                    </div>
                    <div class='floatleft event-interval-monthly-date'>
                        <input class="etn-form-control" type="number" name="<?php echo esc_attr($key . "[recurrence_monthly_date]") ;?>" min='1' max='31' placeholder="1-31" value="<?php echo esc_attr( $saved_recurrence_monthly_date ); ?>"/>
                    </div>
                </div>

                <div class="etn-recurring-event-interval-month-advanced" id="event-interval-month-advanced">
                    <div class="etn-recurring-single-item">
                        <div class="floatleft etn-recurring-event-every-label">
                            <?php echo esc_html__('On every ', 'eventin');?>
                        </div>
                        <div class='floatleft event-monthly-advanced-interval'>
                            <input type="number" size="2" class="etn-form-control" name="<?php echo esc_attr($key . "[recurrence_monthly_advanced_interval]") ;?>" value="<?php echo esc_attr( $saved_recurrence_monthly_advanced_interval ); ?>"/>
                            <span><?php echo esc_html('Months', 'eventin'); ?></span>
                        </div>
                    </div>

                    <div class="etn-recurring-single-item">
                        <div class="floatleft etn-recurring-event-every-label">
                            <?php echo esc_html__('On The', 'eventin');?>
                        </div>
                        <div class='floatleft event-interval-yearly-month'>
                            <select class='event-recurrence-freq etn-form-control' name="<?php echo esc_attr( $key . "[recurrence_monthly_advanced_week_no]" ) ;?>" id='recurrence_monthly_advanced_week_no'>
                                <option value="1" <?php selected($saved_recurrence_monthly_advanced_week_no, '1'); ?>><?php echo esc_html('First', 'eventin'); ?></option>
                                <option value="2" <?php selected($saved_recurrence_monthly_advanced_week_no, '2'); ?>><?php echo esc_html('Second', 'eventin'); ?></option>
                                <option value="3" <?php selected($saved_recurrence_monthly_advanced_week_no, '3'); ?>><?php echo esc_html('Third', 'eventin'); ?></option>
                                <option value="4" <?php selected($saved_recurrence_monthly_advanced_week_no, '4'); ?>><?php echo esc_html('Fourth', 'eventin'); ?></option>
                                <option value="5" <?php selected($saved_recurrence_monthly_advanced_week_no, '5'); ?>><?php echo esc_html('Fifth', 'eventin'); ?></option>
                                <option value="-1" <?php selected($saved_recurrence_monthly_advanced_week_no, '-1'); ?>><?php echo esc_html('Last', 'eventin'); ?></option>
                            </select>
                            <select class='event-recurrence-freq etn-form-control' name="<?php echo esc_attr( $key . "[recurrence_monthly_advanced_weekday_no]" ) ;?>" id="recurrence_monthly_advanced_weekday_no" >
                                <option value="0" <?php selected($saved_recurrence_monthly_advanced_weekday_no, '0'); ?>><?php echo esc_html('Sun', 'eventin'); ?></option>
                                <option value="1" <?php selected($saved_recurrence_monthly_advanced_weekday_no, '1'); ?>><?php echo esc_html('Mon', 'eventin'); ?></option>
                                <option value="2" <?php selected($saved_recurrence_monthly_advanced_weekday_no, '2'); ?>><?php echo esc_html('Tue', 'eventin'); ?></option>
                                <option value="3" <?php selected($saved_recurrence_monthly_advanced_weekday_no, '3'); ?>><?php echo esc_html('Wed', 'eventin'); ?></option>
                                <option value="4" <?php selected($saved_recurrence_monthly_advanced_weekday_no, '4'); ?>><?php echo esc_html('Thu', 'eventin'); ?></option>
                                <option value="5" <?php selected($saved_recurrence_monthly_advanced_weekday_no, '5'); ?>><?php echo esc_html('Fri', 'eventin'); ?></option>
                                <option value="6" <?php selected($saved_recurrence_monthly_advanced_weekday_no, '6'); ?>><?php echo esc_html('Sat', 'eventin'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="etn-recurring-event-interval-year " id="event-interval-year">
                    <div class="etn-recurring-single-item">
                        <div class="floatleft etn-recurring-event-every-label">
                            <?php echo esc_html__('In the month of', 'eventin');?>
                        </div>
                        <div class='floatleft event-interval-yearly-month'>
                            <select class='event-recurrence-freq etn-form-control' name="<?php echo esc_attr( $key . "[recurrence_yearly_month]" ) ;?>" id='recurrence_freq'>
                                <option value='no' <?php selected($saved_recurrence_yearly_month, 'no'); ?> ><?php echo esc_html__('Select month ', 'eventin');?></option>
                                <option value='1' <?php selected($saved_recurrence_yearly_month, '1'); ?>><?php echo esc_html__('January', 'eventin');?></option>
                                <option value='2' <?php selected($saved_recurrence_yearly_month, '2'); ?>><?php echo esc_html__('February', 'eventin');?></option>
                                <option value='3' <?php selected($saved_recurrence_yearly_month, '3'); ?>><?php echo esc_html__('march', 'eventin');?></option>
                                <option value='4' <?php selected($saved_recurrence_yearly_month, '4'); ?>><?php echo esc_html__('April', 'eventin');?></option>
                                <option value='5' <?php selected($saved_recurrence_yearly_month, '5'); ?>><?php echo esc_html__('May', 'eventin');?></option>
                                <option value='6' <?php selected($saved_recurrence_yearly_month, '6'); ?>><?php echo esc_html__('June', 'eventin');?></option>
                                <option value='7' <?php selected($saved_recurrence_yearly_month, '7'); ?>><?php echo esc_html__('July', 'eventin');?></option>
                                <option value='8' <?php selected($saved_recurrence_yearly_month, '8'); ?>><?php echo esc_html__('August', 'eventin');?></option>
                                <option value='9' <?php selected($saved_recurrence_yearly_month, '9'); ?>><?php echo esc_html__('September', 'eventin');?></option>
                                <option value='10' <?php selected($saved_recurrence_yearly_month, '10'); ?>><?php echo esc_html__('October', 'eventin');?></option>
                                <option value='11' <?php selected($saved_recurrence_yearly_month, '11'); ?>><?php echo esc_html__('November', 'eventin');?></option>
                                <option value='12' <?php selected($saved_recurrence_yearly_month, '12'); ?>><?php echo esc_html__('December', 'eventin');?></option>
                            </select>
                        </div>
                    </div>

                    <div class="etn-recurring-single-item">
                        <div class="floatleft etn-recurring-event-every-label">
                            <?php echo esc_html__('On the date of', 'eventin');?>
                        </div>
                        <div class='floatleft event-interval-yearly-date'>
                            <input type="number" class="etn-form-control" name="<?php echo esc_attr($key . "[recurrence_yearly_date]") ;?>" min='1' max='31' placeholder="1-31" value="<?php echo esc_attr( $saved_recurrence_yearly_date ); ?>"/>
                        </div>
                    </div>
                </div>

                <div class="etn-recurring-single-item etn-recurring-event-span" id="etn-recurring-event-span">
                    <div class="floatleft etn-recurring-event-every-label">
                        <?php echo esc_html__('Each recurrence duration for Day(s)', 'eventin');?>
                    </div>
                    <div class='floatleft event-span-days'>
                        <div class="event-multiple-span-days-holder">
                            <input class="etn-form-control etn-recurrence-span-count" type="number" name="<?php echo esc_attr( $key . "[recurrence_span]" ) ;?>" value="<?php echo esc_attr( $event_span_days ); ?>" min='1'/>
                        </div>
                    </div>
                </div>

                <div class="etn-recurring-single-item etn-recurring-event-ends-parent">
                    <div class="floatleft etn-recurring-event-ends-label etn-recurring-event-every-label">
                        <?php echo esc_html__('Ends', 'eventin');?>
                    </div>
                    <div class="floatleft etn-recurring-event-ends-options">
                        <select class="etn-form-control" name="<?php echo esc_attr($key . "[recurrence_ends_on]") ;?>">
                            <option <?php selected( $selected_recurrence_ends_on,'event_end' )?> value="event_end"><?php echo esc_html__( 'On Event End Date', 'eventin' );?></option>
                        </select>
                    </div>
                </div>

                <div class="etn-recurring-single-item etn-recurring-event-span" id="etn-recurring-event-span">
                    <div class="floatleft etn-recurring-event-every-label">
                        <?php echo esc_html__('Do want to hide Recurring event thumbnail?', 'eventin');?>
                    </div>
                    <div class='floatleft'>
                        <input type="hidden" name="<?php echo esc_attr( $key . "[recurring_thumb]" ) ;?>" value="no"/>
                        <input  class="etn-admin-control-input " value="yes" type="checkbox" name="<?php echo esc_attr( $key . "[recurring_thumb]" ) ;?>" id="<?php echo esc_attr( $key . "[recurring_thumb]" ) ;?>" <?php echo esc_attr( $checked ); ?>/>
                        <label for="<?php echo esc_attr( $key . "[recurring_thumb]" ) ;?>" data-left="<?php echo esc_attr( $left_choice ); ?>" data-right="<?php echo esc_attr( $right_choice ); ?>" class="etn_switch_button_label"></label>
                    </div>
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Get Timezone input markup
     *
     * @param [type] $item
     * @param [type] $key
     * @return void
     */
    public function get_timezone_input( $item, $key ){
        $current_offset  = get_option( 'gmt_offset' );
        $timezone_str        = get_option( 'timezone_string' );
         if ( false !== strpos( $timezone_str, 'Etc/GMT' ) ) {
            $timezone_str = '';
        }
        if ( empty( $timezone_str ) ) {
             if ( 0 == $current_offset ) {
                $timezone_str = 'UTC+0';
            } elseif ( $current_offset < 0 ) {
                $timezone_str = 'UTC' . $current_offset;
            } else {
                $timezone_str = 'UTC+' . $current_offset;
            }
        } 

        $saved_time_zone = !empty(  get_post_meta( get_the_ID(), $key, true ) ) ?   get_post_meta( get_the_ID(), $key, true ) : $timezone_str;
        $class  = $key;
        if ( isset( $item['attr'] ) ) {
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field' : 'etn_event_meta_field';
        }
    
        ?>
        <div class="<?php echo esc_attr( $class ); ?>">
            <div class="etn-label">
                <label> <?php echo esc_html( $item['label'] ); ?>  </label>
                <div class="etn-desc"> <?php echo esc_html( $item['desc'] ); ?> </div>
            </div>
            <select id="etn-event-timezone" class="etn-form-control" name="event_timezone" aria-describedby="etn-timezone-description">
			    <?php echo wp_timezone_choice( $saved_time_zone, get_user_locale() ); ?>
		    </select>
        </div>
        <?php
    }

    public function get_checkbox( $item, $key ) {
        $class                  = $key;
        $value                  = get_post_meta( get_the_ID(), $key, true );
        $checked                = isset( $value ) && $value !== '' && $value !== 'no' ? 'checked' : '';

        if ( empty( $value ) && $key == 'zoom_question_and_answer' ) {
            $checked = 'checked';
        }

        $left_choice            = isset( $item['left_choice'] ) ? $item['left_choice'] : 'yes';
        $right_choice           = isset( $item['right_choice'] ) ? $item['right_choice'] : 'no';
        $condition_class        = ( isset( $item["conditional"] ) && $item["conditional"] == true ) ? "etn-conditional-control" : "";       

        if ( isset( $item['attr'] ) ) {
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field' : 'etn_event_meta_field';
        }

        $data = "";
        if ( isset( $item['data'] ) && count($item['data'])>0 ) {
            foreach ( $item['data'] as $index => $value) {
                $data .= "data-".$index ." =" .  "' $value '" ;
            }
        }

        ?>

        <div class="<?php echo esc_attr( $class ); ?>">
            <div class="etn-label">
                <label> <?php echo esc_html( $item['label'] ); ?></label>
                <div class="etn-desc"> <?php echo esc_html( $item['desc'] ); ?> </div>
            </div>
            <?php
                if((isset($item['pro']) && !class_exists( 'Wpeventin_Pro' ))){ 
                    echo Helper::get_pro();
                } else {
            ?>
                <div class="etn-meta">
                    <input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="no"/>
                    <input  class="etn-admin-control-input <?php echo esc_attr( $condition_class ); ?>" value="yes" type="<?php echo esc_attr( $item['type'] ); ?>" name="<?php echo esc_attr( $key ); ?>"

                    <?php echo Helper::kses($data);?> id="<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $checked ); ?>/>

                    <label for="<?php echo esc_attr( $key ); ?>" data-left="<?php echo esc_attr( $left_choice ); ?>" data-right="<?php echo esc_attr( $right_choice ); ?>" class="etn_switch_button_label"></label>
                </div>
            <?php
                }
            ?>
        </div>
        <?php

    }

    public function get_color_picker( $item, $key ) {
        $class         = $key;
        $value         = get_post_meta( get_the_ID(), $key, true );
        $default_color = isset( $item['default-color'] ) ? $item['default-color'] : '#fff';
        if ( isset( $item['attr'] ) ) {
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field' : 'etn_event_meta_field';
        }

        $html = sprintf( '<div class="%s">
                            <div class="etn-label"> <label> %s </label><div class="etn-desc">  %s  </div> </div>
                            <div class="etn-meta">
                                <input data-default-color="%s" data-value="%s" value="%s" class="banner_color_picker"
                                type="hidden" name="%s" id="%s"/>
                            </div>
                         </div>', $class, $item['label'], $item['desc'], $default_color, $value, $value, $key, $key );

        echo Helper::kses( $html );
    }

    /**
     * Multiple Checkbox
     */
    public function get_multi_checkbox($itemData, $key)
    {
        $postMeta = get_post_meta(get_the_ID(), $key, true);
        $valueArr = !empty($postMeta) ? maybe_unserialize($postMeta) : [];
        ?>
        <div class="etn-label-item <?php echo esc_attr($key); ?>">
            <div class="etn-label">
                <label><?php echo esc_html($itemData['label']); ?></label>
                <div class="etn-desc"><?php echo esc_html($itemData['desc']); ?></div>
            </div>
            <?php if (isset($itemData['pro']) && !class_exists('Wpeventin_Pro')): ?>
                <?php echo Helper::get_pro(); ?>
            <?php else: ?>
                <div class="etn-meta">
                <?php
                    $inputs = $itemData['inputs'] ?? [];
                    if (!empty($inputs)) {
                        foreach ($inputs as $index => $inputItem) {
                            $inputKey = strtolower(str_replace(' ', '_', $inputItem));
                            $checked  = '';
                            
                            if ( in_array( $inputKey, $valueArr ) ) {
                                $checked = 'checked="checked"';
                            }
                            
                            ?>
                            <input
                                type="checkbox"
                                name="<?php echo esc_attr($key) . '[' . $inputKey . ']'; ?>"
                                id="<?php echo esc_attr( $key . $index );?>"
                                class="etn_multi_checkbox"
                                value="<?php echo esc_attr( $inputKey );?>"
                                <?php echo $checked; ?>
                            />
                            <label class="etn_multi_label" for="<?php echo esc_attr( $key . $index );?>">
                                <?php esc_html_e($inputItem, 'eventin');?>
                            </label>
                        <?php  }
                    }
                    ?>
                </div>
            <?php endif;?>
        </div>
        <?php
}

    public function get_wp_repeater( $item, $key ) {
        $value          = [];
        $class          = $key;
        $options_fields = $item['options'];
        $repeater_arr   = get_post_meta( get_the_ID(), $key, true );
        $sort_arr       =(array) json_decode( get_post_meta( get_the_ID(), 'etn_schedule_sorting', true ) );
        $count          = is_array( $repeater_arr ) ? count( $repeater_arr ) : 1;
        ?>
        <div class='etn-event-repeater-wrap etn-event-repeater-clearfix etn-repeater-item'>
            <div class="etn-walkthrough-item">
                <h4 class='etn-sub-title'>
                    <?php echo esc_html( $item['label'] ); ?>                  
                </h4>
                <?php if($item['label'] !=''): ?>
                <div class="etn-walkthrough-desc">
                    <?php echo Helper::kses( $item['walkthrough_desc'] ); ?>                     
                </div>  
                <?php endif; ?>
            </div>
            <?php
                if((isset($item['pro']) && !class_exists( 'Wpeventin_Pro' ))){ 
                    echo Helper::get_pro();
                } else {
            ?>
            <div class='etn-event-manager-repeater-fld <?php echo esc_attr( $class ); ?>'>
                <div data-repeater-list='<?php echo esc_html( $key ); ?>' <?php echo ( $key == 'etn_schedule_topics' ) ? "class='schedule_repeater'" : ''; ?>>
                <?php
                for ( $x = 0; $x < $count; $x++ ) {
                    $label_no       = $x;
                    ?>
                    <div data-repeater-list="etn-event-repeater-options" class="etn-repeater-item sort_repeat" data-repeater-item="<?php echo esc_attr( !empty($sort_arr[$x]) ? $sort_arr[$x] : $x ) ?>">
                        <div class="form-group">
                        <div class="etn-event-shedule-collapsible">
                            <span class="event-title"><?php echo esc_html( isset($repeater_arr[$x]['etn_faq_title'])  ? $repeater_arr[$x]['etn_faq_title'] : $item['label'] . ' ' . ++$label_no ); ?></span>
                            <i data-repeater-delete type="button" class="dashicons dashicons-no-alt" aria-hidden="true"></i>
                            <svg class="etn-arrow-icon" width="12" height="7" viewBox="0 0 12 7" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 1L6 5.98584L11 1" stroke="#0D165E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="etn-event-repeater-collapsible-content" style="display: none">
                            <?php $i = $x;
                            foreach ( $options_fields as $op_fld_key => $options_field ){
                                $nested_data = isset( $repeater_arr[$i] ) ? $repeater_arr[$i] : [];

                                echo Helper::render( $this->get_repeater_markup( $options_field, $op_fld_key, $nested_data , $i ) );
                            }
                            ?>
                        </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
                </div>
                <input data-repeater-create type='button' class='etn-btn-text repeater_button' value='<?php echo esc_html__( "Add", "eventin" ); ?>' />
            </div>
            <?php 
                }
            ?>
        </div>
        <?php
    }

    public function get_wp_repeaterpublic( $item, $key, $id ) {
        $value          = [];
        $class          = $key;
        $options_fields = $item['options'];
        $repeater_arr   = get_post_meta( $id, $key, true );
        $count          = is_array( $repeater_arr ) ? !empty( $repeater_arr ) : 1;

        ?>
        <div class='etn-event-repeater-clearfix'>
            <h3><?php echo esc_html( $item['label'] ); ?></h3>
            <div class='form-inline etn-event-repeater <?php echo esc_attr( $class ); ?>'>
                <div data-repeater-list='<?php echo esc_html( $key ); ?>'>
                <input data-repeater-create type='button' class='etn-btn attr-btn-primary mb-2 clearfix' value='<?php echo esc_html__( "Add", "eventin" ); ?>' />
                <?php
                for ( $x = 0; $x < $count; $x++ ) {
                    $label_no = $x;
                    ?>
                    <div data-repeater-list="etn-event-repeater-options" class="etn-repeater-item repeater_<?php echo esc_attr( $key ); ?>">
                        <div class="form-group mb-3" data-repeater-item>
                        <div onclick="etn_essential_event_reapeater_collapse_public(this)" class="etn-event-repeater-collapsible">
                            <?php echo esc_html( $item['label'] . ' ' . ++$label_no ); ?>
                            <i data-repeater-delete type="button" class="dashicons dashicons-no-alt" aria-hidden="true"></i>
                        </div>
                        <div class="etn-event-repeater-collapsible-content">
                            <?php $i = $x;
                            foreach ( $options_fields as $op_fld_key => $options_field ):
                                $nested_data = isset( $repeater_arr[$i] ) ? $repeater_arr[$i] : [];
                                echo Helper::render( $this->get_repeater_markup( $options_field, $op_fld_key, $nested_data , $i ) );
                            endforeach;
                            ?>
                        </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    }

    public function get_wp_repeaterpublicnull( $item, $key ) {

        $value          = [];
        $class          = $key;
        $options_fields = $item['options'];

        $count = 1;
        ?>
        <div class='etn-event-repeater-clearfix'>
            <h3><?php echo esc_html( $item['label'] ); ?></h3>
            <?php echo sprintf( "<div class='form-inline etn-event-repeater %s'><div data-repeater-list='%s'>", $class, $key );?>
            <input data-repeater-create type="button" class="etn-btn attr-btn-primary mb-2 clearfix" value="<?php echo esc_html__( "Add", "eventin" ); ?>" />
            <?php
            for ( $x = 0; $x < $count; $x++ ) {
                $label_no = $x;
                ?>
                <div data-repeater-list="etn-event-repeater-options" class="etn-repeater-item">
                    <div class="form-group mb-3" data-repeater-item>

                    <div onclick="etn_essential_event_repeater_collapse_publicnull(this)" class="etn-event-repeater-collapsible">
                        <?php echo esc_html( $item['label'] . ' ' . ++$label_no ); ?>
                        <i data-repeater-delete type="button" class="dashicons dashicons-no-alt" aria-hidden="true"></i>
                    </div>

                    <div class="etn-event-repeater-collapsible-content">
                        <?php $i = $x;
                        foreach ( $options_fields as $op_fld_key => $options_field ):
                            $nested_data = isset( $repeater_arr[$i] ) ? $repeater_arr[$i] : [];
                            echo Helper::render( $this->get_repeater_markup( $options_field, $op_fld_key, $nested_data , $i ) );
                        endforeach;
                        ?>
                    </div>
                    </div>
                </div>
                <?php
            }
            ?>
            <script>
                function etn_essential_event_repeater_collapse_publicnull(e) {

                    e.classList.toggle("etn-repeater-fld-active");
                    var content = e.nextElementSibling;
                    if (content.style.display === "block") {
                    content.style.display = "none";
                    } else {
                    content.style.display = "block";
                    }
                    jQuery('.etn_event_date').datepicker({
                    dateFormat: "yy,MM,dd",
                    onSelect: function() {
                        jQuery(this).val();
                    }
                    });
                    jQuery('.etn_es_event_repeater_select2').select2();
                    jQuery('.etn_es_event_repeater_select2').select2();
                    if (jQuery(e).next().find('span.select2:eq(1)').length) {
                    jQuery(e).next().find('span.select2:eq(1)').hide();
                    }

                }
            </script>
            </div>
        </div>
        <?php
    }

    public function get_wp_social_repeater( $item, $key ) {
        $value        = '';
        $class        = $key;
        $social_items = $key;

        $dbvalue = get_post_meta( get_the_ID(), $key, true );

        require \Wpeventin::plugin_dir() . 'core/metaboxs/views/fields/icons.php';
        ?>
        <div class='etn-social-clearfix etn-label-social etn-label-item etn-label-top'>
        <div class='etn-label'>
            <label><?php echo esc_html( $item['label'] ); ?></label>
            <div class="etn-desc"><?php echo esc_html( $item['desc'] ); ?></div>
        </div>
        <?php

        if ( is_array( $dbvalue ) ) {
            echo sprintf( "<div class='form-inline etn-meta social-repeater %s'>
            <div class='etn-repeater-wrap' data-repeater-list='%s'>", $class, $social_items );

            foreach ( $dbvalue as $db_socail ) {
                ?>
                    <div data-repeater-item>
                        <div class='etn-form-group mb-2'>
                            <i class='etn-icon <?php echo esc_attr( $db_socail['icon'] ); ?> show-repeater-icon'></i>
                            <input type='text' value='<?php echo esc_html( $db_socail['icon'] ); ?>' name='icon' class='etn-social-icon etn-form-control' data-toggle='modal' data-target='#etn-event-es-social-modal'/>
                            <input type='text' class='etn-form-control' value='<?php echo esc_html( $db_socail['etn_social_title'] ); ?>' name='etn_social_title' placeholder='<?php echo esc_html__( "title", "eventin" ); ?>' />
                            <input type='text' class='etn-form-control' value='<?php echo esc_html( $db_socail['etn_social_url'] ); ?>' name='etn_social_url' placeholder='<?php echo esc_html__( "url", "eventin" ); ?>' />
                            <button data-repeater-delete type='button' class='etn-btn-close'>
                                <span class='dashicons dashicons-no-alt'></span>
                            </button>
                        </div>
                    </div>
                    <?php
            }
            ?>
            </div>
                <div class='add-social'>
                <input class='etn-btn-text' data-repeater-create type='button' value='<?php echo esc_html__( "Add", "eventin" ); ?>'/>
                </div>
            </div>
            <?php
        } else {
            ?>
                <div class='form-inline etn-meta social-repeater <?php echo esc_attr( $class ); ?>'><div data-repeater-list='<?php echo esc_attr( $social_items ); ?>'>
                    <div data-repeater-item>
                        <div class='etn-form-group mb-2'>
                            <i class='etn-icon'></i>
                            <input  type='text' name='icon' class='etn-social-icon etn-form-control'  data-toggle='modal' placeholder="<?php echo esc_html__('Enter Icon','eventin'); ?>" data-target='#etn-event-es-social-modal'/>
                            <input type='text' class='etn-form-control' name='etn_social_title' placeholder='<?php echo esc_html__( "Title here", "eventin" ); ?>' />
                            <input type='text' class='etn-form-control' name='etn_social_url' placeholder='<?php echo esc_html__( "URL here", "eventin" ); ?>' />
                            <button data-repeater-delete type='button' class='etn-btn-close'>
                                <span class='dashicons dashicons-no-alt'></span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class='add-social'>
                    <input class='etn-btn-text' data-repeater-create type='button' value='<?php echo esc_html__( "Add", "eventin" ); ?>'/>
                </div>
                </div>
                <?php
        }
        ?>
        </div>
        <?php
    }

    public function get_separator( $item, $key ) {

        $class = $key;

        if ( isset( $item['attr'] ) ) {
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field' : 'etn_event_meta_field';
        }

        ?>
        <div class='<?php echo esc_attr( $class ); ?>'>
            <hr/>
        </div>
        <?php
    }

    public function get_wp_map( $item, $key ) {
        $options = get_option( 'etn_event_general_options' );
        $value   = '';
        $class   = $key;

        if ( isset( $item['value'] ) ) {
            $value = get_post_meta( get_the_ID(), $key, true );
        }

        if ( isset( $item['attr'] ) ) {
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field ' : ' etn_event_meta_field';
        }

        require \Wpeventin::plugin_dir() . 'views/fields/map.php';
    }

    public function get_heading( $item, $key ) {

        if ( !isset( $item['label'] ) ) {
            return;
        }

        $class = $key;

        if ( isset( $item['attr'] ) ) {
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field' : 'etn_event_meta_field';
        }

        $html = sprintf( '<div class="%s">
      <h3 for="%s"> %s  </h3>

     </div>', $class, $key, $item['label'] );

        echo Helper::kses( $html );
    }

    public function get_textinput( $item, $key ) {
        $value = '';
        $class = $key;
        $icon  = '';
        $placeholder  = '';

        $value = !empty( get_post_meta( get_the_ID(), $key, true ) ) ? get_post_meta( get_the_ID(), $key, true ) : ( !empty( $item['value'] ) ? $item['value'] : "" );

        if ( isset( $item['attr'] ) ) {
            $class = !empty( $item['attr']['class'] ) ? $item['attr']['class'] . ' etn_event_meta_field' : 'etn_event_meta_field';
            $icon  = !empty( $item['attr']['icon'] )  ? $item['attr']['icon']  : '';
        }

        $readonly = ( !empty( $item['readonly'] ) ) ? 'readonly' : "";
        $disabled = ( !empty( $item['disabled'] ) ) ? 'disabled' : "";
        ?>
        <div class="<?php echo esc_html( $class ); ?>">
            <div class="etn-label">
                <label for="<?php echo esc_html( $key ); ?>">
                    <?php echo esc_html( $item['label'] ); ?>
                    <?php if( !empty($item['tooltip_title'])): ?>
                        <span class="tooltip-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M506.3 417l-213.3-364c-16.33-28-57.54-28-73.98 0l-213.2 364C-10.59 444.9 9.849 480 42.74 480h426.6C502.1 480 522.6 445 506.3 417zM232 168c0-13.25 10.75-24 24-24S280 154.8 280 168v128c0 13.25-10.75 24-23.1 24S232 309.3 232 296V168zM256 416c-17.36 0-31.44-14.08-31.44-31.44c0-17.36 14.07-31.44 31.44-31.44s31.44 14.08 31.44 31.44C287.4 401.9 273.4 416 256 416z"/></svg>
                        </span>
                        <div class="tooltip-wrap">
                            <h3>
                                <?php echo esc_html( $item['tooltip_title'] ); ?> <br>
                            </h3>    
                            <div class="tooltip-content">
                                <p>
                                    <?php echo Helper::kses( $item['tooltip_desc'] ); ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </label>
                <div class="etn-desc">  <?php echo esc_html( $item['desc'] ); ?>  </div>
            </div>
            <?php
                if((isset($item['pro']) && !class_exists( 'Wpeventin_Pro' ))){ 
                    echo Helper::get_pro();
                } else {
            ?>
                <div class="etn-meta">
                    <?php echo Helper::render( $icon ); ?>
                    <input placeholder="<?php echo !empty( $item['placeholder'] ) ? esc_attr( $item['placeholder'] ) : ''; ?>" autocomplete="off" class="etn-form-control" type="<?php echo esc_html( $item['type'] ); ?>" name="<?php echo esc_html( $key ); ?>" id="<?php echo esc_html( $key ); ?>" value="<?php echo esc_html( $value ); ?>" <?php echo esc_html( $readonly ); ?> <?php echo esc_html( $disabled ); ?>/>
                </div>
            <?php 
                }
            ?>
        </div>
        <?php
    }

    public function get_hidden_input( $item, $key ) {
        $value = '';
        $class = $key;

        $value = !empty( get_post_meta( get_the_ID(), $key, true ) ) ? get_post_meta( get_the_ID(), $key, true ) : ( !empty( $item['value'] ) ? $item['value'] : "" );

        if ( isset( $item['attr'] ) ) {
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field' : 'etn_event_meta_field';
        }

        $readonly = ( !empty( $item['readonly'] ) ) ? 'readonly' : "";
        $disabled = ( !empty( $item['disabled'] ) ) ? 'disabled' : "";
        ?>
        <div class="<?php echo esc_html( $class ); ?>" style='display:none'>
            <div class="etn-label">
                <label for="<?php echo esc_html( $key ); ?>"> 
                    <?php echo esc_html( $item['label'] ); ?>                     
                </label>
                <div class="etn-desc">  <?php echo esc_html( $item['desc'] ); ?>  </div>
            </div>
            <div class="etn-meta">
                <input autocomplete="off" class="etn-form-control" type="hidden" name="<?php echo esc_html( $key ); ?>" id="<?php echo esc_html( $key ); ?>" value="<?php echo esc_html( $value ); ?>" <?php echo esc_html( $readonly ); ?> <?php echo esc_html( $disabled ); ?>/>
            </div>
        </div>
        <?php
    }

    public function get_number_input( $item, $key ) {

        $value = '';
        $class = $key;
        $placeholder = '';
        if ( isset( $item['value'] ) ) {
            $value = get_post_meta( get_the_ID(), $key, true );
        }

        $value  = get_post_meta( get_the_ID(), $key, true );
        $step   = isset( $item['step'] ) ? $item['step'] : "1";
        $min    = isset( $item['min'] ) ? $item['min'] : "0";
        $max    = isset( $item['max'] ) ? $item['max'] : '2147483647';

        if ( isset( $item['attr'] ) ) {
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field' : 'etn_event_meta_field';
        }
        ?>
            <div class="<?php echo esc_attr($class); ?>">
                <div class="etn-label">
                    <label for="<?php echo esc_attr($key); ?>"> <?php echo esc_html($item['label']); ?> </label>
                    <div class="etn-desc"><?php echo esc_html($item['desc']); ?></div>
                </div>
                <?php
                    if((isset($item['pro']) && !class_exists( 'Wpeventin_Pro' ))){ 
                        echo Helper::get_pro();
                    } else {
                ?>
                <div class="etn-meta">
                    <input placeholder="<?php echo !empty($item['placeholder']) ? esc_attr( $item['placeholder'] ) : ''; ?>" autocomplete="off" class="etn-form-control" type="<?php echo esc_attr($item['type']); ?>" name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" step="<?php echo esc_attr($step); ?>" min="<?php echo esc_attr($min); ?>" max="<?php echo esc_attr($max); ?>"/>
                </div>
                <?php
                   } 
                ?>
            </div>
        <?php
    }

    public function get_email_input( $item, $key ) {

        $value = '';
        $class = $key;
        $placeholder  = '';

        if ( isset( $item['value'] ) ) {
            $value = get_post_meta( get_the_ID(), $key, true );
        }

        if ( isset( $item['attr'] ) ) {
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field ' : ' etn_event_meta_field';
        }

        ?>
        <div class="<?php echo esc_attr( $class ); ?>">
            <div class="etn-label">
                <label for="<?php echo esc_attr( $key ); ?>"> <?php echo esc_html( $item['label'] ); ?> </label>
                <div class="etn-desc">  <?php echo esc_html( $item['desc'] ); ?>  </div>
            </div>
            <div class="etn-meta">
                <input autocomplete="off" class="etn-form-control" type="<?php echo esc_attr( $item['type'] ); ?>" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"  placeholder="<?php echo !empty($item['placeholder']) ? esc_attr( $item['placeholder'] ) : ''; ?>" />
            </div>
        </div>
        <?php
}

    public function get_radio_input( $item, $key ) {

        $value = '';
        $class = $key;
        $input = '';

        $value = get_post_meta( get_the_ID(), $key, true );

        if ( isset( $item['attr'] ) ) {
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field ' : 'etn_event_meta_field ';
        }
        ?>
        <div class="etn-label-item  <?php echo esc_html( $class ); ?>">
            <div class="etn-label">
                <label for="<?php echo esc_html( $key ); ?>"> <?php echo esc_html( $item['label'] ); ?></label>
                <div class="etn-desc">  <?php echo esc_html( $item['desc'] ); ?>  </div>
            </div>
            <div class="etn-meta">
                <?php
                if ( isset( $item['options'] ) && !empty( $item['options'] ) ) {
                    $options = $item['options'];

                    foreach ( $options as $option_key => $option ) {
                        $checked = ( $option_key == $value ) ? 'checked' : '';
                        ?>
                        <input <?php checked( $value, $option_key, true ) ?> type="<?php echo esc_attr($item['type']); ?>" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>"  value="<?php echo esc_attr( $option_key ); ?>"/>
                        <span> <?php echo esc_html( $option ); ?>  </span>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
        <?php
    }

    public function get_select2( $item, $key ) {
        $value = '';
        $class = $key;
        $input = '';
        $value = get_post_meta( get_the_ID(), $key, true );

        if ( isset( $item['attr'] ) ) {
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field' : 'etn_event_meta_field';
        }

        ?>
        <div class="<?php echo esc_attr( $class ); ?>">
            <div class="etn-label">
                <label> <?php echo esc_html( $item['label'] ); ?>  </label>
                <div class="etn-desc"><?php echo esc_html( $item['desc'] ); ?></div>
            </div>
            <?php
                if((isset($item['pro']) && !class_exists( 'Wpeventin_Pro' ))){ 
                    echo Helper::get_pro();
                } else {
            ?>
                <div class="etn-meta">
                    <?php
                        $options = $item['options'];
                        ?>
                        <select multiple name="<?php echo esc_attr( $key ); ?>[]" class="etn_es_event_select2 <?php echo esc_attr( $key ); ?>">
                            <?php
                            if ( !empty( $options ) ) {
                                foreach ( $options as $option_key => $option ) {
                                    if ( is_array( $value ) && in_array( $option_key, $value ) ) {
                                        ?>
                                        <option selected value="<?php echo esc_attr( $option_key ); ?>"> <?php echo esc_html( $option ); ?> </option>
                                        <?php
                                    } else {
                                        ?>
                                        <option value="<?php echo esc_attr( $option_key ); ?>"> <?php echo esc_html( $option ); ?> </option>
                                        <?php
                                    }

                                }

                            }
                            ?>
                        </select>

                        <?php
                        if(!empty($item['warning'])){
                            $warning_text = $item['warning'];
                            $warning_url  = !empty($item['warning_url']) ? $item['warning_url'] : '#';
                            ?>
                            <span class="etn-input-select-warning">
                                <a href="<?php echo esc_url( $warning_url ); ?>" target="_blank" ><?php echo esc_html( $warning_text );?></a>
                            </span>
                            <?php
                        }
                        ?>
                </div>
            <?php
                }
            ?>
        </div>
        <?php
    }

    public function get_select_single( $item, $key ) {
        $value = '';
        $class = $key;
        $input = '';
        $value = ( !empty($item['value']) ) ? $item['value'] :  get_post_meta( get_the_ID(), $key, true ) ;

        if ( isset( $item['attr'] ) ) {
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field' : 'etn_event_meta_field';
        }

        ?>
        <div class="<?php echo esc_attr( $class ); ?>">
            <div class="etn-label">
                <label> <?php echo esc_html( $item['label'] ); ?>  </label>
                <div class="etn-desc"><?php echo esc_html( $item['desc'] ); ?></div>
            </div>
            <?php
                if((isset($item['pro']) && !class_exists( 'Wpeventin_Pro' ))){ 
                    echo Helper::get_pro();
                } else {
                    ?>
                    <div class="etn-meta">
                        <?php
                            $options = $item['options'];
                            ?>
                            <select name="<?php echo esc_attr( $key ); ?>" class="etn_es_event_select2 <?php echo esc_attr( $key ); ?>">
                                <?php
                                if ( is_array( $options ) && !empty( $options ) ) {
                                    foreach ( $options as $option_key => $option ) {
                                        if ( $option_key == $value ) {
                                            ?>
                                            <option selected value="<?php echo esc_attr( $option_key ); ?>"><?php echo esc_html( $option ); ?></option>
                                            <?php
                                        } else {
                                            ?>
                                            <option value="<?php echo esc_attr( $option_key ); ?>"><?php echo esc_html( $option ); ?></option>
                                            <?php
                                        }
                                    }
                                }
                                ?>
                            </select>
                            <?php
                            if(!empty($item['warning'])){
                                $warning_text = $item['warning'];
                                $warning_url  = !empty($item['warning_url']) ? $item['warning_url'] : '#';
                                ?>
                                <span class="etn-input-select-warning">
                                    <a href="<?php echo esc_url( $warning_url ); ?>" target="_blank" ><?php echo esc_html( $warning_text );?></a>
                                </span>
                                <?php
                            }
                            ?>
                    </div>
                    <?php
                }
            ?>
        </div>
        <?php
    }

    public function get_url_input( $item, $key ) {
        $value = '';
        $class = $key;
        $placeholder = '';
        if ( isset( $item['value'] ) ) {
            $value = get_post_meta( get_the_ID(), $key, true );
        }

        if ( isset( $item['attr'] ) ) {
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field ' : 'etn_event_meta_field ';
        }
        ?>
        <div class="<?php echo esc_attr($class); ?>">
            <div class="etn-label">
                <label for="<?php echo esc_attr($key); ?>">
                    <?php echo esc_html($item['label']); ?>
                </label>
                <div class="etn-desc">
                    <?php echo esc_html($item['desc']); ?>
                </div>
            </div>
            <?php
                if((isset($item['pro']) && !class_exists( 'Wpeventin_Pro' ))){ 
                    echo Helper::get_pro();
                } else {
            ?>
                <div class="etn-meta">
                    <input class="etn-form-control" type="<?php echo esc_attr($item['type']); ?>" name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo !empty($item['placeholder']) ? esc_attr( $item['placeholder'] ) : ''; ?>" />
                </div>
            <?php
                }
            ?>
        </div>
        <?php
    }

    public function get_upload( $item, $key ) {

        $class      = $key;
        $value      = get_post_meta( get_the_ID(), $key, true );
        $image      = '">Upload image';
        $image_size = 'full';
        $display    = 'none';
        $multiple   = 0;

        if ( isset( $item['multiple'] ) && $item['multiple'] ) {
            $multiple = true;
        }

        if ( isset( $item['attr'] ) ) {

            if ( isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ) {
                $class = ' etn_event_meta_field ' . $class . ' ' . $item['attr']['class'];
            } else {
                $class = ' etn_event_meta_field ';
            }

        }

        if ( $image_attributes = wp_get_attachment_image_src( $value, $image_size ) ) {

            $image   = '"><img src="' . $image_attributes[0] . '" alt="" style="max-width:95%;display:block;" />';
            $display = 'inline-block';
        }
        ?>
        <div class="<?php echo esc_attr($class); ?>">
            <div class="etn-label"> <label> <?php echo esc_html($item['label']); ?> </label>
                <div class="etn-desc"> <?php echo esc_html($item['desc']); ?> </div>
            </div>
            <?php
                if((isset($item['pro']) && !class_exists( 'Wpeventin_Pro' ))){ 
                    echo Helper::get_pro();
                } else {
            ?>
            <div class="etn-meta">
                <a data-multiple="<?php echo esc_attr($multiple); ?>" class="etn_event_upload_image_button <?php echo $image;?> </a>
                    <input type="hidden" name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr( $key ) ?>" value="<?php echo esc_attr( $value ); ?>" />
                <a href="#" class="essential_event_remove_image_button" style="display:inline-block;display: <?php echo esc_attr($display); ?>"> <?php echo esc_html__( 'Remove image', 'eventin' ); ?></a>
            </div>
            <?php 
                }
            ?>
        </div>
        <?php
    }
    
    
    public function get_textarea( $item, $key ) {

        $rows  = 30;
        $cols  = 50;
        $value = '';
        $class = $key;
        $placeholder = '';

        if ( isset( $item['value'] ) ) {
            $value = get_post_meta( get_the_ID(), $key, true );
        }

        if ( isset( $item['attr'] ) ) {
            $rows  = isset( $item['attr']['row'] ) && $item['attr']['row'] != '' ? $item['attr']['row'] : 30;
            $cols  = isset( $item['attr']['col'] ) && $item['attr']['col'] != '' ? $item['attr']['col'] : 50;
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field ' : 'etn_event_meta_field ';
        }

        ?>
        <div class="<?php echo esc_attr( $class ); ?> form-group">
            <div class="etn-label">
                <label for="<?php echo esc_attr( $key ); ?>"> <?php echo esc_html( $item['label'] ); ?></label>
                <div class="etn-desc">  <?php echo esc_html( $item['desc'] ); ?>  </div>
            </div>
            <?php
                if((isset($item['pro']) && !class_exists( 'Wpeventin_Pro' ))){ 
                    echo Helper::get_pro();
                } else {
            ?>
                <div class="etn-meta">
                    <textarea class="etn-form-control msg-control-box" id="<?php echo esc_attr( $key ); ?>" rows="<?php echo esc_attr( $rows ); ?>" cols="<?php echo esc_attr( $cols ); ?>" name="<?php echo esc_attr( $key ); ?>" placeholder="<?php echo !empty($item['placeholder']) ? esc_attr( $item['placeholder'] ) : ''; ?>"><?php echo Helper::render( $value ) ?></textarea>
                </div>
            <?php
                } 
            ?>
        </div>
        <?php
    }

    public function get_wp_editor( $item, $key ) {

        $rows  = 14;
        $cols  = 50;
        $value = '';
        $class = $key;

        if ( isset( $item['settings'] ) && is_array( $item['settings'] ) ) {
            $settings = $item['settings'];
        }

        if ( isset( $item['value'] ) ) {
            $value = get_post_meta( get_the_ID(), $key, true );
        }

        if ( isset( $item['attr'] ) ) {
            $rows  = isset( $item['attr']['row'] ) && $item['attr']['row'] != '' ? $item['attr']['row'] : 14;
            $cols  = isset( $item['attr']['col'] ) && $item['attr']['col'] != '' ? $item['attr']['col'] : 50;
            $class = isset( $item['attr']['class'] ) && $item['attr']['class'] != '' ? $item['attr']['class'] . ' etn_event_meta_field ' : 'etn_event_meta_field ';
        }

        ?>
		<div class="<?php echo esc_attr( $class ); ?> form-group">
			<div class="etn-label">
				<label for="<?php echo esc_attr( $key ); ?>"> <?php echo esc_html( $item['label'] ); ?></label>
				<div class="etn-desc">  <?php echo esc_html( $item['desc'] ); ?>  </div>
			</div>
			<div class="etn-meta">
				<?php
				wp_editor( $value, $key, $settings );
				?>
			</div>
		</div>
        <?php
    }

}
