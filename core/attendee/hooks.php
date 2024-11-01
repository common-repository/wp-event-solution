<?php

namespace Etn\Core\Attendee;

use \Etn\Core\Attendee\Pages\Attendee_Single_Page;
use Etn\Utils\Helper;

defined( "ABSPATH" ) || exit;

class Hooks {
    use \Etn\Traits\Singleton;

    public $cpt;
    public $action;
    public $base;
    public $settings;
    public $actionPost_type = ['etn-attendee'];

    public function Init() {

        $attendee_module = etn_get_option( 'attendee_registration' ) ? true : false;

        if( $attendee_module ) {
            $this->cpt      = new Cpt();
            $this->action   = new Action();
            $this->settings = new Settings( "etn", "1.0" );

            $this->add_metaboxes();
            $this->add_single_page_template();

            add_action( 'wp_ajax_change_ticket_status', [$this, 'change_ticket_status'] );
            add_action( 'wp_ajax_nopriv_change_ticket_status', [$this, 'change_ticket_status'] );

            // woo thank you page contains key in url so don't show attendee info here. this is for user purchased events
            if ( !isset( $_GET['key'] ) ) {
                add_action( 'woocommerce_order_details_after_order_table', [ $this, 'after_order_table_show_attendee_information' ], 9, 1 );
            }
			if ( !empty($this->actionPost_type) && "etn-attendee"== $this->actionPost_type[0] ) {
				add_action( 'init', [ $this , 'add_manual_attendee'] );
			}
        }

        // woocommerce my account > purchased events sidebar menu related hook
        add_action( 'init', [ $this, 'add_purchased_events_endpoint' ] );
        add_filter( 'query_vars', [ $this, 'purchased_events_query_vars' ], 0 );
        add_filter( 'woocommerce_account_menu_items', [ $this, 'add_purchased_events_link_my_account' ] );
        add_action( 'woocommerce_account_purchased-events_endpoint', [ $this, 'purchased_events_content' ] );

        include_once \Wpeventin::core_dir() . '/attendee/api.php';

        // Add bulk actions.
        add_filter( 'bulk_actions-edit-etn-attendee', [ $this, 'add_bulk_actions' ] );

        add_filter( 'handle_bulk_actions-edit-etn-attendee', [ $this, 'handle_export_bulk_action' ], 10, 3 );
    }

    public function add_metaboxes() {
        // custom post meta
        $attendee_meta = new \Etn\Core\Metaboxs\Attendee_Meta();
        add_action( 'add_meta_boxes', [$attendee_meta, 'register_meta_boxes'] );
        add_action( 'save_post', [$attendee_meta, 'save_meta_box_data'] );
    }

	/**
	 * Save Attendee From Backend
	 */
	public function add_manual_attendee() {
		if ( is_admin() &&
		 	class_exists('Wpeventin_Pro') &&
			\Wpeventin_Pro::version() > '3.3.13' &&
			empty($_POST['etn_attendee_order_id'])) {				
			$sells_engine           = '';
			$post_arr               = filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			
			if ( empty( $post_arr['etn_event_id'] ) ) {
				return true;
			}

			// Save manual attendee
			if ( "woocommerce" == $sells_engine && class_exists( 'Woocommerce' ) ) {
				\Etn_Pro\Core\Attendee\Hooks::instance()->manual_create_order( $post_arr );
			}else{
				\Etn_Pro\Core\Attendee\Hooks::instance()->create_manual_stripe_order( $post_arr );
			}
		}
	}

    public function add_single_page_template() {
        $page = new Attendee_Single_Page();
    }

    /**
     * update ticket status from attendee dashboard
     */
    public function change_ticket_status() {
        $status_code  = 0;
        $messages     = [];
        $content      = [];

        if ( wp_verify_nonce( sanitize_text_field( $_POST['security'] ), 'ticket_status_nonce_value' ) ) {
 
            if ( !current_user_can( 'manage_etn_attendee' ) ) {
                $messages[] = esc_html__( 'Update failed. Try again!', 'eventin' );
            } else {
                $attendee_id    = absint( $_POST['attendee_id'] );
                $ticket_status  = sanitize_text_field( $_POST['ticket_status'] );

                $update_status = update_post_meta( $attendee_id, 'etn_attendeee_ticket_status', $ticket_status );
                if ( $update_status ) {
                    $status_code    = 1;
                    $messages[]     = esc_html__( 'Status updated', 'eventin' );

                    $new_val = 'unused';
                    if ( $ticket_status == 'unused' ) {
                        $new_val = 'used';
                    }

                    $content['new_val']  = $new_val;
                    $content['new_text'] = ucfirst( $ticket_status );

                    $response = [
                        'status_code' => $status_code,
                        'messages'    => $messages,
                        'content'     => $content,
                    ];
                    wp_send_json_success( $response );
                    exit();
                }
            }
        } else {
            $messages[] = esc_html__( 'Update failed. Try again!', 'eventin' );
        }

        $response = [
            'status_code' => $status_code,
            'messages'    => $messages,
            'content'     => $content,
        ];
        wp_send_json_error( $response );
        exit;
    }

    /**
     * adding purchased-events endpoint
     */
    public function add_purchased_events_endpoint() {
        add_rewrite_endpoint( 'purchased-events', EP_ROOT | EP_PAGES );
    }

    /**
     * add extra item purchase-events
     *
     * @param [array] $vars
     * @return array
     */
    public function purchased_events_query_vars( $vars ) {
        $vars[] = 'purchased-events';

        return $vars;
    }

    /**
     * add extra item purchase events in sidebar menu
     * 
     * @param [array] $items
     * @return array
     */
    public function add_purchased_events_link_my_account( $items ) {
        $extra_item = [ 
            'purchased-events' => esc_html__( 'Purchased events', 'eventin' )
        ];

        $split_1 = array_slice( $items, 0, 3 );
        $split_2 = array_slice( $items, 3, count( $items ) );

        $items = $split_1 + $extra_item + $split_2;
        return $items;
    }

    /**
     * view of purchased events page
     */
    public function purchased_events_content() {
        global $wpdb;

        $current_user_id = get_current_user_id();
        $customer_orders = wc_get_orders([
            'customer' => $current_user_id,
            'status'   => array_keys(wc_get_order_statuses()),
            'return'   => 'ids',
        ]);


        $user_events = [];
        foreach ($customer_orders as $order_id) {
            $order          = wc_get_order( $order_id );
            $order_status   = $order->get_status();
            $order_url      = $order->get_view_order_url();
            
            foreach ( $order->get_items() as $item_id => $item ) {
                $product_name  = $item->get_name();
                $event_id      = $item->get_meta('event_id', true);

                if ( !empty( $event_id ) ) {
                    $user_events[ $order_id ][ $event_id ] = [
                        'event_id'     => $event_id,
                        'event_name'   => $product_name,
                        'order_status' => $order_status,
                        'order_id'     => $order_id,
                        'order_url'    => $order_url,
                    ];
                }
            }
        }

        if (!empty($user_events)) {
            include_once \Wpeventin::core_dir() . "attendee/views/purchaser/purchased-events.php";
        } else {
            echo esc_html__('No event has been purchased yet!', 'eventin');
        }
    }

    /**
     * show attendee information in woo order details
     *
     * @param [type] $order
     * @return void
     */
    public function after_order_table_show_attendee_information( $order ) { 
        foreach ( $order->get_items() as $item_id => $item ) {
            $event_id = !is_null( $item->get_meta( 'event_id', true ) ) ? $item->get_meta( 'event_id', true ) : "";

            if ( !empty( $event_id ) ) {
                $args = array(
                    'post_type'     => 'etn-attendee',
                    'post_status'   => 'publish',
                    'meta_key'      => 'etn_attendee_order_id',
                    'meta_value'    => $order->get_id(),
                    'numberposts'   => -1
                );
                
                $attendees = get_posts($args);
                if( count( $attendees ) > 0 ) {
                    $settings        = Helper::get_settings();
                    $include_email   = !empty( $settings["reg_require_email"] ) ? true : false;
                    $include_phone   = !empty( $settings["reg_require_phone"] ) ? true : false;
    
                    $base_url               = home_url( );
                    $attendee_cpt           = new \Etn\Core\Attendee\Cpt();
                    $attendee_endpoint      = $attendee_cpt->get_name();
                    $action_url             = $base_url . "/" . $attendee_endpoint;
    
                    $ticket_download_link   = $action_url . "?etn_action=". urlencode('download_ticket') ."&attendee_id="; 
                    $edit_information_link  = $action_url . "?etn_action=" . urlencode( 'edit_information' ) . "&attendee_id=";
    
                    include_once \Wpeventin::core_dir() . "attendee/views/purchaser/attendee-details.php";
                }
            }
        }
    }


    /**
     * attendee add mechanism
     */
    public function add_attendee_data( $sells_engine = 'woocommerce' , $attendee_data = array() ) {
        if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}

		if ( ! empty( $_POST['sells_engine'] ) && 'woocommerce' == $_POST['sells_engine'] ) {
			$ticket_purchase_next_step = ! empty( $_POST['ticket_purchase_next_step'] ) ? $_POST['ticket_purchase_next_step'] : '';
		}else{
			$ticket_purchase_next_step = isset( $attendee_data['ticket_purchase_next_step'] ) ? $attendee_data['ticket_purchase_next_step'] : '';
		}

		if ( isset( $ticket_purchase_next_step ) && $ticket_purchase_next_step === "three" ) {
			$post_arr =  $sells_engine !== 'stripe' ? filter_input_array( INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS ) : $attendee_data;

            $post_arr = wp_parse_args( $_SESSION['etn_cart_session'], $post_arr );

			$check    = wp_verify_nonce( $post_arr['ticket_purchase_next_step_three'], 'ticket_purchase_next_step_three' );

			if ( $check && !empty( $post_arr['attendee_info_update_key'] )
					&& !empty( $post_arr["add-to-cart"] ) && !empty( $post_arr["quantity"] )
					&& !empty( $post_arr["attendee_name"] ) ) {
					$access_token   = $post_arr['attendee_info_update_key'];
					$event_id       = $post_arr["add-to-cart"];
					$payment_token  = md5( 'etn-payment-token' . $access_token . time() . rand( 1, 9999 ) );
					$ticket_price   = get_post_meta( $event_id, "etn_ticket_price", true );

					// Variation Data.

					// total variations.
					$total_attendee = isset( $post_arr["variation_picked_total_qty"] ) ? $post_arr["variation_picked_total_qty"] : $post_arr["quantity"];

					// check if there's any attendee extra field set from Plugin Settings
					$settings              = Helper::get_settings();
                    
                    // Event wise custom fields.
                    $attendee_extra_fields = get_post_meta( $event_id, 'attendee_extra_fields', true );

                    if ( ! $attendee_extra_fields ) {
                        $attendee_extra_fields = isset($settings['attendee_extra_fields']) ? $settings['attendee_extra_fields'] : [];
                    }
					

					$extra_field_array = [];
					if( is_array( $attendee_extra_fields ) && !empty( $attendee_extra_fields )){

						foreach( $attendee_extra_fields as $attendee_extra_field ){
								$label_content = $attendee_extra_field['label'];

								if( $label_content != '' ){
										$name_from_label['label'] = $label_content;
										$name_from_label['type']  = $attendee_extra_field['type'];
										$name_from_label['name']  = Helper::generate_name_from_label("etn_attendee_extra_field_", $label_content);
										array_push( $extra_field_array, $name_from_label );
								}
						}
					}

					$special_types = [
							'radio',
							'checkbox',
					];

                    // Prepare seats data.
                    $seats = ! empty( $attendee_data['selected_seats'] ) ? implode(',', $attendee_data['selected_seats'] ) : [];
                    if(!is_array($seats) && $seats) {
                        $seats = explode( ',', $seats );
                    }

					// insert attendee custom post
					for ( $i = 0; $i < $total_attendee; $i++ ) {
						$attendee_name  = !empty( $post_arr["attendee_name"][$i] ) ? $post_arr["attendee_name"][$i] : "";
						$attendee_email = !empty( $post_arr["attendee_email"][$i] ) ? $post_arr["attendee_email"][$i] : "";
						$attendee_phone = !empty( $post_arr["attendee_phone"][$i] ) ? $post_arr["attendee_phone"][$i] : "";

						$post_id = wp_insert_post( [
								'post_title'  => $attendee_name,
								'post_type'   => 'etn-attendee',
								'post_status' => 'publish',
						] );

						if ( $post_id ) {
								$info_edit_token = md5( 'etn-edit-token' . $post_id . $access_token . time() );
								$ticket_index = $post_arr['ticket_index'][$i];
								$data            = [
										// passing variation start
										'ticket_name'                   => $post_arr["attendee_ticket_name"][$i],
										'ticket_slug'                   => $post_arr["attendee_ticket_slug"][$i],
										'etn_ticket_price'              => (float) $post_arr["attendee_ticket_price"][$i],
										// passing variation end

										'etn_status_update_token'       => $access_token,
										'etn_payment_token'             => $payment_token,
										'etn_info_edit_token'           => $info_edit_token,
										'etn_timestamp'                 => time(),
										'etn_name'                      => $attendee_name,
										'etn_email'                     => $attendee_email,
										'etn_phone'                     => $attendee_phone,
										'etn_status'                    => 'failed',
										'etn_attendeee_ticket_status'   => 'unused',
										'etn_event_id'                  => intval( $event_id ),
										'etn_unique_ticket_id'          => Helper::generate_unique_ticket_id_from_attendee_id($post_id),
                                        'attendee_seat'                 => ! empty( $seats[$i] ) ? $seats[$i] : '',
								];

								// check and insert attendee extra field data from attendee form
								if( is_array( $extra_field_array ) && !empty( $extra_field_array ) ){
										foreach( $extra_field_array as $key => $value ){
												$post_content   = '';
												$field_name     = $value['name'];

												if ( !in_array( $value['type'], $special_types ) ) {
														$post_content = $post_arr[$field_name][$i];
												} else {
														if ( $value['type'] == 'checkbox') { // for checkbox
																$checkbox_index_now = $post_arr['checkbox_track_index'][$i];

																$checkbox_field_name = $field_name . '_' . $checkbox_index_now;
																if ( !empty( $post_arr[$checkbox_field_name] ) ) {
																		$post_content = maybe_serialize( $post_arr[$checkbox_field_name] );
																}
														} else { // for radio
																$radio_index_now = $post_arr['radio_track_index'][$i];

																$radio_field_name = $field_name . '_' . $radio_index_now;
																if ( !empty( $post_arr[$radio_field_name] ) ) {
																		$post_content    = $post_arr[$radio_field_name][0];
																}
														}
												}

												$data[$field_name] = $post_content;
										}
								}

								foreach ( $data as $key => $value ) {
										// insert post meta data of attendee
										update_post_meta( $post_id, $key, $value );
								}

								// Write post content (triggers save_post).
								wp_update_post( ['ID' => $post_id] );
						}
					}
					unset( $_POST['ticket_purchase_next_step'] );
					if ( 'stripe' == $sells_engine ) {
						return 'success';
					}
			} else {
				if ( 'stripe' == $sells_engine ) {
					return 'error';
				}
				wp_redirect( get_permalink() );
			}

		}else{
			if ( 'stripe' == $sells_engine ) {
				return 'error';
			}
		}
	}

    /**
     * Add bulk action on event post type
     *
     * @param   array  $bulk_actions
     *
     * @return  array
     */
    public function add_bulk_actions( $bulk_actions ) {
        $bulk_actions['export-csv']  = __( 'Export CSV', 'eventin' );
        $bulk_actions['export-json'] = __( 'Export JSON', 'eventin' );

        return $bulk_actions;
    }

    /**
     * Handle bulk action for export
     *
     * @param   string  $redirect_url
     * @param   string  $action
     * @param   array  $post_ids
     *
     * @return  string
     */
    public function handle_export_bulk_action( $redirect_url, $action, $post_ids ) {
        $actions = [
            'export-csv',
            'export-json'
        ];

        if ( ! in_array( $action, $actions ) ) {
            return $redirect_url;
        }

        $export_type = 'json';

        if ( 'export-csv' == $action ) {
            $export_type = 'csv';
        }

        $event_exporter = new Attendee_Exporter();
        $event_exporter->export( $post_ids, $export_type );
    }

}
