<?php
namespace Eventin\Order;

use Etn\Core\Event\Event_Model;
use Etn\Core\Attendee\Attendee_Model;
use Eventin\Emails\AdminOrderEmail;
use Eventin\Emails\AttendeeOrderEmail;
use Eventin\Mails\Mail;

/**
 * Order email trait
 * 
 * @package Eventin
 */
trait OrderEmailTrait {
    /**
     * Send email for a specific order
     *
     * @return  Void
     */
    public function send_email() {
        $order = $this;

        $attendees   = $order->get_attendees();
        $event       = new Event_Model( $order->event_id );
        $admin_email = get_option('admin_email');
        $from        = etn_get_email_settings( 'purchase_email' )['from'];
        $send_to_admin = etn_get_email_settings( 'purchase_email' )['send_to_admin'];

        // Send to admin order email.
        if ( $send_to_admin ) {
            Mail::to( $admin_email )->from( $from )->send( new AdminOrderEmail( $order ) );
        }

        // Send to customer order email.
        Mail::to( $order->customer_email )->from( $from )->send( new AdminOrderEmail( $order ) );

        // Send to attendees email.
        if ( $attendees ) {
            foreach( $attendees as $attendee ) {
                $attendee = new Attendee_Model( $attendee['id'] );
                
                if ( $attendee->etn_email ) {
                    Mail::to( $attendee->etn_email )->from( $from )->send( new AttendeeOrderEmail( $event, $attendee ) );
                }
            }
        }
    }
}
