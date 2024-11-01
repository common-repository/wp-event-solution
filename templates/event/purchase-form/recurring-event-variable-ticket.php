<?php

use Etn\Core\Event\Event_Model;
use Etn\Utils\Helper;

$etn_left_tickets     = ! empty( $data['etn_left_tickets'] ) ? $data['etn_left_tickets'] : 0;
$etn_ticket_unlimited = isset( $data['etn_ticket_unlimited'] ) && $data['etn_ticket_unlimited'] == "no";
$etn_ticket_price     = $data['etn_ticket_price'] ?? '';
$ticket_qty           = get_post_meta( $single_event_id, "etn_sold_tickets", true );
$total_sold_ticket    = isset( $ticket_qty ) ? intval( $ticket_qty ) : 0;
$is_zoom_event        = get_post_meta( $single_event_id, 'etn_zoom_event', true );
$event_options        = ! empty( $data['event_options'] ) ? $data['event_options'] : [];
$event_title          = get_the_title( $single_event_id );
$separate             = ( ! empty( $data['event_end_date'] ) ) ? ' - ' : '';
$settings             = Helper::get_settings();
$attendee_reg_enable  = ! empty( $settings["attendee_registration"] );
$active_class         = ( $i === 0 ) ? 'active' : '';
$active_item          = ( $i === 0 ) ? 'style=display:block' : '';
$etn_min_ticket       = ! empty( get_post_meta( $single_event_id, 'etn_min_ticket', true ) ) ? get_post_meta( $single_event_id, 'etn_min_ticket', true ) : 1;
$etn_max_ticket       = ! empty( get_post_meta( $single_event_id, 'etn_max_ticket', true ) ) ? get_post_meta( $single_event_id, 'etn_max_ticket', true ) : $etn_left_tickets;
$etn_max_ticket       = min( $etn_left_tickets, $etn_max_ticket );
$disable_purchase_form = get_post_meta( $single_event_id, 'etn_disable_purchase_form', true );
$timezone 			= get_post_meta( $single_event_id, 'event_timezone', true );

$event 				= new Event_Model( $single_event_id );
$timezone 			= $timezone ? etn_create_date_timezone( $timezone ) : 'Asia/Dhaka';

$event_end_date 	= (new DateTime( $event->etn_end_date, new DateTimeZone( $timezone ) ) )->format('Y-m-d');
$event_end_time      = $event->etn_end_time;
$event_end_date_time = strtotime( $event_end_date . ' ' . $event_end_time );

?>
<div class="etn-widget etn-recurring-widget <?php echo esc_attr( $active_class ); ?>">
    <div class="etn-row">
        <div class="etn-col-lg-12">
            <div class="recurring-content <?php echo esc_attr( $active_class ); ?>">
                <div class="etn-recurring-header">
                    <div class="etn-left-datemeta">
                        <div class="etn-date-meta">
							<?php
							$start_date     = $data['event_start_date'];
							$end_date       = $data['event_end_date'];
							$same_day_event = ( $start_date === $end_date ) ? true : false;
							?>

                            <p class="etn-date-text">
								<?php echo esc_html( $start_date ); ?>
                            </p>

							<?php if ( ! $same_day_event ) : ?>
                                <p class="etn-date-to">
									<?php echo esc_html__( 'To', 'eventin' ) ?>
                                </p>

                                <p class="etn-date-text">
									<?php echo esc_html( $end_date ); ?>
                                </p>
							<?php endif; ?>
                        </div>
						<?php
						// show if this is a zoom event
						if ( isset( $is_zoom_event ) && ( "on" == $is_zoom_event || "yes" == $is_zoom_event ) ) {
							?>
                            <div class="etn-zoom-event-notice">
                                <img src="<?php echo esc_url( \Wpeventin::assets_url() . "images/zoom.svg" ); ?>"
                                     alt="<?php echo esc_attr__( 'Zoom', 'eventin' ) ?>">
								<?php echo esc_html__( "Zoom Event", "eventin" ); ?>
                            </div>
							<?php
						}
						?>
                    </div>
                    <div class="etn-title-wrap">
                        <div class="etn-time-meta">
							<?php
							if ( ! isset( $event_options["etn_hide_time_from_details"] ) ) {
								$separate = ( ! empty( $data['event_end_time'] ) ) ? ' - ' : '';
								?>
                                <div>
                                    <i class="etn-icon etn-clock"></i>
									<?php echo esc_html( $data['event_start_time'] . $separate . $data['event_end_time'] ); ?>
                                    <span class="etn-event-timezone">
                                        <?php
                                        if ( ! empty( $data['event_timezone'] ) && ! isset( $event_options["etn_hide_timezone_from_details"] ) ) {
	                                        ?>
                                            (<?php echo esc_html( $data['event_timezone'] ); ?>)
	                                        <?php
                                        }
                                        ?>
                                    </span>
                                </div>
								<?php
							}
							?>
                        </div>
                        <h4 class="etn-title etn-post-title etn-accordion-heading">
                            <a href="<?php echo esc_url( get_permalink( $single_event_id ) ); ?>">
								<?php echo esc_html( $event_title ); ?>
                            </a>
                        </h4>
                    </div>
					
                    <?php
					// Recurring event small thumbnail show / hide
					$parent_post_id = wp_get_post_parent_id($single_event_id);
					$recurring_thumb     = !empty( get_post_meta( $parent_post_id, 'etn_event_recurrence', true )['recurring_thumb'] ) ? get_post_meta( $parent_post_id, 'etn_event_recurrence', true )['recurring_thumb'] : 'no';

					if($recurring_thumb != 'yes'){
						?>
						<div class="etn-thumb-wrap">
							<?php echo get_the_post_thumbnail( $single_event_id ); ?>
						</div>
					<?php } ?>

                    <i class="etn-icon etn-angle-down"></i>
                </div>
                <div class="etn-widget etn-variable-ticket-widget etn-form-wrap" <?php echo esc_attr( $active_item ); ?>>
                    <div class="etn-row">
                        <div class="etn-col-lg-4">
                            <div class="etn-recurring-add-calendar">
								<?php
								etn_after_single_event_meta_add_to_calendar( $single_event_id );
								?>
                            </div>
                        </div>
                        <div class="etn-col-lg-8">
							<?php

							if($disable_purchase_form != 'yes'){
						
								$show_form_button = apply_filters( "etn_form_submit_visibility", true, $single_event_id );
								if ( $event_left_ticket <= 0 ) {
									?>
									<h4><?php echo esc_html__( 'All Tickets Sold!!', "eventin" ); ?></h4>
									<?php
								} else if ( time() > $event_end_date_time ) {
									?>
									<h4 class="registration-expired-message"><?php echo esc_html__( 'Registration Deadline Expired!!', "eventin" ); ?></h4>
									<?php
								} else if ( $show_form_button === false ) {
									?>
									<h4 class="registration-expired-message"><?php echo esc_html__( 'Registration Deadline Expired!!', "eventin" ); ?></h4>
									<div class="etn-event-form-parent"></div>
									<?php
								} else { 
									Helper::eventin_ticket_widget( $single_event_id );
								}
							}
							?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
