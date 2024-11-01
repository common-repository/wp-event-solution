<?php

    // Add meta tag for responsive design in the head
    function etn_viewport_meta() {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0"/>';
    }
    add_action('wp_head', 'etn_viewport_meta', '1');

    
    wp_head();
    
    $ticket_file_name = sanitize_title_with_dashes($attendee_name);
    $payment_status =  get_post_meta( $attendee_id, 'etn_status', true);

    $all_payment_status = [
        'success' => esc_html__('Success', 'eventin'),
        'failed'  => esc_html__('Failed', 'eventin')
    ];

    // Load ticket layout style
    wp_enqueue_style( 'etn-ticket-markup' );
    wp_enqueue_script( 'etn-pdf-gen' );
    wp_enqueue_script( 'etn-html-2-canvas' );
    wp_enqueue_script( 'etn-dom-purify-pdf' );
    wp_enqueue_script( 'html-to-image' );

    // Include QR Code related scripts when pro plugin is activated
    if(class_exists('Wpeventin_Pro')) {
        wp_enqueue_script('etn-qr-code');
        wp_enqueue_script('etn-qr-code-scanner');
        wp_enqueue_script('etn-qr-code-custom');
    }
?>
<meta name="viewport" content="width=device-width, initial-scale=1">
<div class="etn-ticket-download-wrapper">
    <div class="etn-ticket-wrap" id="etn_attendee_details_to_print" >
      <div class="etn-ticket-wrapper">
            <div class="etn-ticket-main-wrapper">
                <div class="etn-ticket">
                    <?php  if(has_custom_logo()){ ?>
                          <div class="etn-ticket-logo-wrapper">
                             <?php 
                                $custom_logo_id = get_theme_mod( 'custom_logo' );
                                $image = wp_get_attachment_image_src( $custom_logo_id, 'Full' );
                            ?>
                             <img style="max-height: 70px; object-fit: cover"  src="<?php echo esc_url($image[0]); ?>" />

                            <div class="logo-shape">
                                <span class="logo-bar bar-one" ></span>
                                <span class="logo-bar bar-two" ></span>
                                <span class="logo-bar bar-three" ></span>
                            </div>
                        </div>
                    <?php    
                        }elseif( class_exists( 'ET_Builder_Element' ) ){ 
                    ?>
                        <div class="etn-ticket-logo-wrapper">
                             <?php 
                                $image = et_get_option( 'divi_logo');
                            ?>
                             <img style="max-height: 70px; object-fit: cover"  src="<?php echo esc_url($image); ?>" />

                            <div class="logo-shape">
                                <span class="logo-bar bar-one" ></span>
                                <span class="logo-bar bar-two" ></span>
                                <span class="logo-bar bar-three" ></span>
                            </div>
                        </div>
                    <?php             
                        } 
                    ?>
                  
                    <div class="etn-ticket-head">
                        <h3 class="etn-ticket-head-title"><?php echo esc_html( $event_name ) ?></h3>
                        <p class="etn-ticket-head-time"><?php echo esc_html( $date.' @ '. $time ) ?> </p>
                    </div>
                    <div class="etn-ticket-body">
                        <div class="etn-ticket-body-top">
                            <div class="etn-ticket-body-top-ul-wrapper">
                                <ul class="etn-ticket-body-top-ul">
                                    <?php do_action('etn_pro_ticket_id', $attendee_id, $event_id); ?>
                                    <?php if ( $ticket_price !== "") { ?>
                                        <li class="etn-ticket-body-top-li">
                                            <?php echo esc_html__( "PRICE :", "eventin" ); ?> 
                                            <p>
                                                <?php 
                                                    printf( '%s %s', etn_currency_symbol(), $ticket_price );
                                                ?>
                                            </p>
                                        </li>
                                    <?php }?>
                                    <?php if ( $ticket_name !== "") { ?>
                                        <li class="etn-ticket-body-top-li flex-100">
                                            <?php echo esc_html__( "TYPE :", "eventin" ); ?> 
                                            <p><?php echo esc_html( $ticket_name ) ?></p>
                                        </li>
                                    <?php }?>
                                    <?php 
                                        if ( !empty($attendee_seat) ) {
                                        ?>
                                        <li class="etn-ticket-body-top-li flex-100">
                                            <?php echo esc_html__( "Seat :", "eventin" ); ?> 
                                            <p><?php echo esc_html( $attendee_seat ) ?></p>
                                        </li>
                                    <?php }  ?>
                                    <?php if ( $event_location !== "" && $event_location_type === 'existing_location') { ?>
                                        <li class="etn-ticket-body-top-li flex-100">
                                            <?php echo esc_html__( "VENUE :", "eventin" ); ?> 
                                            <p><?php echo esc_html( $event_location ) ?></p>
                                        </li>
                                    <?php }?>
                                    <?php if($event_location_type === 'new_location'): ?>
                                        <li class="etn-ticket-body-top-li flex-100 etn-event-location etn-taxonomy-location-meta">
                                            <?php echo esc_html__( "VENUE :", "eventin" ); ?> 
                                            <?php foreach($event_terms as $term) : ?>
                                                <span><?php echo esc_html($term->name); ?></span>
                                            <?php endforeach; ?>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ( $attendee_name !== "") { ?>
                                        <li class="etn-ticket-body-top-li">
                                            <?php echo esc_html__( "ATTENDEE :", "eventin" ); ?> 
                                            <p><?php echo esc_html( $attendee_name ) ?></p>
                                        </li>
                                    <?php }?>
                                    
                                    <?php if ( $include_phone  && $attendee_phone !== "") { ?>
                                        <li class="etn-ticket-body-top-li">
                                            <?php echo esc_html__( "PHONE :", "eventin" ); ?> 
                                            <p><?php echo esc_html( $attendee_phone ) ?></p>
                                        </li>
                                    <?php }?>
                                    <?php if ( $include_email && $attendee_email !== "" ) { ?>
                                        <li class="etn-ticket-body-top-li">
                                            <?php echo esc_html__( "EMAIL :", "eventin" ); ?> 
                                            <p><?php echo esc_html( $attendee_email ) ?></p>
                                        </li>
                                    <?php }?>

                                    <?php if($payment_status){ ?>
                                        <li class="etn-ticket-body-top-li">
                                            <?php echo esc_html__( "Payment Status :", "eventin" ); ?> 
                                            <p><?php echo esc_html( $all_payment_status[$payment_status] ); ?></p>
                                        </li>
                                    <?php } ?>
                                </ul>
                            
                        </div>
                        <!-- <div class="etn-ticket-body-bottom"></div> -->
                    </div>
                  
                    <div class="etn-ticket-qr-code">
                        <?php
                            if( $payment_status ==='success'){
                                do_action('etn_pro_ticket_qr', $attendee_id, $event_id);
                            }
                        ?>
                    </div>
                </div>
                <!-- <div class="etn-ticket-action"></div> -->
            </div>
      </div>
    </div>
</div>
<div class="etn-download-ticket">
    <button class="etn-btn button etn-print-ticket-btn" id="etn_ticket_print_btn" data-ticketname="<?php echo esc_html( $ticket_file_name )?>" ><?php echo esc_html__( "Print", "eventin" ); ?></button>
    
    <button class="etn-btn button etn-download-ticket-btn" id="etn_ticket_download_btn" data-ticketname="<?php echo esc_html( $ticket_file_name )?>" ><?php echo esc_html__( "Download", "eventin" ); ?></button>
</div>

<?php wp_footer(); ?>

