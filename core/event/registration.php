<?php

namespace Etn\Core\Event;

use Etn\Utils\Helper;

defined( "ABSPATH" ) or die();

class Registration {

	use \Etn\Traits\Singleton;

	/**
	 * Call all necessary hook
	 */
	public function init() {
		add_action( 'wp_loaded', [$this, 'registration_step_two'] );
	}

	/**
	 * Store attendee report
	 */
	public function registration_step_two() {
		$this->set_purchase_session();
		
		if ( isset( $_POST['ticket_purchase_next_step'] ) && $_POST['ticket_purchase_next_step'] === "two" ) {
			// Seat plan max purchase validation.
			$event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : 0;
			$selected_seats = isset( $_POST['selected_seats'] ) ? $_POST['selected_seats'] : [];
			$permalink = get_permalink( $event_id );
			$ticket_variations = get_post_meta( $event_id, 'etn_ticket_variations', true );
			
			$errors = 0;
			

			// if ( $ticket_variations ) {
			// 	foreach( $ticket_variations as $variation ) {
			// 		$var_name = $variation['etn_ticket_name'];
			// 		$max_ticket = isset( $variation['etn_max_ticket'] ) ? $variation['etn_max_ticket']: 0;
			// 		$total_variation = ! empty( $selected_seats[ $var_name ] ) ? count( explode(',', $selected_seats[$var_name]) ) : 0;

			// 		if ( $total_variation > $max_ticket ) {
			// 			$errors += 1;
			// 		}
			// 	}
			// }

			if ( $errors > 0 ) {
				$permalink = add_query_arg( ['etn_errors' => [
					'seat_limit_error'	=> __( 'You can not select more than the ticket purchase limit', 'eventin' ),
				]], $permalink );

				wp_redirect( $permalink );
				exit;
			}
			
			
				$post_arr          = $_SESSION['etn_cart_session'];
				$check             = wp_verify_nonce( $post_arr['ticket_purchase_next_step_two'], 'ticket_purchase_next_step_two' );
				$settings          = Helper::get_settings();
				$include_phone     = !empty( $settings["reg_require_phone"] ) ? true : false;
				$include_email     = !empty( $settings["reg_require_email"] ) ? true : false;
				$reg_form_template = \Wpeventin::core_dir() . "attendee/views/registration/attendee-details-form.php";

				// check if WPML is activated
				if( class_exists('SitePress') && function_exists('icl_object_id') ){
						global $sitepress;
						$event_id = $post_arr["event_id"];
						$trid = $sitepress->get_element_trid($event_id);
						$post_arr["event_id"] = $sitepress->get_original_element_id($event_id, 'post_etn');
						$post_arr["lang_event_id"] = $event_id;
				}

				if ( file_exists( $reg_form_template ) ) {
						// for compatibility with deposit plugin: check two variables are exist in request. if exist, so deposit is running
						$deposit_enabled      = ( isset( $post_arr['wc_deposit_option'] ) && $post_arr['wc_deposit_option'] === 'yes' ) ? 1 : 0;
						$deposit_payment_plan = isset( $post_arr['wc_deposit_payment_plan'] ) ? absint( $post_arr['wc_deposit_payment_plan'] )  : 0;
						include_once $reg_form_template;
				}
		}

		return false;
	}

	/**
	 * Set purchase session data.
	 *
	 * @return  void
	 */
	private function set_purchase_session() {
		$event_id 			= ! empty( $_POST['event_id'] ) ? $_POST['event_id'] : [];
		$ticket_quantity 	= ! empty( $_POST['ticket_quantity'] ) ? $_POST['ticket_quantity'] : [];
		$ticket_variations  = get_post_meta( $event_id, 'etn_ticket_variations', true );
		$seat_plan          = get_post_meta( $event_id, 'seat_plan_settings', true );
		$total_price = 0;
		$total_ticket = 0;

		$session_data = filter_input_array( INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS );
		unset($session_data['ticket_quantity']);

		if ( ! $ticket_quantity ) {
			return;
		}

		$variations = [];

		foreach ( $ticket_variations as $key => $ticket ) {
			$ticket_name = ! empty( $ticket['etn_ticket_name'] ) ? $ticket['etn_ticket_name'] : '';
			if( $seat_plan ){
                $quantity   = ! empty( $ticket_quantity[$key] ) ? $ticket_quantity[$key]: 0;
            } else {
                $quantity   = ! empty( $ticket_quantity[$ticket_name] ) ? $ticket_quantity[$ticket_name]: 0;
            }
			$price 		= !empty( $ticket['etn_ticket_price'] ) ? $ticket['etn_ticket_price'] : 0; 

			if ( $quantity ) {
				$total_price += $quantity * $price;
				$total_ticket += $quantity;
				$session_data['ticket_quantity'][] 	= $quantity;
				$session_data['ticket_price'][] 	= $price;
				$session_data['ticket_name'][] 		= $ticket['etn_ticket_name'];
				$session_data['ticket_slug'][] 		= $ticket['etn_ticket_slug'];

				$variation = [
					'etn_ticket_slug' => $ticket['etn_ticket_slug'],
					'etn_ticket_name' => $ticket['etn_ticket_name'],
					'ticket_price' 	  => $price,
					'etn_ticket_qty'  => $quantity,
				];

				$variations[] = $variation;
			}
			
		}

		// Set session data.
		$session_data['variation_picked_total_qty'] = $total_ticket;
		$session_data['etn_total_qty'] = $total_ticket;
		$session_data['etn_total_price'] = $total_price;
		$session_data['ticket_variations'] = $variations;

		if ( session_status() === PHP_SESSION_NONE ) {
		    session_start();
		}

		if ( isset( $_SESSION['etn_cart_session'] ) ) {
			unset( $_SESSION['etn_cart_session'] );
		}

		$_SESSION['etn_cart_session'] = $session_data;
	}
}
