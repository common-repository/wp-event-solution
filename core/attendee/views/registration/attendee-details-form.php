<?php

use Etn\Utils\Helper;
if ( $check && !empty( $post_arr["variation_picked_total_qty"] ) && !empty( $post_arr["event_id"] ) ) {

	$total_qty = 0;
	if ( isset( $post_arr["variation_picked_total_qty"] ) ) {
		$total_qty = absint( $post_arr["variation_picked_total_qty"] );
	}

	if ( empty( $total_qty ) ) {
		return;
	}
	
	// Add meta tag for responsive design in the head
	function etn_viewport_meta() {
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0"/>';
		echo '<meta name="viewport" content="width=device-width, initial-scale=1.0"/>';
	}
	add_action('wp_head', 'etn_viewport_meta', '1');

    $attendee_info_update_key = md5( md5( "etn-access-token" . time() . $total_qty ) );

    header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
	header("Pragma: no-cache"); // HTTP 1.0.
	header('Content-Type: text/html; charset=utf-8');


    wp_head();
    $add_to_cart_id = $post_arr["event_id"];
    ?>

	<div class="etn-es-events-page-container etn-attendee-registration-page etn-event-id-<?php echo esc_attr($add_to_cart_id); ?>">
		<div class="etn-event-single-wrap">
			<div class="etn-container">
				<div class="etn-attendee-form">
					<!-- Title -->
					<h3 class="attendee-title"><?php echo esc_html__( "Attendee Details for - ", "eventin" ) . esc_html( $post_arr["event_name"] ); ?></h3>
					<form action="" method="post" id="etn-event-attendee-data-form" class="attende_form">
						<?php wp_nonce_field( 'ticket_purchase_next_step_three', 'ticket_purchase_next_step_three' ); ?>
						<?php
						$args = [
						'ticket_total_quantity' => $total_qty
						];
						do_action( 'after_attendee_ticket_title', $args ); ?>
						<input type="hidden" name="ticket_purchase_next_step" value="three" />

						<!-- for compatibility with deposit plugin: check two variables are set in request. if set, deposit is running and pass them in reg form popup -->
						<?php if ( ! empty( $deposit_enabled ) ) { ?>
							<input type="hidden" name="wc_deposit_option" value="yes" />
						<?php } ?>

						<?php if ( ! empty( $deposit_payment_plan ) ) { ?>
							<input type="hidden" name="wc_deposit_payment_plan" value="<?php echo esc_attr( $deposit_payment_plan ); ?>" />
						<?php
							}
							$add_to_cart_id = $post_arr["event_id"];
							if ( isset( $post_arr["lang_event_id"] ) ) {
								$add_to_cart_id = $post_arr["lang_event_id"];
							}

							$specific_lang = '';
							if ( isset( $_GET['lang'] ) ) {
								$specific_lang = $_GET["lang"];
							}
						?>
						
						<input type="hidden" name="event_name" value="<?php echo esc_html( $post_arr["event_name"] ); ?>" />
						<input type="hidden" name="event_id" value="<?php echo esc_attr( $post_arr["event_id"] ); ?>" />
						<input type="hidden" name="sells_engine" value="<?php echo esc_html( !empty($post_arr['sells_engine']) ? $post_arr['sells_engine'] : 'woocommerce'); ?>" />
						<input type="hidden" name="client_fname" value="<?php echo esc_html( !empty( $post_arr["client_fname"] ) ? $post_arr["client_fname"] : '' ); ?>" />
						<input type="hidden" name="client_lname" value="<?php echo esc_html( !empty( $post_arr["client_lname"] ) ? $post_arr["client_lname"] : '' ); ?>" />
						<input type="hidden" name="client_email" value="<?php echo esc_html( !empty( $post_arr["client_email"] ) ? $post_arr["client_email"] : '' ); ?>" />
						<input type="hidden" name="add-to-cart" value="<?php echo intval( $add_to_cart_id ); ?>" />
						<input type="hidden" name="specific_lang" value="<?php echo esc_html( $specific_lang ); ?>" />
						<input type="hidden" name="quantity" value="1" />
						<input type="hidden" name="attendee_info_update_key" value="<?php echo esc_html( $attendee_info_update_key ); ?>" />
						<input type="hidden" name="variation_picked_total_qty" value="<?php echo esc_attr( $total_qty ); ?>" />

						<?php
						if ( !empty( $post_arr["ticket_name"] ) &&  count( $post_arr["ticket_name"] ) > 0 ) {
							// ticket variation loop. 1st loop.
							foreach ( $post_arr["ticket_variations"] as $key => $ticket_item ) {
								$ticket_name = $ticket_item['etn_ticket_name'];
								$ticket_price = $ticket_item['ticket_price'];
								$ticket_slug = $ticket_item['etn_ticket_slug'];
								$ticket_quantity = $ticket_item['etn_ticket_qty'];

								$block_empty_class = ($ticket_item['etn_ticket_qty'] > 0 ? '' : 'block-empty');

								
								?>
								<div class="etn-ticket-single-variation-details <?php echo esc_attr($block_empty_class); ?>">
									<?php if( !empty( $post_arr["ticket_quantity"] ) && (int) $post_arr[ 'ticket_quantity' ][ $key ] > 0 ) {?>
										<div class="etn-ticket-single-variation-title" data-ticket_name="<?php echo esc_attr($ticket_name); ?>" >
											<div class="etn-ticket-single-variation-title-wrap">
												<svg width="34" height="34" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg">
													<circle cx="17" cy="17" r="17" fill="#5D78FF" fill-opacity="0.2"/>
													<path d="M24.8476 12.6595C23.879 13.6281 22.3087 13.6281 21.3405 12.6595C20.3723 11.6909 20.3719 10.1206 21.3405 9.1524L19.6252 7.4375L7.4375 19.6252L9.1524 21.3401C10.121 20.3715 11.6913 20.3715 12.6599 21.3401C13.6285 22.3087 13.6285 23.879 12.6599 24.8472L14.3748 26.5625L26.5625 14.3748L24.8476 12.6595ZM16.9821 14.2713L16.1864 13.4757L16.9787 12.6834L17.7743 13.4791L16.9821 14.2713ZM18.573 15.8622L17.7773 15.0666L18.5696 14.2743L19.3652 15.0699L18.573 15.8622ZM20.1642 17.4535L19.3686 16.6578L20.1609 15.8656L20.9565 16.6612L20.1642 17.4535Z" fill="#5D78FF"/>
												</svg>
												<h3><?php echo esc_html( $ticket_name );?></h3>
											</div>

											<svg class="etn-arrow-icon" width="20" height="13" viewBox="0 0 20 13" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M2 11L10 3L18 11" stroke="black" stroke-width="3"/>
											</svg>
										</div>
										
									<?php
										}
										$ticket_quantity = !empty( $post_arr["ticket_quantity"] ) ? $post_arr["ticket_quantity"] : [];

										if ( !empty( $post_arr["ticket_quantity"] ) && count( $post_arr["ticket_quantity"] ) >0 ) {
											$radio_generated_indexes = $checkbox_generated_indexes = [];

											$variation_qty 	 = (int) $post_arr[ 'ticket_quantity' ][ $key ];
											// client purchase no of tickets . 2nd loop.
											for ( $i = 1; $i <= $variation_qty; $i++ ) {
												?>
												<input type="hidden" name="attendee_ticket_name[]" 	value="<?php echo esc_attr( $ticket_name ); ?>">
												<input type="hidden" name="attendee_ticket_price[]" value="<?php echo esc_attr( $ticket_price ); ?>">
												<input type="hidden" name="attendee_ticket_slug[]" 	value="<?php echo esc_attr( $ticket_slug ); ?>">
												<div class="etn-attendee-form-wrap <?php echo esc_attr($ticket_name); ?>" data-ticket_name="<?php echo esc_attr($ticket_name); ?>" >
														<div class="etn-attendy-count">
															<h4><?php echo esc_html__( "Attendee - ", "eventin" ) . $i; ?></h4>
														</div>
														<input type="hidden" name="ticket_index[]" value="<?php echo esc_attr( $key ); ?>" />
														<?php
														// render template.
														if( file_exists( \Wpeventin::core_dir() . "attendee/views/ticket/part/ticket-form.php" ) ){
															include \Wpeventin::core_dir() . "attendee/views/ticket/part/ticket-form.php";
														}

													$attendee_extra_fields = get_post_meta($post_arr["event_id"], 'attendee_extra_fields', true);

													

													if ( ! $attendee_extra_fields ) {
														$attendee_extra_fields = isset($settings['attendee_extra_fields']) ? $settings['attendee_extra_fields'] : [];
													}
														

													include \Wpeventin::core_dir() . "attendee/views/registration/attendee-extra-field.php";
														?>
												</div>
												<?php
											}
										}

									?>
									<?php
									/**
									 * If seat plan exist add seat details
									 */
									if ( !empty($post_arr['selected_seats']) && !empty($post_arr['selected_seats'][$ticket_name]) ) {
											?>
												<input type="hidden" name="selected_seats[]" value="<?php echo esc_attr( $post_arr['selected_seats'][$ticket_name]); ?>" />
												<input type="hidden" name="seat_unique_id" value="<?php echo esc_attr( $post_arr['seat_unique_id']); ?>" />
											<?php
									}
									?>
								</div>
								<?php
							}
						}

						?>
						<div class="attendee-button-wrapper">
							<a href="<?php echo get_permalink(); ?>" class="etn-btn etn-btn-secondary attendee_goback"><?php echo esc_html__( "Go Back", "eventin" ); ?></a>
							<button type="submit" name="submit" class="etn-btn etn-primary attendee_submit"><?php echo esc_html__( "Confirm", "eventin" ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	<?php
	wp_footer();
	exit;
} else {
	wp_redirect( get_permalink() );
}

return;

