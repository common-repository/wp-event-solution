<?php
namespace Eventin\Admin;

use Etn\Core\Attendee\Attendee_Model;
use Eventin\Interfaces\HookableInterface;
use Eventin\Order\OrderModel;

class OrderAttendee implements HookableInterface {
    /**
     * Register hooks
     *
     * @return  void
     */ 
    public function register_hooks(): void {
        add_action( 'eventin_order_completed', [ $this, 'update_attendee_payment_status' ] );    
    }

    /**
     * Update attendee payment status
     *
     * @param   OrderModel  $order
     *
     * @return  void
     */
    public function update_attendee_payment_status( OrderModel $order ) {
        $attendess = $order->get_attendees();
        
        if ( 'completed' != $order->status ) {
            return;
        }

        if ( $attendess ) {
            foreach( $attendess as $attendee ) {
                $attendee = new Attendee_Model($attendee['id']);

                $attendee->update([
                    'etn_status' => 'success'
                ]);
            }
        }
    }
}