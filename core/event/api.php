<?php

namespace Etn\Core\Event;

use WP_Error;
use WP_Query;
use \Etn\Utils\Helper;

defined( 'ABSPATH' ) || exit;

class Api extends \Etn\Base\Api_Handler {

	/**
	 * define prefix and parameter patten
	*
	* @return void
	*/
	public function config() {
		$this->prefix = 'event';
		$this->param  = ''; // /(?P<id>\w+)/
	}

	/**
	 * get user profile when user is logged in
	* @API Link www.domain.com/wp-json/eventin/v1/events/
	* @return array status_code, messages, content
	*/
	public function get_events() {

		$status_code     = 0;
		$messages        = $content        = [];
		$translated_text = ['see_details_text' => esc_html__( 'See Details', 'eventin' )];
		$request         = $this->request;

		if ( ! empty( $request['id'] ) && is_numeric( $request['id'] ) ) {
			// request for a single event
			$event_id        = $request['id'];
			$event           = (array) get_post( $event_id ); // obj
			$event_meta      = get_post_meta( $event_id ); // array
			$serialized_meta = ["etn_event_schedule", "etn_event_socials", "etn_ticket_variations"];
			
			// prepare event meta
			foreach ( $event_meta as $key => $val ) {

				if ( is_array( $val ) ) {
					$event_meta[$key] = $val[0];
				}

				if ( in_array( $key, $serialized_meta ) ) {
					$event_meta[$key] = maybe_unserialize( $event_meta[$key] );
				}

				
        		
				if ( 'etn_event_speaker' == $key ) {
					$speaker   = get_post_meta( $event_id, 'etn_event_speaker', true );
					if ( $speaker && is_array( $speaker ) ) {
						$speaker_rterms = get_the_terms( $speaker[0], 'etn_speaker_category' );
						if ( ! empty( $speaker_rterms ) ){
							// get the first term
							$speaker_rterms    = array_shift( $speaker_rterms );
							$speaker = $speaker_rterms->slug;
						}
					}

					$event_meta[$key] = $speaker;
				}

				if ( 'etn_event_organizer' == $key ) { 
					$organizer = get_post_meta( $event_id, 'etn_event_organizer', true );

					if ( $organizer && is_array( $organizer ) ) {
						$organizer_terms = get_the_terms( $organizer[0], 'etn_speaker_category' );
						if ( ! empty( $organizer_terms ) ){
							// get the first term
							$organizer_terms = array_shift( $organizer_terms );
							$organizer = $organizer_terms->slug;
						}
					}

					$event_meta[$key] = $organizer;
				}

				if ( 'certificate_template' === $key ) {
					$event_meta['certificate_template'] = get_post_meta( $event_id, 'etn_event_certificate', true );
				}

				if ( 'virtual' === $key ) {
					$event_meta['_virtual'] = 'yes';
				}

				if ( 'external_link' === $key ) {
					$event_meta['event_external_link'] = get_post_meta( $event_id, 'external_link', true );
				}

				if ( 'etn_event_location' === $key ) {
					$event_meta['etn_event_location'] = get_post_meta( $event_id, 'etn_event_location', true );
				}

				if ( 'event_etzone' === $key ) {
					$event_meta['event_timezone'] = get_post_meta( $event_id, 'event_etzone', true );
				}
			}

			$event_type			   = get_post_meta( $event_id, 'event_type', true );

			$address 			   = 'offline' === $event_type && isset( $location['address'] ) ? $location['address'] : '';

			$content['etn_category']              = $categories = array_keys( Helper::get_event_category( $event_id ) );
			$content['etn_tags']                  = $tags       = array_keys( Helper::get_event_tag( $event_id ) );
			$content['etn_location']              = $address;



			$event_meta['etn_event_logo_url']     = ( isset( $event_meta['etn_event_logo'] ) && ! empty( $event_meta['etn_event_logo'] ) ) ? wp_get_attachment_url( $event_meta['etn_event_logo'] ) : '';
			$event_meta['_thumbnail_id_url']      = ( isset( $event_meta['_thumbnail_id'] ) && ! empty( $event_meta['_thumbnail_id'] ) ) ? wp_get_attachment_url( $event_meta['_thumbnail_id'] ) : '';
			$event_meta['banner_bg_image_url']    = ( isset( $event_meta['banner_bg_image'] ) && ! empty( $event_meta['banner_bg_image'] ) ) ? wp_get_attachment_url( $event_meta['banner_bg_image'] ) : '';
			$event_meta['selected_etn_category']  = wp_get_post_terms( $event_id, 'etn_category', ['fields' => 'ids'] );
			$event_meta['selected_etn_tags']      = wp_get_post_terms( $event_id, 'etn_tags', ['fields' => 'ids'] );
			$event_meta['selected_etn_location']  = wp_get_post_terms( $event_id, 'etn_location', ['fields' => 'ids'] );
			$content                              = $event + $event_meta;
			$content['etn_event_recurrence']      = get_post_meta( $event_id, 'etn_event_recurrence', true );
			$content['etn_event_faq']             = get_post_meta( $event_id, 'etn_event_faq', true );
			$content['etn_faq']                   = !empty( $content['etn_event_faq'] ) ? "yes" : "no";
			$content['etn_recurrence_timestamps'] = get_post_meta( $event_id, 'etn_recurrence_timestamps', true );
			
			return [
				'status_code' => 200,
				'messages'    => [
					'success' => esc_html__( 'Event data retrieve successful', 'eventin' ),
				],
				'content'     => $content,
			];

		} else {

		// request for all events, may include filtering

		// pass input field for checking empty value
		$inputs_field = [
			['name' => 'month', 'required' => true, 'type' => 'number'],
			['name' => 'year', 'required' => true, 'type' => 'number'],
			['name' => 'display', 'required' => false, 'type' => 'text'],
			['name' => 'endDate', 'required' => false, 'type' => 'text'],
			['name' => 'startTime', 'required' => false, 'type' => 'text'],
		];

		$validation = Helper::input_field_validation( $request, $inputs_field );

		if ( ! empty( $validation['status_code'] ) && $validation['status_code'] == true ) {
			$input_data    = $validation['data'];
			$month         = sprintf( "%02d", $input_data['month'] );
			$year          = $input_data['year'];
			$display       = ! empty( $input_data['display'] ) ? $input_data['display'] : '';
			$endDate       = ! empty( $input_data['endDate'] ) ? filter_var( $input_data['endDate'], FILTER_VALIDATE_BOOLEAN ) : false;
			$startTime     = ! empty( $input_data['startTime'] ) ? filter_var( $input_data['startTime'], FILTER_VALIDATE_BOOLEAN ) : false;
			$start         = $request['start'];
			$end           = $request['end'];
			$post_parent   = ! empty( $request['postParent'] ) ? $request['postParent'] : 'child';
			$post_id       = ! empty( $request['postID'] ) ? $request['postID'] : 0;
			$selected_cats = ! empty( $request['selectedCats'] ) ? $request['selectedCats'] : [];
			$event_list    = Helper::get_events_by_date( $month, $year, $display, $endDate, $startTime, $start, $end, $post_parent, $post_id, $selected_cats );

			if ( ! empty( $event_list ) ) {
				$status_code         = 1;
				$content             = $event_list;
				$messages['success'] = 'success';
			} else {
				$messages['error'] = 'error';
			}

		} else {
			$status_code = $validation['status_code'];
			$messages    = $validation['messages'];
		}

		return [
			'status_code'     => $status_code,
			'messages'        => $messages,
			'content'         => $content,
			'translated_text' => $translated_text,
		];
	}

	}

	/**
	 * @description get settings data  through api
	* @API Link www.domain.com/wp-json/eventin/v1/event/settings
	* @return array
	*/
	public function get_settings() {
		$status_code = 0;
		$messages    = $content    = [];
		$request     = $this->request;
		$settings    = etn_get_option();

		if ( ! is_admin() && ! current_user_can( 'manage_options' ) ) {
			if ( ! wp_verify_nonce( $this->request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
				$messages[] = esc_html__( 'Nonce is not valid! Please try again.', 'eventin' );
			} else {
				if ( ! empty( $settings ) ) {
					$content['settings'] = $settings;
				}
			}
		} else {
			$messages[] = esc_html__( 'You haven\'t authorization permission to update settings.', 'eventin' );
		}

		$sample_date      = strtotime( date( 'd' ) . " " . date( 'M' ) . " " . date( 'Y' ) );
		$date_formats     = Helper::get_date_formats();
		$get_date_formats = [];

		if ( is_array( $date_formats ) ) {
			foreach ( $date_formats as $key => $date_format ) {
				array_push( $get_date_formats, date( $date_format, $sample_date ) );
			}
		}

		return [
			'status_code'      => $status_code,
			'messages'         => $messages,
			'date_format_list' => $get_date_formats,
			'content'          => $content,
		];
	}

	/**
	 * save settings data through api
	*
	* @return array
	*/
	public function post_settings() {
		$status_code = 0;
		$messages    = $content    = [];
		$request     = json_decode( $this->request->get_body(), true );

		if ( ! is_admin() && ! current_user_can( 'manage_options' ) ) {

			if ( ! wp_verify_nonce( $this->request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
				$messages[] = esc_html__( 'Nonce is not valid! Please try again.', 'eventin' );
			} else {
				if ( isset( $request ) && ! empty( $request ) ) {
					$status_code                           = 1;
					$all_settings                          = get_option( 'etn_event_options', [] );
					$settings                              = $request;
					$all_settings['events_per_page']       = isset( $settings['events_per_page'] ) ? absint( $settings['events_per_page'] ) : 10;
					$all_settings['date_format']           = isset( $settings['date_format'] ) ? $settings['date_format'] : "";
					$all_settings['time_format']           = isset( $settings['time_format'] ) ? $settings['time_format'] : "";
					$all_settings['etn_primary_color']     = isset( $settings['etn_primary_color'] ) ? $settings['etn_primary_color'] : "";
					$all_settings['etn_secondary_color']   = isset( $settings['etn_secondary_color'] ) ? $settings['etn_secondary_color'] : "";
					$all_settings['attendee_registration'] = isset( $settings['attendee_registration'] ) ? $settings['attendee_registration'] : "";
					$all_settings['sell_tickets']          = isset( $settings['sell_tickets'] ) ? $settings['sell_tickets'] : "";
					update_option( 'etn_event_options', $all_settings );
				}
			}
		} else {
			$messages[] = esc_html__( 'You haven\'t authorization permission to update settings.', 'eventin' );
		}

		return [
			'status_code' => $status_code,
			'messages'    => $messages,
			'content'     => $content,
		];
	}

	/**
	 * save email data through api from onboard
	*
	* @return array
	*/
	public function post_onboard_mail() {
		$status_code = 0;
		$messages    = $content    = [];
		$request     = $this->request;
		$email       = ! empty( $request['email'] ) ? $request['email'] : '';
		$data        = [];

		if ( $email ) {
			$status_code   = 1;
			$data['email'] = $email;
			$url           = '';
			wp_remote_post( $url, ['body' => $data] );
			$content['email'] = $request['email'];
		}

		return [
			'status_code' => $status_code,
			'messages'    => $messages,
			'content'     => $content,
		];
	}

	/**
	 * Event lists route to get certain user events
	*
	* @return  array  Event lists for certain user
	*/
	public function get_list() {
		$request        = $this->request;
		$user_id        = isset( $request['user_id'] ) ? intval( $request['user_id'] ) : 0;
		$posts_per_page = isset( $request['posts_per_page'] ) ? intval( $request['posts_per_page'] ) : 20;
		$paged          = isset( $request['paged'] ) ? intval( $request['paged'] ) : 1;
		$group_id       = isset( $request['group_id'] ) ? intval( $request['group_id'] ) : 0;
		$type           = isset( $request['type'] ) ? $request['type'] : "";

		$args = [
			'post_type'      => 'etn',
			'post_status'    => 'any',
			'posts_per_page' => $posts_per_page,
			'paged'          => $paged,
		];

		if ( $group_id ) {
			$args['meta_query'] = [
				[
					'key'   => 'etn_bp_group_' . $group_id,
					'value' => $group_id,
				],
			];
		}

		// get latest events
		if ( $type == "upcoming" ) {
			$args['meta_query'] = [
				[
				'key'     => 'etn_start_date',
				'value'   => date( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
				],
			];
		}
		
		$user = get_userdata( $user_id );

		if ( ! user_can( $user, 'manage_options' ) ) {
			$args['author'] = $user_id;
		}

		$events = [];
		$items  = new WP_Query( $args );

		if ( $items->posts ) {
			foreach ( $items->posts as $item ) {
				$events[] = $this->prepare_event( $item );
			}
		}
		return [
			'total_pages' => $items->max_num_pages,
			'total_items' => $items->found_posts,
			'items'       => $events,
		];
	}

	/**
	 * Prepare event for respose
	*
	* @param   integer  $event_id  Event id
	*
	* @return  array   Event data
	*/
	private function prepare_event( $event ) {
		/**
		 * Event meta data
		 */
		$sold_tickets          = get_post_meta( $event->ID, 'etn_total_sold_tickets', true );
		$avaiilable_tickets    = get_post_meta( $event->ID, 'etn_total_avaiilable_tickets', true );
		$start_date            = get_post_meta( $event->ID, 'etn_start_date', true );
		$permalink             = get_permalink( $event->ID );
		$event_image           = wp_get_attachment_url( get_post_thumbnail_id( $event->ID ) );
		$locations             = wp_get_post_terms( $event->ID, 'etn_location', ['fields' => 'all'] );
		$selected_etn_location = is_array( $locations ) ? array_column( $locations, 'name' ) : [];
		$location              = get_post_meta( $event->ID, 'etn_event_location', true );
		$location_type         = get_post_meta( $event->ID, 'etn_event_location_type', true );
		$location              = 'new_location' === $location_type ? $selected_etn_location : $location;
		$event_type			   = get_post_meta( $event->ID, 'event_type', true );

		$address 			   = 'offline' === $event_type && isset( $location['address'] ) ? $location['address'] : '';

		$event_banner 		  = get_post_meta( $event->ID, 'event_banner', true );

		$event_image		  = $event_banner ?: $event_image;
		$virtual 			  = get_post_meta( $event->ID, '_virtual', true ) ?: get_post_meta( $event->ID, 'virtual', true ); 

		/**
		 * Get event data and prepare for response
		 */
		return [
			'id'                      => $event->ID,
			'title'                   => $event->post_title,
			'date'                    => date_i18n( get_option( 'date_format' ), strtotime( $start_date ) ),
			'location'                => $address,
			'etn_event_location_type' => $location_type,
			'image'                   => $event_image,
			'permalink'               => $permalink,
			'availbe_seats'           => intval( $avaiilable_tickets ),
			'booked_seats'            => intval( $sold_tickets ),
			'type'                    => $this->get_event_type( $event->ID ),
			'event_type'			  => $event_type,
			'virtual'				  => $virtual,
		];
	}

	/**
	 * Get single event by event id
	*
	* @return  Array single event array
	*/
	public function get_single_event() {
		$event_id = isset( $this->request['id'] ) ? intval( $this->request['id'] ) : false;
		$event    = get_post( $event_id );

		if ( ! $event_id ) {
			return new WP_Error( 'event_id_err', __( 'Please enter a event id', 'eventin' ) );
		}

		if ( ! $event ) {
			return new WP_Error( 'event_not_found', __( 'Event not found.', 'eventin' ) );
		}

		return $this->prepare_event( $event );
	}

	/**
	 * Delete one or more events route
	*
	* @return  array
	*/
	public function delete_events() {
		$request   = $this->request;
		$event_ids = isset( $request['ids'] ) ? explode( ',', $request['ids'] ) : [];

		if ( empty( $event_ids ) ) {
			return new WP_Error( 'event_id_required', __( 'Event id is required.', 'eventin' ) );
		}

		foreach ( $event_ids as $event_id ) {
			$event	 = get_post( $event_id );
			$user_id = get_current_user_id();

			if ( ! current_user_can( 'manage_options' ) && $event->post_author != $user_id ) {
				return new WP_Error( 'unauthorized', __( 'Unauthorized user. Sorry you are not allowed to do that', 'eventin' ), [ 'status' => 403 ] );
			}
	
			if ( ! $event ) {
				return new WP_Error( 'event_not_found', __( 'Event not found.', 'eventin' ) );
			}

			if ( is_wp_error( wp_delete_post( $event_id, true ) ) ) {
				return new WP_Error( 'unable_to_delete', __( 'Unable to delete one or more event. Please try again.', 'eventin' ) );
			}
		}

		return [
			'status_code' => 200,
			'message'     => __( 'Successfully deleted', 'eventin' ),
		];
	}

	/**
	 * Get event ticket details
	*
	*/
	public function get_seatmap_details() {

		$request            = $this->request;
		$event_id           = ! empty( $request['id'] ) ? intval( $request['id'] ) : null;
		$ticket_details     = get_post_meta( $event_id, 'etn_ticket_variations', true );
		$seat_plan          = get_post_meta( $event_id, 'seat_plan', true );
		$seat_plan_settings = get_post_meta( $event_id, 'seat_plan_settings', true );
		$ticket_availability= get_post_meta( $event_id, 'etn_ticket_availability', true );
		$etn_booked_seats   = get_post_meta( $event_id, '_etn_seat_unique_id', true );
		$post				= get_post( $event_id );

		if ( !empty($etn_booked_seats) ) {
			$etn_booked_seats = explode(",",$etn_booked_seats);
		}
		
		$ticket_quantity    = 100;

		$prices             = [];
		if ( ! empty( $ticket_details ) ) {
			foreach ( $ticket_details as $key => $value ) {
				$prices[$key] = [
					'ticket_name'     => $value['etn_ticket_name'],
					'etn_min_ticket'  => !empty($value['etn_min_ticket']) ? $value['etn_min_ticket'] : 0,
					'etn_max_ticket'  => !empty($value['etn_max_ticket']) ? $value['etn_max_ticket'] : 0,
					'ticket_price'    => !empty($value['etn_ticket_price']) ? (float) $value['etn_ticket_price'] : 0,
					'ticket_quantity' => !empty($value['etn_avaiilable_tickets']) ? (int) $value['etn_avaiilable_tickets'] : $ticket_quantity,
					'ticket_slug'     => $value['etn_ticket_slug']
				];
			}
		}

		if ( ! empty( $seat_plan ) ) {
			$chair_id = 1;
			foreach ($seat_plan as $key => &$seat) {
				if ( ! empty( $seat['chairs'] ) ) {
					foreach( $seat['chairs'] as &$chair ) {
						if ( ! empty( $chair['id'] ) ) {
							continue;
						}

						$chair['id'] = $chair_id;
						$chair_id++;
					}
				}
			}
		}

		$data = [
			'success'     => 1,
			'status_code' => 200,
			'data'        => [
				'price'              => $prices,
				'seat_plan'          => $seat_plan,
				'seat_plan_settings' => $seat_plan_settings,
				'id'                 => $etn_booked_seats,
				'link'         		 => get_the_permalink( $event_id ),
				'title'				 => $post->post_title,
				'location'			 => get_post_meta( $event_id, 'etn_event_location', true ),			
				'start_date'	     => get_post_meta( $event_id, 'etn_start_date', true ),			
				'start_time'	     => get_post_meta( $event_id, 'etn_start_time', true ),			
			],
		];

		return rest_ensure_response( $data );
	}

	/**
	 * save seat mapping data for an event
	*
	* @return array
	*/
	public function post_seatmap() {
		if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'unauthorized', __( 'Unauthorized user. Sorry you are not allowed to do that', 'eventin' ), [ 'status' => 403 ] );
        }
		
		$status_code        = 0;
		$messages           = [esc_html__( 'Something is Wrong', 'eventin' )];
		$request            = $this->request;
		$event_id           = ! empty( $request['event_id'] ) ? intval( $request['event_id'] ) : 0;
		$seat_plan          = ! empty( $request['seat_plan'] ) ?  $request['seat_plan']  : [];
		$seat_plan_settings = ! empty( $request['seat_plan_settings'] ) ? $request['seat_plan_settings']  : [];
		if ( ! empty( $seat_plan ) ) {
			$chair_id = 1;
			foreach ($seat_plan as $key => &$seat) {
				if ( ! empty( $seat['chairs'] ) ) {
					foreach( $seat['chairs'] as &$chair ) {
						$chair['id'] = $chair_id;
						$chair_id++;
					}
				}
				
				$seat['id'] = $key;
			}
		}

		if ( $event_id !== 0 ) {
			$status_code = 1;
			update_post_meta( $event_id, 'seat_plan', $seat_plan );
			update_post_meta( $event_id, 'seat_plan_settings', $seat_plan_settings );
			$messages = [esc_html__( 'Event Seat Mapping has been saved successfully', 'eventin' )];
		}else{
			$messages = [esc_html__( 'Event ID not found', 'eventin' )];
		}

		$data = [
			'status_code' => $status_code,
			'messages'    => $messages,
		];

		return rest_ensure_response( $data );
	}

	/**
	 * save on-bording email using fluent CRM link
	*
	* @return array
	*/
	public function post_subscribe_email() {
		$status_code = 0;
		$messages    = $content    = [];
		$request     = json_decode( $this->request->get_body(), true );

		if ( ! is_admin() && ! current_user_can( 'manage_options' ) ) {		

			if ( ! empty( $request[ 'subscriber_email' ] ) ) {
				$status_code = 1;

				$body = [
					'email' => $request[ 'subscriber_email' ]
				];

				$url_fluent = 'https://themewinter.com/?fluentcrm=1&route=contact&hash=4358b0a5-2c38-447e-bdf2-8d4b0a3a464f';

				wp_remote_post( $url_fluent, [ 'body' => $body ] );

			}

		} else {
			$messages[] = esc_html__( 'You haven\'t authorization permission to update settings.', 'eventin' );
		}

		return [
			'status_code' => $status_code,
			'messages'    => $messages,
			'content'     => $content,
		];
	}

	/**
	 * Get event type
	 *
	 * @param   integer  $post_id
	 *
	 * @return  string
	 */
	private function get_event_type( $post_id ) {
		$is_recurring = get_post_meta( $post_id, 'recurring_enabled', true );

		if ( 'yes' == $is_recurring ) {
			return 'recurring_parent';
		} else if ( has_post_parent( $post_id ) ) {
			return 'recurring_child';
		} else {
			return 'simple';
		}
	}

}

new Api();
