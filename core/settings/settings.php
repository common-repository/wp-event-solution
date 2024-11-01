<?php

namespace Etn\Core\Settings;

defined( 'ABSPATH' ) || exit;

class Settings {

    use \Etn\Traits\Singleton;

    private $key_settings_option;

    public function init() {
        $this->key_settings_option = 'etn_event_options';
        add_action( 'after_setup_theme', [$this, 'register_actions']  );
    }

    public function get_settings_option( $key = null, $default = null ) {

        if ( $key != null ) {
            $this->key_settings_option = $key;
        }

        return get_option( $this->key_settings_option );
    }

    /**
     * Save Settings Form Data
     * @since 1.0.0 
     *
     * @return void
     */
    public function register_actions() {
		$post_arr = filter_input_array( INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS );
		if ( isset( $_POST['etn_settings_page_action'] ) ) {
			if ( !check_admin_referer( 'etn-settings-page', 'etn-settings-page' ) ) {
					return;
			}
			// empty field discard logic
			if( is_array($post_arr) && !empty($post_arr) ){
					if( array_key_exists('attendee_extra_fields', $post_arr) ){
							$attendee_extra_fields = $post_arr['attendee_extra_fields'];

							$special_types = [
									'date',
									'radio',
									'checkbox',
							];

							$new_attendee_extra_fields = array(); // for storing 0,1,2... based index.

							$duplicate_label_arr = array();

							foreach( $attendee_extra_fields as $index => $attendee_extra_field ) {

									// if label/type empty then discard this index.
								if( !isset( $attendee_extra_field['label'] ) || empty( $attendee_extra_field['label'] ) ||
										!isset( $attendee_extra_field['type'] ) || empty( $attendee_extra_field['type'] ) ) {
										unset( $attendee_extra_fields[ $index ] );
								}
								else {
										// change same label to label-2, label-3...
										$user_typed_label = $attendee_extra_field['label'];
										if( in_array( $user_typed_label, $duplicate_label_arr ) ){
												$label_count_arr = array_count_values( $duplicate_label_arr );
												$attendee_extra_field['label'] = $user_typed_label . '-' . ( $label_count_arr[$user_typed_label]+1 );
										}
										$duplicate_label_arr[$index] = $user_typed_label;

										$selected_type = $attendee_extra_field['type'];
										// no need placeholder text for date, radio, checkbox etc.
										if( in_array( $selected_type, $special_types ) ){
												$attendee_extra_field['place_holder'] = ''; // change placeholder value to empty
										}

										// check whether it is radio, if radio then unset all empty radio label.
										if( $selected_type == 'radio' ){
												if( isset( $attendee_extra_field['radio'] ) && count( $attendee_extra_field['radio'] ) >= 2 ){

														$new_radio_arr = [];
														foreach( $attendee_extra_field['radio'] as $radio_index => $radio_val ){
																if( empty( $radio_val ) ){
																		unset( $attendee_extra_field['radio'][$radio_index] );
																} else {
																		// for maintaing 0,1,2... based index.
																		array_push( $new_radio_arr, $radio_val );
																}
														}
														$attendee_extra_field['radio'] = $new_radio_arr;

														// after discarding empty radio label check there exists minimum 2 radio label.
														if( count( $attendee_extra_field['radio'] ) < 2 ){
																unset($attendee_extra_field); // minimium 2 radio label required, else unset
														}

												} else {
														unset($attendee_extra_field); // initialy minimium 2 radio label required, else unset.
												}
										} else {
												// radio index can only stay if selected type is radio, otherwise discard.
												unset( $attendee_extra_field['radio'] );
										}
										// radio logic finished.


										// check whether it is checkbox, if checkbox then unset all empty checkbox label.
										if( $selected_type == 'checkbox' ){
												if( isset( $attendee_extra_field['checkbox'] ) && count( $attendee_extra_field['checkbox'] ) >= 1 ){

														$new_checkbox_arr = [];
														foreach( $attendee_extra_field['checkbox'] as $checkbox_index => $checkbox_val ){
																if( empty( $checkbox_val ) ){
																		unset( $attendee_extra_field['checkbox'][$checkbox_index] );
																} else {
																		// for maintaing 0,1,2... based index
																		array_push( $new_checkbox_arr, $checkbox_val ); 
																}
														}
														$attendee_extra_field['checkbox'] = $new_checkbox_arr;
														// after discarding empty checkbox label check there exists minimum 1 checkbox label.
														if( count( $attendee_extra_field['checkbox'] ) < 1 ){
																unset($attendee_extra_field); // minimium 1 checkbox label required, else unset
														}

												} else {
														unset($attendee_extra_field); // initialy minimium 1 checkbox label required, else unset
												}
										} else {
												// checkbox index can only stay if selected type is checkbox, otherwise discard
												unset( $attendee_extra_field['checkbox'] );
										}
										// checkbox logic finished.

										if( !empty( $attendee_extra_field ) ){
												array_push( $new_attendee_extra_fields, $attendee_extra_field );
										}
								}
							}

					$post_arr['attendee_extra_fields'] = $this->migrate_extra_field( $attendee_extra_fields );

					}
			}

			$data            = \Etn\Base\Action::instance()->store( -1, $post_arr );
			$check_transient = get_option( 'zoom_user_list' );

			if ( isset( $post_arr['zoom_api_key'] ) && isset( $post_arr['zoom_secret_key'] ) && $check_transient == false ) {
				// get host list
				// \Etn\Core\Zoom_Meeting\Api_Handlers::instance()->zoom_meeting_user_list();
			}
			return $data;
		}
		else if( !empty( $_POST['etn_addons_action'] ) && "addons_save" == $_POST['etn_addons_action'] ){
			update_option( 'etn_addons_options', $post_arr );
			return true;
		}
		else if( class_exists('EtnFBAddon') &&  !empty( $_POST['import_facebook_events'] ) && "fb_events_save" == $_POST['import_facebook_events'] ){
			update_option( 'etn_facebook_options', $post_arr );
			$result = \EtnFBAddon\Core\Modules\Integrations\Facebook_Events\Admin\ImportEvents::instance()->import_fb_events();
			if(!isset($_SESSION)) { 
				session_start(); 
			}
			$_SESSION["fb_import"] = $result;
			return true;
		}

		return false;
    }


	protected function migrate_extra_field( $extra_fields = [] ) {
        $updated_fields = [];

        if ( ! empty( $extra_fields ) && is_array( $extra_fields ) ) {

            foreach( $extra_fields as $field ) {
                $field_type = ! empty( $field['type'] ) ? $field['type'] : '';
                $required   = ! empty( $field['etn_field_type'] ) ? $field['etn_field_type'] : '';

                $required = 'required' == $required ? true : false;

                switch( $field_type ) {
                    case 'text':
                        $new_field = [
                            'label'             => $field['label'],
                            'field_type'        => $field_type,
                            'placeholder_text'  => $field['place_holder'],
                            'required'          => $required,
                        ];
                        break;
					
					case 'number':
						$new_field = [
							'label'             => $field['label'],
							'field_type'        => $field_type,
							'placeholder_text'  => $field['place_holder'],
							'required'          => $required,
						];
						break;

					case 'date':
						$new_field = [
							'label'             => $field['label'],
							'field_type'        => $field_type,
							'placeholder_text'  => $field['place_holder'],
							'required'          => $required,
						];
						break;
                    
                    case 'radio':
						$field_options			= $field['radio'] ? $field['radio'] : $field['field_options'];
                        $new_field = [
                            'label'             => $field['label'],
                            'field_type'        => $field_type,
                            'placeholder_text'  => $field['place_holder'],
                            'field_options'     => $this->prepare_field_options( $field_options ),
                            'required'          => $required,
                        ];
                        break;

                        case 'checkbox':
							$field_options			= $field['checkbox'] ? $field['checkbox'] : $field['field_options'];
                            $new_field = [
                                'label'             => $field['label'],
                                'field_type'        => $field_type,
                                'placeholder_text'  => $field['place_holder'],
                                'field_options'     => $this->prepare_field_options( $field_options ),
                                'required'          => $required,
                            ];
                            break;
                }

                $updated_fields[] = $new_field;
            }
        }

		return $updated_fields;
    }

	/**
     * Prepare extra field options
     *
     * @param   array  $options
     *
     * @return  array
     */
    protected function prepare_field_options( $options ) {
        $new_fields = [];

        foreach ( $options as $option ) {
            $new_fields[] = array( 'value' => $option );
        }

        return $new_fields;
    }
}
