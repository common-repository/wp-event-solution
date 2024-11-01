<?php

do_action( 'etn_before_add_to_cart_form', $single_event_id );

$sells_engine="";
if ( class_exists('Wpeventin_Pro') ) {
	$sells_engine = \Etn_Pro\Core\Modules\Sells_Engine\Sells_Engine::instance()->check_sells_engine();
} else {
	$sells_engine = "woocommerce";
}

if(class_exists('WooCommerce') && 'woocommerce' === $sells_engine) {
	$price_decimal      =  esc_attr( wc_get_price_decimals() );
	$thousand_separator =  esc_attr( wc_get_price_thousand_separator() );
	$price_decimal_separator = esc_attr( wc_get_price_decimal_separator() );
} else {
	$price_decimal      =  '2';
	$thousand_separator =  ',';
	$price_decimal_separator =  '.';
}

?>

    <form 
		action=""
		method="post"
		id="purchase_ticket_form"
		class="etn-event-form-parent etn-ticket-variation"
		data-etn_uid="<?php echo esc_html( $unique_id ); ?>"
		data-decimal-number-points="<?php echo esc_attr( $price_decimal ); ?>"
		data-thousand-separator="<?php echo esc_attr( $thousand_separator ); ?>"
		data-decimal-separator="<?php echo esc_attr( $price_decimal_separator ); ?>"
		>
		<?php wp_nonce_field( 'ticket_purchase_next_step_two', 'ticket_purchase_next_step_two' ); ?>
        <input name="event_name"
			type="hidden"
			value="<?php echo esc_html( $event_title ); ?>"/>
        <input name="specific_lang"
			type="hidden"
			value="<?php echo isset( $_GET['lang'] ) ? esc_html( $_GET['lang'] ) : ''; ?>"/>
		<input name="event_id" type="hidden" value="<?php echo intval( $single_event_id ); ?>"/>
		<?php
		apply_filters( 'etn_pro/stripe/stripe_field', null );
		if ( ! class_exists( 'Wpeventin_Pro' ) ) {
			?>
            <input type="hidden" name="sells_engine" value="woocommerce"/>
			<?php
		}

		if ( $attendee_reg_enable ) {
			?>
            <input name="ticket_purchase_next_step" type="hidden" value="two"/>
			<?php
		} else {
			?>
            <input name="add-to-cart" type="hidden" value="<?php echo intval( $single_event_id ); ?>"/>
			<?php
		}
		?>

        <!-- Ticket Markup Starts Here -->
		<?php
		$ticket_variation        = get_post_meta( $single_event_id, "etn_ticket_variations", true );
		$etn_ticket_availability = get_post_meta( $single_event_id, "etn_ticket_availability", true );
		$time_zone = get_post_meta( $single_event_id, "event_timezone", true );

		$time_zone = $time_zone ? etn_create_date_timezone( $time_zone ) : 'Asia/Dhaka';
	


		if ( is_array( $ticket_variation ) && count( $ticket_variation ) > 0 ) {
			$cart_ticket = [];
			if ( class_exists( 'Woocommerce' ) && ! is_admin() ) {
				global $woocommerce;
				$items = $woocommerce->cart = new WC_Cart();
				foreach ( $items as $item ) {
					if ( ! empty( $item['etn_ticket_variations'] ) ) {
						$variations = $item['etn_ticket_variations'];
						if ( isset( $variations[0]['etn_ticket_slug'] ) && isset( $variations[0]['etn_ticket_qty'] ) ) {
							$slug = $variations[0]['etn_ticket_slug'];
							$qty  = $variations[0]['etn_ticket_qty'];

							if ( isset( $cart_ticket[ $slug ] ) ) {
								$cart_ticket[ $slug ] += $qty;
							} else {
								$cart_ticket[ $slug ] = $qty;
							}
						}
					}
				}
			}
			$number = ! empty( $i ) ? $i : 0;
			?>
            <div class="variations_<?php echo intval( $number ); ?>">
                
				<?php foreach ( $ticket_variation as $key => $value ) {
					
					$total_tickets =  ! empty( $value['etn_avaiilable_tickets'] ) ? intval( $value['etn_avaiilable_tickets'] )  : 100000;
					$sold_tickets   = ! empty( $value['etn_sold_tickets'] ) ? intval( $value['etn_sold_tickets'] ) : 0;

					
					
					$start_time = ! empty( $value['start_time'] ) ? $value['start_time'] : '';
					$end_time 	= ! empty( $value['end_time'] ) ? $value['end_time'] : '';
					$start_date 	= ! empty( $value['start_date'] ) ? $value['start_date'] : '';
					$end_date 	= ! empty( $value['end_date'] ) ? $value['end_date'] : '';
					
					$start_date = new DateTime( $start_date . ' ' . $start_time );
					$end_date 	= new DateTime( $end_date . ' ' . $end_time );

					$start_date_time = $start_date->format( 'Y-m-d h:i:s A' );
					$end_date_time 	 = $end_date->format( 'Y-m-d h:i:s A' );

					// if($etn_ticket_availability == 'yes' || $etn_ticket_availability == 'on' || $etn_ticket_availability) {

					// 	$total_tickets = ! empty( $value['etn_avaiilable_tickets'] ) ? absint( $value['etn_avaiilable_tickets'] ) : 100000;
					// }

					
					$etn_min_ticket = ! empty( $value['etn_min_ticket'] ) ? absint( $value['etn_min_ticket'] ) : 0;
					$etn_max_ticket = ! empty( $value['etn_max_ticket'] ) ? absint( $value['etn_max_ticket'] ) : 0;
					

					$etn_cart_limit = 0;
					if ( ! empty( $cart_ticket ) ) {
						$etn_cart_limit = ! empty( $cart_ticket[ $value['etn_ticket_slug'] ] ) ? $cart_ticket[ $value['etn_ticket_slug'] ] : 0;
					}

					$etn_current_stock = intval( $total_tickets - $sold_tickets );

					if (  etn_is_ticket_sale_end( $end_date_time, $time_zone ) ) {
						$etn_current_stock = 0;
					}

					// $stock_outClass    = ( $etn_current_stock < 1 ) ? 'stock_out' : '';
					$stock_outClass    = ( $etn_current_stock < 1 ) || ! etn_is_ticket_sale_start( $start_date_time, $time_zone ) ? 'stock_out' : '';


					?>
					<div class="variation_<?php echo esc_attr( $key ) ?>">

						<div class="etn-single-ticket-item">
							<h5 class="ticket-header">

								<?php
								esc_html_e( $value['etn_ticket_name'], 'eventin' );

								if ( ! isset( $event_options["etn_hide_seats_from_details"] ) ) {
									if ( ! etn_is_ticket_sale_start( $start_date_time, $time_zone ) ) {
										?>
										<span class="seat-remaining-text"><?php echo esc_html__( '(Sale start on ', 'eventin' );  echo $start_date->format( 'Y-m-d' ) .' ' . $start_time ;?> )</span>
										<?php
									}

									elseif ( $etn_current_stock > 0 ) {
										?>
										<span class="seat-remaining-text">(<?php echo esc_html( $etn_current_stock );
												echo esc_html__( ' seats remaining', 'eventin' ); ?>)</span>
									<?php } else { ?>
										<span class="seat-remaining-text">(<?php echo esc_html__( 'All tickets have been sold out', 'eventin' ); ?>)</span>
										<?php
									}
								}


								?>
							</h5>
							<div class="etn-ticket-divider"></div>
							<div class="etn-ticket-price-body <?php echo esc_attr( $stock_outClass ) ?>">
								<div class="ticket-price-item etn-ticket-price">
									<label><?php echo esc_html__( "Ticket Price :", "eventin" ); ?></label>
									<strong>
										<?php
										$price = number_format( (float) $value['etn_ticket_price'], $price_decimal, $price_decimal_separator, $thousand_separator );
										echo \Etn\Core\Event\Helper::instance()->currency_with_position( $price );
										?>
									</strong>
								</div>
								<!-- Min , Max and stock quantity checking start -->
								<div class="ticket-price-item etn-quantity">
									<label for="ticket-input_<?php echo intval( $key ); ?>"><?php echo esc_html__( "Quantity :", "eventin" ); ?></label>
									<button type="button" class="qt-btn qt-sub" data-multi="-1"
											data-key="<?php echo intval( $key ) ?>">-
									</button>
									<input name="ticket_quantity[<?php echo  $value['etn_ticket_name']?>]" type="number"
											class="etn_ticket_variation ticket_<?php echo intval( $key ); ?>"
											value="0" id="ticket-input_<?php echo intval( $key ); ?>"
											data-price="<?php echo number_format( (float) $value['etn_ticket_price'], $price_decimal, '.', '' ); ?>"
											data-etn_min_ticket="<?php echo absint( $etn_min_ticket ); ?>"
											data-etn_max_ticket="<?php echo absint( $etn_max_ticket ); ?>"
											data-etn_current_stock="<?php echo absint( $etn_current_stock ); ?>"
											data-stock_out="<?php echo esc_attr__( "All ticket has has been sold", "eventin" ) ?>"
											data-cart_ticket_limit="<?php echo esc_attr__( "You have already added 5 tickets. You can't purchase more than $etn_max_ticket tickets", "eventin" ) ?>"
											data-stock_limit="<?php echo esc_attr__( "Stock limit $etn_current_stock. You can purchase within $etn_current_stock.", "eventin" ) ?>"
											data-qty_message="<?php echo esc_attr__( "Total ticket quantity should be atleast ", "eventin" ) . $etn_min_ticket . esc_attr__( " and can not be higher than ", "eventin" ) . $etn_max_ticket; ?>"
											data-etn_cart_limit="<?php echo absint( $etn_cart_limit ); ?>"
											data-etn_cart_limit_message="<?php echo esc_attr__( "You have already added $etn_cart_limit, Which is greater than maximum quantity $etn_max_ticket . You can add maximum $etn_max_ticket tickets. ", "eventin" ); ?>"/>
									<button type="button" class="qt-btn qt-add" data-multi="1"
											data-key="<?php echo intval( $key ) ?>">+
									</button>
								</div>
								<!-- Min , Max and stock quantity checking start -->
								<div 
									class="ticket-price-item etn-subtotal"
									data-subtotal="<?php echo esc_attr( number_format( (float) $value['etn_ticket_price'], $price_decimal, '.', '' ) ); ?>" 
								>
									<label><?php echo esc_html__( "Sub Total :", "eventin" ); ?></label>
									<strong>
										<?php
										$price = '<span class="_sub_total_' . floatval( $key ) . '">0</span>';
										echo \Etn\Core\Event\Helper::instance()->currency_with_position( $price );
										?>
									</strong>
								</div>
							<input name="ticket_price[]" type="hidden" value="<?php echo number_format( (float) $value['etn_ticket_price'], $price_decimal, '.','' );?>">
							<input name="ticket_name[]" type="hidden" value="<?php echo esc_attr($value['etn_ticket_name'] ); ?>">
							<input name="ticket_slug[]" type="hidden" value="<?php echo esc_attr($value['etn_ticket_slug'] ); ?>">
							
						</div>
						<div class="show_message show_message_<?php echo intval( $key ); ?> quantity-error-msg"></div>
					</div>
					</div>
					<?php do_action( 'etn_before_add_to_cart_total_price', $single_event_id, $key, $value ); ?>
					<?php
					
				}
				?>

                <!-- Ticket Markup Ends Here -->
                <div class="etn-variable-total-price">
                    <div id="etn_variable_ticket_form_price" class="etn_variable_ticket_form_price">
                        <div class="etn-total-quantity">
                            <label><?php echo esc_html__( 'Total Quantity', "eventin" ); ?></label>
                            <strong class="variation_total_qty">0.00</strong>
                        </div>

                        <div class="etn-ticket-total-price">
                            <label><?php echo esc_html__( 'Total Price', "eventin" ); ?></label>
                            <strong>
								<?php
								$price = '<span class="variation_total_price">0</span>';
								echo \Etn\Core\Event\Helper::instance()->currency_with_position( $price );
								?>
                            </strong>
                        </div>
                        
                    </div>
                </div>
            </div>
			<?php
		}
		?>

		<?php do_action( 'etn_before_add_to_cart_button', $single_event_id ); ?>

		<?php

		if ( ! isset( $event_options["etn_purchase_login_required"] ) || ( isset( $event_options["etn_purchase_login_required"] ) && is_user_logged_in() ) ) {
			$multivendor_active = ( class_exists( 'Woocommerce' ) && class_exists( 'WeDevs_Dokan' ) ) ? true : false;
			if ( $multivendor_active ) {
				do_action( 'etn_cart_multivendor_products_modal' );
			}
			?>
            <button name="submit"
                   class="etn-btn etn-primary etn-add-to-cart-block disabled button button--loader <?php echo esc_attr( absint( $single_event_id ) ); ?>"
                   data-event_id="<?php echo esc_attr( absint( $single_event_id ) ); ?>" data-validation_checked="0"
                   data-multivendor_active="<?php echo ( $multivendor_active ) ? '1' : '0'; ?>" type="submit"
                   value=""><?php $cart_button_text = apply_filters( 'etn_event_cart_button_text', esc_html__( "Buy ticket", "eventin" ) );
			       echo esc_html( $cart_button_text ); ?></button>
			<?php
		} else {
			?>
            <small>
				<?php echo esc_html__( 'Please', 'eventin' ); ?> <a
                        href="<?php echo wp_login_url( get_permalink() ); ?>"><?php echo esc_html__( "Login", "eventin" ); ?></a> <?php echo esc_html__( ' to buy ticket!', "eventin" ); ?>
            </small>
			<?php
		}
		?>

		<?php do_action( 'etn_after_add_to_cart_button', $single_event_id ); ?>
    </form>

<?php do_action( 'etn_after_add_to_cart_form', $single_event_id ); ?>